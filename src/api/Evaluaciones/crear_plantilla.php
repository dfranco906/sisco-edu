<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/EvaluacionMec.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$idProcedimiento = filter_input(INPUT_POST, 'id_procedimiento', FILTER_VALIDATE_INT);
$idInstrumento = filter_input(INPUT_POST, 'id_instrumento', FILTER_VALIDATE_INT);
$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$criteriosRaw = $_POST['criterios'] ?? '';
$puntajeTotal = filter_input(INPUT_POST, 'puntaje_total', FILTER_VALIDATE_FLOAT) ?: 5.0;

$idProfesor = !empty($_SESSION['id_profesor']) ? (int)$_SESSION['id_profesor'] : null;
if (!$idProfesor && !empty($_POST['id_profesor'])) {
    $idProfesor = (int)$_POST['id_profesor'];
}

if (!$idProcedimiento || !$idInstrumento || empty($titulo)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Faltan campos requeridos (id_procedimiento, id_instrumento, titulo)"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$criterios = [];
if (is_array($criteriosRaw)) {
    $criterios = $criteriosRaw;
} elseif (is_string($criteriosRaw) && !empty($criteriosRaw)) {
    $criterios = json_decode($criteriosRaw, true) ?: [];
}

try {
    $db = (new Database())->getConnection();
    $evalModel = new EvaluacionMec($db);

    $idNueva = $evalModel->crearPlantilla(
        $idProcedimiento,
        $idInstrumento,
        $titulo,
        $descripcion ?: null,
        $criterios,
        (float)$puntajeTotal,
        $idProfesor
    );

    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Plantilla de instrumento evaluativo creada y guardada exitosamente",
        "data" => [
            "id_plantilla" => $idNueva,
            "titulo" => $titulo,
            "puntaje_total" => $puntajeTotal
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error en Evaluaciones/crear_plantilla: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al guardar plantilla"
    ], JSON_UNESCAPED_UNICODE);
}
