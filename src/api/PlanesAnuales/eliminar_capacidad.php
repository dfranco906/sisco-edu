<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/PlanAnual.php';

verificarRol(['SuperAdmin', 'Administrador', 'Coordinador', 'Profesor']);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id_capacidad = filter_var($data['id_capacidad'] ?? null, FILTER_VALIDATE_INT);
if (!$id_capacidad) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID de capacidad no especificado"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $planModel = new PlanAnual($db);

    $rol = $_SESSION['rol'] ?? null;
    $idProfesorSesion = $_SESSION['id_profesor'] ?? null;

    if ($rol === 'Profesor') {
        $stmtOwner = $db->prepare("
            SELECT a.id_profesor 
            FROM plan_capacidades pc
            JOIN plan_unidades pu ON pu.id_unidad = pc.id_unidad
            JOIN planes_anuales p ON p.id_plan = pu.id_plan
            JOIN asignacion_docente a ON a.id_asignacion = p.id_asignacion 
            WHERE pc.id_capacidad = :id_capacidad LIMIT 1
        ");
        $stmtOwner->execute([':id_capacidad' => $id_capacidad]);
        $ownerProf = $stmtOwner->fetchColumn();
        if ($ownerProf && (int)$ownerProf !== (int)$idProfesorSesion) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "No tiene permiso para eliminar esta capacidad"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $ok = $planModel->eliminarCapacidad($id_capacidad);
    if ($ok) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Capacidad eliminada correctamente"], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "No se pudo eliminar la capacidad"], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/eliminar_capacidad: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al eliminar capacidad: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
