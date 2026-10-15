<?php
require_once __DIR__ . '/red.php';

function base_url($path = '') {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = preg_split('#/(mvc|src|public|test|pruebas|vistas|hardware)/#', $script)[0];
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}
?>
