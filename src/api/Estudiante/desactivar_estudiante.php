<?php
require_once '../config/db.php';
require_once '../classes/Estudiante.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$estudiante = new Estudiante($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $estudiante->id_estudiante = $_POST['id_estudiante'] ?? null;

    if (!empty($estudiante->id_estudiante)) {
        if ($estudiante->desactivar()) {
            echo json_encode(["message" => "Estudiante desactivado correctamente"]);
        } else {
            echo json_encode(["message" => "Error al desactivar estudiante"]);
        }
    } else {
        echo json_encode(["message" => "ID de estudiante requerido"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>