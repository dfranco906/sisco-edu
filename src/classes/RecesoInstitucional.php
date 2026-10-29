<?php
declare(strict_types=1);

class RecesoInstitucional {
    private PDO $conn;
    private string $table_name = "public.recesos_institucionales";

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    /**
     * Normaliza el día de la semana para búsquedas uniformes
     */
    public static function normalizarDia(string|int $dia): array {
        $nombres = [
            0 => ['con_tilde' => 'Domingo', 'sin_tilde' => 'Domingo'],
            1 => ['con_tilde' => 'Lunes', 'sin_tilde' => 'Lunes'],
            2 => ['con_tilde' => 'Martes', 'sin_tilde' => 'Martes'],
            3 => ['con_tilde' => 'Miércoles', 'sin_tilde' => 'Miercoles'],
            4 => ['con_tilde' => 'Jueves', 'sin_tilde' => 'Jueves'],
            5 => ['con_tilde' => 'Viernes', 'sin_tilde' => 'Viernes'],
            6 => ['con_tilde' => 'Sábado', 'sin_tilde' => 'Sabado'],
        ];

        if (is_numeric($dia)) {
            $num = (int)$dia % 7;
            return $nombres[$num] ?? $nombres[1];
        }

        $diaLimpio = mb_strtolower(trim((string)$dia), 'UTF-8');
        foreach ($nombres as $info) {
            if ($diaLimpio === mb_strtolower($info['con_tilde'], 'UTF-8') ||
                $diaLimpio === mb_strtolower($info['sin_tilde'], 'UTF-8')) {
                return $info;
            }
        }

        return ['con_tilde' => 'Lunes', 'sin_tilde' => 'Lunes'];
    }

