<?php
// src/api/Gateway/obtener_aulas_sync.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/NodoEsp32.php';

// Validar cabecera de seguridad X-GATEWAY-KEY
$receivedKey = '';
if (function_exists('getallheaders')) {
    $headers = getallheaders();
    $receivedKey = $headers['X-GATEWAY-KEY'] ?? ($headers['x-gateway-key'] ?? '');
}
if (empty($receivedKey)) {
    $receivedKey = $_SERVER['HTTP_X_GATEWAY_KEY'] ?? '';
}

if ($receivedKey !== GATEWAY_API_KEY) {
    http_response_code(404);
    echo json_encode(["status" => "not_found"]);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $nodoModel = new NodoEsp32($db);

    $aulas = $nodoModel->obtenerAulasParaSync();

    echo json_encode([
        "status" => "success",
        "data" => [
            "total_aulas" => count($aulas),
            "aulas" => $aulas
        ]
    ]);
} catch (Throwable $e) {
    error_log("Error en obtener_aulas_sync.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al consultar aulas LoRa para sincronización."
    ]);
}
