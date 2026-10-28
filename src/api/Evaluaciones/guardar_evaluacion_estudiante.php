<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/EvaluacionMec.php';

$idClase = filter_input(INPUT_POST, 'id_clase', FILTER_VALIDATE_INT);
$idEstudiante = filter_input(INPUT_POST, 'id_estudiante', FILTER_VALIDATE_INT);
$idPlantilla = filter_input(INPUT_POST, 'id_plantilla', FILTER_VALIDATE_INT) ?: null;
$idInstrumento = filter_input(INPUT_POST, 'id_instrumento', FILTER_VALIDATE_INT) ?: null;
$puntajeObtenido = filter_input(INPUT_POST, 'puntaje_obtenido', FILTER_VALIDATE_FLOAT);
$puntajeMaximo = filter_input(INPUT_POST, 'puntaje_maximo', FILTER_VALIDATE_FLOAT) ?: 1.0;
$detallesRaw = $_POST['detalles_criterios'] ?? '[]';
$observaciones = trim($_POST['observaciones'] ?? '');

if (!$idClase || !$idEstudiante || $puntajeObtenido === false) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Faltan parámetros requeridos (id_clase, id_estudiante, puntaje_obtenido)"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$detalles = [];
if (is_array($detallesRaw)) {
    $detalles = $detallesRaw;
} elseif (is_string($detallesRaw)) {
    $detalles = json_decode($detallesRaw, true) ?: [];
}

try {
    $db = (new Database())->getConnection();
    $evalModel = new EvaluacionMec($db);

    $ok = $evalModel->guardarEvaluacionEstudiante(
        $idClase,
        $idEstudiante,
        $idPlantilla,
        $idInstrumento,
        (float)$puntajeObtenido,
        (float)$puntajeMaximo,
        $detalles,
        $observaciones ?: null
    );

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Evaluación individual del estudiante guardada correctamente",
        "data" => [
            "id_clase" => $idClase,
            "id_estudiante" => $idEstudiante,
            "puntaje_obtenido" => $puntajeObtenido,
            "puntaje_maximo" => $puntajeMaximo
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error en Evaluaciones/guardar_evaluacion_estudiante: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al guardar evaluación del estudiante"
    ], JSON_UNESCAPED_UNICODE);
}
