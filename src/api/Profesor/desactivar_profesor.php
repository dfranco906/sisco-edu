<?php
require_once '../config/db.php';
require_once '../classes/Profesor.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$profesor = new Profesor($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $profesor->id_profesor = $_POST['id_profesor'] ?? null;

    if (!empty($profesor->id_profesor)) {
        if ($profesor->desactivar()) {
            echo json_encode(["message" => "Profesor desactivado correctamente"]);
        } else {
            echo json_encode(["message" => "Error al desactivar profesor"]);
        }
    } else {
        echo json_encode(["message" => "ID de profesor requerido"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>