    /**
     * Lista todos los recesos institucionales con filtros opcionales
     */
    public function listar(?int $id_turno = null, ?int $id_nivel = null, ?int $id_bachillerato = null, ?string $dia_semana = null, ?int $id_grado = null): array {
        $where = ["r.activo = 1"];
        $params = [];

        if ($id_turno !== null && $id_turno > 0) {
            $where[] = "r.id_turno = :id_turno";
            $params[':id_turno'] = $id_turno;
        }
        if ($id_nivel !== null && $id_nivel > 0) {
            $where[] = "r.id_nivel = :id_nivel";
            $params[':id_nivel'] = $id_nivel;
        }
        if ($id_bachillerato !== null && $id_bachillerato > 0) {
            $where[] = "r.id_bachillerato = :id_bachi";
            $params[':id_bachi'] = $id_bachillerato;
        }
        if ($id_grado !== null && $id_grado > 0) {
            $where[] = "r.id_grado = :id_grado";
            $params[':id_grado'] = $id_grado;
        }
        if (!empty($dia_semana)) {
            $diaInfo = self::normalizarDia($dia_semana);
            $where[] = "(r.dia_semana = :dia1 OR r.dia_semana = :dia2 OR r.dia_semana = 'Todos')";
            $params[':dia1'] = $diaInfo['con_tilde'];
            $params[':dia2'] = $diaInfo['sin_tilde'];
        }

        $whereClause = implode(" AND ", $where);

        $sql = "
            SELECT 
                r.id_receso,
                r.nombre,
                r.dia_semana,
                r.id_turno,
                COALESCE(t.nombre, 'Sin turno') AS turno_nombre,
                r.id_nivel,
                COALESCE(n.nombre, 'Todos los niveles') AS nivel_nombre,
                r.id_bachillerato,
                COALESCE(b.nombre, 'Educación General (Todos)') AS bachillerato_nombre,
                COALESCE(b.codigo, 'GEN') AS bachillerato_codigo,
                r.id_grado,
                g.nombre AS grado_nombre,
                r.hora_inicio,
                r.hora_fin,
                r.descripcion,
                r.activo,
                r.fecha_actualizacion
            FROM {$this->table_name} r
            LEFT JOIN public.turnos t ON r.id_turno = t.id_turno
            LEFT JOIN public.niveles_academicos n ON r.id_nivel = n.id_nivel
            LEFT JOIN public.bachilleratos b ON r.id_bachillerato = b.id_bachillerato
            LEFT JOIN public.grados g ON r.id_grado = g.id_grado
            WHERE {$whereClause}
            ORDER BY 
              CASE r.dia_semana 
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
              r.hora_inicio ASC,
              COALESCE(g.nombre, '') ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna todos los recesos aplicables para un curso en un día determinado
     */
    public function obtenerRecesosParaCurso(int $id_grado, ?string $dia_semana = null): array {
        // 1. Obtener la información del grado
        $stmtG = $this->conn->prepare("
            SELECT id_grado, nombre, id_turno, id_nivel, id_bachillerato 
            FROM public.grados 
            WHERE id_grado = :id AND activo = 1
        ");
        $stmtG->execute([':id' => $id_grado]);
        $grado = $stmtG->fetch(PDO::FETCH_ASSOC);

        if (!$grado || empty($grado['id_turno'])) {
            return [];
        }

        $idTurno = (int)$grado['id_turno'];
        $idNivel = !empty($grado['id_nivel']) ? (int)$grado['id_nivel'] : null;
        $idBachi = !empty($grado['id_bachillerato']) ? (int)$grado['id_bachillerato'] : null;

        $diaInfo = self::normalizarDia($dia_semana ?? (int)date('w'));

        // 2. Consultar recesos aplicables (generales del nivel/bachillerato + específicos del curso)
        $sql = "
            SELECT 
                r.id_receso,
                r.nombre,
                r.dia_semana,
                r.hora_inicio,
                r.hora_fin,
                r.descripcion,
                r.id_grado,
                (CASE WHEN r.id_grado = :id_grado THEN 1 ELSE 0 END) AS es_especifico
            FROM {$this->table_name} r
            WHERE r.activo = 1
              AND (r.dia_semana = :dia1 OR r.dia_semana = :dia2 OR r.dia_semana = 'Todos')
              AND r.id_turno = :id_turno
              AND (
                r.id_grado = :id_grado
                OR (
                  r.id_grado IS NULL 
                  AND (r.id_bachillerato = :id_bachi OR r.id_bachillerato IS NULL)
                  AND (r.id_nivel = :id_nivel OR r.id_nivel IS NULL)
                )
              )
            ORDER BY r.hora_inicio ASC
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':id_grado' => $id_grado,
            ':dia1' => $diaInfo['con_tilde'],
            ':dia2' => $diaInfo['sin_tilde'],
            ':id_turno' => $idTurno,
            ':id_nivel' => $idNivel,
            ':id_bachi' => $idBachi
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Determina si un grado se encuentra en este exacto momento dentro de un receso determinista
     */
    public function obtenerRecesoActivoParaGrado(int $id_grado, ?string $dia_semana = null, ?string $hora_actual = null): ?array {
        $hora = $hora_actual ? substr($hora_actual, 0, 8) : date('H:i:s');
        $recesos = $this->obtenerRecesosParaCurso($id_grado, $dia_semana);

        foreach ($recesos as $r) {
            $hIni = substr($r['hora_inicio'], 0, 8);
            $hFin = substr($r['hora_fin'], 0, 8);

            if ($hora >= $hIni && $hora < $hFin) {
                $minutosRestantes = max(0, (int)ceil((strtotime($hFin) - strtotime($hora)) / 60));
                return array_merge($r, [
                    'en_receso' => true,
                    'hora_inicio_corta' => substr($hIni, 0, 5),
                    'hora_fin_corta' => substr($hFin, 0, 5),
                    'minutos_restantes' => $minutosRestantes,
                    'mensaje' => "En {$r['nombre']} (" . substr($hIni, 0, 5) . " a " . substr($hFin, 0, 5) . "). Faltan {$minutosRestantes} min para retomar clases."
                ]);
            }
        }

        return null;
    }

    /**
     * Guarda o actualiza un receso institucional
     */
    public function guardar(array $datos): int {
        $idReceso = (int)($datos['id_receso'] ?? 0);
        $nombre = trim((string)($datos['nombre'] ?? 'Receso Institucional'));
        if (empty($nombre)) {
            throw new InvalidArgumentException("El nombre del receso es obligatorio.");
        }

        $diaSemana = trim((string)($datos['dia_semana'] ?? 'Lunes'));
        if (strcasecmp($diaSemana, 'Todos') !== 0) {
            $diaInfo = self::normalizarDia($diaSemana);
            $diaSemana = $diaInfo['con_tilde'];
        }

        $idTurno = (int)($datos['id_turno'] ?? 0);
        $idNivel = !empty($datos['id_nivel']) ? (int)$datos['id_nivel'] : null;
        $idBachi = !empty($datos['id_bachillerato']) ? (int)$datos['id_bachillerato'] : null;
        $idGrado = !empty($datos['id_grado']) ? (int)$datos['id_grado'] : null;

        $horaInicio = trim((string)($datos['hora_inicio'] ?? '10:10:00'));
        $horaFin = trim((string)($datos['hora_fin'] ?? '10:50:00'));
        $descripcion = trim((string)($datos['descripcion'] ?? ''));

        if ($idTurno <= 0) {
            throw new InvalidArgumentException("El turno es obligatorio.");
        }
        if (strtotime($horaInicio) >= strtotime($horaFin)) {
            throw new InvalidArgumentException("La hora de inicio del receso debe ser anterior a la hora de fin.");
        }

        if ($idReceso > 0) {
            $stmt = $this->conn->prepare("
                UPDATE {$this->table_name}
                SET nombre = :nombre,
                    dia_semana = :dia,
                    id_turno = :id_turno,
                    id_nivel = :id_nivel,
                    id_bachillerato = :id_bachi,
                    id_grado = :id_grado,
                    hora_inicio = :inicio,
                    hora_fin = :fin,
                    descripcion = :desc,
                    fecha_actualizacion = NOW()
                WHERE id_receso = :id
            ");
            $stmt->execute([
                ':nombre' => $nombre,
                ':dia' => $diaSemana,
                ':id_turno' => $idTurno,
                ':id_nivel' => $idNivel,
                ':id_bachi' => $idBachi,
                ':id_grado' => $idGrado,
                ':inicio' => $horaInicio,
                ':fin' => $horaFin,
                ':desc' => $descripcion,
                ':id' => $idReceso
            ]);
            return $idReceso;
        }

        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table_name}
                (nombre, dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_inicio, hora_fin, descripcion, activo)
            VALUES
                (:nombre, :dia, :id_turno, :id_nivel, :id_bachi, :id_grado, :inicio, :fin, :desc, 1)
            RETURNING id_receso
        ");
        $stmt->execute([
            ':nombre' => $nombre,
            ':dia' => $diaSemana,
            ':id_turno' => $idTurno,
            ':id_nivel' => $idNivel,
            ':id_bachi' => $idBachi,
            ':id_grado' => $idGrado,
            ':inicio' => $horaInicio,
            ':fin' => $horaFin,
            ':desc' => $descripcion
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Desactiva lógicamente un receso institucional
     */
    public function desactivar(int $idReceso): bool {
        $stmt = $this->conn->prepare("UPDATE {$this->table_name} SET activo = 0 WHERE id_receso = :id");
        return $stmt->execute([':id' => $idReceso]);
    }
}
