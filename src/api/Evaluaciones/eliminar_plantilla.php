<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/EvaluacionMec.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$idPlantilla = filter_input(INPUT_POST, 'id_plantilla', FILTER_VALIDATE_INT);
$idProfesor = !empty($_SESSION['id_profesor']) ? (int)$_SESSION['id_profesor'] : null;

if (!$idPlantilla) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Identificador de plantilla no proporcionado"], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $evalModel = new EvaluacionMec($db);

    $ok = $evalModel->eliminarPlantilla($idPlantilla, $idProfesor);

    if ($ok) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Plantilla evaluativa desactivada correctamente"], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "No se pudo desactivar la plantilla"], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    error_log("Error en Evaluaciones/eliminar_plantilla: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al desactivar plantilla"], JSON_UNESCAPED_UNICODE);
}
