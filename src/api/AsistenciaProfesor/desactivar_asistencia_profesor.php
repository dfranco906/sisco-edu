<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/AsistenciaProfesor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metodo no permitido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $asistencia = new AsistenciaProfesor($db);
    $asistencia->id_asistencia_profesor = $_POST['id_asistencia_profesor'] ?? null;

    if (!$asistencia->id_asistencia_profesor) {
        echo json_encode(["status" => "error", "message" => "ID de asistencia requerido"]);
        exit;
    }

    $resultado = $asistencia->desactivar();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Asistencia desactivada correctamente" : "Error al desactivar asistencia"
    ]);
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Error interno", "debug" => $e->getMessage()]);
}
?>
