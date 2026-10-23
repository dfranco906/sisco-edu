<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Materia.php';

$idMateria = (int)($_GET['id_materia'] ?? $_POST['id_materia'] ?? 0);

if ($idMateria <= 0) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => "Identificador de materia inválido"
    ]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $materiaService = new Materia($db);
    $detalle = $materiaService->obtenerDetalle($idMateria);

    if (!$detalle) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Materia no encontrada"
        ]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "message" => "Detalle de materia obtenido correctamente",
        "data" => $detalle
    ]);
} catch (Throwable $e) {
    error_log("Error obteniendo detalle de materia: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error al consultar el detalle de la materia"
    ]);
}
?>
