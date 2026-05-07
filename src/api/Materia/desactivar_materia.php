<?php
require_once '../config/db.php';
require_once '../classes/Materia.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$materia = new Materia($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $materia->id_materia = $_POST['id_materia'] ?? null;

    if (!empty($materia->id_materia)) {
        if ($materia->desactivar()) {
            echo json_encode(["message" => "Materia desactivada correctamente"]);
        } else {
            echo json_encode(["message" => "Error al desactivar materia"]);
        }
    } else {
        echo json_encode(["message" => "ID de materia requerido"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>