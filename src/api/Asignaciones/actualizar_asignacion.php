<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Asignacion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $asignacion = new Asignacion($db);

    $id_asignacion = filter_input(INPUT_POST, 'id_asignacion', FILTER_VALIDATE_INT);
    $id_profesor = filter_input(INPUT_POST, 'id_profesor', FILTER_VALIDATE_INT);
    $id_materia = filter_input(INPUT_POST, 'id_materia', FILTER_VALIDATE_INT);
    $id_grado = filter_input(INPUT_POST, 'id_grado', FILTER_VALIDATE_INT);
    $carga_horaria = filter_input(INPUT_POST, 'carga_horaria', FILTER_VALIDATE_INT) ?: 0;
    $anho = filter_input(INPUT_POST, 'año_lectivo', FILTER_VALIDATE_INT)
         ?: (filter_input(INPUT_POST, 'anio_lectivo', FILTER_VALIDATE_INT)
         ?: (filter_input(INPUT_POST, 'anio', FILTER_VALIDATE_INT) ?: (int)date('Y')));

    if (!$id_asignacion || !$id_profesor || !$id_materia || !$id_grado) {
        http_response_code(422);
        echo json_encode([
            "status" => "error",
            "message" => "ID de asignación, Grado, Materia y Profesor son obligatorios."
        ]);
        exit;
    }

    $asignacion->id_asignacion = (int)$id_asignacion;
    $asignacion->id_profesor = (int)$id_profesor;
    $asignacion->id_materia = (int)$id_materia;
    $asignacion->id_grado = (int)$id_grado;
    $asignacion->carga_horaria = (int)$carga_horaria;
    $asignacion->año_lectivo = (int)$anho;

    $resultado = $asignacion->actualizar();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Asignación docente actualizada correctamente." : "Error al actualizar asignación."
    ]);
} catch (Throwable $e) {
    error_log("Error en actualizar_asignacion: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al actualizar la asignación."
    ]);
}
