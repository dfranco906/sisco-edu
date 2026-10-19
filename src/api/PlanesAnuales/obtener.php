<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/PlanAnual.php';

$id_plan = filter_input(INPUT_GET, 'id_plan', FILTER_VALIDATE_INT);
if (!$id_plan) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID de plan no especificado o inválido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $planModel = new PlanAnual($db);
    $plan = $planModel->obtenerPlanCompleto($id_plan);

    if (!$plan) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Plan anual no encontrado"]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Plan anual obtenido",
        "data" => $plan
    ]);
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/obtener: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al obtener detalle del plan anual"]);
}
