<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/EvaluacionMec.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$idProfesor = null;
if (!empty($_SESSION['id_profesor'])) {
    $idProfesor = (int)$_SESSION['id_profesor'];
} elseif (isset($_GET['id_profesor']) && is_numeric($_GET['id_profesor'])) {
    $idProfesor = (int)$_GET['id_profesor'];
}

$idInstrumento = isset($_GET['id_instrumento']) && is_numeric($_GET['id_instrumento']) ? (int)$_GET['id_instrumento'] : null;

try {
    $db = (new Database())->getConnection();
    $evalModel = new EvaluacionMec($db);

    $plantillas = $evalModel->obtenerPlantillas($idProfesor, $idInstrumento);

    // Obtener catálogo base de procedimientos e instrumentos
    $stmtProc = $db->query("SELECT id_procedimiento, nombre FROM procedimientos_evaluativos WHERE activo = 1 ORDER BY id_procedimiento ASC");
    $procedimientos = $stmtProc->fetchAll(PDO::FETCH_ASSOC);

    $stmtInst = $db->query("SELECT id_instrumento, nombre FROM instrumentos_evaluativos WHERE activo = 1 ORDER BY id_instrumento ASC");
    $instrumentos = $stmtInst->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Plantillas evaluativas obtenidas correctamente",
        "total" => count($plantillas),
        "data" => [
            "plantillas" => $plantillas,
            "procedimientos" => $procedimientos,
            "instrumentos" => $instrumentos
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error en Evaluaciones/leer_plantillas: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al obtener plantillas evaluativas"
    ], JSON_UNESCAPED_UNICODE);
}
