<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/PlanAnual.php';

try {
    $db = (new Database())->getConnection();
    $planModel = new PlanAnual($db);

    $rol = $_SESSION['rol'] ?? null;
    $idProfesorSesion = $_SESSION['id_profesor'] ?? null;

    $id_profesor = filter_input(INPUT_GET, 'id_profesor', FILTER_VALIDATE_INT) ?: null;

    // Regla de seguridad: Si el rol es Profesor, SOLO ve sus propios planes asignados
    if ($rol === 'Profesor') {
        $id_profesor = $idProfesorSesion ? (int)$idProfesorSesion : -1;
    }

    $planes = $planModel->leer($id_profesor);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Planes anuales obtenidos correctamente",
        "data" => $planes
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/listar: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al obtener planes anuales"
    ], JSON_UNESCAPED_UNICODE);
}
