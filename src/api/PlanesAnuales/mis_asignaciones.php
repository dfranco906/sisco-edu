<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/PlanAnual.php';

verificarRol(['SuperAdmin', 'Administrador', 'Coordinador', 'Profesor']);

try {
    $db = (new Database())->getConnection();
    $planModel = new PlanAnual($db);

    $rol = $_SESSION['rol'] ?? null;
    $idProfesorSesion = $_SESSION['id_profesor'] ?? null;

    $id_profesor = filter_input(INPUT_GET, 'id_profesor', FILTER_VALIDATE_INT) ?: null;

    if ($rol === 'Profesor') {
        $id_profesor = $idProfesorSesion ? (int)$idProfesorSesion : -1;
    }

    $asignaciones = $planModel->obtenerAsignacionesDocente($id_profesor);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Asignaciones obtenidas correctamente",
        "data" => $asignaciones
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/mis_asignaciones: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al obtener asignaciones"], JSON_UNESCAPED_UNICODE);
}
