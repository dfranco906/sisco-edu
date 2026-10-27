<?php
// src/classes/SyncBiometrica.php

class SyncBiometrica {
    private $conn;
    private $table_name = "sync_biometrica";

    public $id_sync;
    public $id_huella;
    public $id_aula;
    public $estado;
    public $intentos;
    public $mensaje;
    public $room_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear(): bool {
        $query = "INSERT INTO " . $this->table_name . " (id_huella, id_aula, room_id, estado, intentos, mensaje, fecha_creacion, fecha_actualizacion)
                  VALUES (:id_huella, :id_aula, :room_id, :estado, :intentos, :mensaje, NOW(), NOW())";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_huella", $this->id_huella, PDO::PARAM_INT);
        $stmt->bindParam(":id_aula", $this->id_aula, PDO::PARAM_INT);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":intentos", $this->intentos, PDO::PARAM_INT);
        $stmt->bindParam(":mensaje", $this->mensaje);

        return $stmt->execute();
    }

    public function leer() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id_sync DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar(): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET id_huella = :id_huella,
                      id_aula = :id_aula,
                      room_id = :room_id,
                      estado = :estado,
                      intentos = :intentos,
                      mensaje = :mensaje,
                      fecha_actualizacion = NOW()
                  WHERE id_sync = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":id_huella", $this->id_huella, PDO::PARAM_INT);
        $stmt->bindParam(":id_aula", $this->id_aula, PDO::PARAM_INT);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":intentos", $this->intentos, PDO::PARAM_INT);
        $stmt->bindParam(":mensaje", $this->mensaje);
        $stmt->bindParam(":id", $this->id_sync, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function confirmar(?string $mensaje = null): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET estado = 'CONFIRMADO',
                      mensaje = COALESCE(:mensaje, mensaje),
                      fecha_actualizacion = NOW()
                  WHERE id_sync = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_sync, PDO::PARAM_INT);
        $stmt->bindParam(":mensaje", $mensaje);

        return $stmt->execute();
    }

    public function marcarError(?string $mensaje = null): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET estado = 'ERROR',
                      intentos = intentos + 1,
                      mensaje = COALESCE(:mensaje, mensaje),
                      fecha_actualizacion = NOW()
                  WHERE id_sync = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_sync, PDO::PARAM_INT);
        $stmt->bindParam(":mensaje", $mensaje);

        return $stmt->execute();
    }

    public function marcarEnviado(int $idSync, ?string $mensaje = null): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET estado = 'ENVIADO',
                      intentos = intentos + 1,
                      mensaje = COALESCE(:mensaje, mensaje),
                      fecha_actualizacion = NOW()
                  WHERE id_sync = :id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':id' => $idSync, ':mensaje' => $mensaje]);
    }

    /**
     * Obtiene la cola de sincronización con datos relacionales completos
     * (persona, aula, huella, nodo LoRa).
     */
    public function obtenerCola(array $filtros = []): array {
        $whereClauses = ["h.activo = 1"];
        $params = [];

        // Filtro por estado
        $estado = strtoupper(trim((string)($filtros['estado'] ?? '')));
        if ($estado !== '' && $estado !== 'TODOS') {
            $whereClauses[] = "s.estado = :estado";
            $params[':estado'] = $estado;
        }

        // Filtro por aula específica
        if (!empty($filtros['id_aula'])) {
            $whereClauses[] = "s.id_aula = :id_aula";
            $params[':id_aula'] = (int)$filtros['id_aula'];
        }

        // Filtro por huella específica
        if (!empty($filtros['id_huella'])) {
            $whereClauses[] = "s.id_huella = :id_huella";
            $params[':id_huella'] = (int)$filtros['id_huella'];
        }

        // Búsqueda textual (persona, cédula, aula, user_id_global)
        $busqueda = trim((string)($filtros['busqueda'] ?? ''));
        if ($busqueda !== '') {
            $term = '%' . $busqueda . '%';
            $whereClauses[] = "(
                e.nombre ILIKE :b1 OR e.apellido ILIKE :b2 OR e.cedula_identidad ILIKE :b3 OR
                p.nombre ILIKE :b4 OR p.apellido ILIKE :b5 OR p.cedula_identidad ILIKE :b6 OR
                a.nombre ILIKE :b7 OR a.codigo ILIKE :b8 OR
                h.user_id_global ILIKE :b9
            )";
            $params[':b1'] = $term;
            $params[':b2'] = $term;
            $params[':b3'] = $term;
            $params[':b4'] = $term;
            $params[':b5'] = $term;
            $params[':b6'] = $term;
            $params[':b7'] = $term;
            $params[':b8'] = $term;
            $params[':b9'] = $term;
        }

        $whereSql = implode(' AND ', $whereClauses);
        $limit = isset($filtros['limit']) ? max(1, (int)$filtros['limit']) : 200;
        $offset = isset($filtros['offset']) ? max(0, (int)$filtros['offset']) : 0;

        $sql = "
            SELECT 
                s.id_sync,
                s.id_huella,
                s.id_aula,
                s.estado,
                s.intentos,
                s.mensaje,
                s.fecha_creacion,
                s.fecha_actualizacion,
                s.room_id,
                a.nombre AS aula_nombre,
                a.codigo AS aula_codigo,
                a.codigo_sala,
                n.lora_id,
                n.node_id,
                n.estado AS estado_nodo,
                h.user_id_global,
                h.crc32_hash,
                h.sensor_slot,
                h.formato,
                COALESCE(e.nombre || ' ' || e.apellido, p.nombre || ' ' || p.apellido, 'Sin asignar') AS nombre_completo,
                COALESCE(e.cedula_identidad, p.cedula_identidad, '') AS ci,
                CASE 
                    WHEN e.id_estudiante IS NOT NULL THEN 'Estudiante'
                    WHEN p.id_profesor IS NOT NULL THEN 'Profesor'
                    ELSE 'Desconocido'
                END AS tipo_persona,
                COALESCE(e.id_estudiante, p.id_profesor) AS id_persona
            FROM {$this->table_name} s
            INNER JOIN huellas_templates h ON h.id_huella = s.id_huella
            LEFT JOIN estudiantes e ON e.activo = 1 
                AND (e.id_estudiante = h.id_estudiante OR (h.id_estudiante IS NULL AND e.user_id_global = h.user_id_global))
            LEFT JOIN profesores p ON p.activo = 1 
                AND (p.id_profesor = h.id_profesor OR (h.id_profesor IS NULL AND p.user_id_global = h.user_id_global))
            LEFT JOIN aulas a ON a.id_aula = s.id_aula
            LEFT JOIN nodos_esp32 n ON n.id_aula = s.id_aula AND n.activo = 1 AND UPPER(n.tipo) = 'AULA'
            WHERE {$whereSql}
            ORDER BY 
                CASE s.estado 
                    WHEN 'PENDIENTE' THEN 1 
                    WHEN 'ERROR' THEN 2 
                    WHEN 'ENVIADO' THEN 3 
                    WHEN 'CONFIRMADO' THEN 4 
                    ELSE 5 
                END,
                s.fecha_creacion DESC,
                s.id_sync DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retorna contadores agregados para el panel de sincronización
     */
    public function obtenerEstadisticas(): array {
        $sql = "
            SELECT 
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN estado = 'PENDIENTE' THEN 1 ELSE 0 END), 0) AS pendientes,
                COALESCE(SUM(CASE WHEN estado = 'ENVIADO' THEN 1 ELSE 0 END), 0) AS enviados,
                COALESCE(SUM(CASE WHEN estado = 'CONFIRMADO' THEN 1 ELSE 0 END), 0) AS confirmados,
                COALESCE(SUM(CASE WHEN estado = 'ERROR' THEN 1 ELSE 0 END), 0) AS errores
            FROM {$this->table_name}
        ";

        $stmt = $this->conn->query($sql);
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : [];

        return [
            'total'       => (int)($row['total'] ?? 0),
            'pendientes'  => (int)($row['pendientes'] ?? 0),
            'enviados'    => (int)($row['enviados'] ?? 0),
            'confirmados' => (int)($row['confirmados'] ?? 0),
            'errores'     => (int)($row['errores'] ?? 0),
        ];
    }

    /**
     * Obtiene el detalle de un registro específico de sync_biometrica
     */
    public function obtenerDetalle(int $idSync): ?array {
        $cola = $this->obtenerCola(['limit' => 1]);
        $sql = "
            SELECT 
                s.id_sync,
                s.id_huella,
                s.id_aula,
                s.estado,
                s.intentos,
                s.mensaje,
                s.fecha_creacion,
                s.fecha_actualizacion,
                s.room_id,
                a.nombre AS aula_nombre,
                n.lora_id,
                h.user_id_global,
                h.crc32_hash,
                COALESCE(e.nombre || ' ' || e.apellido, p.nombre || ' ' || p.apellido, 'Sin asignar') AS nombre_completo,
                COALESCE(e.cedula_identidad, p.cedula_identidad, '') AS ci,
                CASE 
                    WHEN e.id_estudiante IS NOT NULL THEN 'Estudiante'
                    WHEN p.id_profesor IS NOT NULL THEN 'Profesor'
                    ELSE 'Desconocido'
                END AS tipo_persona
            FROM {$this->table_name} s
            INNER JOIN huellas_templates h ON h.id_huella = s.id_huella
            LEFT JOIN estudiantes e ON e.activo = 1 
                AND (e.id_estudiante = h.id_estudiante OR (h.id_estudiante IS NULL AND e.user_id_global = h.user_id_global))
            LEFT JOIN profesores p ON p.activo = 1 
                AND (p.id_profesor = h.id_profesor OR (h.id_profesor IS NULL AND p.user_id_global = h.user_id_global))
            LEFT JOIN aulas a ON a.id_aula = s.id_aula
            LEFT JOIN nodos_esp32 n ON n.id_aula = s.id_aula AND n.activo = 1 AND UPPER(n.tipo) = 'AULA'
            WHERE s.id_sync = :id
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $idSync]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Reintenta o reprograma un ítem individual marcándolo como PENDIENTE
     */
    public function reintentar(int $idSync, ?string $mensaje = 'Reintento programado manualmente'): ?array {
        $detalle = $this->obtenerDetalle($idSync);
        if (!$detalle) {
            return null;
        }

        $stmt = $this->conn->prepare("
            UPDATE {$this->table_name}
            SET estado = 'PENDIENTE',
                intentos = 0,
                mensaje = :mensaje,
                fecha_actualizacion = NOW()
            WHERE id_sync = :id
        ");
        $stmt->execute([':id' => $idSync, ':mensaje' => $mensaje]);

        $detalle['estado'] = 'PENDIENTE';
        $detalle['mensaje'] = $mensaje;
        return $detalle;
    }

    /**
     * Reintenta o reprograma múltiples ítems seleccionados
     */
    public function reintentarMultiples(array $idsSync, ?string $mensaje = 'Reintento grupal desde panel'): array {
        $validos = array_values(array_filter(array_map('intval', $idsSync), fn($v) => $v > 0));
        if (empty($validos)) {
            return [];
        }

        $inClause = implode(',', array_fill(0, count($validos), '?'));
        $stmt = $this->conn->prepare("
            UPDATE {$this->table_name}
            SET estado = 'PENDIENTE',
                intentos = 0,
                mensaje = ?,
                fecha_actualizacion = NOW()
            WHERE id_sync IN ({$inClause})
        ");

        $params = array_merge([$mensaje], $validos);
        $stmt->execute($params);

        // Retornar lista de ítems afectados
        $stmtSel = $this->conn->prepare("
            SELECT s.id_sync, s.id_huella, s.id_aula, a.nombre AS aula_nombre
            FROM {$this->table_name} s
            LEFT JOIN aulas a ON a.id_aula = s.id_aula
            WHERE s.id_sync IN ({$inClause})
        ");
        $stmtSel->execute($validos);

        return $stmtSel->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Obtiene todos los ítems pendientes o en error para procesar en lote
     */
    public function obtenerPendientesParaProcesar(?int $limit = 100): array {
        return $this->obtenerCola([
            'estado' => 'PENDIENTE',
            'limit'  => $limit ?? 100
        ]);
    }
}
