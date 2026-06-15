<?php
    define('GATEWAY_API_KEY', 'SISCO_GATEWAY_2026_SECRETO');
function base_url($path = '') {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    $base = preg_split('#/(mvc|src|public|test)/#', $script)[0];
    return $base . '/' . ltrim($path, '/');
}
?>