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

    if (!$asignacion->id_asignacion) {
        echo json_encode(["status" => "error", "message" => "ID de asignacion requerido"]);
        exit;
    }

    if (!$asignacion->cargarPorId()) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "La asignación no existe"]);
        exit;
    }

    if (!$asignacion->id_grado || !$asignacion->relacionesActivas()) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "La asignación no puede restaurarse porque tiene profesor, materia, grado o aula inactivos/incompletos."]);
        exit;
    }

    if ($asignacion->existeDuplicada($asignacion->id_asignacion)) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Ya existe una asignación activa equivalente."]);
        exit;
    }

    $resultado = $asignacion->restaurar();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Asignacion restaurada correctamente" : "Error al restaurar asignacion"
    ]);
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Error interno", "debug" => $e->getMessage()]);
}
?>
