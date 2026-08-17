<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Grado.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$idGrado = filter_var($_POST['id_grado'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$idAula = filter_var($_POST['id_aula'] ?? null, FILTER_VALIDATE_INT);

if (!$idGrado || $nombre === '' || !$idAula) {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "Grado, nombre y aula son obligatorios."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $grado = new Grado($db);
    $grado->id_grado = $idGrado;
    $grado->nombre = $nombre;
    $grado->id_aula = $idAula;

    if (!$grado->aulaActivaExiste()) {
        http_response_code(422);
        echo json_encode(["success" => false, "status" => "error", "message" => "El aula seleccionada no está activa."]);
        exit;
    }

    $resultado = $grado->actualizar();
    echo json_encode(["success" => (bool) $resultado, "status" => $resultado ? "success" : "error", "message" => $resultado ? "Grado actualizado correctamente." : "No se pudo actualizar el grado.", "data" => $resultado ? ["id_grado" => $idGrado] : null]);
} catch (Throwable $e) {
    error_log('actualizar_grado: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo actualizar el grado."]);
}
?>
