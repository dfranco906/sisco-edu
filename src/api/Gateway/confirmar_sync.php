<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';

$headers = getallheaders();
$key = $headers['X-GATEWAY-KEY'] ?? '';

if ($key !== GATEWAY_API_KEY) {
    http_response_code(404);
    echo json_encode(["status" => "not_found"]);
    exit;
}

$db = (new Database())->getConnection();

$id_sync = $_POST['id_sync'] ?? null;
$estado = $_POST['estado'] ?? null;
$mensaje = $_POST['mensaje'] ?? '';

if (!$id_sync || !$estado) {
    echo json_encode(["status" => "error", "message" => "Faltan datos"]);
    exit;
}

if (!in_array($estado, ["CONFIRMADO", "ERROR"])) {
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