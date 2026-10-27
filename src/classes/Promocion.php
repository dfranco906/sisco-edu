<?php
// src/classes/Promocion.php
declare(strict_types=1);

class Promocion {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /**
     * Obtiene la nómina de estudiantes de un grado con información de su aula actual
     * y el estado de su huella biométrica registrada.
     */
    public function obtenerEstudiantesPorGrado(int $idGrado): array {
        $stmt = $this->conn->prepare("
            SELECT 
                e.id_estudiante,
                e.nombre,
                e.apellido,
                e.cedula_identidad,
                e.id_grado,
                e.id_aula,
                COALESCE(a.nombre, 'Sin aula') AS aula_nombre,
                e.room_id,
                e.user_id_global,
                e.huella_id,
                e.huella_registrada,
                e.activo,
                CASE 
                    WHEN (COALESCE(e.huella_registrada, 0) > 0 OR COALESCE(e.huella_id, 0) > 0) 
                         AND h.id_huella IS NOT NULL THEN 1 
                    ELSE 0 
                END AS tiene_huella_activa,
                h.crc32_hash,
                h.sensor_slot
            FROM estudiantes e
            LEFT JOIN aulas a ON a.id_aula = e.id_aula
            LEFT JOIN huellas_templates h ON h.id_huella = e.huella_id AND h.activo = 1
            WHERE e.id_grado = :id_grado AND e.activo = 1
            ORDER BY e.apellido ASC, e.nombre ASC
        ");
        $stmt->execute([':id_grado' => $idGrado]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtiene el catálogo de grados activos con información de sus aulas asignadas
     * para los selectores de origen y destino.
     */
    public function obtenerCatalogoGrados(): array {
        $stmt = $this->conn->prepare("
            SELECT 
                g.id_grado,
                g.nombre,
                g.id_aula,
                COALESCE(a.nombre, 'Sin aula asignada') AS aula_nombre,
                COALESCE(a.codigo_sala, a.codigo, '') AS codigo_sala,
                g.room_id,
                g.id_turno,
                COALESCE(t.nombre, '') AS turno,
                g.id_nivel,
                COALESCE(n.nombre, '') AS nivel,
                g.id_bachillerato,
                COALESCE(b.codigo, '') AS bachillerato,
                n_esp.lora_id,
                (SELECT COUNT(*) FROM estudiantes e WHERE e.id_grado = g.id_grado AND e.activo = 1) AS total_estudiantes
            FROM grados g
            LEFT JOIN aulas a ON a.id_aula = g.id_aula
            LEFT JOIN turnos t ON t.id_turno = g.id_turno
            LEFT JOIN niveles_academicos n ON n.id_nivel = g.id_nivel
            LEFT JOIN bachilleratos b ON b.id_bachillerato = g.id_bachillerato
            LEFT JOIN nodos_esp32 n_esp ON n_esp.id_aula = g.id_aula AND n_esp.activo = 1
            WHERE g.activo = 1
            ORDER BY COALESCE(g.id_nivel, 99) ASC, g.nombre ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Ejecuta la promoción de los estudiantes seleccionados hacia el grado destino o graduación.
     * Encola automáticamente las huellas para el aula de destino si migrarHuellas = true.
     * 
     * @param int $idGradoOrigen Grado del que salen los estudiantes
     * @param int|null $idGradoDestino Grado al que pasan (null si es graduación)
     * @param array $idsEstudiantes Lista de IDs de estudiantes a promover
     * @param bool $migrarHuellas Si debe encolar automáticamente las huellas al aula destino
     * @param int $anhoLectivo Año lectivo (ej. 2027)
     * @param bool $esGraduacion Si se marca como cohorte egresada/graduada
     * @return array Resumen del proceso ejecutado
     */
    public function ejecutar(
        int $idGradoOrigen,
        ?int $idGradoDestino,
        array $idsEstudiantes,
        bool $migrarHuellas = true,
        int $anhoLectivo = 2027,
        bool $esGraduacion = false
    ): array {
        $validos = array_values(array_filter(array_map('intval', $idsEstudiantes), fn($v) => $v > 0));
        if (empty($validos)) {
            throw new InvalidArgumentException('No se proporcionaron estudiantes válidos para promover.');
        }

        $this->conn->beginTransaction();
        try {
            // 1. Obtener datos de los estudiantes a promover
            $inClause = implode(',', array_fill(0, count($validos), '?'));
            $stmtEst = $this->conn->prepare("
                SELECT e.id_estudiante, e.nombre, e.apellido, e.cedula_identidad,
                       e.id_grado, e.id_aula, e.user_id_global, e.huella_id,
                       h.id_huella, h.activo AS huella_activa
                FROM estudiantes e
                LEFT JOIN huellas_templates h ON h.id_huella = e.huella_id AND h.activo = 1
                WHERE e.id_estudiante IN ({$inClause})
            ");
            $stmtEst->execute($validos);
            $estudiantes = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

            if (empty($estudiantes)) {
                $this->conn->rollBack();
                throw new RuntimeException('No se encontraron estudiantes para los IDs suministrados.');
            }

            // 2. Si es graduación (fin de ciclo/egresados)
            if ($esGraduacion || !$idGradoDestino) {
                // Desactivar mapeos biométricos de los estudiantes en todas las aulas
                $stmtDesMap = $this->conn->prepare("
                    UPDATE biometric_mapping 
                    SET activo = 0 
                    WHERE user_id_global IN (
                        SELECT user_id_global FROM estudiantes WHERE id_estudiante IN ({$inClause})
                    )
                ");
                $stmtDesMap->execute($validos);

                // Marcar matrícula previa como no activa y registrar graduación
                $stmtGrad = $this->conn->prepare("
                    UPDATE estudiantes 
                    SET activo = 0, pendiente_sync = 0 
                    WHERE id_estudiante IN ({$inClause})
                ");
                $stmtGrad->execute($validos);

                $this->conn->commit();
                return [
                    'ok'               => true,
                    'es_graduacion'    => true,
                    'total_promovidos' => count($validos),
                    'huellas_migradas' => 0,
                    'message'          => "Se registraron " . count($validos) . " estudiantes como egresados/graduados exitosamente. Sus accesos biométricos en aulas fueron desactivados."
                ];
            }

            // 3. Promoción regular a otro grado
            // Obtener información del grado de destino y su aula
            $stmtGradoDest = $this->conn->prepare("
                SELECT g.id_grado, g.nombre AS grado_nombre, g.id_aula, g.room_id,
                       a.nombre AS aula_nombre, n.lora_id
                FROM grados g
                LEFT JOIN aulas a ON a.id_aula = g.id_aula
                LEFT JOIN nodos_esp32 n ON n.id_aula = g.id_aula AND n.activo = 1
                WHERE g.id_grado = :id AND g.activo = 1
                LIMIT 1
            ");
            $stmtGradoDest->execute([':id' => $idGradoDestino]);
            $gradoDestinoInfo = $stmtGradoDest->fetch(PDO::FETCH_ASSOC);

            if (!$gradoDestinoInfo) {
                $this->conn->rollBack();
                throw new RuntimeException("El grado de destino #{$idGradoDestino} no existe o no está activo.");
            }

            $idAulaDestino = !empty($gradoDestinoInfo['id_aula']) ? (int)$gradoDestinoInfo['id_aula'] : null;
            $roomIdDestino = $gradoDestinoInfo['room_id'] ?? null;
            $nombreGradoDestino = $gradoDestinoInfo['grado_nombre'];

            // 4. Actualizar id_grado, id_aula y room_id de los estudiantes
            $stmtUpdEst = $this->conn->prepare("
                UPDATE estudiantes 
                SET id_grado = ?,
                    id_aula = ?,
                    room_id = ?
                WHERE id_estudiante IN ({$inClause})
            ");
            $paramsUpd = array_merge([$idGradoDestino, $idAulaDestino, $roomIdDestino], $validos);
            $stmtUpdEst->execute($paramsUpd);

            // 5. Vincular materias del nuevo grado si existen asignaciones docentes activas
            require_once __DIR__ . '/Estudiante.php';
            $estudianteModel = new Estudiante($this->conn);
            foreach ($validos as $idEst) {
                $estudianteModel->matricularEnMateriasDeGrado($idEst, $idGradoDestino);
            }

            // 6. Migración y encolado de huellas biométricas hacia el aula nueva
            $huellasEncoladas = 0;
            if ($migrarHuellas && $idAulaDestino) {
                $stmtSync = $this->conn->prepare("
                    INSERT INTO sync_biometrica (id_huella, id_aula, estado, intentos, mensaje, fecha_creacion)
                    VALUES (:huella, :aula, 'PENDIENTE', 0, :msg, NOW())
                ");

                $stmtPend = $this->conn->prepare("
                    UPDATE huellas_templates 
                    SET pendiente_sync = 1 
                    WHERE id_huella = :id
                ");

                $stmtDesMapViejo = $this->conn->prepare("
                    UPDATE biometric_mapping 
                    SET activo = 0 
                    WHERE user_id_global = :uid AND id_aula <> :aula_nueva
                ");

                foreach ($estudiantes as $est) {
                    $idHuella = !empty($est['id_huella']) ? (int)$est['id_huella'] : null;
                    if (!$idHuella) {
                        continue;
                    }

                    // Encolar huella para la nueva aula
                    $stmtSync->execute([
                        ':huella' => $idHuella,
                        ':aula'   => $idAulaDestino,
                        ':msg'    => "Promoción a {$nombreGradoDestino} (Año {$anhoLectivo})"
                    ]);

                    $stmtPend->execute([':id' => $idHuella]);

                    if (!empty($est['user_id_global'])) {
                        $stmtDesMapViejo->execute([
                            ':uid'        => $est['user_id_global'],
                            ':aula_nueva' => $idAulaDestino
                        ]);
                    }

                    $huellasEncoladas++;
                }
            }

            $this->conn->commit();

            return [
                'ok'                  => true,
                'es_graduacion'       => false,
                'total_promovidos'    => count($validos),
                'huellas_migradas'    => $huellasEncoladas,
                'grado_destino'       => $nombreGradoDestino,
                'id_aula_destino'     => $idAulaDestino,
                'aula_destino_nombre' => $gradoDestinoInfo['aula_nombre'] ?? 'Sin aula',
                'nodo_lora_id'        => $gradoDestinoInfo['lora_id'] ?? null,
                'anho_lectivo'        => $anhoLectivo,
                'message'             => "Se promovieron exitosamente " . count($validos) . " estudiantes a '{$nombreGradoDestino}'. " .
                                         ($huellasEncoladas > 0 ? "Se encolaron {$huellasEncoladas} huellas para transmisión LoRa hacia el aula asignada." : "")
            ];

        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}
