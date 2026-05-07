<?php
require_once '../config/db.php';
require_once '../classes/Usuario.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$usuario = new Usuario($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario->id_usuario = $_POST['id_usuario'] ?? null;
    $usuario->nombre = $_POST['nombre'] ?? null;
    $usuario->apellido = $_POST['apellido'] ?? null;
    $usuario->usuario = $_POST['usuario'] ?? null;
    $usuario->email = $_POST['email'] ?? null;
    $usuario->celular = $_POST['celular'] ?? null;
    $usuario->rol = $_POST['rol'] ?? null;

    if (!empty($usuario->id_usuario)) {
        if ($usuario->actualizar()) {
            echo json_encode(["message" => "Usuario actualizado correctamente"]);
        } else {
            echo json_encode(["message" => "Error al actualizar usuario"]);
        }
    } else {
        echo json_encode(["message" => "ID de usuario requerido"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>