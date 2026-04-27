<?php
header("Content-Type: application/json; charset=UTF-8");

require_once '../config/db.php';
require_once '../classes/Asistencia.php';

$db = (new Database())->getConnection();
$asistencia = new Asistencia($db);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $asistencia->leer();
    $num = $stmt->rowCount();

    if ($num > 0) {
        $data = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);

            $data[] = [
                "id_asistencia" => $id_asistencia,
                "huella_id" => $huella_id,
                "tipo_usuario" => $tipo_usuario,
                "fecha_hora" => $fecha_hora,
                "estado" => $estado
            ];
        }

        http_response_code(200);
        echo json_encode(["data" => $data]);

    } else {
        http_response_code(404);
        echo json_encode(["message" => "No hay asistencias"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>