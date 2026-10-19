<?php
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
     * Lista los planes anuales disponibles con datos de la asignación
     */
    public function leer(?int $id_profesor = null): array {
        $sql = "SELECT p.*, 
                       a.id_profesor, a.id_materia, a.id_grado, a.carga_horaria,
                       prof.nombre AS profesor_nombre, prof.apellido AS profesor_apellido,
                       m.nombre AS materia_nombre,
                       g.nombre AS grado_nombre,
                       (SELECT COUNT(*) FROM plan_temas pt 
                        JOIN plan_capacidades pc ON pc.id_capacidad = pt.id_capacidad
                        JOIN plan_unidades pu ON pu.id_unidad = pc.id_unidad
                        WHERE pu.id_plan = p.id_plan) AS total_temas
                FROM {$this->table_name} p
                LEFT JOIN asignacion_docente a ON a.id_asignacion = p.id_asignacion
                LEFT JOIN profesores prof ON prof.id_profesor = a.id_profesor
                LEFT JOIN materias m ON m.id_materia = a.id_materia
                LEFT JOIN grados g ON g.id_grado = a.id_grado";

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
     * Obtiene la estructura completa de un plan anual:
     * Unidades -> Capacidades -> Temas -> Indicadores, Instrumentos y Programación
     */
    public function obtenerPlanCompleto(int $id_plan): ?array {
        $stmtPlan = $this->conn->prepare("
            SELECT p.*, 
                   a.id_profesor, a.id_materia, a.id_grado, a.carga_horaria,
                   prof.nombre AS profesor_nombre, prof.apellido AS profesor_apellido,
                   m.nombre AS materia_nombre,
                   g.nombre AS grado_nombre
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

        // 1. Obtener Unidades
        $stmtUni = $this->conn->prepare("
            SELECT * FROM plan_unidades 
            WHERE id_plan = :id_plan 
            ORDER BY orden ASC, id_unidad ASC
        ");
        $stmtUni->execute([':id_plan' => $id_plan]);
        $unidades = $stmtUni->fetchAll(PDO::FETCH_ASSOC);

        foreach ($unidades as &$unidad) {
            // 2. Obtener Capacidades
            $stmtCap = $this->conn->prepare("
                SELECT * FROM plan_capacidades 
                WHERE id_unidad = :id_unidad 
                ORDER BY orden ASC, id_capacidad ASC
            ");
            $stmtCap->execute([':id_unidad' => $unidad['id_unidad']]);
            $capacidades = $stmtCap->fetchAll(PDO::FETCH_ASSOC);

            foreach ($capacidades as &$capacidad) {
                // 3. Obtener Temas
                $stmtTem = $this->conn->prepare("
                    SELECT pt.*,
                           prog.id_programacion, prog.fecha_inicio, prog.fecha_fin,
                           prog.horas_catedra_planificadas, prog.observaciones AS prog_observaciones
                    FROM plan_temas pt
                    LEFT JOIN plan_tema_programacion prog ON prog.id_tema = pt.id_tema
                    WHERE pt.id_capacidad = :id_capacidad
                    ORDER BY pt.orden ASC, pt.id_tema ASC
                ");
                $stmtTem->execute([':id_capacidad' => $capacidad['id_capacidad']]);
                $temas = $stmtTem->fetchAll(PDO::FETCH_ASSOC);

                foreach ($temas as &$tema) {
                    // 4. Obtener Indicadores
                    $stmtInd = $this->conn->prepare("
                        SELECT * FROM plan_indicadores 
                        WHERE id_tema = :id_tema 
                        ORDER BY orden ASC, id_indicador ASC
                    ");
                    $stmtInd->execute([':id_tema' => $tema['id_tema']]);
                    $tema['indicadores'] = $stmtInd->fetchAll(PDO::FETCH_ASSOC);

                    // 5. Procedimientos e Instrumentos
                    $stmtProc = $this->conn->prepare("
                        SELECT pe.nombre 
                        FROM plan_tema_procedimientos ptp
                        JOIN procedimientos_evaluativos pe ON pe.id_procedimiento = ptp.id_procedimiento
                        WHERE ptp.id_tema = :id_tema
                        ORDER BY ptp.orden ASC
                    ");
                    $stmtProc->execute([':id_tema' => $tema['id_tema']]);
                    $tema['procedimientos'] = $stmtProc->fetchAll(PDO::FETCH_COLUMN);

                    $stmtInst = $this->conn->prepare("
                        SELECT ie.nombre 
                        FROM plan_tema_instrumentos pti
                        JOIN instrumentos_evaluativos ie ON ie.id_instrumento = pti.id_instrumento
                        WHERE pti.id_tema = :id_tema
                        ORDER BY pti.orden ASC
                    ");
                    $stmtInst->execute([':id_tema' => $tema['id_tema']]);
                    $tema['instrumentos'] = $stmtInst->fetchAll(PDO::FETCH_COLUMN);
                }
                unset($tema);
                $capacidad['temas'] = $temas;
            }
            unset($capacidad);
            $unidad['capacidades'] = $capacidades;
        }
        unset($unidad);

        $plan['unidades'] = $unidades;
        return $plan;
    }

    /**
     * Obtiene el tema/lección programado para una fecha específica y asignación
     */
    public function obtenerLeccionDelDia(int $id_asignacion, string $fecha): ?array {
        // Buscar coincidencia exacta en programación de fechas
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

        // Si no hay fecha programada para hoy, obtener el primer tema del plan para no dejar la clase sin lección
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
        $stmtInd = $this->conn->prepare("
            SELECT id_indicador, codigo, descripcion, orden 
            FROM plan_indicadores 
            WHERE id_tema = :id_tema 
            ORDER BY orden ASC, id_indicador ASC
        ");
        $stmtInd->execute([':id_tema' => $leccion['id_tema']]);
        $leccion['indicadores'] = $stmtInd->fetchAll(PDO::FETCH_ASSOC);

        $stmtProc = $this->conn->prepare("
            SELECT pe.nombre 
            FROM plan_tema_procedimientos ptp
            JOIN procedimientos_evaluativos pe ON pe.id_procedimiento = ptp.id_procedimiento
            WHERE ptp.id_tema = :id_tema
            ORDER BY ptp.orden ASC
        ");
        $stmtProc->execute([':id_tema' => $leccion['id_tema']]);
        $leccion['procedimientos'] = $stmtProc->fetchAll(PDO::FETCH_COLUMN);

        $stmtInst = $this->conn->prepare("
            SELECT ie.nombre 
            FROM plan_tema_instrumentos pti
            JOIN instrumentos_evaluativos ie ON ie.id_instrumento = pti.id_instrumento
            WHERE pti.id_tema = :id_tema
            ORDER BY pti.orden ASC
        ");
        $stmtInst->execute([':id_tema' => $leccion['id_tema']]);
        $leccion['instrumentos'] = $stmtInst->fetchAll(PDO::FETCH_COLUMN);

        return $leccion;
    }

    /**
     * Crear encabezado de un nuevo plan anual
     */
    public function crear(): int {
        $sql = "INSERT INTO {$this->table_name} (
                    id_asignacion, anio, competencia_general, competencia_especifica,
                    institucion_fuente, materia_fuente, profesor_fuente, curso_fuente,
                    turno_fuente, dias_clase_fuente, estado, observaciones, created_by
                ) VALUES (
                    :id_asignacion, :anio, :competencia_general, :competencia_especifica,
                    :institucion_fuente, :materia_fuente, :profesor_fuente, :curso_fuente,
                    :turno_fuente, :dias_clase_fuente, :estado, :observaciones, :created_by
                )";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id_asignacion' => $this->id_asignacion,
            ':anio' => $this->anio ?? (int)date('Y'),
            ':competencia_general' => $this->competencia_general,
            ':competencia_especifica' => $this->competencia_especifica,
            ':institucion_fuente' => $this->institucion_fuente ?? 'COLEGIO SAN PABLO',
            ':materia_fuente' => $this->materia_fuente,
            ':profesor_fuente' => $this->profesor_fuente,
            ':curso_fuente' => $this->curso_fuente,
            ':turno_fuente' => $this->turno_fuente ?? 'MAÑANA',
            ':dias_clase_fuente' => $this->dias_clase_fuente,
            ':estado' => $this->estado ?? 'PUBLICADO',
            ':observaciones' => $this->observaciones,
            ':created_by' => $this->created_by
        ]);

        return (int)$this->conn->lastInsertId();
    }

    /**
     * Guarda un tema pedagógico con su programación de fechas, horas cátedra e indicadores
     */
    public function guardarTemaCompleto(
        int $id_capacidad,
        string $titulo,
        float $horas_catedra,
        ?string $fecha_inicio = null,
        ?string $fecha_fin = null,
        array $indicadores = [],
        array $procedimientos = [],
        array $instrumentos = []
    ): int {
        $this->conn->beginTransaction();
        try {
            // 1. Insertar tema
            $stmtTema = $this->conn->prepare("
                INSERT INTO plan_temas (id_capacidad, titulo, horas_catedra, orden)
                VALUES (:id_capacidad, :titulo, :horas, 
                        COALESCE((SELECT MAX(orden) + 1 FROM plan_temas WHERE id_capacidad = :id_capacidad), 1))
            ");
            $stmtTema->execute([
                ':id_capacidad' => $id_capacidad,
                ':titulo' => $titulo,
                ':horas' => $horas_catedra
            ]);
            $id_tema = (int)$this->conn->lastInsertId();

            // 2. Programación de fecha y horas cátedra (40 min cada una)
            if ($fecha_inicio) {
                $stmtProg = $this->conn->prepare("
                    INSERT INTO plan_tema_programacion (id_tema, fecha_inicio, fecha_fin, horas_catedra_planificadas)
                    VALUES (:id_tema, :inicio, :fin, :horas)
                ");
                $stmtProg->execute([
                    ':id_tema' => $id_tema,
                    ':inicio' => $fecha_inicio,
                    ':fin' => $fecha_fin ?? $fecha_inicio,
                    ':horas' => $horas_catedra
                ]);
            }

            // 3. Indicadores
            if (!empty($indicadores)) {
                $stmtInd = $this->conn->prepare("
                    INSERT INTO plan_indicadores (id_tema, descripcion, orden)
                    VALUES (:id_tema, :desc, :orden)
                ");
                foreach ($indicadores as $idx => $indDesc) {
                    if (trim((string)$indDesc) !== '') {
                        $stmtInd->execute([
                            ':id_tema' => $id_tema,
                            ':desc' => trim($indDesc),
                            ':orden' => $idx + 1
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
}
