<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Turno.php';

try {
    $db = (new Database())->getConnection();
    $turno = new Turno($db);

    $turno->id_turno = (int)($_POST['id_turno'] ?? 0);
    $turno->nombre = $_POST['nombre'] ?? '';
    $turno->hora_inicio = $_POST['hora_inicio'] ?? null;
    $turno->hora_fin = $_POST['hora_fin'] ?? null;
    $turno->descripcion = $_POST['descripcion'] ?? null;

    if (!$turno->id_turno || empty(trim((string)$turno->nombre))) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        exit;
    }

    $res = $turno->actualizar();
    http_response_code(200);
    echo json_encode([
        "status" => $res ? "success" : "error",
        "message" => $res ? "Turno actualizado correctamente" : "Error al actualizar turno"
    ]);
} catch (Throwable $e) {
    error_log("Error en actualizar_turno: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al actualizar turno"]);
}
