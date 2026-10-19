<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/PlanAnual.php';

$id_plan = filter_input(INPUT_GET, 'id_plan', FILTER_VALIDATE_INT);
if (!$id_plan) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID de plan no especificado o inválido"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $planModel = new PlanAnual($db);
    $plan = $planModel->obtenerPlanCompleto($id_plan);

    if (!$plan) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Plan anual no encontrado"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $rol = $_SESSION['rol'] ?? null;
    $idProfesorSesion = $_SESSION['id_profesor'] ?? null;

    // Si es Profesor, validar que el plan le pertenezca
    if ($rol === 'Profesor') {
        if ($plan['id_profesor'] !== null && (int)$plan['id_profesor'] !== (int)$idProfesorSesion) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "No tienes permiso para ver este plan anual"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Plan anual obtenido correctamente",
        "data" => $plan
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/obtener: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al obtener detalle del plan anual"], JSON_UNESCAPED_UNICODE);
}
