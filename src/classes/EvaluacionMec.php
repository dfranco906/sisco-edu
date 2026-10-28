<?php
declare(strict_types=1);

class EvaluacionMec {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /**
     * Obtiene el catálogo de plantillas evaluativas (institucionales del MEC + personalizadas del docente)
     */
    public function obtenerPlantillas(?int $idProfesor = null, ?int $idInstrumento = null): array {
        $sql = "
            SELECT 
                p.id_plantilla,
                p.id_profesor,
                p.id_procedimiento,
                p.id_instrumento,
                p.titulo,
                p.descripcion,
                p.criterios,
                p.puntaje_total,
                p.es_institucional,
                pe.nombre AS procedimiento_nombre,
                ie.nombre AS instrumento_nombre,
                CASE 
                    WHEN p.es_institucional = 1 THEN 'Plantilla Estándar MEC'
                    ELSE CONCAT('Personalizada por Prof. ', COALESCE(prof.nombre, ''), ' ', COALESCE(prof.apellido, ''))
                END AS autor
            FROM instrumentos_plantillas p
            INNER JOIN procedimientos_evaluativos pe ON pe.id_procedimiento = p.id_procedimiento
            INNER JOIN instrumentos_evaluativos ie ON ie.id_instrumento = p.id_instrumento
            LEFT JOIN profesores prof ON prof.id_profesor = p.id_profesor
            WHERE p.activo = 1
              AND (p.es_institucional = 1 OR (:id_profesor IS NOT NULL AND p.id_profesor = :id_profesor))
        ";

        $params = [':id_profesor' => $idProfesor];

        if ($idInstrumento !== null && $idInstrumento > 0) {
            $sql .= " AND p.id_instrumento = :id_instrumento";
            $params[':id_instrumento'] = $idInstrumento;
        }

        $sql .= " ORDER BY p.es_institucional DESC, p.titulo ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            if (is_string($r['criterios'])) {
                $r['criterios'] = json_decode($r['criterios'], true) ?: [];
            }
            $r['puntaje_total'] = (float)$r['puntaje_total'];
        }
        unset($r);

        return $rows;
    }

    /**
     * Crea una nueva plantilla de instrumento evaluativo reutilizable
     */
    public function crearPlantilla(
        int $idProcedimiento,
        int $idInstrumento,
        string $titulo,
        ?string $descripcion,
        array $criterios,
        float $puntajeTotal,
        ?int $idProfesor = null
    ): int {
        $stmt = $this->conn->prepare("
            INSERT INTO instrumentos_plantillas (
                id_profesor, id_procedimiento, id_instrumento, titulo, descripcion, criterios, puntaje_total, es_institucional, activo
            ) VALUES (
                :id_profesor, :id_proc, :id_inst, :titulo, :descripcion, :criterios::jsonb, :puntaje, :es_inst, 1
            ) RETURNING id_plantilla
        ");

        $stmt->execute([
            ':id_profesor' => $idProfesor,
            ':id_proc' => $idProcedimiento,
            ':id_inst' => $idInstrumento,
            ':titulo' => trim($titulo),
            ':descripcion' => $descripcion ? trim($descripcion) : null,
            ':criterios' => json_encode($criterios, JSON_UNESCAPED_UNICODE),
            ':puntaje' => $puntajeTotal,
            ':es_inst' => ($idProfesor === null) ? 1 : 0
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Elimina (desactiva) una plantilla creada por el profesor
     */
    public function eliminarPlantilla(int $idPlantilla, ?int $idProfesor = null): bool {
        $sql = "UPDATE instrumentos_plantillas SET activo = 0 WHERE id_plantilla = :id";
        $params = [':id' => $idPlantilla];

        if ($idProfesor !== null) {
            $sql .= " AND (id_profesor = :id_profesor OR id_profesor IS NULL)";
            $params[':id_profesor'] = $idProfesor;
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Registra o actualiza la evaluación individual del estudiante en la clase
     */
    public function guardarEvaluacionEstudiante(
        int $idClase,
        int $idEstudiante,
        ?int $idPlantilla,
        ?int $idInstrumento,
        float $puntajeObtenido,
        float $puntajeMaximo,
        array $detallesCriterios,
        ?string $observaciones = null
    ): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO clase_diaria_evaluaciones (
                id_clase, id_estudiante, id_plantilla, id_instrumento,
                puntaje_obtenido, puntaje_maximo, detalles_criterios, observaciones, updated_at
            ) VALUES (
                :id_clase, :id_estudiante, :id_plantilla, :id_instrumento,
                :puntaje_obtenido, :puntaje_maximo, :detalles::jsonb, :observaciones, CURRENT_TIMESTAMP
            )
            ON CONFLICT (id_clase, id_estudiante)
            DO UPDATE SET
                id_plantilla = EXCLUDED.id_plantilla,
                id_instrumento = EXCLUDED.id_instrumento,
                puntaje_obtenido = EXCLUDED.puntaje_obtenido,
                puntaje_maximo = EXCLUDED.puntaje_maximo,
                detalles_criterios = EXCLUDED.detalles_criterios,
                observaciones = EXCLUDED.observaciones,
                updated_at = CURRENT_TIMESTAMP
        ");

        return $stmt->execute([
            ':id_clase' => $idClase,
            ':id_estudiante' => $idEstudiante,
            ':id_plantilla' => $idPlantilla,
            ':id_instrumento' => $idInstrumento,
            ':puntaje_obtenido' => $puntajeObtenido,
            ':puntaje_maximo' => $puntajeMaximo,
            ':detalles' => json_encode($detallesCriterios, JSON_UNESCAPED_UNICODE),
            ':observaciones' => $observaciones ? trim($observaciones) : null
        ]);
    }

    /**
     * Registra un evento anecdotario individual para un estudiante en la sesión de clase
     */
    public function guardarRegistroAnecdotico(
        int $idClase,
        int $idEstudiante,
        string $observacion,
        ?int $createdBy = null,
        ?int $idAsistenciaEstudiante = null
    ): int {
        $stmt = $this->conn->prepare("
            INSERT INTO registros_anecdoticos (
                id_clase, id_estudiante, id_asistencia_estudiante, observacion, created_by, activo, created_at, updated_at
            ) VALUES (
                :id_clase, :id_estudiante, :id_asist, :observacion, :created_by, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            ) RETURNING id_registro
        ");

        $stmt->execute([
            ':id_clase' => $idClase,
            ':id_estudiante' => $idEstudiante,
            ':id_asist' => $idAsistenciaEstudiante,
            ':observacion' => trim($observacion),
            ':created_by' => $createdBy
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtiene los registros anecdotarios de una clase diaria
     */
    public function obtenerRegistrosAnecdoticosClase(int $idClase): array {
        $stmt = $this->conn->prepare("
            SELECT 
                ra.id_registro,
                ra.id_clase,
                ra.id_estudiante,
                ra.observacion,
                ra.created_at,
                e.nombre AS estudiante_nombre,
                e.apellido AS estudiante_apellido,
                e.cedula_identidad AS estudiante_ci,
                CONCAT(e.nombre, ' ', e.apellido) AS estudiante_completo
            FROM registros_anecdoticos ra
            INNER JOIN estudiantes e ON e.id_estudiante = ra.id_estudiante
            WHERE ra.id_clase = :id_clase AND ra.activo = 1
            ORDER BY ra.created_at DESC
        ");
        $stmt->execute([':id_clase' => $idClase]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las evaluaciones registradas en una clase diaria
     */
    public function obtenerEvaluacionesClase(int $idClase): array {
        $stmt = $this->conn->prepare("
            SELECT 
                cde.id_evaluacion,
                cde.id_clase,
                cde.id_estudiante,
                cde.id_plantilla,
                cde.id_instrumento,
                cde.puntaje_obtenido,
                cde.puntaje_maximo,
                cde.detalles_criterios,
                cde.observaciones,
                cde.updated_at,
                ip.titulo AS plantilla_titulo,
                ie.nombre AS instrumento_nombre,
                e.nombre AS estudiante_nombre,
                e.apellido AS estudiante_apellido,
                e.cedula_identidad AS estudiante_ci,
                CONCAT(e.nombre, ' ', e.apellido) AS estudiante_completo
            FROM clase_diaria_evaluaciones cde
            INNER JOIN estudiantes e ON e.id_estudiante = cde.id_estudiante
            LEFT JOIN instrumentos_plantillas ip ON ip.id_plantilla = cde.id_plantilla
            LEFT JOIN instrumentos_evaluativos ie ON ie.id_instrumento = COALESCE(cde.id_instrumento, ip.id_instrumento)
            WHERE cde.id_clase = :id_clase
            ORDER BY e.apellido ASC, e.nombre ASC
        ");
        $stmt->execute([':id_clase' => $idClase]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$r) {
            if (is_string($r['detalles_criterios'])) {
                $r['detalles_criterios'] = json_decode($r['detalles_criterios'], true) ?: [];
            }
            $r['puntaje_obtenido'] = (float)$r['puntaje_obtenido'];
            $r['puntaje_maximo'] = (float)$r['puntaje_maximo'];
        }
        unset($r);

        return $rows;
    }
}
