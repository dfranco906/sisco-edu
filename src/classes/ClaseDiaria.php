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
     * Detecta o inicializa la clase activa en un grado/aula/fecha/hora determinada
     */
    public function obtenerOCrearClaseActiva(?int $id_aula = null, ?string $timestamp = null, ?int $id_profesor = null, ?int $id_grado = null): ?array {
        // Regla estricta: si no se especifica ni grado, ni aula, ni profesor, no realizar consultas arbitrarias ni predeterminadas
        if ($id_grado === null && $id_aula === null && $id_profesor === null) {
            return null;
        }

        $fechaHora = $timestamp ? new DateTime($timestamp) : new DateTime();
        $fecha = $fechaHora->format('Y-m-d');
        $hora = $fechaHora->format('H:i:s');
        $diaInfo = self::obtenerDiaEspanol((int)$fechaHora->format('w'));

        // Si es profesor y no se indicó grado ni aula, resolver grado y aula de hoy
        if ($id_profesor !== null && !$id_grado && !$id_aula) {
            $sqlProfHoy = "SELECT COALESCE(h.id_grado, a.id_grado) AS id_grado, COALESCE(h.id_aula, g.id_aula) AS id_aula,
                                  h.hora_inicio, h.hora_fin
                           FROM horarios h
                           JOIN asignacion_docente a ON a.id_asignacion = h.id_asignacion AND a.activo = 1
                           LEFT JOIN grados g ON g.id_grado = COALESCE(h.id_grado, a.id_grado)
                           WHERE a.id_profesor = :id_profesor
                             AND (h.dia_semana = :dia1 OR h.dia_semana = :dia2)
                             AND h.activo = 1
                           ORDER BY h.hora_inicio ASC";
            $stmtPH = $this->conn->prepare($sqlProfHoy);
            $stmtPH->execute([
                ':id_profesor' => $id_profesor,
                ':dia1' => $diaInfo['con_tilde'],
                ':dia2' => $diaInfo['sin_tilde']
            ]);
            $filasProf = $stmtPH->fetchAll(PDO::FETCH_ASSOC);
            if (empty($filasProf)) {
                return null;
            }

            // Prioridad 1: Clase activa en el minuto actual
            $filaSeleccionada = null;
            foreach ($filasProf as $fp) {
                $iniP = substr($fp['hora_inicio'], 0, 8);
                $finP = substr($fp['hora_fin'], 0, 8);
                if ($hora >= $iniP && $hora < $finP) {
                    $filaSeleccionada = $fp;
                    break;
                }
            }

            // Prioridad 2: Si está en receso o antes de clases, la siguiente próxima
            if (!$filaSeleccionada) {
                foreach ($filasProf as $fp) {
                    $iniP = substr($fp['hora_inicio'], 0, 8);
                    if ($hora < $iniP) {
                        $filaSeleccionada = $fp;
                        break;
                    }
                }
            }

            // Prioridad 3: La última del día
            if (!$filaSeleccionada) {
                $filaSeleccionada = end($filasProf);
            }

            $id_grado = (int)$filaSeleccionada['id_grado'] ?: null;
            $id_aula = (int)$filaSeleccionada['id_aula'] ?: null;
        }

        // 1. Obtener todos los horarios programados para este grado/aula/profesor hoy ordenados cronológicamente
        $sqlHorario = "SELECT h.*, 
                              a.id_profesor, a.id_materia, a.id_grado, a.carga_horaria,
                              p.nombre AS profesor_nombre, p.apellido AS profesor_apellido, p.cedula_identidad AS profesor_ci,
                              p.user_id_global AS profesor_user_id,
                              m.nombre AS materia_nombre,
                              g.nombre AS grado_nombre,
                              aul.id_aula, aul.nombre AS aula_nombre, aul.codigo AS aula_codigo
                       FROM horarios h
                       JOIN asignacion_docente a ON a.id_asignacion = h.id_asignacion AND a.activo = 1
                       JOIN profesores p ON p.id_profesor = a.id_profesor
                       JOIN materias m ON m.id_materia = a.id_materia
                       LEFT JOIN grados g ON g.id_grado = COALESCE(h.id_grado, a.id_grado)
                       LEFT JOIN aulas aul ON aul.id_aula = COALESCE(h.id_aula, g.id_aula)
                       WHERE (h.dia_semana = :dia1 OR h.dia_semana = :dia2)
                         AND h.activo = 1";

        $paramsH = [
            ':dia1' => $diaInfo['con_tilde'],
            ':dia2' => $diaInfo['sin_tilde']
        ];

        if ($id_grado !== null && $id_grado > 0) {
            $sqlHorario .= " AND COALESCE(h.id_grado, a.id_grado) = :id_grado";
            $paramsH[':id_grado'] = $id_grado;
        } elseif ($id_aula !== null && $id_aula > 0) {
            $sqlHorario .= " AND (h.id_aula = :id_aula OR g.id_aula = :id_aula)";
            $paramsH[':id_aula'] = $id_aula;
        }

        if ($id_profesor !== null) {
            $sqlHorario .= " AND a.id_profesor = :id_profesor";
            $paramsH[':id_profesor'] = $id_profesor;
        }

        $sqlHorario .= " ORDER BY h.hora_inicio ASC";

        $stmtH = $this->conn->prepare($sqlHorario);
        $stmtH->execute($paramsH);
        $horariosDia = $stmtH->fetchAll(PDO::FETCH_ASSOC);

        if (empty($horariosDia)) {
            return null;
        }

        // 2. Determinar si hay clase activa o si nos encontramos en receso/pausa/previo
        $horarioActivo = null;
        $materiaAnterior = null;
        $materiaSiguiente = null;

        foreach ($horariosDia as $hd) {
            $hIni = substr($hd['hora_inicio'], 0, 8);
            $hFin = substr($hd['hora_fin'], 0, 8);

            if ($hora >= $hIni && $hora < $hFin) {
                $horarioActivo = $hd;
                break;
            }

            if ($hora >= $hFin) {
                $materiaAnterior = $hd;
            } elseif ($hora < $hIni && $materiaSiguiente === null) {
                $materiaSiguiente = $hd;
            }
        }

        // Si no hay clase activa en este exacto segundo, gestionar estados intersticiales (Receso / Previo / Finalizado)
        if (!$horarioActivo) {
            if ($materiaAnterior !== null && $materiaSiguiente !== null) {
                // Entre dos clases: EN RECESO / PAUSA INSTITUCIONAL
                $minutosRestantes = max(0, (int)ceil((strtotime($materiaSiguiente['hora_inicio']) - strtotime($hora)) / 60));
                return [
                    'id_clase' => 0,
                    'en_receso' => true,
                    'estado_tiempo' => 'EN_RECESO',
                    'minutos_restantes' => $minutosRestantes,
                    'hora_inicio_proxima' => substr($materiaSiguiente['hora_inicio'], 0, 5),
                    'materia_anterior' => [
                        'materia_nombre' => $materiaAnterior['materia_nombre'],
                        'profesor_nombre' => $materiaAnterior['profesor_nombre'] . ' ' . $materiaAnterior['profesor_apellido'],
                        'hora_fin' => substr($materiaAnterior['hora_fin'], 0, 5)
                    ],
                    'materia_siguiente' => [
                        'materia_nombre' => $materiaSiguiente['materia_nombre'],
                        'profesor_nombre' => $materiaSiguiente['profesor_nombre'] . ' ' . $materiaSiguiente['profesor_apellido'],
                        'hora_inicio' => substr($materiaSiguiente['hora_inicio'], 0, 5)
                    ],
                    'grado_nombre' => $materiaSiguiente['grado_nombre'] ?? $materiaAnterior['grado_nombre'],
                    'aula_nombre' => $materiaSiguiente['aula_nombre'] ?? $materiaAnterior['aula_nombre'],
                    'mensaje' => "En receso o pausa institucional. La siguiente materia ({$materiaSiguiente['materia_nombre']}) inicia a las " . substr($materiaSiguiente['hora_inicio'], 0, 5) . " (faltan {$minutosRestantes} min)."
                ];
            }

            if ($materiaAnterior === null && $materiaSiguiente !== null) {
                // Antes del inicio de la primera clase del día
                $minutosRestantes = max(0, (int)ceil((strtotime($materiaSiguiente['hora_inicio']) - strtotime($hora)) / 60));
                return [
                    'id_clase' => 0,
                    'en_receso' => false,
                    'estado_tiempo' => 'PREVIO_CLASES',
                    'minutos_restantes' => $minutosRestantes,
                    'hora_inicio_proxima' => substr($materiaSiguiente['hora_inicio'], 0, 5),
                    'materia_siguiente' => [
                        'materia_nombre' => $materiaSiguiente['materia_nombre'],
                        'profesor_nombre' => $materiaSiguiente['profesor_nombre'] . ' ' . $materiaSiguiente['profesor_apellido'],
                        'hora_inicio' => substr($materiaSiguiente['hora_inicio'], 0, 5)
                    ],
                    'grado_nombre' => $materiaSiguiente['grado_nombre'],
                    'aula_nombre' => $materiaSiguiente['aula_nombre'],
                    'mensaje' => "Jornada previa. La primera clase ({$materiaSiguiente['materia_nombre']}) inicia a las " . substr($materiaSiguiente['hora_inicio'], 0, 5) . " (faltan {$minutosRestantes} min)."
                ];
            }

            if ($materiaAnterior !== null && $materiaSiguiente === null) {
                // Clases del día concluidas
                return [
                    'id_clase' => 0,
                    'en_receso' => false,
                    'estado_tiempo' => 'JORNADA_FINALIZADA',
                    'materia_anterior' => [
                        'materia_nombre' => $materiaAnterior['materia_nombre'],
                        'profesor_nombre' => $materiaAnterior['profesor_nombre'] . ' ' . $materiaAnterior['profesor_apellido'],
                        'hora_fin' => substr($materiaAnterior['hora_fin'], 0, 5)
                    ],
                    'grado_nombre' => $materiaAnterior['grado_nombre'],
                    'aula_nombre' => $materiaAnterior['aula_nombre'],
                    'mensaje' => "Las clases programadas para este grado ya han finalizado por el día de hoy (última materia finalizó a las " . substr($materiaAnterior['hora_fin'], 0, 5) . ")."
                ];
            }

            return null;
        }

        $horario = $horarioActivo;
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
            ORDER BY COALESCE(hora_marcacion, hora) ASC
            LIMIT 1
        ");
        $stmtProfAsist->execute([':profesor' => $id_profesor_actual, ':fecha' => $fecha]);
        $asistProfesor = $stmtProfAsist->fetch(PDO::FETCH_ASSOC);

        $clase['profesor_asistencia'] = [
            'presente' => (bool)$asistProfesor && ($asistProfesor['estado'] ?? '') !== 'AUSENTE',
            'hora' => $asistProfesor['hora_marcacion'] ?? ($asistProfesor['hora'] ?? null),
            'estado' => $asistProfesor['estado'] ?? 'AUSENTE',
            'tipo_registro' => $asistProfesor['tipo_registro'] ?? null,
            'nodo_origen' => $asistProfesor['nodo_origen'] ?? null
        ];

        // 4. Lista de todos los Estudiantes del grado y cruce con asistencias
        $stmtEst = $this->conn->prepare("
            SELECT e.id_estudiante, e.nombre, e.apellido, e.cedula_identidad,
                   e.user_id_global, e.huella_id,
                   CONCAT(e.nombre, ' ', e.apellido) AS nombre_completo,
                   COALESCE(ae.hora_marcacion, ae.hora) AS hora_marcacion,
                   ae.id_asistencia_estudiante,
                   COALESCE(ae.estado, 'PENDIENTE') AS estado_asistencia,
                   ae.tipo_registro,
                   ae.nodo_origen,
                   (CASE WHEN ae.id_asistencia_estudiante IS NOT NULL AND UPPER(ae.estado) = 'PRESENTE' THEN 1 ELSE 0 END) AS presente
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
            $estadoUpper = strtoupper($est['estado_asistencia'] ?? 'PENDIENTE');
            if ((int)$est['presente'] === 1 && $estadoUpper === 'PRESENTE') {
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
     * Registra o actualiza la asistencia de un estudiante con control estricto anti-duplicación por día.
     * Si ya marcó asistencia hoy, NO genera doble escritura en la base de datos y preserva su hora original.
     *
     * @return array [
     *     'exito' => bool,
     *     'duplicado' => bool,
     *     'actualizado' => bool,
     *     'id' => int|null,
     *     'hora' => string,
     *     'estado' => string,
     *     'mensaje' => string
     * ]
     */
    public function registrarAsistenciaEstudiante(
        int $id_estudiante,
        string $fecha,
        string $hora,
        string $estado = 'PRESENTE',
        string $tipo_registro = 'BIOMETRICO',
        ?string $nodo_origen = null,
        ?int $id_horario = null,
        bool $forzar_actualizacion = false
    ): array {
        // Guard clause: Validación inicial de parámetros
        if ($id_estudiante <= 0 || empty($fecha)) {
            return [
                'exito' => false,
                'duplicado' => false,
                'actualizado' => false,
                'id' => null,
                'hora' => $hora,
                'estado' => $estado,
                'mensaje' => 'Identificador de estudiante o fecha no válida'
            ];
        }

        // 1. Control estricto anti-duplicación: consultar marcación activa previa del día
        $stmtCheck = $this->conn->prepare("
            SELECT id_asistencia_estudiante, hora, hora_marcacion, estado, tipo_registro, nodo_origen
            FROM asistencias_estudiantes 
            WHERE id_estudiante = :id AND fecha = :fecha AND activo = 1
            LIMIT 1
        ");
        $stmtCheck->execute([':id' => $id_estudiante, ':fecha' => $fecha]);
        $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            $horaRegistrada = $existente['hora_marcacion'] ?: ($existente['hora'] ?: $hora);
            $idAsistencia = (int)$existente['id_asistencia_estudiante'];

            // Si la asistencia previa fue registrada mediante huella dactilar física, es INMUTABLE ante modificaciones forzadas
            if ($forzar_actualizacion && $existente['tipo_registro'] === 'BIOMETRICO' && $existente['estado'] !== 'AUSENTE') {
                return [
                    'exito' => false,
                    'duplicado' => true,
                    'actualizado' => false,
                    'id' => $idAsistencia,
                    'hora' => $horaRegistrada,
                    'estado' => $existente['estado'],
                    'mensaje' => "La asistencia del estudiante fue registrada por huella dactilar y es inmutable. No se permite modificación."
                ];
            }

            // Si es una marcación biométrica o ya posee el mismo estado,
            // CERO DOBLE ESCRITURA: se protege la hora de llegada y no se modifica la BD
            if (!$forzar_actualizacion || $existente['estado'] === $estado) {
                return [
                    'exito' => true,
                    'duplicado' => true,
                    'actualizado' => false,
                    'id' => $idAsistencia,
                    'hora' => $horaRegistrada,
                    'estado' => $existente['estado'],
                    'mensaje' => "El estudiante ya cuenta con asistencia registrada hoy a las {$horaRegistrada} ({$existente['estado']}). No se generó duplicación."
                ];
            }

            // Si es una acción manual explícita (e.g. Docente o Coordinador justifica o rectifica estado)
            $stmtUpd = $this->conn->prepare("
                UPDATE asistencias_estudiantes
                SET estado = :estado,
                    tipo_registro = :tipo_registro,
                    nodo_origen = COALESCE(:nodo_origen, nodo_origen),
                    fecha_registro = CURRENT_TIMESTAMP
                WHERE id_asistencia_estudiante = :id
            ");
            $updOk = $stmtUpd->execute([
                ':estado' => $estado,
                ':tipo_registro' => $tipo_registro,
                ':nodo_origen' => $nodo_origen,
                ':id' => $idAsistencia
            ]);

            return [
                'exito' => $updOk,
                'duplicado' => false,
                'actualizado' => true,
                'id' => $idAsistencia,
                'hora' => $horaRegistrada,
                'estado' => $estado,
                'mensaje' => "Asistencia del estudiante rectificada a {$estado}."
            ];
        }

        // 2. Obtener huella_id del estudiante para enlace biométrico
        $stmtH = $this->conn->prepare("SELECT huella_id FROM estudiantes WHERE id_estudiante = :id LIMIT 1");
        $stmtH->execute([':id' => $id_estudiante]);
        $huellaId = (int)$stmtH->fetchColumn() ?: 0;

        // 3. Inserción protegida de asistencia del estudiante
        $stmtIns = $this->conn->prepare("
            INSERT INTO asistencias_estudiantes (
                id_estudiante, id_horario, huella_id, fecha, hora, hora_marcacion, estado, tipo_registro, nodo_origen, activo, fecha_registro
            ) VALUES (
                :id, :id_horario, :huella_id, :fecha, :hora, :hora_marcacion, :estado, :tipo_registro, :nodo_origen, 1, CURRENT_TIMESTAMP
            )
        ");

        $insertOk = $stmtIns->execute([
            ':id' => $id_estudiante,
            ':id_horario' => $id_horario,
            ':huella_id' => $huellaId,
            ':fecha' => $fecha,
            ':hora' => $hora,
            ':hora_marcacion' => $hora,
            ':estado' => $estado,
            ':tipo_registro' => $tipo_registro,
            ':nodo_origen' => $nodo_origen
        ]);

        $nuevoId = (int)$this->conn->lastInsertId();

        return [
            'exito' => $insertOk,
            'duplicado' => false,
            'actualizado' => false,
            'id' => $nuevoId,
            'hora' => $hora,
            'estado' => $estado,
            'mensaje' => "Asistencia registrada exitosamente a las {$hora}."
        ];
    }

    /**
     * Registra o actualiza la asistencia del profesor con control estricto anti-duplicación por día.
     *
     * @return array [
     *     'exito' => bool,
     *     'duplicado' => bool,
     *     'actualizado' => bool,
     *     'id' => int|null,
     *     'hora' => string,
     *     'estado' => string,
     *     'mensaje' => string
     * ]
     */
    public function registrarAsistenciaProfesor(
        int $id_profesor,
        string $fecha,
        string $hora,
        string $estado = 'PRESENTE',
        string $tipo_registro = 'BIOMETRICO',
        ?string $nodo_origen = null,
        ?int $id_horario = null,
        bool $forzar_actualizacion = false
    ): array {
        if ($id_profesor <= 0 || empty($fecha)) {
            return [
                'exito' => false,
                'duplicado' => false,
                'actualizado' => false,
                'id' => null,
                'hora' => $hora,
                'estado' => $estado,
                'mensaje' => 'Identificador de profesor o fecha no válida'
            ];
        }

        // 1. Control estricto anti-duplicación: verificar marcación activa del día
        $stmtCheck = $this->conn->prepare("
            SELECT id_asistencia_profesor, hora, hora_marcacion, estado, tipo_registro, nodo_origen
            FROM asistencias_profesores 
            WHERE id_profesor = :id AND fecha = :fecha AND activo = 1
            LIMIT 1
        ");
        $stmtCheck->execute([':id' => $id_profesor, ':fecha' => $fecha]);
        $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            $horaRegistrada = $existente['hora_marcacion'] ?: ($existente['hora'] ?: $hora);
            $idAsistencia = (int)$existente['id_asistencia_profesor'];

            // Si la asistencia previa fue registrada mediante huella dactilar física, es INMUTABLE ante modificaciones forzadas
            if ($forzar_actualizacion && $existente['tipo_registro'] === 'BIOMETRICO' && $existente['estado'] !== 'AUSENTE') {
                return [
                    'exito' => false,
                    'duplicado' => true,
                    'actualizado' => false,
                    'id' => $idAsistencia,
                    'hora' => $horaRegistrada,
                    'estado' => $existente['estado'],
                    'mensaje' => "La asistencia del profesor fue registrada por huella dactilar y es inmutable. No se permite modificación."
                ];
            }

            if (!$forzar_actualizacion || $existente['estado'] === $estado) {
                return [
                    'exito' => true,
                    'duplicado' => true,
                    'actualizado' => false,
                    'id' => $idAsistencia,
                    'hora' => $horaRegistrada,
                    'estado' => $existente['estado'],
                    'mensaje' => "El profesor ya cuenta con asistencia registrada hoy a las {$horaRegistrada} ({$existente['estado']}). No se generó duplicación."
                ];
            }

            $stmtUpd = $this->conn->prepare("
                UPDATE asistencias_profesores
                SET estado = :estado,
                    tipo_registro = :tipo_registro,
                    nodo_origen = COALESCE(:nodo_origen, nodo_origen),
                    fecha_registro = CURRENT_TIMESTAMP
                WHERE id_asistencia_profesor = :id
            ");
            $updOk = $stmtUpd->execute([
                ':estado' => $estado,
                ':tipo_registro' => $tipo_registro,
                ':nodo_origen' => $nodo_origen,
                ':id' => $idAsistencia
            ]);

            return [
                'exito' => $updOk,
                'duplicado' => false,
                'actualizado' => true,
                'id' => $idAsistencia,
                'hora' => $horaRegistrada,
                'estado' => $estado,
                'mensaje' => "Asistencia del profesor rectificada a {$estado}."
            ];
        }

        // 2. Huella del profesor
        $stmtH = $this->conn->prepare("SELECT huella_id FROM profesores WHERE id_profesor = :id LIMIT 1");
        $stmtH->execute([':id' => $id_profesor]);
        $huellaId = (int)$stmtH->fetchColumn() ?: 0;

        // 3. Inserción protegida de asistencia del profesor
        $stmtIns = $this->conn->prepare("
            INSERT INTO asistencias_profesores (
                id_profesor, id_horario, huella_id, fecha, hora, hora_marcacion, estado, tipo_registro, nodo_origen, activo, fecha_registro
            ) VALUES (
                :id, :id_horario, :huella_id, :fecha, :hora, :hora_marcacion, :estado, :tipo_registro, :nodo_origen, 1, CURRENT_TIMESTAMP
            )
        ");

        $insertOk = $stmtIns->execute([
            ':id' => $id_profesor,
            ':id_horario' => $id_horario,
            ':huella_id' => $huellaId,
            ':fecha' => $fecha,
            ':hora' => $hora,
            ':hora_marcacion' => $hora,
            ':estado' => $estado,
            ':tipo_registro' => $tipo_registro,
            ':nodo_origen' => $nodo_origen
        ]);

        $nuevoId = (int)$this->conn->lastInsertId();

        return [
            'exito' => $insertOk,
            'duplicado' => false,
            'actualizado' => false,
            'id' => $nuevoId,
            'hora' => $hora,
            'estado' => $estado,
            'mensaje' => "Asistencia del docente registrada exitosamente a las {$hora}."
        ];
    }
}
