<?php
declare(strict_types=1);

// src/classes/PlanAnual.php

class PlanAnual {
    private PDO $conn;
    private string $table_name = "planes_anuales";

    public ?int $id_plan = null;
    public ?int $id_asignacion = null;
    public ?int $anio = null;
    public ?string $competencia_general = null;
    public ?string $competencia_especifica = null;
    public ?string $institucion_fuente = null;
    public ?string $materia_fuente = null;
    public ?string $profesor_fuente = null;
    public ?string $curso_fuente = null;
    public ?string $turno_fuente = null;
    public ?string $dias_clase_fuente = null;
    public ?string $estado = 'PUBLICADO';
    public ?string $observaciones = null;
    public ?int $created_by = null;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /**
     * Lista resumida de los planes anuales disponibles con datos de la asignación.
     * Consulta liviana y rápida para selectores y catálogos.
     */
    public function leer(?int $id_profesor = null): array {
        $sql = "SELECT p.id_plan, p.id_asignacion, p.anio, p.estado, p.turno_fuente,
                       p.competencia_general, p.competencia_especifica, p.institucion_fuente,
                       p.materia_fuente, p.curso_fuente, p.profesor_fuente,
                       a.id_profesor, a.id_materia, a.id_grado, a.carga_horaria,
                       prof.nombre AS profesor_nombre, prof.apellido AS profesor_apellido,
                       m.nombre AS materia_nombre,
                       g.nombre AS grado_nombre, g.seccion, g.turno,
                       COALESCE(temas_count.total_temas, 0) AS total_temas
                FROM {$this->table_name} p
                LEFT JOIN asignacion_docente a ON a.id_asignacion = p.id_asignacion
                LEFT JOIN profesores prof ON prof.id_profesor = a.id_profesor
                LEFT JOIN materias m ON m.id_materia = a.id_materia
                LEFT JOIN grados g ON g.id_grado = a.id_grado
                LEFT JOIN (
                    SELECT pu.id_plan, COUNT(pt.id_tema) AS total_temas
                    FROM plan_temas pt
                    JOIN plan_capacidades pc ON pc.id_capacidad = pt.id_capacidad
                    JOIN plan_unidades pu ON pu.id_unidad = pc.id_unidad
                    GROUP BY pu.id_plan
                ) temas_count ON temas_count.id_plan = p.id_plan";

        $params = [];
        if ($id_profesor !== null) {
            $sql .= " WHERE a.id_profesor = :profesor";
            $params[':profesor'] = $id_profesor;
        }

        $sql .= " ORDER BY p.anio DESC, p.id_plan DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene la estructura completa de un plan anual en CONSULTAS BATCH MASIVAS (sin N+1):
     * Unidades -> Capacidades -> Temas -> Indicadores, Instrumentos y Procedimientos
     */
    public function obtenerPlanCompleto(int $id_plan): ?array {
        // 1. Cabecera del plan y asignación
        $stmtPlan = $this->conn->prepare("
            SELECT p.*, 
                   a.id_profesor, a.id_materia, a.id_grado, a.carga_horaria,
                   prof.nombre AS profesor_nombre, prof.apellido AS profesor_apellido,
                   m.nombre AS materia_nombre,
                   g.nombre AS grado_nombre, g.seccion, g.turno
            FROM {$this->table_name} p
            LEFT JOIN asignacion_docente a ON a.id_asignacion = p.id_asignacion
            LEFT JOIN profesores prof ON prof.id_profesor = a.id_profesor
            LEFT JOIN materias m ON m.id_materia = a.id_materia
            LEFT JOIN grados g ON g.id_grado = a.id_grado
            WHERE p.id_plan = :id
            LIMIT 1
        ");
        $stmtPlan->execute([':id' => $id_plan]);
        $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            return null;
        }

        // 2. Unidades del plan
        $stmtUni = $this->conn->prepare("
            SELECT id_unidad, id_plan, nombre, tiempo_texto, orden 
            FROM plan_unidades 
            WHERE id_plan = :id_plan 
            ORDER BY orden ASC, id_unidad ASC
        ");
        $stmtUni->execute([':id_plan' => $id_plan]);
        $unidades = $stmtUni->fetchAll(PDO::FETCH_ASSOC);

        // 3. Capacidades de todo el plan en una sola consulta
        $stmtCap = $this->conn->prepare("
            SELECT pc.id_capacidad, pc.id_unidad, pc.descripcion, pc.orden
            FROM plan_capacidades pc
            JOIN plan_unidades pu ON pc.id_unidad = pu.id_unidad
            WHERE pu.id_plan = :id_plan
            ORDER BY pc.orden ASC, pc.id_capacidad ASC
        ");
        $stmtCap->execute([':id_plan' => $id_plan]);
        $capacidadesRaw = $stmtCap->fetchAll(PDO::FETCH_ASSOC);

        // 4. Temas y su programación de todo el plan en una sola consulta
        $stmtTem = $this->conn->prepare("
            SELECT pt.id_tema, pt.id_capacidad, pt.titulo, pt.contenido, pt.horas_catedra, pt.orden,
                   prog.id_programacion, prog.fecha_inicio, prog.fecha_fin,
                   prog.horas_catedra_planificadas, prog.observaciones AS prog_observaciones
            FROM plan_temas pt
            JOIN plan_capacidades pc ON pt.id_capacidad = pc.id_capacidad
            JOIN plan_unidades pu ON pc.id_unidad = pu.id_unidad
            LEFT JOIN plan_tema_programacion prog ON prog.id_tema = pt.id_tema
            WHERE pu.id_plan = :id_plan
            ORDER BY pt.orden ASC, pt.id_tema ASC
        ");
        $stmtTem->execute([':id_plan' => $id_plan]);
        $temasRaw = $stmtTem->fetchAll(PDO::FETCH_ASSOC);

        // 5. Indicadores de todo el plan en una sola consulta
        $stmtInd = $this->conn->prepare("
            SELECT pi.id_indicador, pi.id_tema, pi.codigo, pi.descripcion, pi.orden
            FROM plan_indicadores pi
            JOIN plan_temas pt ON pi.id_tema = pt.id_tema
            JOIN plan_capacidades pc ON pt.id_capacidad = pc.id_capacidad
            JOIN plan_unidades pu ON pc.id_unidad = pu.id_unidad
            WHERE pu.id_plan = :id_plan
            ORDER BY pi.orden ASC, pi.id_indicador ASC
        ");
        $stmtInd->execute([':id_plan' => $id_plan]);
        $indicadoresRaw = $stmtInd->fetchAll(PDO::FETCH_ASSOC);

        // 6. Procedimientos e Instrumentos de evaluación de todo el plan
        $stmtProc = $this->conn->prepare("
            SELECT ptp.id_tema, pe.id_procedimiento, pe.nombre
            FROM plan_tema_procedimientos ptp
            JOIN procedimientos_evaluativos pe ON pe.id_procedimiento = ptp.id_procedimiento
            JOIN plan_temas pt ON ptp.id_tema = pt.id_tema
            JOIN plan_capacidades pc ON pt.id_capacidad = pc.id_capacidad
            JOIN plan_unidades pu ON pc.id_unidad = pu.id_unidad
            WHERE pu.id_plan = :id_plan
            ORDER BY ptp.orden ASC
        ");
        $stmtProc->execute([':id_plan' => $id_plan]);
        $procedimientosRaw = $stmtProc->fetchAll(PDO::FETCH_ASSOC);

        $stmtInst = $this->conn->prepare("
            SELECT pti.id_tema, ie.id_instrumento, ie.nombre
            FROM plan_tema_instrumentos pti
            JOIN instrumentos_evaluativos ie ON ie.id_instrumento = pti.id_instrumento
            JOIN plan_temas pt ON pti.id_tema = pt.id_tema
            JOIN plan_capacidades pc ON pt.id_capacidad = pc.id_capacidad
            JOIN plan_unidades pu ON pc.id_unidad = pu.id_unidad
            WHERE pu.id_plan = :id_plan
            ORDER BY pti.orden ASC
        ");
        $stmtInst->execute([':id_plan' => $id_plan]);
        $instrumentosRaw = $stmtInst->fetchAll(PDO::FETCH_ASSOC);

        // Indexación en memoria rápida
        $indicadoresPorTema = [];
        foreach ($indicadoresRaw as $ind) {
            $indicadoresPorTema[$ind['id_tema']][] = $ind;
        }

        $procedimientosPorTema = [];
        $procedimientosIdsPorTema = [];
        foreach ($procedimientosRaw as $proc) {
            $procedimientosPorTema[$proc['id_tema']][] = $proc['nombre'];
            $procedimientosIdsPorTema[$proc['id_tema']][] = (int)$proc['id_procedimiento'];
        }

        $instrumentosPorTema = [];
        $instrumentosIdsPorTema = [];
        foreach ($instrumentosRaw as $inst) {
            $instrumentosPorTema[$inst['id_tema']][] = $inst['nombre'];
            $instrumentosIdsPorTema[$inst['id_tema']][] = (int)$inst['id_instrumento'];
        }

        $temasPorCapacidad = [];
        foreach ($temasRaw as $tema) {
            $idTema = $tema['id_tema'];
            $tema['indicadores'] = $indicadoresPorTema[$idTema] ?? [];
            $tema['procedimientos'] = $procedimientosPorTema[$idTema] ?? [];
            $tema['procedimientos_ids'] = $procedimientosIdsPorTema[$idTema] ?? [];
            $tema['instrumentos'] = $instrumentosPorTema[$idTema] ?? [];
            $tema['instrumentos_ids'] = $instrumentosIdsPorTema[$idTema] ?? [];
            $temasPorCapacidad[$tema['id_capacidad']][] = $tema;
        }

        $capacidadesPorUnidad = [];
        foreach ($capacidadesRaw as $cap) {
            $idCap = $cap['id_capacidad'];
            $cap['temas'] = $temasPorCapacidad[$idCap] ?? [];
            $capacidadesPorUnidad[$cap['id_unidad']][] = $cap;
        }

        foreach ($unidades as &$u) {
            $idUni = $u['id_unidad'];
            $u['capacidades'] = $capacidadesPorUnidad[$idUni] ?? [];
        }
        unset($u);

        $plan['unidades'] = $unidades;
        return $plan;
    }

    /**
     * Obtiene el tema/lección programado para una fecha específica y asignación
     */
    public function obtenerLeccionDelDia(int $id_asignacion, string $fecha): ?array {
        $sql = "SELECT pt.id_tema, pt.titulo AS tema_titulo, pt.contenido AS tema_contenido,
                       pt.horas_catedra,
                       pc.id_capacidad, pc.descripcion AS capacidad_descripcion,
                       pu.id_unidad, pu.nombre AS unidad_nombre,
                       p.id_plan, p.anio,
                       prog.id_programacion, prog.fecha_inicio, prog.fecha_fin,
                       prog.horas_catedra_planificadas
                FROM planes_anuales p
                JOIN plan_unidades pu ON pu.id_plan = p.id_plan
                JOIN plan_capacidades pc ON pc.id_unidad = pu.id_unidad
                JOIN plan_temas pt ON pt.id_capacidad = pc.id_capacidad
                JOIN plan_tema_programacion prog ON prog.id_tema = pt.id_tema
                WHERE p.id_asignacion = :id_asignacion
                  AND (prog.fecha_inicio = :fecha OR (:fecha BETWEEN prog.fecha_inicio AND COALESCE(prog.fecha_fin, prog.fecha_inicio)))
                ORDER BY pt.orden ASC
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_asignacion' => $id_asignacion, ':fecha' => $fecha]);
        $leccion = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fallback: primer tema si no hay fecha exacta hoy
        if (!$leccion) {
            $sqlFallback = "SELECT pt.id_tema, pt.titulo AS tema_titulo, pt.contenido AS tema_contenido,
                                   pt.horas_catedra,
                                   pc.id_capacidad, pc.descripcion AS capacidad_descripcion,
                                   pu.id_unidad, pu.nombre AS unidad_nombre,
                                   p.id_plan, p.anio,
                                   NULL AS id_programacion, :fecha AS fecha_inicio, :fecha AS fecha_fin,
                                   pt.horas_catedra AS horas_catedra_planificadas
                            FROM planes_anuales p
                            JOIN plan_unidades pu ON pu.id_plan = p.id_plan
                            JOIN plan_capacidades pc ON pc.id_unidad = pu.id_unidad
                            JOIN plan_temas pt ON pt.id_capacidad = pc.id_capacidad
                            WHERE p.id_asignacion = :id_asignacion
                            ORDER BY pu.orden ASC, pc.orden ASC, pt.orden ASC
                            LIMIT 1";
            $stmtFallback = $this->conn->prepare($sqlFallback);
            $stmtFallback->execute([':id_asignacion' => $id_asignacion, ':fecha' => $fecha]);
            $leccion = $stmtFallback->fetch(PDO::FETCH_ASSOC);
        }

        if (!$leccion) {
            return null;
        }

        // Agregar indicadores e instrumentos
        $stmtInd = $this->conn->prepare("SELECT id_indicador, codigo, descripcion, orden FROM plan_indicadores WHERE id_tema = :id_tema ORDER BY orden ASC");
        $stmtInd->execute([':id_tema' => $leccion['id_tema']]);
        $leccion['indicadores'] = $stmtInd->fetchAll(PDO::FETCH_ASSOC);

        $stmtProc = $this->conn->prepare("SELECT pe.nombre FROM plan_tema_procedimientos ptp JOIN procedimientos_evaluativos pe ON pe.id_procedimiento = ptp.id_procedimiento WHERE ptp.id_tema = :id_tema ORDER BY ptp.orden ASC");
        $stmtProc->execute([':id_tema' => $leccion['id_tema']]);
        $leccion['procedimientos'] = $stmtProc->fetchAll(PDO::FETCH_COLUMN);

        $stmtInst = $this->conn->prepare("SELECT ie.nombre FROM plan_tema_instrumentos pti JOIN instrumentos_evaluativos ie ON ie.id_instrumento = pti.id_instrumento WHERE pti.id_tema = :id_tema ORDER BY pti.orden ASC");
        $stmtInst->execute([':id_tema' => $leccion['id_tema']]);
        $leccion['instrumentos'] = $stmtInst->fetchAll(PDO::FETCH_COLUMN);

        return $leccion;
    }

    /**
     * Crear o Actualizar encabezado del plan anual
     */
    public function guardarPlan(
        int $id_asignacion,
        int $anio,
        string $competencia_general,
        string $competencia_especifica,
        string $turno_fuente = 'MAÑANA',
        string $institucion_fuente = 'COLEGIO PRIV. PRESB. SAN PABLO',
        string $estado = 'PUBLICADO',
        ?string $observaciones = null,
        ?int $id_plan = null,
        ?int $created_by = null
    ): int {
        if ($id_plan !== null && $id_plan > 0) {
            $sql = "UPDATE {$this->table_name} SET
                        id_asignacion = :id_asignacion,
                        anio = :anio,
                        competencia_general = :comp_gen,
                        competencia_especifica = :comp_esp,
                        turno_fuente = :turno,
                        institucion_fuente = :inst,
                        estado = :estado,
                        observaciones = :obs,
                        updated_at = NOW()
                    WHERE id_plan = :id_plan";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':id_asignacion' => $id_asignacion,
                ':anio' => $anio,
                ':comp_gen' => $competencia_general,
                ':comp_esp' => $competencia_especifica,
                ':turno' => $turno_fuente,
                ':inst' => $institucion_fuente,
                ':estado' => $estado,
                ':obs' => $observaciones,
                ':id_plan' => $id_plan,
            ]);
            return $id_plan;
        }

        $sql = "INSERT INTO {$this->table_name} (
                    id_asignacion, anio, competencia_general, competencia_especifica,
                    turno_fuente, institucion_fuente, estado, observaciones, created_by, created_at, updated_at
                ) VALUES (
                    :id_asignacion, :anio, :comp_gen, :comp_esp,
                    :turno, :inst, :estado, :obs, :created_by, NOW(), NOW()
                )";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id_asignacion' => $id_asignacion,
            ':anio' => $anio,
            ':comp_gen' => $competencia_general,
            ':comp_esp' => $competencia_especifica,
            ':turno' => $turno_fuente,
            ':inst' => $institucion_fuente,
            ':estado' => $estado,
            ':obs' => $observaciones,
            ':created_by' => $created_by,
        ]);
        return (int)$this->conn->lastInsertId();
    }

    /**
     * Eliminar plan anual completo y todas sus dependencias en cascada
     */
    public function eliminarPlan(int $id_plan): bool {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table_name} WHERE id_plan = :id_plan");
        return $stmt->execute([':id_plan' => $id_plan]);
    }

    /**
     * Guardar o editar una Unidad Temática
     */
    public function guardarUnidad(int $id_plan, string $nombre, ?string $tiempo_texto = null, int $orden = 1, ?int $id_unidad = null): int {
        if ($id_unidad !== null && $id_unidad > 0) {
            $stmt = $this->conn->prepare("
                UPDATE plan_unidades 
                SET nombre = :nombre, tiempo_texto = :tiempo, orden = :orden, updated_at = NOW()
                WHERE id_unidad = :id_unidad AND id_plan = :id_plan
            ");
            $stmt->execute([
                ':nombre' => $nombre,
                ':tiempo' => $tiempo_texto,
                ':orden' => $orden,
                ':id_unidad' => $id_unidad,
                ':id_plan' => $id_plan,
            ]);
            return $id_unidad;
        }

        $stmt = $this->conn->prepare("
            INSERT INTO plan_unidades (id_plan, nombre, tiempo_texto, orden, created_at, updated_at)
            VALUES (:id_plan, :nombre, :tiempo, :orden, NOW(), NOW())
        ");
        $stmt->execute([
            ':id_plan' => $id_plan,
            ':nombre' => $nombre,
            ':tiempo' => $tiempo_texto,
            ':orden' => $orden,
        ]);
        return (int)$this->conn->lastInsertId();
    }

    /**
     * Eliminar unidad temática
     */
    public function eliminarUnidad(int $id_unidad): bool {
        $stmt = $this->conn->prepare("DELETE FROM plan_unidades WHERE id_unidad = :id_unidad");
        return $stmt->execute([':id_unidad' => $id_unidad]);
    }

    /**
     * Guardar o editar una Capacidad
     */
    public function guardarCapacidad(int $id_unidad, string $descripcion, int $orden = 1, ?int $id_capacidad = null): int {
        if ($id_capacidad !== null && $id_capacidad > 0) {
            $stmt = $this->conn->prepare("
                UPDATE plan_capacidades 
                SET descripcion = :desc, orden = :orden, updated_at = NOW()
                WHERE id_capacidad = :id_capacidad
            ");
            $stmt->execute([
                ':desc' => $descripcion,
                ':orden' => $orden,
                ':id_capacidad' => $id_capacidad,
            ]);
            return $id_capacidad;
        }

        $stmt = $this->conn->prepare("
            INSERT INTO plan_capacidades (id_unidad, descripcion, orden, created_at, updated_at)
            VALUES (:id_unidad, :desc, :orden, NOW(), NOW())
        ");
        $stmt->execute([
            ':id_unidad' => $id_unidad,
            ':desc' => $descripcion,
            ':orden' => $orden,
        ]);
        return (int)$this->conn->lastInsertId();
    }

    /**
     * Eliminar capacidad
     */
    public function eliminarCapacidad(int $id_capacidad): bool {
        $stmt = $this->conn->prepare("DELETE FROM plan_capacidades WHERE id_capacidad = :id_capacidad");
        return $stmt->execute([':id_capacidad' => $id_capacidad]);
    }

    /**
     * Guarda o edita un tema pedagógico con su programación de fechas, horas cátedra e indicadores
     */
    public function guardarTemaCompleto(
        int $id_capacidad,
        string $titulo,
        float $horas_catedra,
        ?string $fecha_inicio = null,
        ?string $fecha_fin = null,
        array $indicadores = [],
        array $procedimientos = [],
        array $instrumentos = [],
        ?int $id_tema = null
    ): int {
        $this->conn->beginTransaction();
        try {
            if ($id_tema !== null && $id_tema > 0) {
                // Actualizar tema
                $stmtTema = $this->conn->prepare("
                    UPDATE plan_temas 
                    SET id_capacidad = :id_capacidad, titulo = :titulo, horas_catedra = :horas, updated_at = NOW()
                    WHERE id_tema = :id_tema
                ");
                $stmtTema->execute([
                    ':id_capacidad' => $id_capacidad,
                    ':titulo' => $titulo,
                    ':horas' => $horas_catedra,
                    ':id_tema' => $id_tema,
                ]);

                // Actualizar programación
                $stmtProgCheck = $this->conn->prepare("SELECT COUNT(*) FROM plan_tema_programacion WHERE id_tema = :id_tema");
                $stmtProgCheck->execute([':id_tema' => $id_tema]);
                if ((int)$stmtProgCheck->fetchColumn() > 0) {
                    $stmtProg = $this->conn->prepare("
                        UPDATE plan_tema_programacion 
                        SET fecha_inicio = :inicio, fecha_fin = :fin, horas_catedra_planificadas = :horas
                        WHERE id_tema = :id_tema
                    ");
                    $stmtProg->execute([
                        ':inicio' => $fecha_inicio,
                        ':fin' => $fecha_fin ?? $fecha_inicio,
                        ':horas' => $horas_catedra,
                        ':id_tema' => $id_tema,
                    ]);
                } else if ($fecha_inicio) {
                    $stmtProg = $this->conn->prepare("
                        INSERT INTO plan_tema_programacion (id_tema, fecha_inicio, fecha_fin, horas_catedra_planificadas)
                        VALUES (:id_tema, :inicio, :fin, :horas)
                    ");
                    $stmtProg->execute([
                        ':id_tema' => $id_tema,
                        ':inicio' => $fecha_inicio,
                        ':fin' => $fecha_fin ?? $fecha_inicio,
                        ':horas' => $horas_catedra,
                    ]);
                }

                // Reemplazar indicadores
                $this->conn->prepare("DELETE FROM plan_indicadores WHERE id_tema = :id_tema")->execute([':id_tema' => $id_tema]);
                // Reemplazar procedimientos e instrumentos
                $this->conn->prepare("DELETE FROM plan_tema_procedimientos WHERE id_tema = :id_tema")->execute([':id_tema' => $id_tema]);
                $this->conn->prepare("DELETE FROM plan_tema_instrumentos WHERE id_tema = :id_tema")->execute([':id_tema' => $id_tema]);
            } else {
                // Insertar nuevo tema
                $stmtTema = $this->conn->prepare("
                    INSERT INTO plan_temas (id_capacidad, titulo, horas_catedra, orden, created_at, updated_at)
                    VALUES (:id_capacidad, :titulo, :horas, 
                            COALESCE((SELECT MAX(orden) + 1 FROM plan_temas WHERE id_capacidad = :id_capacidad), 1),
                            NOW(), NOW())
                ");
                $stmtTema->execute([
                    ':id_capacidad' => $id_capacidad,
                    ':titulo' => $titulo,
                    ':horas' => $horas_catedra,
                ]);
                $id_tema = (int)$this->conn->lastInsertId();

                if ($fecha_inicio) {
                    $stmtProg = $this->conn->prepare("
                        INSERT INTO plan_tema_programacion (id_tema, fecha_inicio, fecha_fin, horas_catedra_planificadas)
                        VALUES (:id_tema, :inicio, :fin, :horas)
                    ");
                    $stmtProg->execute([
                        ':id_tema' => $id_tema,
                        ':inicio' => $fecha_inicio,
                        ':fin' => $fecha_fin ?? $fecha_inicio,
                        ':horas' => $horas_catedra,
                    ]);
                }
            }

            // Insertar indicadores
            if (!empty($indicadores)) {
                $stmtInd = $this->conn->prepare("
                    INSERT INTO plan_indicadores (id_tema, descripcion, orden, created_at, updated_at)
                    VALUES (:id_tema, :desc, :orden, NOW(), NOW())
                ");
                foreach ($indicadores as $idx => $indDesc) {
                    if (trim((string)$indDesc) !== '') {
                        $stmtInd->execute([
                            ':id_tema' => $id_tema,
                            ':desc' => trim((string)$indDesc),
                            ':orden' => $idx + 1,
                        ]);
                    }
                }
            }

            // Insertar procedimientos
            if (!empty($procedimientos)) {
                $stmtProc = $this->conn->prepare("
                    INSERT INTO plan_tema_procedimientos (id_tema, id_procedimiento, orden)
                    VALUES (:id_tema, :id_proc, :orden)
                ");
                foreach ($procedimientos as $idx => $idProc) {
                    $idProcInt = (int)$idProc;
                    if ($idProcInt > 0) {
                        $stmtProc->execute([
                            ':id_tema' => $id_tema,
                            ':id_proc' => $idProcInt,
                            ':orden' => $idx + 1,
                        ]);
                    }
                }
            }

            // Insertar instrumentos
            if (!empty($instrumentos)) {
                $stmtInst = $this->conn->prepare("
                    INSERT INTO plan_tema_instrumentos (id_tema, id_instrumento, orden)
                    VALUES (:id_tema, :id_inst, :orden)
                ");
                foreach ($instrumentos as $idx => $idInst) {
                    $idInstInt = (int)$idInst;
                    if ($idInstInt > 0) {
                        $stmtInst->execute([
                            ':id_tema' => $id_tema,
                            ':id_inst' => $idInstInt,
                            ':orden' => $idx + 1,
                        ]);
                    }
                }
            }

            $this->conn->commit();
            return $id_tema;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Eliminar tema
     */
    public function eliminarTema(int $id_tema): bool {
        $stmt = $this->conn->prepare("DELETE FROM plan_temas WHERE id_tema = :id_tema");
        return $stmt->execute([':id_tema' => $id_tema]);
    }

    /**
     * Retorna las asignaciones del docente para poder asociarlas a un nuevo plan anual
     */
    public function obtenerAsignacionesDocente(?int $id_profesor = null): array {
        $sql = "SELECT ad.id_asignacion, ad.carga_horaria,
                       m.nombre AS materia_nombre,
                       g.nombre AS grado_nombre, g.seccion, g.turno
                FROM asignacion_docente ad
                JOIN materias m ON ad.id_materia = m.id_materia
                LEFT JOIN grados g ON ad.id_grado = g.id_grado
                WHERE ad.activo = 1";

        $params = [];
        if ($id_profesor !== null) {
            $sql .= " AND ad.id_profesor = :profesor";
            $params[':profesor'] = $id_profesor;
        }

        $sql .= " ORDER BY g.nombre ASC, m.nombre ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
