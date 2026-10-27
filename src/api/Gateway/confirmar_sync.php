<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';

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
    exit;
}

try {
    $db = (new Database())->getConnection();

    $id_sync = $_POST['id_sync'] ?? null;
    $estado = $_POST['estado'] ?? null;
    $mensaje = $_POST['mensaje'] ?? '';

    if (!$id_sync || !$estado) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Faltan datos"]);
        exit;
    }

    if (!in_array($estado, ["CONFIRMADO", "ERROR"])) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Estado inválido"]);
        exit;
    }

    $stmt = $db->prepare("
        UPDATE sync_biometrica
        SET estado = :estado,
            mensaje = :mensaje,
            fecha_actualizacion = NOW()
        WHERE id_sync = :id_sync
    ");

    $resultado = $stmt->execute([
        ":estado" => $estado,
        ":mensaje" => $mensaje,
        ":id_sync" => $id_sync
    ]);

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Sincronización confirmada" : "Error al confirmar"
    ]);
} catch (Throwable $e) {
    error_log("Error en Gateway/confirmar_sync: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al confirmar"]);
}
