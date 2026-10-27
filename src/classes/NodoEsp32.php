<?php
// src/classes/NodoEsp32.php

class NodoEsp32 {
    private $conn;
    private $table_name = "nodos_esp32";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Lista todos los nodos activos con información detallada del aula vinculada.
     */
    public function listarTodos(): array {
        $sql = "SELECT n.id_nodo, n.node_id, n.id_aula, n.tipo, n.estado,
                       n.lora_id, n.lora_red_id, n.room_id, n.ultimo_heartbeat,
                       n.activo, n.fecha_creacion,
                       a.nombre AS nombre_aula, COALESCE(a.codigo, a.codigo_sala) AS codigo_sala,
                       COALESCE(a.bloque, 'Bloque A') AS bloque, a.ubicacion, a.piso
                FROM {$this->table_name} n
                LEFT JOIN aulas a ON a.id_aula = n.id_aula
                WHERE n.activo = 1
                ORDER BY n.lora_id ASC NULLS LAST, n.id_nodo ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un nodo por su ID primario.
     */
    public function obtenerPorId(int $id_nodo): ?array {
        $sql = "SELECT n.*, a.nombre AS nombre_aula, a.codigo_sala
                FROM {$this->table_name} n
                LEFT JOIN aulas a ON a.id_aula = n.id_aula
                WHERE n.id_nodo = :id_nodo AND n.activo = 1
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_nodo' => $id_nodo]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Obtiene el nodo LoRa activo vinculado a un aula específica.
     */
    public function obtenerPorAula(int $id_aula): ?array {
        $sql = "SELECT n.*, a.nombre AS nombre_aula, COALESCE(a.codigo, a.codigo_sala) AS codigo_sala
                FROM {$this->table_name} n
                LEFT JOIN aulas a ON a.id_aula = n.id_aula
                WHERE n.id_aula = :id_aula AND n.activo = 1
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id_aula' => $id_aula]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Resetea / vacía las huellas registradas en un aula tanto a nivel de hardware (vía LoRa)
     * como a nivel de base de datos (PostgreSQL).
     */
    public function resetearAulaHuellas(int $idAula, bool $forzar = false): array {
        $nodo = $this->obtenerPorAula($idAula);
        $nombreAula = $nodo['nombre_aula'] ?? "Aula #{$idAula}";
        $loraId = !empty($nodo['lora_id']) ? (int)$nodo['lora_id'] : null;

        $loraOk = false;
        $mensajeLora = '';

        if ($loraId && function_exists('solicitarVaciadoAulaGateway')) {
            $respLora = solicitarVaciadoAulaGateway($idAula, $loraId);
            $loraOk = $respLora['ok'];
            $mensajeLora = $respLora['message'];
        } else {
            $mensajeLora = "No hay nodo LoRa asignado a esta aula.";
        }

        if (!$loraOk && !$forzar) {
            return [
                'ok'      => false,
                'message' => "Fallo de comunicación LoRa con {$nombreAula}: {$mensajeLora}. Si el aula está apagada o fuera de servicio, puede usar la opción 'Forzar vaciado en BD'.",
                'lora_ok' => false
            ];
        }

        // Ejecutar limpieza en Base de Datos de forma atómica
        $this->conn->beginTransaction();
        try {
            // 1. Desactivar mapeos biométricos de esa aula
            $stmt1 = $this->conn->prepare("UPDATE biometric_mapping SET activo = 0 WHERE id_aula = :id_aula");
            $stmt1->execute([':id_aula' => $idAula]);
            $slotsAfectados = $stmt1->rowCount();

            // 2. Eliminar registros de confirmación en aula_huellas_sync
            $stmt2 = $this->conn->prepare("DELETE FROM aula_huellas_sync WHERE id_aula = :id_aula");
            $stmt2->execute([':id_aula' => $idAula]);

            // 3. Eliminar registros de sincronización pendientes o erróneos para este aula
            $stmt3 = $this->conn->prepare("DELETE FROM sync_biometrica WHERE id_aula = :id_aula");
            $stmt3->execute([':id_aula' => $idAula]);
            $syncsBorradas = $stmt3->rowCount();

            $this->conn->commit();

            return [
                'ok'               => true,
                'message'          => "Aula '{$nombreAula}' reseteada correctamente. " . ($loraOk ? "Sensor DY50 vaciado por LoRa." : "Limpieza realizada en BD (forzada)."),
                'id_aula'          => $idAula,
                'lora_id'          => $loraId,
                'lora_ok'          => $loraOk,
                'slots_afectados'  => $slotsAfectados,
                'syncs_eliminadas' => $syncsBorradas
            ];
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Sugiere el siguiente ID LoRa correlativo disponible (ej. 101, 102...).
     */
    public function obtenerSiguienteLoraId(int $lora_red_id = 18): int {
        $sql = "SELECT COALESCE(MAX(lora_id), 100) + 1 AS siguiente
                FROM {$this->table_name}
                WHERE lora_red_id = :lora_red_id AND activo = 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':lora_red_id' => $lora_red_id]);
        $resultado = (int)$stmt->fetchColumn();
        return max(101, $resultado);
    }

    /**
     * Devuelve las aulas activas que no tienen un nodo asignado actualmente.
     */
    public function obtenerAulasDisponibles(?int $excluirIdNodo = null): array {
        $sql = "SELECT 
                    a.id_aula, 
                    a.nombre, 
                    COALESCE(a.codigo, a.codigo_sala) AS codigo,
                    COALESCE(a.codigo, a.codigo_sala) AS codigo_sala,
                    COALESCE(a.bloque, 'Bloque A') AS bloque, 
                    a.piso, 
                    a.ubicacion,
                    n.id_nodo AS nodo_asignado_id,
                    n.lora_id AS nodo_lora_id,
                    n.room_id AS nodo_room_id
                FROM aulas a
                LEFT JOIN {$this->table_name} n ON n.id_aula = a.id_aula AND n.activo = 1" .
                ($excluirIdNodo ? " AND n.id_nodo != :excluir" : "") . "
                WHERE a.activo = 1
                ORDER BY 
                    CASE WHEN n.id_nodo IS NULL THEN 0 ELSE 1 END ASC,
                    a.bloque ASC, a.piso ASC, a.nombre ASC";

        $stmt = $this->conn->prepare($sql);
        if ($excluirIdNodo) {
            $stmt->bindValue(':excluir', $excluirIdNodo, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda (crea o actualiza) un nodo LoRa en la base de datos.
     * Retorna array con ['success' => bool, 'message' => string, 'id' => int]
     */
    public function guardar(array $datos): array {
        $id_nodo = !empty($datos['id_nodo']) ? (int)$datos['id_nodo'] : null;
        $id_aula = !empty($datos['id_aula']) ? (int)$datos['id_aula'] : null;
        $lora_id = !empty($datos['lora_id']) ? (int)$datos['lora_id'] : null;
        $lora_red_id = !empty($datos['lora_red_id']) ? (int)$datos['lora_red_id'] : 18;
        $tipo = !empty($datos['tipo']) ? trim($datos['tipo']) : 'AULA';
        $room_id = !empty($datos['room_id']) ? trim($datos['room_id']) : '';
        $node_id = !empty($datos['node_id']) ? trim($datos['node_id']) : ('NODO_' . ($room_id ?: $lora_id));

        if (!$id_aula && $tipo === 'AULA') {
            return ['success' => false, 'message' => 'Debe asociar un aula al nodo.'];
        }
        if (!$lora_id) {
            return ['success' => false, 'message' => 'Debe especificar un ID LoRa válido.'];
        }

        // Validar unicidad de lora_id en la misma red LoRa
        $sqlCheckLora = "SELECT id_nodo FROM {$this->table_name} 
                         WHERE lora_red_id = :lora_red_id AND lora_id = :lora_id AND activo = 1" .
                         ($id_nodo ? " AND id_nodo != :id_nodo" : "") . " LIMIT 1";
        $stmtCheck = $this->conn->prepare($sqlCheckLora);
        $paramsCheck = [':lora_red_id' => $lora_red_id, ':lora_id' => $lora_id];
        if ($id_nodo) $paramsCheck[':id_nodo'] = $id_nodo;
        $stmtCheck->execute($paramsCheck);
        if ($stmtCheck->fetchColumn()) {
            return ['success' => false, 'message' => "El LoRa ID {$lora_id} ya está asignado a otro nodo en la Red {$lora_red_id}."];
        }

        // Si el aula ya estaba asignada a otro nodo diferente, desvincularla para permitir reasignación transparente
        if ($id_aula) {
            $sqlDesvincular = "UPDATE {$this->table_name} 
                               SET id_aula = NULL 
                               WHERE id_aula = :id_aula AND activo = 1" .
                               ($id_nodo ? " AND id_nodo != :id_nodo" : "");
            $stmtDesv = $this->conn->prepare($sqlDesvincular);
            $paramsDesv = [':id_aula' => $id_aula];
            if ($id_nodo) $paramsDesv[':id_nodo'] = $id_nodo;
            $stmtDesv->execute($paramsDesv);
        }

        // Si no viene room_id, obtenerlo de aulas garantizando el código oficial
        if (empty($room_id) && $id_aula) {
            $stmtCode = $this->conn->prepare("SELECT COALESCE(codigo, codigo_sala) FROM aulas WHERE id_aula = :id");
            $stmtCode->execute([':id' => $id_aula]);
            $room_id = (string)$stmtCode->fetchColumn();
        }

        if ($id_nodo) {
            $sql = "UPDATE {$this->table_name}
                    SET node_id = :node_id,
                        id_aula = :id_aula,
                        tipo = :tipo,
                        lora_id = :lora_id,
                        lora_red_id = :lora_red_id,
                        room_id = :room_id
                    WHERE id_nodo = :id_nodo";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':node_id' => $node_id,
                ':id_aula' => $id_aula,
                ':tipo' => $tipo,
                ':lora_id' => $lora_id,
                ':lora_red_id' => $lora_red_id,
                ':room_id' => $room_id,
                ':id_nodo' => $id_nodo
            ]);
            return ['success' => true, 'message' => 'Nodo actualizado correctamente.', 'id' => $id_nodo];
        } else {
            $sql = "INSERT INTO {$this->table_name} 
                    (node_id, id_aula, tipo, estado, activo, lora_id, lora_red_id, room_id)
                    VALUES (:node_id, :id_aula, :tipo, 'online', 1, :lora_id, :lora_red_id, :room_id)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':node_id' => $node_id,
                ':id_aula' => $id_aula,
                ':tipo' => $tipo,
                ':lora_id' => $lora_id,
                ':lora_red_id' => $lora_red_id,
                ':room_id' => $room_id
            ]);
            $nuevoId = (int)$this->conn->lastInsertId();
            return ['success' => true, 'message' => 'Nodo LoRa registrado exitosamente.', 'id' => $nuevoId];
        }
    }

    /**
     * Desactiva un nodo (soft delete).
     */
    public function desactivar(int $id_nodo): bool {
        $sql = "UPDATE {$this->table_name} SET activo = 0 WHERE id_nodo = :id_nodo";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id_nodo' => $id_nodo]);
    }

    /**
     * Endpoint dinámico para el Gateway: obtiene las aulas activas en la red LoRa
     * y la cantidad de huellas pendientes por sincronizar en cada una.
     */
    public function obtenerAulasParaSync(?int $idHuella = null): array {
        $filterHuella = "";
        $params = [];
        if ($idHuella !== null && $idHuella > 0) {
            $filterHuella = " AND s.id_huella = :huella";
            $params[':huella'] = $idHuella;
        }

        $sql = "SELECT n.id_aula, n.lora_id, n.lora_red_id,
                       COALESCE(n.room_id, a.codigo_sala, 'AULA_' || n.id_aula) AS codigo_aula,
                       COALESCE(a.nombre, 'Aula ' || n.id_aula) AS nombre_aula,
                       COUNT(s.id_sync) AS pendientes
                FROM {$this->table_name} n
                LEFT JOIN aulas a ON a.id_aula = n.id_aula
                INNER JOIN sync_biometrica s ON s.id_aula = n.id_aula AND s.estado = 'PENDIENTE'{$filterHuella}
                INNER JOIN huellas_templates h ON h.id_huella = s.id_huella AND h.activo = 1
                WHERE n.tipo = 'AULA' AND n.activo = 1 AND n.lora_id IS NOT NULL
                GROUP BY n.id_aula, n.lora_id, n.lora_red_id, n.room_id, a.codigo_sala, a.nombre
                HAVING COUNT(s.id_sync) > 0
                ORDER BY pendientes DESC, n.lora_id ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $resultado = [];
        foreach ($rows as $r) {
            $resultado[] = [
                'id_aula' => (int)$r['id_aula'],
                'lora_id' => (int)$r['lora_id'],
                'lora_red_id' => (int)$r['lora_red_id'],
                'codigo_aula' => $r['codigo_aula'],
                'nombre_aula' => $r['nombre_aula'],
                'pendientes' => (int)$r['pendientes']
            ];
        }
        return $resultado;
    }

    /**
     * Actualiza el heartbeat del nodo.
     */
    public function heartbeat(string $node_id): bool {
        $query = "UPDATE {$this->table_name}
                  SET estado = 'online', ultimo_heartbeat = NOW()
                  WHERE node_id = :node_id AND activo = 1";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([':node_id' => $node_id]);
    }
}
