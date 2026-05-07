<?php
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../classes/Estudiante.php';

$db = (new Database())->getConnection();
$estudiante = new Estudiante($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $estudiante->leer();
    $num = $stmt->rowCount();

    if ($num > 0) {
        $data = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);

            $data[] = [
                "id_estudiante" => $id_estudiante,
                "nombre" => $nombre,
                "apellido" => $apellido,
                "cedula_identidad" => $cedula_identidad,
                "huella_id" => $huella_id,
                "fecha_registro" => $fecha_registro
            ];
        }

        http_response_code(200);
        echo json_encode(["data" => $data]);

    } else {
        http_response_code(404);
        echo json_encode(["message" => "No hay estudiantes"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>