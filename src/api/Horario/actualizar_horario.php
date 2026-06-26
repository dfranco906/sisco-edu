<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Horario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metodo no permitido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $horario = new Horario($db);

    $horario->id_horario = $_POST['id_horario'] ?? null;
    $horario->id_asignacion = $_POST['id_asignacion'] ?? null;
    $horario->grado = $_POST['grado'] ?? null;
    $horario->dia_semana = $_POST['dia_semana'] ?? null;
    $horario->hora_inicio = $_POST['hora_inicio'] ?? null;
    $horario->hora_fin = $_POST['hora_fin'] ?? null;
    $horario->aula = $_POST['aula'] ?? null;

    if (!$horario->id_horario) {
        echo json_encode(["status" => "error", "message" => "ID de horario requerido"]);
        exit;
    }

    $resultado = $horario->actualizar();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Horario actualizado correctamente" : "Error al actualizar horario"
    ]);
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Error interno", "debug" => $e->getMessage()]);
}
?>
