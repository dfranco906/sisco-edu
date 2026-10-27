<?php
/**
 * ============================================================================
 * SISCO-EDU: CONFIGURACIÓN CENTRALIZADA DE RED E IPS DINÁMICAS
 * ============================================================================
 * Este archivo centraliza todas las direcciones IP y parámetros de red.
 *
 * Prioridad de resolución:
 * 1. Variables de entorno (Docker/Apache/Servicio Cloud).
 * 2. Archivo config.local.php (ignorado por Git, para overrides locales).
 * 3. Base de Datos (tabla configuraciones_sistema, editable desde el panel web).
 * 4. Valores por defecto de contingencia.
 */

// 1. Cargar archivo local opcional si existe
$localConfigFile = __DIR__ . '/../../config.local.php';
$localConfig = file_exists($localConfigFile) ? (include $localConfigFile) : [];
if (!is_array($localConfig)) {
    $localConfig = [];
}

// 2. Cargar configuraciones dinámicas desde la Base de Datos
$dbConfig = [];
try {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/../classes/ConfiguracionSistema.php';
    $db = (new Database())->getConnection();
    if ($db) {
        $configModel = new ConfiguracionSistema($db);
        $configModel->asegurarTabla();
        $dbConfig = $configModel->obtenerMapa();
    }
} catch (Throwable $e) {
    // Si la BD está temporalmente inaccesible, se utilizan los valores de contingencia
}

// 3. IP / Host del Servidor Central
if (!defined('IP_SERVIDOR')) {
    $ipServidor = getenv('IP_SERVIDOR') ?: (!empty($dbConfig['ip_servidor']) ? $dbConfig['ip_servidor'] : ($localConfig['IP_SERVIDOR'] ?? '192.168.100.52'));
    define('IP_SERVIDOR', $ipServidor);
}

// 4. IP del Nodo Registrador Biométrico (ESP32 + DY50)
if (!defined('IP_ESP32_REGISTRADOR')) {
    $ipReg = getenv('IP_ESP32_REGISTRADOR') ?: (!empty($dbConfig['ip_registrador']) ? $dbConfig['ip_registrador'] : ($localConfig['IP_ESP32_REGISTRADOR'] ?? '192.168.100.39'));
    define('IP_ESP32_REGISTRADOR', $ipReg);
}

// 5. IP del Gateway LoRa (ESP32)
if (!defined('IP_ESP32_GATEWAY')) {
    $ipGw = getenv('IP_ESP32_GATEWAY') ?: (!empty($dbConfig['ip_gateway']) ? $dbConfig['ip_gateway'] : ($localConfig['IP_ESP32_GATEWAY'] ?? '192.168.100.137'));
    define('IP_ESP32_GATEWAY', $ipGw);
}

// 6. URL para notificar sincronizaciones al Gateway (Auto-regenerada con IP actual si hay discordancia)
if (!defined('GATEWAY_SYNC_URL')) {
    $dbSyncUrl = !empty($dbConfig['gateway_sync_url']) ? $dbConfig['gateway_sync_url'] : null;
    if ($dbSyncUrl && strpos($dbSyncUrl, IP_ESP32_GATEWAY) === false) {
        $dbSyncUrl = null;
    }
    $syncUrl = getenv('GATEWAY_SYNC_URL') ?: ($localConfig['GATEWAY_SYNC_URL'] ?? ($dbSyncUrl ?: ('http://' . IP_ESP32_GATEWAY . '/sync')));
    define('GATEWAY_SYNC_URL', $syncUrl);
}

// 7. Clave secreta para el Gateway
if (!defined('GATEWAY_API_KEY')) {
    $apiKey = getenv('GATEWAY_API_KEY') ?: ($localConfig['GATEWAY_API_KEY'] ?? ($dbConfig['gateway_api_key'] ?? 'SISCO_GATEWAY_2026_SECRETO'));
    define('GATEWAY_API_KEY', $apiKey);
}

// 8. Parámetros LoRa
if (!defined('LORA_RED_ID')) {
    $loraRed = getenv('LORA_RED_ID') ?: ($localConfig['LORA_RED_ID'] ?? ($dbConfig['lora_red_id'] ?? '18'));
    define('LORA_RED_ID', (int)$loraRed);
}

if (!defined('LORA_ID_GATEWAY')) {
    $loraGw = getenv('LORA_ID_GATEWAY') ?: ($localConfig['LORA_ID_GATEWAY'] ?? ($dbConfig['lora_id_gateway'] ?? '100'));
    define('LORA_ID_GATEWAY', (int)$loraGw);
}

// 9. Configuración Supabase Realtime (WebSockets)
if (!defined('SUPABASE_URL')) {
    $sbUrl = getenv('SUPABASE_URL') ?: ($localConfig['SUPABASE_URL'] ?? ($dbConfig['supabase_url'] ?? ''));
    define('SUPABASE_URL', rtrim($sbUrl, '/'));
}

if (!defined('SUPABASE_ANON_KEY')) {
    $sbKey = getenv('SUPABASE_ANON_KEY') ?: ($localConfig['SUPABASE_ANON_KEY'] ?? ($dbConfig['supabase_anon_key'] ?? ''));
    define('SUPABASE_ANON_KEY', trim($sbKey));
}
