<?php
// src/classes/ClaseDiaria.php
require_once __DIR__ . '/PlanAnual.php';

class ClaseDiaria {
    private PDO $conn;
    private string $table_name = "clases_diarias";

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /**
     * Mapea el número de día en PHP a su nombre en español
     */
    public static function obtenerDiaEspanol(int $diaSemanaNumero): array {
        $diasConTilde = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado'
        ];
        $diasSinTilde = [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miercoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sabado'
        ];

        return [
            'con_tilde' => $diasConTilde[$diaSemanaNumero] ?? 'Lunes',
            'sin_tilde' => $diasSinTilde[$diaSemanaNumero] ?? 'Lunes'
        ];
    }

    /**
     * Detecta o inicializa la clase activa en un aula/fecha/hora determinada
     */
    public function obtenerOCrearClaseActiva(?int $id_aula = null, ?string $timestamp = null, ?int $id_profesor = null): ?array {
        $fechaHora = $timestamp ? new DateTime($timestamp) : new DateTime();
        $fecha = $fechaHora->format('Y-m-d');
        $hora = $fechaHora->format('H:i:s');
        $diaInfo = self::obtenerDiaEspanol((int)$fechaHora->format('w'));

        // Si no se proporcionó aula, resolver automáticamente
        if (!$id_aula) {
            if ($id_profesor !== null) {
                // 1. Buscar aula según horario de hoy para el profesor
                $sqlAulaHoy = "SELECT COALESCE(h.id_aula, g.id_aula) AS id_aula
                               FROM horarios h
                               JOIN asignacion_docente a ON a.id_asignacion = h.id_asignacion AND a.activo = 1
                               LEFT JOIN grados g ON g.id_grado = COALESCE(h.id_grado, a.id_grado)
                               WHERE a.id_profesor = :id_profesor
                                 AND (h.dia_semana = :dia1 OR h.dia_semana = :dia2)
                                 AND h.activo = 1
                               ORDER BY (CASE 
                                            WHEN :hora BETWEEN (h.hora_inicio - INTERVAL '30 minutes') AND (h.hora_fin + INTERVAL '30 minutes') THEN 0
                                            ELSE 1
                                         END), h.hora_inicio ASC
                               LIMIT 1";
                $stmtAH = $this->conn->prepare($sqlAulaHoy);
                $stmtAH->execute([
                    ':id_profesor' => $id_profesor,
                    ':dia1' => $diaInfo['con_tilde'],
                    ':dia2' => $diaInfo['sin_tilde'],
                    ':hora' => $hora
                ]);
                $id_aula = (int)$stmtAH->fetchColumn();

                // 2. Si hoy no tiene clases, tomar la primera aula asignada al profesor
                if (!$id_aula) {
                    $sqlAulaFall = "SELECT COALESCE(h.id_aula, g.id_aula) AS id_aula
                                    FROM asignacion_docente a
                                    LEFT JOIN grados g ON g.id_grado = a.id_grado
                                    LEFT JOIN horarios h ON h.id_asignacion = a.id_asignacion
                                    WHERE a.id_profesor = :id_profesor
                                      AND a.activo = 1
                                      AND COALESCE(h.id_aula, g.id_aula) IS NOT NULL
                                    LIMIT 1";
                    $stmtAF = $this->conn->prepare($sqlAulaFall);
                    $stmtAF->execute([':id_profesor' => $id_profesor]);
                    $id_aula = (int)$stmtAF->fetchColumn();
                }
            } else {
                $stmtA = $this->conn->query("
                    SELECT COALESCE(
                        (SELECT id_aula FROM horarios WHERE activo = 1 AND id_aula IS NOT NULL LIMIT 1),
                        (SELECT id_aula FROM aulas WHERE activo = 1 ORDER BY id_aula ASC LIMIT 1),
                        1
                    )
                ");
                $id_aula = (int)$stmtA->fetchColumn();
            }
        }

        if (!$id_aula) {
            return null;
        }

        // 1. Buscar horario activo en el aula en este día y rango de horas (con 30 min de tolerancia antes/después)
        $sqlHorario = "SELECT h.*, 
                              a.id_profesor, a.id_materia, a.id_grado, a.carga_horaria,
                              p.nombre AS profesor_nombre, p.apellido AS profesor_apellido, p.cedula_identidad AS profesor_ci,
                              p.user_id_global AS profesor_user_id,
                              m.nombre AS materia_nombre,
                              g.nombre AS grado_nombre,
                              aul.nombre AS aula_nombre, aul.codigo AS aula_codigo
                       FROM horarios h
                       JOIN asignacion_docente a ON a.id_asignacion = h.id_asignacion AND a.activo = 1
                       JOIN profesores p ON p.id_profesor = a.id_profesor
                       JOIN materias m ON m.id_materia = a.id_materia
                       LEFT JOIN grados g ON g.id_grado = COALESCE(h.id_grado, a.id_grado)
                       LEFT JOIN aulas aul ON aul.id_aula = COALESCE(h.id_aula, g.id_aula, :id_aula)
                       WHERE (h.id_aula = :id_aula OR g.id_aula = :id_aula)
                         AND (h.dia_semana = :dia1 OR h.dia_semana = :dia2)
                         AND h.activo = 1";

        $paramsH = [
            ':id_aula' => $id_aula,
            ':dia1' => $diaInfo['con_tilde'],
            ':dia2' => $diaInfo['sin_tilde'],
            ':hora' => $hora
        ];

        if ($id_profesor !== null) {
            $sqlHorario .= " AND a.id_profesor = :id_profesor";
            $paramsH[':id_profesor'] = $id_profesor;
        }

        $sqlHorario .= " ORDER BY (CASE 
                                     WHEN :hora BETWEEN (h.hora_inicio - INTERVAL '30 minutes') AND (h.hora_fin + INTERVAL '30 minutes') THEN 0
                                     ELSE 1
                                  END), h.hora_inicio ASC
                        LIMIT 1";

        $stmtH = $this->conn->prepare($sqlHorario);
        $stmtH->execute($paramsH);
        $horario = $stmtH->fetch(PDO::FETCH_ASSOC);

        // Si no hay horario hoy en esa aula, buscar cualquier horario de la materia o primer horario del aula
        if (!$horario) {
            $sqlCualquiera = "SELECT h.*, 
                                     a.id_profesor, a.id_materia, a.id_grado, a.carga_horaria,
                                     p.nombre AS profesor_nombre, p.apellido AS profesor_apellido, p.cedula_identidad AS profesor_ci,
                                     p.user_id_global AS profesor_user_id,
                                     m.nombre AS materia_nombre,
                                     g.nombre AS grado_nombre,
                                     aul.nombre AS aula_nombre, aul.codigo AS aula_codigo
                              FROM horarios h
                              JOIN asignacion_docente a ON a.id_asignacion = h.id_asignacion AND a.activo = 1
                              JOIN profesores p ON p.id_profesor = a.id_profesor
                              JOIN materias m ON m.id_materia = a.id_materia
                              LEFT JOIN grados g ON g.id_grado = COALESCE(h.id_grado, a.id_grado)
                              LEFT JOIN aulas aul ON aul.id_aula = COALESCE(h.id_aula, g.id_aula, :id_aula)
                              WHERE (h.id_aula = :id_aula OR g.id_aula = :id_aula)
                                AND h.activo = 1";

            $paramsC = [':id_aula' => $id_aula];
            if ($id_profesor !== null) {
                $sqlCualquiera .= " AND a.id_profesor = :id_profesor";
                $paramsC[':id_profesor'] = $id_profesor;
            }
            $sqlCualquiera .= " LIMIT 1";

            $stmtC = $this->conn->prepare($sqlCualquiera);
            $stmtC->execute($paramsC);
            $horario = $stmtC->fetch(PDO::FETCH_ASSOC);
        }

        if (!$horario) {
            return null;
        }

        $id_asignacion = (int)$horario['id_asignacion'];
        $id_horario = (int)$horario['id_horario'];

        // 2. Obtener la lección del día según el plan anual del profesor
        $planModel = new PlanAnual($this->conn);
        $leccion = $planModel->obtenerLeccionDelDia($id_asignacion, $fecha);

        $id_plan = $leccion ? (int)$leccion['id_plan'] : null;
        $id_unidad = $leccion ? (int)$leccion['id_unidad'] : null;
        $id_capacidad = $leccion ? (int)$leccion['id_capacidad'] : null;
        $id_tema = $leccion ? (int)$leccion['id_tema'] : null;

        // 3. Buscar si ya existe la clase diaria registrada hoy para esta asignación/horario
        $stmtClase = $this->conn->prepare("
            SELECT * FROM {$this->table_name}
            WHERE fecha = :fecha 
              AND id_asignacion = :id_asignacion
            ORDER BY id_clase DESC
            LIMIT 1
        ");
        $stmtClase->execute([':fecha' => $fecha, ':id_asignacion' => $id_asignacion]);
        $clase = $stmtClase->fetch(PDO::FETCH_ASSOC);

        if (!$clase) {
            // Crear el registro de la clase diaria
            $stmtInsert = $this->conn->prepare("
                INSERT INTO {$this->table_name} (
                    id_asignacion, id_horario, id_plan, id_unidad, id_capacidad, id_tema,
                    fecha, hora_inicio, hora_fin
                ) VALUES (
                    :id_asignacion, :id_horario, :id_plan, :id_unidad, :id_capacidad, :id_tema,
                    :fecha, :hora_inicio, :hora_fin
                )
            ");
            $stmtInsert->execute([
                ':id_asignacion' => $id_asignacion,
                ':id_horario' => $id_horario,
                ':id_plan' => $id_plan,
                ':id_unidad' => $id_unidad,
                ':id_capacidad' => $id_capacidad,
                ':id_tema' => $id_tema,
                ':fecha' => $fecha,
                ':hora_inicio' => $horario['hora_inicio'],
                ':hora_fin' => $horario['hora_fin']
            ]);
            $id_clase = (int)$this->conn->lastInsertId();

            // Asociar los indicadores de la lección a la clase diaria
            if ($leccion && !empty($leccion['indicadores'])) {
                $stmtIndIns = $this->conn->prepare("
                    INSERT INTO clase_diaria_indicadores (id_clase, id_indicador, cumplido)
                    VALUES (:id_clase, :id_indicador, 0)
                    ON CONFLICT (id_clase, id_indicador) DO NOTHING
                ");
                foreach ($leccion['indicadores'] as $ind) {
                    $stmtIndIns->execute([
                        ':id_clase' => $id_clase,
                        ':id_indicador' => $ind['id_indicador']
                    ]);
                }
            }
        } else {
            $id_clase = (int)$clase['id_clase'];
        }

        // 4. Retornar el detalle en vivo completo
        return $this->obtenerDetalleClase($id_clase, $id_profesor);
    }

    /**
     * Obtiene el estado completo de la clase en vivo:
     * Datos pedagógicos, lección, indicadores, profesor y lista de estudiantes presentes / ausentes
     */
    public function obtenerDetalleClase(int $id_clase, ?int $id_profesor = null): ?array {
        $sql = "SELECT c.*,
                       a.id_profesor, a.id_materia, a.id_grado, a.carga_horaria,
                       prof.nombre AS profesor_nombre, prof.apellido AS profesor_apellido,
                       prof.cedula_identidad AS profesor_ci, prof.user_id_global AS profesor_user_id,
                       m.nombre AS materia_nombre,
                       g.nombre AS grado_nombre,
                       h.dia_semana, h.hora_inicio AS horario_inicio, h.hora_fin AS horario_fin,
                       aul.id_aula, aul.nombre AS aula_nombre, aul.codigo AS aula_codigo,
                       pt.titulo AS tema_titulo, pt.contenido AS tema_contenido, pt.horas_catedra,
                       pc.descripcion AS capacidad_descripcion,
                       pu.nombre AS unidad_nombre
                FROM {$this->table_name} c
                JOIN asignacion_docente a ON a.id_asignacion = c.id_asignacion
                JOIN profesores prof ON prof.id_profesor = a.id_profesor
                JOIN materias m ON m.id_materia = a.id_materia
                LEFT JOIN grados g ON g.id_grado = a.id_grado
                LEFT JOIN horarios h ON h.id_horario = c.id_horario
                LEFT JOIN aulas aul ON aul.id_aula = COALESCE(h.id_aula, g.id_aula)
                LEFT JOIN plan_temas pt ON pt.id_tema = c.id_tema
                LEFT JOIN plan_capacidades pc ON pc.id_capacidad = c.id_capacidad
                LEFT JOIN plan_unidades pu ON pu.id_unidad = c.id_unidad
                WHERE c.id_clase = :id_clase";

        $params = [':id_clase' => $id_clase];
        if ($id_profesor !== null) {
            $sql .= " AND a.id_profesor = :id_profesor";
            $params[':id_profesor'] = $id_profesor;
        }
        $sql .= " LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $clase = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$clase) {
            return null;
        }

        $fecha = $clase['fecha'];
        $id_grado = (int)$clase['id_grado'];
        $id_profesor_actual = (int)$clase['id_profesor'];

        // 1. Obtener indicadores de la clase y su estado de cumplimiento
        $stmtInd = $this->conn->prepare("
            SELECT i.id_indicador, i.codigo, i.descripcion, i.orden,
                   COALESCE(cdi.cumplido, 0) AS cumplido,
                   cdi.observaciones
            FROM plan_indicadores i
            LEFT JOIN clase_diaria_indicadores cdi 
                   ON cdi.id_indicador = i.id_indicador AND cdi.id_clase = :id_clase
            WHERE i.id_tema = :id_tema
            ORDER BY i.orden ASC, i.id_indicador ASC
        ");
        $stmtInd->execute([
            ':id_clase' => $id_clase,
            ':id_tema' => $clase['id_tema']
        ]);
        $clase['indicadores'] = $stmtInd->fetchAll(PDO::FETCH_ASSOC);

        // 2. Procedimientos e Instrumentos
        if (!empty($clase['id_tema'])) {
            $stmtProc = $this->conn->prepare("
                SELECT pe.nombre 
                FROM plan_tema_procedimientos ptp
                JOIN procedimientos_evaluativos pe ON pe.id_procedimiento = ptp.id_procedimiento
                WHERE ptp.id_tema = :id_tema
            ");
            $stmtProc->execute([':id_tema' => $clase['id_tema']]);
            $clase['procedimientos'] = $stmtProc->fetchAll(PDO::FETCH_COLUMN);

            $stmtInst = $this->conn->prepare("
                SELECT ie.nombre 
                FROM plan_tema_instrumentos pti
                JOIN instrumentos_evaluativos ie ON ie.id_instrumento = pti.id_instrumento
                WHERE pti.id_tema = :id_tema
            ");
            $stmtInst->execute([':id_tema' => $clase['id_tema']]);
            $clase['instrumentos'] = $stmtInst->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $clase['procedimientos'] = [];
            $clase['instrumentos'] = [];
        }

        // 3. Asistencia del Profesor en la fecha de hoy
        $stmtProfAsist = $this->conn->prepare("
            SELECT * FROM asistencias_profesores
            WHERE id_profesor = :profesor AND fecha = :fecha AND activo = 1
            ORDER BY hora ASC
            LIMIT 1
        ");
        $stmtProfAsist->execute([':profesor' => $id_profesor_actual, ':fecha' => $fecha]);
        $asistProfesor = $stmtProfAsist->fetch(PDO::FETCH_ASSOC);

        $clase['profesor_asistencia'] = [
            'presente' => (bool)$asistProfesor,
            'hora' => $asistProfesor['hora'] ?? null,
            'estado' => $asistProfesor['estado'] ?? 'AUSENTE'
        ];

        // 4. Lista de todos los Estudiantes del grado y cruce con asistencias
        $stmtEst = $this->conn->prepare("
            SELECT e.id_estudiante, e.nombre, e.apellido, e.cedula_identidad,
                   e.user_id_global,
                   CONCAT(e.nombre, ' ', e.apellido) AS nombre_completo,
                   ae.hora AS hora_marcacion,
                   COALESCE(ae.estado, 'AUSENTE') AS estado_asistencia,
                   (CASE WHEN ae.id_estudiante IS NOT NULL THEN 1 ELSE 0 END) AS presente
            FROM estudiantes e
            LEFT JOIN asistencias_estudiantes ae 
                   ON ae.id_estudiante = e.id_estudiante 
                  AND ae.fecha = :fecha 
                  AND ae.activo = 1
            WHERE e.id_grado = :id_grado 
              AND e.activo = 1
            ORDER BY e.apellido ASC, e.nombre ASC
        ");
        $stmtEst->execute([':fecha' => $fecha, ':id_grado' => $id_grado]);
        $estudiantes = $stmtEst->fetchAll(PDO::FETCH_ASSOC);

        $presentes = [];
        $ausentes = [];

        foreach ($estudiantes as $est) {
            if ((int)$est['presente'] === 1 && $est['estado_asistencia'] !== 'AUSENTE') {
                $presentes[] = $est;
            } else {
                $ausentes[] = $est;
            }
        }

        $totalMatriculados = count($estudiantes);
        $totalPresentes = count($presentes);
        $totalAusentes = count($ausentes);
        $porcentaje = $totalMatriculados > 0 ? round(($totalPresentes / $totalMatriculados) * 100, 1) : 0;

        $clase['estudiantes_presentes'] = $presentes;
        $clase['estudiantes_ausentes'] = $ausentes;
        $clase['metricas'] = [
            'total_matriculados' => $totalMatriculados,
            'presentes' => $totalPresentes,
            'ausentes' => $totalAusentes,
            'porcentaje_asistencia' => $porcentaje
        ];

        return $clase;
    }

    /**
     * Marca o desmarca un indicador cumplido durante la clase
     */
    public function actualizarIndicador(int $id_clase, int $id_indicador, int $cumplido, ?string $obs = null): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO clase_diaria_indicadores (id_clase, id_indicador, cumplido, observaciones)
            VALUES (:id_clase, :id_indicador, :cumplido, :obs)
            ON CONFLICT (id_clase, id_indicador) 
            DO UPDATE SET cumplido = EXCLUDED.cumplido, observaciones = EXCLUDED.observaciones
        ");
        return $stmt->execute([
            ':id_clase' => $id_clase,
            ':id_indicador' => $id_indicador,
            ':cumplido' => $cumplido,
            ':obs' => $obs
        ]);
    }

    /**
     * Registra o actualiza la asistencia de un estudiante para la clase
     */
    public function registrarAsistenciaEstudiante(int $id_estudiante, string $fecha, string $hora, string $estado = 'PRESENTE'): bool {
        $stmtCheck = $this->conn->prepare("
            SELECT id_asistencia_estudiante FROM asistencias_estudiantes 
            WHERE id_estudiante = :id AND fecha = :fecha AND activo = 1
            LIMIT 1
        ");
        $stmtCheck->execute([':id' => $id_estudiante, ':fecha' => $fecha]);
        $idExistente = $stmtCheck->fetchColumn();

        if ($idExistente) {
            $stmtUpd = $this->conn->prepare("
                UPDATE asistencias_estudiantes
                SET hora = :hora, estado = :estado, fecha_registro = CURRENT_TIMESTAMP
                WHERE id_asistencia_estudiante = :id
            ");
            return $stmtUpd->execute([
                ':hora' => $hora,
                ':estado' => $estado,
                ':id' => $idExistente
            ]);
        }

        $stmtIns = $this->conn->prepare("
            INSERT INTO asistencias_estudiantes (id_estudiante, huella_id, fecha, hora, estado, activo)
            VALUES (:id, 0, :fecha, :hora, :estado, 1)
        ");
        return $stmtIns->execute([
            ':id' => $id_estudiante,
            ':fecha' => $fecha,
            ':hora' => $hora,
            ':estado' => $estado
        ]);
    }

    /**
     * Registra la asistencia del profesor para la clase
     */
    public function registrarAsistenciaProfesor(int $id_profesor, string $fecha, string $hora, string $estado = 'PRESENTE'): bool {
        $stmtCheck = $this->conn->prepare("
            SELECT id_asistencia_profesor FROM asistencias_profesores 
            WHERE id_profesor = :id AND fecha = :fecha AND activo = 1
            LIMIT 1
        ");
        $stmtCheck->execute([':id' => $id_profesor, ':fecha' => $fecha]);
        $idExistente = $stmtCheck->fetchColumn();

        if ($idExistente) {
            $stmtUpd = $this->conn->prepare("
                UPDATE asistencias_profesores
                SET hora = :hora, estado = :estado, fecha_registro = CURRENT_TIMESTAMP
                WHERE id_asistencia_profesor = :id
            ");
            return $stmtUpd->execute([
                ':hora' => $hora,
                ':estado' => $estado,
                ':id' => $idExistente
            ]);
        }

        $stmtIns = $this->conn->prepare("
            INSERT INTO asistencias_profesores (id_profesor, huella_id, fecha, hora, estado, activo)
            VALUES (:id, 0, :fecha, :hora, :estado, 1)
        ");
        return $stmtIns->execute([
            ':id' => $id_profesor,
            ':fecha' => $fecha,
            ':hora' => $hora,
            ':estado' => $estado
        ]);
    }
}
