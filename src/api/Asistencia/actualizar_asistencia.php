<?php
require_once '../config/db.php';
require_once '../classes/Asistencia.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$asistencia = new Asistencia($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $asistencia->id_asistencia = $_POST['id_asistencia'] ?? null;
    $asistencia->huella_id = $_POST['huella_id'] ?? null;
    $asistencia->tipo_usuario = $_POST['tipo_usuario'] ?? null;
    $asistencia->estado = $_POST['estado'] ?? null;

    if (!empty($asistencia->id_asistencia)) {
        if ($asistencia->actualizar()) {
            echo json_encode(["message" => "Asistencia actualizada correctamente"]);
        } else {
            echo json_encode(["message" => "Error al actualizar asistencia"]);
        }
    } else {
        echo json_encode(["message" => "ID de asistencia requerido"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>