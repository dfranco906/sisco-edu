<?php
require_once '../config/db.php';
require_once '../classes/Profesor.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$profesor = new Profesor($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $profesor->id_profesor = $_POST['id_profesor'] ?? null;
    $profesor->nombre = $_POST['nombre'] ?? null;
    $profesor->apellido = $_POST['apellido'] ?? null;
    $profesor->cedula_identidad = $_POST['cedula_identidad'] ?? null;
    $profesor->huella_id = $_POST['huella_id'] ?? null;

    if (!empty($profesor->id_profesor)) {

        if ($profesor->actualizar()) {
            echo json_encode(["message" => "Profesor actualizado correctamente"]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Error al actualizar"]);
        }

    } else {
        http_response_code(400);
        echo json_encode(["message" => "ID requerido"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>