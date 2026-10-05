<?php
$localConfig = __DIR__.'/devices.local.php';
$devices = is_file($localConfig) ? require $localConfig : [];
define('GATEWAY_API_KEY', (string)(getenv('SISCO_GATEWAY_API_KEY') ?: ($devices['gateway_api_key'] ?? '')));
define('GATEWAY_SYNC_URL', getenv('GATEWAY_SYNC_URL') ?: 'http://192.168.100.117/sync');
function base_url($path = '') {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $base = preg_split('#/(mvc|src|public|test)/#', $script)[0];
    return $base . '/' . ltrim($path, '/');
}
?>
