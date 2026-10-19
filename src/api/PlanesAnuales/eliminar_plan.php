<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/PlanAnual.php';

verificarRol(['SuperAdmin', 'Administrador', 'Coordinador', 'Profesor']);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id_plan = filter_var($data['id_plan'] ?? null, FILTER_VALIDATE_INT);
if (!$id_plan) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID de plan no especificado"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $planModel = new PlanAnual($db);

    $rol = $_SESSION['rol'] ?? null;
    $idProfesorSesion = $_SESSION['id_profesor'] ?? null;

    if ($rol === 'Profesor') {
        $stmtOwner = $db->prepare("SELECT a.id_profesor FROM planes_anuales p JOIN asignacion_docente a ON a.id_asignacion = p.id_asignacion WHERE p.id_plan = :id_plan LIMIT 1");
        $stmtOwner->execute([':id_plan' => $id_plan]);
        $ownerProf = $stmtOwner->fetchColumn();
        if ($ownerProf && (int)$ownerProf !== (int)$idProfesorSesion) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "No tiene permiso para eliminar este plan"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $ok = $planModel->eliminarPlan($id_plan);
    if ($ok) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Plan anual eliminado correctamente"], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "No se pudo eliminar el plan anual"], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/eliminar_plan: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al eliminar plan anual: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
