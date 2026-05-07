<?php
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../classes/Usuario.php';

$db = (new Database())->getConnection();
$usuario = new Usuario($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $usuario->leer();
    $num = $stmt->rowCount();

    if ($num > 0) {

        $usuarios_arr = array();
        $usuarios_arr["data"] = array();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);

            $item = array(
                "id_usuario" => $id_usuario,
                "nombre" => $nombre,
                "apellido" => $apellido,
                "usuario" => $usuario,
                "email" => $email,
                "celular" => $celular,
                "rol" => $rol,
                "fecha_creacion" => $fecha_creacion
            );

            array_push($usuarios_arr["data"], $item);
        }

        http_response_code(200);
        echo json_encode($usuarios_arr);

    } else {
        http_response_code(404);
        echo json_encode(["message" => "No hay usuarios"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>