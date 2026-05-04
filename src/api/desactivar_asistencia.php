<?php
require_once '../config/db.php';
require_once '../classes/Asistencia.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$asistencia = new Asistencia($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $asistencia->id_asistencia = $_POST['id_asistencia'] ?? null;

    if (!empty($asistencia->id_asistencia)) {
        if ($asistencia->desactivar()) {
            echo json_encode(["message" => "Asistencia desactivada correctamente"]);
        } else {
            echo json_encode(["message" => "Error al desactivar asistencia"]);
        }
    } else {
        echo json_encode(["message" => "ID de asistencia requerido"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>