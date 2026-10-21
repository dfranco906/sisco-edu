<?php
require_once __DIR__ . '/red.php';

// Configuración centralizada de Zona Horaria Institucional (Paraguay)
$appTimezone = getenv('APP_TIMEZONE') ?: 'America/Asuncion';
date_default_timezone_set($appTimezone);

function base_url($path = '') {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = preg_split('#/(mvc|src|public|test|pruebas|vistas|hardware)/#', $script)[0];
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}
?>
