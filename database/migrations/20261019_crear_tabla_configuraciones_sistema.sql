-- ============================================================================
-- SISCO-EDU: Migración para Configuración Dinámica del Sistema en Base de Datos
-- Fecha: 2026-10-19
-- ============================================================================
-- Esta tabla almacena de forma persistente y segura las direcciones IP de los
-- dispositivos IoT, URLs y credenciales para que puedan gestionarse desde el
-- panel web administrativo sin necesidad de modificar el código fuente.

CREATE TABLE IF NOT EXISTS `configuraciones_sistema` (
  `clave` VARCHAR(50) NOT NULL,
  `valor` TEXT NOT NULL,
  `categoria` VARCHAR(50) NOT NULL DEFAULT 'red',
  `tipo` VARCHAR(20) NOT NULL DEFAULT 'string',
  `descripcion` VARCHAR(255) NULL,
  `fecha_actualizacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Inserción de valores iniciales por defecto (no sobreescribe si ya existen)
INSERT INTO `configuraciones_sistema` (`clave`, `valor`, `categoria`, `tipo`, `descripcion`) VALUES
('ip_servidor', '192.168.100.109', 'red', 'ip', 'Dirección IP o hostname del servidor central Apache/XAMPP'),
('url_servidor', '', 'red', 'url', 'URL base del servidor backend (vacía para autodetectar mediante base_url)'),
('ip_registrador', '192.168.100.125', 'dispositivos', 'ip', 'Dirección IP del ESP32 registrador biométrico (captura de huella)'),
('ip_gateway', '192.168.100.17', 'dispositivos', 'ip', 'Dirección IP del ESP32 Gateway LoRa'),
('gateway_sync_url', 'http://192.168.100.17/sync', 'red', 'url', 'URL completa para notificar sincronizaciones al Gateway LoRa'),
('gateway_api_key', 'SISCO_GATEWAY_2026_SECRETO', 'seguridad', 'password', 'Clave secreta enviada en la cabecera X-GATEWAY-KEY'),
('lora_red_id', '18', 'lora', 'number', 'ID de red LoRa configurado en los módulos RYLR998'),
('lora_id_gateway', '100', 'lora', 'number', 'Dirección LoRa del Gateway receptor')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);
