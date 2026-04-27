<?php
require_once '../config/db.php';
require_once '../classes/Estudiante.php';

header("Content-Type: application/json; charset=UTF-8");

$database = new Database();
$db = $database->getConnection();
$estudiante = new Estudiante($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $estudiante->nombre = $_POST['nombre'] ?? null;
    $estudiante->apellido = $_POST['apellido'] ?? null;
    $estudiante->cedula_identidad = $_POST['cedula_identidad'] ?? null;
    $estudiante->huella_id = $_POST['huella_id'] ?? null;

    if (!empty($estudiante->nombre) && !empty($estudiante->apellido) && !empty($estudiante->cedula_identidad)) {

        if ($estudiante->crear()) {
            http_response_code(201);
            echo json_encode(["message" => "Estudiante creado correctamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Error al crear estudiante."]);
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