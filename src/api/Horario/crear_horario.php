<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Horario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

$id_asignacion = $_POST['id_asignacion'] ?? null;
$id_grado = $_POST['id_grado'] ?? null;
$dia_semana = trim((string) ($_POST['dia_semana'] ?? ''));
$hora_inicio = trim((string) ($_POST['hora_inicio'] ?? ''));
$hora_fin = trim((string) ($_POST['hora_fin'] ?? ''));

if (!$id_asignacion || !$id_grado || $dia_semana === '' || $hora_inicio === '' || $hora_fin === '') {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Asignación, grado, día y horas son obligatorios."]);
    exit;
}

$diasValidos = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
if (!in_array($dia_semana, $diasValidos, true)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "El día seleccionado no es válido."]);
    exit;
}

$formatoHoraValido = static function ($hora) {
    return preg_match('/^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $hora) === 1;
};

if (!$formatoHoraValido($hora_inicio) || !$formatoHoraValido($hora_fin)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Las horas indicadas no son válidas."]);
    exit;
}

if (strtotime($hora_fin) <= strtotime($hora_inicio)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "La hora fin debe ser mayor que la hora inicio."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $horario = new Horario($db);

    if (!$horario->asignacionActivaExiste($id_asignacion)) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "La asignación seleccionada no es válida."]);
        exit;
    }

    $grado = $horario->obtenerGradoActivo($id_grado);
    if (!$grado || !$grado['id_aula']) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "El grado seleccionado no tiene aula asignada."]);
        exit;
    }

    $horario->id_asignacion = $id_asignacion;
    $horario->id_grado = $id_grado;
    $horario->dia_semana = $dia_semana;
    $horario->hora_inicio = $hora_inicio;
    $horario->hora_fin = $hora_fin;
    $horario->id_aula = $grado['id_aula'];

    $resultado = $horario->crear();
    $id_horario = $resultado ? (int) $db->lastInsertId() : null;

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Horario creado correctamente" : "Error al crear horario",
        "id_horario" => $id_horario,
        "id_aula" => $resultado ? $grado['id_aula'] : null
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al crear el horario."]);
}
