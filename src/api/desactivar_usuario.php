<?php
require_once '../config/db.php';
require_once '../classes/Usuario.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$usuario = new Usuario($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario->id_usuario = $_POST['id_usuario'] ?? null;

    if (!empty($usuario->id_usuario)) {
        if ($usuario->desactivar()) {
            echo json_encode(["message" => "Usuario desactivado correctamente"]);
        } else {
            echo json_encode(["message" => "Error al desactivar usuario"]);
        }
    } else {
        echo json_encode(["message" => "ID de usuario requerido"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>