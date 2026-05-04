<?php
require_once '../config/db.php';
require_once '../classes/Horario.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$horario = new Horario($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $horario->id_horario = $_POST['id_horario'] ?? null;

    if (!empty($horario->id_horario)) {
        if ($horario->desactivar()) {
            echo json_encode(["message" => "Horario desactivado correctamente"]);
        } else {
            echo json_encode(["message" => "Error al desactivar horario"]);
        }
    } else {
        echo json_encode(["message" => "ID de horario requerido"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>