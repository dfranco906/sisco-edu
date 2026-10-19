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
$id_capacidad = filter_var($data['id_capacidad'] ?? null, FILTER_VALIDATE_INT) ?: null;
$descripcion = trim((string)($data['descripcion'] ?? ''));
$orden = filter_var($data['orden'] ?? 1, FILTER_VALIDATE_INT) ?: 1;

if (!$id_unidad || $descripcion === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos (id_unidad, descripcion)"], JSON_UNESCAPED_UNICODE);
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
            echo json_encode(["status" => "error", "message" => "No tiene permiso para editar este plan"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $savedId = $planModel->guardarCapacidad($id_unidad, $descripcion, $orden, $id_capacidad);

    http_response_code($id_capacidad ? 200 : 201);
    echo json_encode([
        "status" => "success",
        "message" => $id_capacidad ? "Capacidad actualizada" : "Capacidad creada",
        "data" => ["id_capacidad" => $savedId]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/guardar_capacidad: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al guardar capacidad"], JSON_UNESCAPED_UNICODE);
}
