<?php
/**
 * ============================================================================
 * SISCO-EDU: CONFIGURACIÓN CENTRALIZADA DE RED E IPS
 * ============================================================================
 * Este archivo centraliza todas las direcciones IP y URLs del sistema para
 * evitar direcciones quemadas (hardcoded) en el código fuente.
 * 
 * Si necesitas cambiar las IPs para un nuevo entorno o máquina local:
 * 1. Puedes editar los valores por defecto aquí.
 * 2. O crear un archivo `config.local.php` en la raíz (ignorado por Git).
 * 3. O definir variables de entorno (ej. en Apache o Docker).
 */

// 1. Cargar archivo local opcional si existe (ignorado por Git)
$localConfigFile = __DIR__ . '/../../config.local.php';
$localConfig = file_exists($localConfigFile) ? (include $localConfigFile) : [];
if (!is_array($localConfig)) {
    $localConfig = [];
}

// 2. IP / Host del Servidor Central
if (!defined('IP_SERVIDOR')) {
    define('IP_SERVIDOR', getenv('IP_SERVIDOR') ?: ($localConfig['IP_SERVIDOR'] ?? '192.168.100.109'));
}

// 3. IP del Nodo Registrador Biométrico (ESP32 + DY50 en estación de enrolamiento)
if (!defined('IP_ESP32_REGISTRADOR')) {
    define('IP_ESP32_REGISTRADOR', getenv('IP_ESP32_REGISTRADOR') ?: ($localConfig['IP_ESP32_REGISTRADOR'] ?? '192.168.100.125'));
}

// 4. IP y URL del Gateway LoRa (ESP32 que retransmite hacia las aulas)
if (!defined('IP_ESP32_GATEWAY')) {
    define('IP_ESP32_GATEWAY', getenv('IP_ESP32_GATEWAY') ?: ($localConfig['IP_ESP32_GATEWAY'] ?? '192.168.100.17'));
}

if (!defined('GATEWAY_SYNC_URL')) {
    define('GATEWAY_SYNC_URL', getenv('GATEWAY_SYNC_URL') ?: ($localConfig['GATEWAY_SYNC_URL'] ?? 'http://' . IP_ESP32_GATEWAY . '/sync'));
}

// 5. Clave de autenticación para el Gateway LoRa
if (!defined('GATEWAY_API_KEY')) {
    define('GATEWAY_API_KEY', getenv('GATEWAY_API_KEY') ?: ($localConfig['GATEWAY_API_KEY'] ?? 'SISCO_GATEWAY_2026_SECRETO'));
}
