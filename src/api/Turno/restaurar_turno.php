<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Turno.php';

try {
    $db = (new Database())->getConnection();
    $turno = new Turno($db);
    $turno->id_turno = (int)($_POST['id_turno'] ?? 0);

    if (!$turno->id_turno) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "ID no proporcionado"]);
        exit;
    }

    $res = $turno->restaurar();
    http_response_code(200);
    echo json_encode([
        "status" => $res ? "success" : "error",
        "message" => $res ? "Turno restaurado correctamente" : "Error al restaurar turno"
    ]);
} catch (Throwable $e) {
    error_log("Error en restaurar_turno: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al restaurar turno"]);
}
