<?php
require_once '../config/db.php';
require_once '../classes/Asignacion.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$asignacion = new Asignacion($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $asignacion->id_asignacion = $_POST['id_asignacion'] ?? null;

    if (!empty($asignacion->id_asignacion)) {

        if ($asignacion->eliminar()) {
            echo json_encode(["message" => "Asignación eliminada correctamente"]);
        } else {
            echo json_encode(["message" => "Error al eliminar asignación"]);
        }

    } else {
        echo json_encode(["message" => "ID de asignación requerido"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>