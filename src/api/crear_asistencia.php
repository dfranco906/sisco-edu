<?php
require_once '../config/db.php';
require_once '../classes/Asistencia.php';

header("Content-Type: application/json; charset=UTF-8");

$database = new Database();
$db = $database->getConnection();
$asistencia = new Asistencia($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $asistencia->huella_id = $_POST['huella_id'] ?? null;
    $asistencia->tipo_usuario = $_POST['tipo_usuario'] ?? null;
    $asistencia->estado = $_POST['estado'] ?? 'Presente';

    if (!empty($asistencia->huella_id) && !empty($asistencia->tipo_usuario)) {

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