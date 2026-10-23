<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Asignacion.php';

$idAsignacion = (int)($_GET['id_asignacion'] ?? $_POST['id_asignacion'] ?? 0);

if ($idAsignacion <= 0) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => "Identificador de asignación inválido."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $asignacionService = new Asignacion($db);
    $detalle = $asignacionService->obtenerDetalle($idAsignacion);

    if (!$detalle) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Asignación docente no encontrada."
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "message" => "Detalle de la asignación docente obtenido correctamente.",
        "data" => $detalle
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error al obtener detalle de asignación: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al consultar el detalle de la asignación docente."
    ], JSON_UNESCAPED_UNICODE);
}
