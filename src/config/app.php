<?php
define('GATEWAY_API_KEY', 'SISCO_GATEWAY_2026_SECRETO');
define('GATEWAY_SYNC_URL', getenv('GATEWAY_SYNC_URL') ?: 'http://192.168.100.122/sync');
function base_url($path = '') {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $base = preg_split('#/(mvc|src|public|test)/#', $script)[0];
    return $base . '/' . ltrim($path, '/');
}
?>
