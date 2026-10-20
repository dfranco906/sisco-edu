<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Turno.php';

try {
    $db = (new Database())->getConnection();
    $turno = new Turno($db);

    $turno->nombre = $_POST['nombre'] ?? '';
    $turno->hora_inicio = $_POST['hora_inicio'] ?? null;
    $turno->hora_fin = $_POST['hora_fin'] ?? null;
    $turno->descripcion = $_POST['descripcion'] ?? null;

    if (empty(trim((string)$turno->nombre))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "El nombre del turno es obligatorio"]);
        exit;
    }

    $res = $turno->crear();
    http_response_code($res ? 201 : 400);
    echo json_encode([
        "status" => $res ? "success" : "error",
        "message" => $res ? "Turno creado correctamente" : "Error al registrar el turno"
    ]);
} catch (Throwable $e) {
    error_log("Error en crear_turno: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al crear turno"]);
}
