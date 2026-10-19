<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/PlanAnual.php';

verificarRol(['SuperAdmin', 'Administrador', 'Coordinador', 'Profesor']);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id_unidad = filter_var($data['id_unidad'] ?? null, FILTER_VALIDATE_INT);
if (!$id_unidad) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID de unidad no especificado"], JSON_UNESCAPED_UNICODE);
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
            FROM plan_unidades pu
            JOIN planes_anuales p ON p.id_plan = pu.id_plan
            JOIN asignacion_docente a ON a.id_asignacion = p.id_asignacion 
            WHERE pu.id_unidad = :id_unidad LIMIT 1
        ");
        $stmtOwner->execute([':id_unidad' => $id_unidad]);
        $ownerProf = $stmtOwner->fetchColumn();
        if ($ownerProf && (int)$ownerProf !== (int)$idProfesorSesion) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "No tiene permiso para eliminar esta unidad"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $ok = $planModel->eliminarUnidad($id_unidad);
    if ($ok) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Unidad temática eliminada"], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "No se pudo eliminar la unidad temática"], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/eliminar_unidad: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al eliminar unidad: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
