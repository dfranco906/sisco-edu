<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Bachillerato.php';

try {
    $db = (new Database())->getConnection();
    $bach = new Bachillerato($db);
    $bach->id_bachillerato = (int)($_POST['id_bachillerato'] ?? 0);

    if (!$bach->id_bachillerato) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "ID no proporcionado"]);
        exit;
    }

    $res = $bach->desactivar();
    http_response_code(200);
    echo json_encode([
        "status" => $res ? "success" : "error",
        "message" => $res ? "Bachillerato desactivado correctamente" : "Error al desactivar bachillerato"
    ]);
} catch (Throwable $e) {
    error_log("Error en desactivar_bachillerato: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al desactivar bachillerato"]);
}
