<?php
require_once '../config/db.php';
require_once '../classes/Horario.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$horario = new Horario($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $horario->id_horario = $_POST['id_horario'] ?? null;
    $horario->id_asignacion = $_POST['id_asignacion'] ?? null;
    $horario->dia_semana = $_POST['dia_semana'] ?? null;
    $horario->hora_inicio = $_POST['hora_inicio'] ?? null;
    $horario->hora_fin = $_POST['hora_fin'] ?? null;
    $horario->aula = $_POST['aula'] ?? null;

    if (!empty($horario->id_horario)) {
        if ($horario->actualizar()) {
            echo json_encode(["message" => "Horario actualizado correctamente"]);
        } else {
            echo json_encode(["message" => "Error al actualizar horario"]);
        }
    } else {
        echo json_encode(["message" => "ID de horario requerido"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>