<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/PlanAnual.php';

try {
    $db = (new Database())->getConnection();
    $planModel = new PlanAnual($db);

    $id_profesor = filter_input(INPUT_GET, 'id_profesor', FILTER_VALIDATE_INT);
    $planes = $planModel->leer($id_profesor ?: null);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Planes anuales obtenidos correctamente",
        "data" => $planes
    ]);
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/listar: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al obtener planes anuales"]);
}
