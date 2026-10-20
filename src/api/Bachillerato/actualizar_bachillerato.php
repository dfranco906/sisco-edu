<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Bachillerato.php';

try {
    $db = (new Database())->getConnection();
    $bach = new Bachillerato($db);

    $bach->id_bachillerato = (int)($_POST['id_bachillerato'] ?? 0);
    $bach->nombre = $_POST['nombre'] ?? '';
    $bach->codigo = $_POST['codigo'] ?? '';
    $bach->tipo = $_POST['tipo'] ?? 'Técnico';
    $bach->descripcion = $_POST['descripcion'] ?? null;

    if (!$bach->id_bachillerato || empty(trim((string)$bach->nombre)) || empty(trim((string)$bach->codigo))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        exit;
    }

    $res = $bach->actualizar();
    http_response_code(200);
    echo json_encode([
        "status" => $res ? "success" : "error",
        "message" => $res ? "Bachillerato actualizado correctamente" : "Error al actualizar bachillerato"
    ]);
} catch (Throwable $e) {
    error_log("Error en actualizar_bachillerato: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al actualizar bachillerato"]);
}
