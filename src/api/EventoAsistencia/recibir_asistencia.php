<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/EventoAsistencia.php';

try {
    $db = (new Database())->getConnection();
    $evento = new EventoAsistencia($db);

    $evento->user_id_global = $_POST['user_id_global'] ?? null;
    $evento->id_aula = filter_input(INPUT_POST, 'id_aula', FILTER_VALIDATE_INT) ?: null;
    $evento->estado = strtoupper(trim($_POST['estado'] ?? 'PRESENTE'));
    $evento->timestamp_evento = $_POST['timestamp_evento'] ?? date('Y-m-d H:i:s');
    $evento->origen_node_id = trim($_POST['origen_node_id'] ?? 'MANUAL');
    $evento->sincronizado = filter_input(INPUT_POST, 'sincronizado', FILTER_VALIDATE_INT) ?? 1;

    if (!$evento->user_id_global || !$evento->id_aula) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Faltan datos obligatorios (user_id_global, id_aula)"
        ]);
        exit;
    }

    if ($evento->crear()) {
        echo json_encode([
            "status" => "success",
            "message" => "Evento de asistencia registrado correctamente"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Error al guardar el evento de asistencia"
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno del servidor",
        "debug" => $e->getMessage()
    ]);
}
