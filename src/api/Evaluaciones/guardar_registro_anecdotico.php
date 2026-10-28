<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/EvaluacionMec.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$idClase = filter_input(INPUT_POST, 'id_clase', FILTER_VALIDATE_INT);
$idEstudiante = filter_input(INPUT_POST, 'id_estudiante', FILTER_VALIDATE_INT);
$idAsistencia = filter_input(INPUT_POST, 'id_asistencia_estudiante', FILTER_VALIDATE_INT) ?: null;
$observacion = trim($_POST['observacion'] ?? '');
$createdBy = !empty($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : null;

if (!$idClase || !$idEstudiante || empty($observacion)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Faltan parámetros requeridos (id_clase, id_estudiante, observacion)"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $evalModel = new EvaluacionMec($db);

    $idRegistro = $evalModel->guardarRegistroAnecdotico(
        $idClase,
        $idEstudiante,
        $observacion,
        $createdBy,
        $idAsistencia
    );

    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Registro anecdotario asentado exitosamente en la sesión",
        "data" => [
            "id_registro" => $idRegistro,
            "id_clase" => $idClase,
            "id_estudiante" => $idEstudiante
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error en Evaluaciones/guardar_registro_anecdotico: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al guardar registro anecdotario"
    ], JSON_UNESCAPED_UNICODE);
}
