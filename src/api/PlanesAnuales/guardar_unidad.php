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
$id_unidad = filter_var($data['id_unidad'] ?? null, FILTER_VALIDATE_INT) ?: null;
$nombre = trim((string)($data['nombre'] ?? ''));
$tiempo_texto = !empty($data['tiempo_texto']) ? trim((string)$data['tiempo_texto']) : null;
$orden = filter_var($data['orden'] ?? 1, FILTER_VALIDATE_INT) ?: 1;

if (!$id_plan || $nombre === '') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos (id_plan, nombre)"], JSON_UNESCAPED_UNICODE);
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
            echo json_encode(["status" => "error", "message" => "No tiene permiso para editar este plan"], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $savedId = $planModel->guardarUnidad($id_plan, $nombre, $tiempo_texto, $orden, $id_unidad);

    http_response_code($id_unidad ? 200 : 201);
    echo json_encode([
        "status" => "success",
        "message" => $id_unidad ? "Unidad temática actualizada" : "Unidad temática creada",
        "data" => ["id_unidad" => $savedId]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/guardar_unidad: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al guardar unidad temática"], JSON_UNESCAPED_UNICODE);
}
