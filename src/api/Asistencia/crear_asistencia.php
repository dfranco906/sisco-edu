<?php
require_once '../../config/db.php';
require_once '../../classes/Asistencia.php';

header("Content-Type: application/json; charset=UTF-8");

$database = new Database();
$db = $database->getConnection();
$asistencia = new Asistencia($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $asistencia->user_id_global = $_POST['user_id_global'] ?? null;
    $asistencia->id_aula = $_POST['id_aula'] ?? null;
    $asistencia->tipo_usuario = $_POST['tipo_usuario'] ?? null;
    $asistencia->estado = $_POST['estado'] ?? 'Presente';
    $asistencia->fecha_hora = $_POST['fecha_hora'] ?? date('c');

    if (!empty($asistencia->user_id_global) && !empty($asistencia->tipo_usuario) && !empty($asistencia->id_aula)) {

        if ($asistencia->crear()) {
            http_response_code(201);
            echo json_encode(["message" => "Asistencia registrada."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Error al registrar asistencia."]);
        }

    } else {
        http_response_code(400);
        echo json_encode(["message" => "Datos incompletos."]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido."]);
}
?>