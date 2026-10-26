<?php
// src/api/Configuracion/cambiar_entorno.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolUsuario = strtolower($_SESSION['rol'] ?? '');
if (!in_array($rolUsuario, ['superadmin', 'administracion', 'administrador'], true)) {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "message" => "Acceso denegado. Se requieren privilegios administrativos."
    ]);
    exit();
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

$nuevoModo = strtolower(trim((string)($data['modo'] ?? '')));
if (!in_array($nuevoModo, ['local', 'cloud'], true)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Modo inválido. Especifique 'local' o 'cloud'."
    ]);
    exit();
}

$configFile = __DIR__ . '/../../../config.local.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "El archivo config.local.php no fue encontrado."
    ]);
    exit();
}

$content = file_get_contents($configFile);
$nuevoContent = preg_replace("/\\\$modoActivo\s*=\s*'[^']+'/", "\$modoActivo = '{$nuevoModo}'", $content);

if (file_put_contents($configFile, $nuevoContent) === false) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "No se pudo escribir en config.local.php. Verifique permisos de archivo."
    ]);
    exit();
}

echo json_encode([
    "status"  => "success",
    "message" => "Entorno cambiado exitosamente a " . strtoupper($nuevoModo) . ".",
    "data"    => ["modo" => $nuevoModo]
], JSON_UNESCAPED_UNICODE);
