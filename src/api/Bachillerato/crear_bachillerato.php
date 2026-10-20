<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Bachillerato.php';

try {
    $db = (new Database())->getConnection();
    $bach = new Bachillerato($db);

    $bach->nombre = $_POST['nombre'] ?? '';
    $bach->codigo = $_POST['codigo'] ?? '';
    $bach->tipo = $_POST['tipo'] ?? 'Técnico';
    $bach->descripcion = $_POST['descripcion'] ?? null;

    if (empty(trim((string)$bach->nombre)) || empty(trim((string)$bach->codigo))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Nombre y código del bachillerato son obligatorios"]);
        exit;
    }

    $res = $bach->crear();
    http_response_code($res ? 201 : 400);
    echo json_encode([
        "status" => $res ? "success" : "error",
        "message" => $res ? "Bachillerato creado correctamente" : "Error al registrar bachillerato"
    ]);
} catch (Throwable $e) {
    error_log("Error en crear_bachillerato: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al crear bachillerato"]);
}
