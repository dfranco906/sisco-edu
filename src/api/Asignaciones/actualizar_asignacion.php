<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Asignacion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metodo no permitido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $asignacion = new Asignacion($db);

    $asignacion->id_asignacion = $_POST['id_asignacion'] ?? null;
    $asignacion->id_profesor = $_POST['id_profesor'] ?? null;
    $asignacion->id_materia = $_POST['id_materia'] ?? null;
    $asignacion->aÃ±o_lectivo = $_POST['aÃ±o_lectivo'] ?? $_POST['anio'] ?? null;

    if (!$asignacion->id_asignacion) {
        echo json_encode(["status" => "error", "message" => "ID de asignacion requerido"]);
        exit;
    }

    $resultado = $asignacion->actualizar();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Asignacion actualizada correctamente" : "Error al actualizar asignacion"
    ]);
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Error interno", "debug" => $e->getMessage()]);
}
?>
