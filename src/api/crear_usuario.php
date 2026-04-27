<?php
require_once '../config/db.php';
require_once '../classes/Usuario.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$usuario = new Usuario($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario->nombre = $_POST['nombre'] ?? null;
    $usuario->apellido = $_POST['apellido'] ?? null;
    $usuario->usuario = $_POST['usuario'] ?? null;
    $usuario->email = $_POST['email'] ?? null;
    $usuario->celular = $_POST['celular'] ?? null;
    $usuario->password = $_POST['password'] ?? null;
    $usuario->rol = $_POST['rol'] ?? null;

    if (!empty($usuario->nombre) && !empty($usuario->usuario) && !empty($usuario->password)) {

        if ($usuario->crear()) {
            http_response_code(201);
            echo json_encode(["message" => "Usuario creado correctamente"]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Error al crear usuario"]);
        }

    } else {
        http_response_code(400);
        echo json_encode(["message" => "Datos incompletos"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>