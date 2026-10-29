<?php
declare(strict_types=1);

/**
 * Clase MarcoHorario
 * Gestiona los marcos horarios institucionales (apertura, inicio de clases,
 * tolerancia para tardanzas y cierre de jornada escolar) por día de la semana,
 * turno, nivel académico y especialidad de bachillerato (ej. jornada extendida BTI).
 */
class MarcoHorario {
    private PDO $conn;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /**
     * Mapea un índice numérico de día (0 = Domingo, 1 = Lunes, ...) a su representación en español
     */
    public static function normalizarDia(int|string|null $dia): array {
        if (is_numeric($dia)) {
            $map = [
                0 => ['con_tilde' => 'Domingo', 'sin_tilde' => 'Domingo'],
                1 => ['con_tilde' => 'Lunes', 'sin_tilde' => 'Lunes'],
                2 => ['con_tilde' => 'Martes', 'sin_tilde' => 'Martes'],
                3 => ['con_tilde' => 'Miércoles', 'sin_tilde' => 'Miercoles'],
                4 => ['con_tilde' => 'Jueves', 'sin_tilde' => 'Jueves'],
                5 => ['con_tilde' => 'Viernes', 'sin_tilde' => 'Viernes'],
                6 => ['con_tilde' => 'Sábado', 'sin_tilde' => 'Sabado'],
            ];
            $idx = ((int)$dia) % 7;
            return $map[$idx] ?? ['con_tilde' => 'Lunes', 'sin_tilde' => 'Lunes'];
        }

        $diaStr = trim((string)$dia);
        $sinTilde = str_replace(['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú'], ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'], $diaStr);
        $conTilde = match (strtolower($sinTilde)) {
            'miercoles' => 'Miércoles',
            'sabado' => 'Sábado',
            default => ucfirst(strtolower($diaStr))
        };

        return [
            'con_tilde' => $conTilde,
            'sin_tilde' => ucfirst(strtolower($sinTilde))
        ];
    }

    /**
     * Resuelve el marco horario aplicable para un grado específico según el día de la semana.
     * Utiliza resolución jerárquica:
     *   1. Coincidencia exacta: (Turno + Nivel + Bachillerato) -> ej. BTI
     *   2. Coincidencia por Bachillerato: (Turno + Bachillerato)
     *   3. Coincidencia por Nivel: (Turno + Nivel)
     *   4. Coincidencia general: (Turno)
     */
    public function resolverMarcoParaCurso(int $id_grado, ?string $dia_semana = null, ?string $hora_actual = null): ?array {
        // 1. Obtener atributos del grado
        $stmtG = $this->conn->prepare("
            SELECT g.id_grado, g.nombre AS grado_nombre, g.seccion, g.id_turno, g.id_nivel, g.id_bachillerato, g.id_aula,
                   t.nombre AS turno_nombre,
                   b.codigo AS bachillerato_codigo, b.nombre AS bachillerato_nombre
            FROM public.grados g
            LEFT JOIN public.turnos t ON g.id_turno = t.id_turno
            LEFT JOIN public.bachilleratos b ON g.id_bachillerato = b.id_bachillerato
            WHERE g.id_grado = :id_grado AND g.activo = 1
        ");
        $stmtG->execute([':id_grado' => $id_grado]);
        $grado = $stmtG->fetch(PDO::FETCH_ASSOC);

        if (!$grado || empty($grado['id_turno'])) {
            return null;
        }

        $idTurno = (int)$grado['id_turno'];
        $idNivel = !empty($grado['id_nivel']) ? (int)$grado['id_nivel'] : null;
        $idBachi = !empty($grado['id_bachillerato']) ? (int)$grado['id_bachillerato'] : null;

        $diaInfo = self::normalizarDia($dia_semana ?? (int)date('w'));
        $hora = $hora_actual ? substr($hora_actual, 0, 8) : date('H:i:s');

        // 2. Consulta jerárquica ponderada
        $sqlMarco = "
            SELECT m.id_marco, m.dia_semana, m.id_turno, m.id_nivel, m.id_bachillerato,
                   m.hora_apertura, m.hora_inicio, m.tolerancia_minutos, m.hora_cierre,
                   m.descripcion, m.activo,
                   t.nombre AS turno_nombre,
                   n.nombre AS nivel_nombre,
                   COALESCE(b.nombre, 'General') AS bachillerato_nombre,
                   COALESCE(b.codigo, 'GEN') AS bachillerato_codigo
            FROM public.marcos_horarios_institucionales m
            LEFT JOIN public.turnos t ON m.id_turno = t.id_turno
            LEFT JOIN public.niveles_academicos n ON m.id_nivel = n.id_nivel
            LEFT JOIN public.bachilleratos b ON m.id_bachillerato = b.id_bachillerato
            WHERE m.activo = 1
              AND (m.dia_semana = :dia1 OR m.dia_semana = :dia2)
              AND m.id_turno = :id_turno
              AND (m.id_nivel = :id_nivel OR m.id_nivel IS NULL)
              AND (m.id_bachillerato = :id_bachi OR m.id_bachillerato IS NULL)
            ORDER BY 
              (CASE 
                 WHEN m.id_bachillerato = :id_bachi AND m.id_nivel = :id_nivel THEN 4
                 WHEN m.id_bachillerato = :id_bachi THEN 3
                 WHEN m.id_nivel = :id_nivel THEN 2
                 ELSE 1 
               END) DESC
            LIMIT 1
        ";

        $stmtM = $this->conn->prepare($sqlMarco);
        $stmtM->execute([
            ':dia1' => $diaInfo['con_tilde'],
            ':dia2' => $diaInfo['sin_tilde'],
            ':id_turno' => $idTurno,
            ':id_nivel' => $idNivel,
            ':id_bachi' => $idBachi
        ]);
        $marco = $stmtM->fetch(PDO::FETCH_ASSOC);

        if (!$marco) {
            return null;
        }

        // 3. Cálculos dinámicos de ventana y estado
        $tolerancia = (int)$marco['tolerancia_minutos'];
        $horaInicioTs = strtotime($marco['hora_inicio']);
        $horaTardanzaLimite = date('H:i:s', $horaInicioTs + ($tolerancia * 60));

        $horaActualTs = strtotime($hora);
        $horaAperturaTs = strtotime($marco['hora_apertura']);
        $horaCierreTs = strtotime($marco['hora_cierre']);
        $horaTardanzaTs = strtotime($horaTardanzaLimite);

        $enVentana = ($horaActualTs >= $horaAperturaTs && $horaActualTs <= $horaCierreTs);

        $estadoAsistencia = 'FUERA_HORARIO';
        if ($horaActualTs < $horaAperturaTs) {
            $estadoAsistencia = 'PREVIO_APERTURA';
        } elseif ($horaActualTs <= $horaTardanzaTs) {
            $estadoAsistencia = 'PRESENTE';
        } elseif ($horaActualTs < $horaCierreTs) {
            $estadoAsistencia = 'TARDANZA';
        } else {
            $estadoAsistencia = 'JORNADA_CERRADA';
        }

        return array_merge($marco, [
            'hora_actual' => $hora,
            'hora_limite_tardanza' => $horaTardanzaLimite,
            'en_ventana_asistencia' => $enVentana,
            'estado_asistencia_sugerido' => $estadoAsistencia,
            'grado_info' => $grado
        ]);
    }

    /**
     * Resuelve el marco horario aplicable para un aula determinada
     */
    public function resolverMarcoParaAula(int $id_aula, ?string $dia_semana = null, ?string $hora_actual = null): ?array {
        $stmtA = $this->conn->prepare("
            SELECT id_grado 
            FROM public.grados 
            WHERE id_aula = :id_aula AND activo = 1 
            ORDER BY id_grado ASC 
            LIMIT 1
        ");
        $stmtA->execute([':id_aula' => $id_aula]);
        $idGrado = (int)($stmtA->fetchColumn() ?: 0);

        if ($idGrado <= 0) {
            return null;
        }

        return $this->resolverMarcoParaCurso($idGrado, $dia_semana, $hora_actual);
    }

    /**
     * Lista todos los marcos horarios registrados con filtros opcionales
     */
    public function listar(?int $id_turno = null, ?int $id_nivel = null, ?int $id_bachillerato = null, ?string $dia_semana = null): array {
        $where = ["m.activo = 1"];
        $params = [];

        if ($id_turno !== null && $id_turno > 0) {
            $where[] = "m.id_turno = :id_turno";
            $params[':id_turno'] = $id_turno;
        }
        if ($id_nivel !== null && $id_nivel > 0) {
            $where[] = "m.id_nivel = :id_nivel";
            $params[':id_nivel'] = $id_nivel;
        }
        if ($id_bachillerato !== null && $id_bachillerato > 0) {
            $where[] = "m.id_bachillerato = :id_bachi";
            $params[':id_bachi'] = $id_bachillerato;
        }
        if (!empty($dia_semana)) {
            $diaInfo = self::normalizarDia($dia_semana);
            $where[] = "(m.dia_semana = :dia1 OR m.dia_semana = :dia2)";
            $params[':dia1'] = $diaInfo['con_tilde'];
            $params[':dia2'] = $diaInfo['sin_tilde'];
        }

        $whereClause = implode(" AND ", $where);

        $sql = "
            SELECT 
                m.id_marco,
                m.dia_semana,
                m.id_turno,
                COALESCE(t.nombre, 'Sin turno') AS turno_nombre,
                m.id_nivel,
                COALESCE(n.nombre, 'Todos los niveles') AS nivel_nombre,
                m.id_bachillerato,
                COALESCE(b.nombre, 'Educación General (Todos)') AS bachillerato_nombre,
                COALESCE(b.codigo, 'GEN') AS bachillerato_codigo,
                m.hora_apertura,
                m.hora_inicio,
                m.tolerancia_minutos,
                m.hora_cierre,
                m.descripcion,
                m.activo,
                m.fecha_actualizacion
            FROM public.marcos_horarios_institucionales m
            LEFT JOIN public.turnos t ON m.id_turno = t.id_turno
            LEFT JOIN public.niveles_academicos n ON m.id_nivel = n.id_nivel
            LEFT JOIN public.bachilleratos b ON m.id_bachillerato = b.id_bachillerato
            WHERE {$whereClause}
            ORDER BY 
              CASE m.dia_semana 
                WHEN 'Lunes' THEN 1 
                WHEN 'Martes' THEN 2 
                WHEN 'Miercoles' THEN 3 
                WHEN 'Miércoles' THEN 3 
                WHEN 'Jueves' THEN 4 
                WHEN 'Viernes' THEN 5 
                WHEN 'Sabado' THEN 6 
                WHEN 'Sábado' THEN 6 
                ELSE 7 
              END,
              t.nombre ASC,
              COALESCE(b.codigo, '') ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda o actualiza un marco horario institucional
     */
    public function guardar(array $datos): int {
        $idMarco = (int)($datos['id_marco'] ?? 0);
        $diaSemana = trim((string)($datos['dia_semana'] ?? 'Lunes'));
        $diaInfo = self::normalizarDia($diaSemana);
        $diaSemana = $diaInfo['con_tilde'];

        $idTurno = (int)($datos['id_turno'] ?? 0);
        $idNivel = !empty($datos['id_nivel']) ? (int)$datos['id_nivel'] : null;
        $idBachi = !empty($datos['id_bachillerato']) ? (int)$datos['id_bachillerato'] : null;

        $horaApertura = trim((string)($datos['hora_apertura'] ?? '06:45:00'));
        $horaInicio = trim((string)($datos['hora_inicio'] ?? '07:00:00'));
        $tolerancia = (int)($datos['tolerancia_minutos'] ?? 15);
        $horaCierre = trim((string)($datos['hora_cierre'] ?? '12:00:00'));
        $descripcion = trim((string)($datos['descripcion'] ?? ''));

        if ($idTurno <= 0) {
            throw new InvalidArgumentException("El turno es obligatorio.");
        }
        if (strtotime($horaApertura) > strtotime($horaInicio)) {
            throw new InvalidArgumentException("La hora de apertura no puede ser posterior a la hora de inicio.");
        }
        if (strtotime($horaInicio) >= strtotime($horaCierre)) {
            throw new InvalidArgumentException("La hora de inicio debe ser anterior a la hora de cierre.");
        }

        if ($idMarco > 0) {
            $stmt = $this->conn->prepare("
                UPDATE public.marcos_horarios_institucionales
                SET dia_semana = :dia,
                    id_turno = :id_turno,
                    id_nivel = :id_nivel,
                    id_bachillerato = :id_bachi,
                    hora_apertura = :apertura,
                    hora_inicio = :inicio,
                    tolerancia_minutos = :tolerancia,
                    hora_cierre = :cierre,
                    descripcion = :desc,
                    fecha_actualizacion = NOW()
                WHERE id_marco = :id
            ");
            $stmt->execute([
                ':dia' => $diaSemana,
                ':id_turno' => $idTurno,
                ':id_nivel' => $idNivel,
                ':id_bachi' => $idBachi,
                ':apertura' => $horaApertura,
                ':inicio' => $horaInicio,
                ':tolerancia' => $tolerancia,
                ':cierre' => $horaCierre,
                ':desc' => $descripcion,
                ':id' => $idMarco
            ]);
            return $idMarco;
        }

        $stmt = $this->conn->prepare("
            INSERT INTO public.marcos_horarios_institucionales
                (dia_semana, id_turno, id_nivel, id_bachillerato, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion, activo)
            VALUES
                (:dia, :id_turno, :id_nivel, :id_bachi, :apertura, :inicio, :tolerancia, :cierre, :desc, 1)
            ON CONFLICT (dia_semana, id_turno, COALESCE(id_nivel, 0), COALESCE(id_bachillerato, 0))
            DO UPDATE SET
                hora_apertura = EXCLUDED.hora_apertura,
                hora_inicio = EXCLUDED.hora_inicio,
                tolerancia_minutos = EXCLUDED.tolerancia_minutos,
                hora_cierre = EXCLUDED.hora_cierre,
                descripcion = EXCLUDED.descripcion,
                activo = 1,
                fecha_actualizacion = NOW()
            RETURNING id_marco
        ");
        $stmt->execute([
            ':dia' => $diaSemana,
            ':id_turno' => $idTurno,
            ':id_nivel' => $idNivel,
            ':id_bachi' => $idBachi,
            ':apertura' => $horaApertura,
            ':inicio' => $horaInicio,
            ':tolerancia' => $tolerancia,
            ':cierre' => $horaCierre,
            ':desc' => $descripcion
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Desactiva lógicamente un marco horario institucional
     */
    public function desactivar(int $idMarco): bool {
        $stmt = $this->conn->prepare("UPDATE public.marcos_horarios_institucionales SET activo = 0 WHERE id_marco = :id");
        return $stmt->execute([':id' => $idMarco]);
    }
}
