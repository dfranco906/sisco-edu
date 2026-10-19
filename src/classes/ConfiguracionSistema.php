<?php
// src/classes/ConfiguracionSistema.php

class ConfiguracionSistema {
    private $conn;
    private $table_name = "configuraciones_sistema";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Asegura que la tabla de configuraciones exista y contenga valores iniciales.
     */
    public function asegurarTabla(): void {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->table_name}` (
                `clave` VARCHAR(50) NOT NULL,
                `valor` TEXT NOT NULL,
                `categoria` VARCHAR(50) NOT NULL DEFAULT 'red',
                `tipo` VARCHAR(20) NOT NULL DEFAULT 'string',
                `descripcion` VARCHAR(255) NULL,
                `fecha_actualizacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`clave`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            $this->conn->exec($sql);

            // Verificar si hay registros
            $stmt = $this->conn->query("SELECT COUNT(*) FROM `{$this->table_name}`");
            if ((int)$stmt->fetchColumn() === 0) {
                $seeds = [
                    ['ip_servidor', '192.168.100.109', 'red', 'ip', 'Dirección IP o hostname del servidor central Apache/XAMPP'],
                    ['url_servidor', '', 'red', 'url', 'URL base del servidor backend (vacía para autodetectar mediante base_url)'],
                    ['ip_registrador', '192.168.100.125', 'dispositivos', 'ip', 'Dirección IP del ESP32 registrador biométrico (captura de huella)'],
                    ['ip_gateway', '192.168.100.17', 'dispositivos', 'ip', 'Dirección IP del ESP32 Gateway LoRa'],
                    ['gateway_sync_url', 'http://192.168.100.17/sync', 'red', 'url', 'URL completa para notificar sincronizaciones al Gateway LoRa'],
                    ['gateway_api_key', 'SISCO_GATEWAY_2026_SECRETO', 'seguridad', 'password', 'Clave secreta enviada en la cabecera X-GATEWAY-KEY'],
                    ['lora_red_id', '18', 'lora', 'number', 'ID de red LoRa configurado en los módulos RYLR998'],
                    ['lora_id_gateway', '100', 'lora', 'number', 'Dirección LoRa del Gateway receptor']
                ];
                $ins = $this->conn->prepare("INSERT INTO `{$this->table_name}` (clave, valor, categoria, tipo, descripcion) VALUES (?, ?, ?, ?, ?)");
                foreach ($seeds as $s) {
                    $ins->execute($s);
                }
            }
        } catch (Throwable $e) {
            error_log("No se pudo auto-inicializar la tabla configuraciones_sistema: " . $e->getMessage());
        }
    }

    /**
     * Obtiene el valor de una clave específica.
     */
    public function obtener(string $clave, ?string $default = null): ?string {
        try {
            $stmt = $this->conn->prepare("SELECT valor FROM `{$this->table_name}` WHERE clave = :clave LIMIT 1");
            $stmt->execute([':clave' => $clave]);
            $valor = $stmt->fetchColumn();
            return ($valor !== false && $valor !== null) ? (string)$valor : $default;
        } catch (Throwable $e) {
            return $default;
        }
    }

    /**
     * Obtiene todas las configuraciones como un mapa asociativo [clave => valor].
     */
    public function obtenerMapa(): array {
        try {
            $stmt = $this->conn->query("SELECT clave, valor FROM `{$this->table_name}`");
            $resultado = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $resultado[$row['clave']] = $row['valor'];
            }
            return $resultado;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Obtiene el listado completo con metadatos para la vista administrativa.
     */
    public function obtenerTodas(): array {
        try {
            $stmt = $this->conn->query("SELECT clave, valor, categoria, tipo, descripcion, fecha_actualizacion FROM `{$this->table_name}` ORDER BY categoria ASC, clave ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Actualiza el valor de una clave individual.
     */
    public function actualizar(string $clave, string $valor): bool {
        $stmt = $this->conn->prepare("UPDATE `{$this->table_name}` SET valor = :valor WHERE clave = :clave");
        return $stmt->execute([
            ':valor' => $valor,
            ':clave' => $clave
        ]);
    }

    /**
     * Actualiza múltiples configuraciones dentro de una transacción.
     */
    public function actualizarLote(array $datos): bool {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("UPDATE `{$this->table_name}` SET valor = :valor WHERE clave = :clave");
            foreach ($datos as $clave => $valor) {
                $stmt->execute([
                    ':valor' => trim((string)$valor),
                    ':clave' => trim((string)$clave)
                ]);
            }
            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            error_log("Error al actualizar lote de configuraciones: " . $e->getMessage());
            return false;
        }
    }
}
