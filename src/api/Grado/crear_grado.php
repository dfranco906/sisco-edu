<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Grado.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$idAula = filter_var($_POST['id_aula'] ?? null, FILTER_VALIDATE_INT);

if ($nombre === '' || !$idAula) {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "Nombre y aula son obligatorios."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $grado = new Grado($db);
    $grado->nombre = $nombre;
    $grado->id_aula = $idAula;

    if (!$grado->aulaActivaExiste()) {
        http_response_code(422);
        echo json_encode(["success" => false, "status" => "error", "message" => "El aula seleccionada no está activa."]);
        exit;
    }

    $resultado = $grado->crear();
    if (!$resultado) throw new RuntimeException('La inserción no se completó.');
    $idGrado = (int) $db->lastInsertId();
    http_response_code(201);
    echo json_encode(["success" => true, "status" => "success", "message" => "Grado creado correctamente.", "data" => ["id_grado" => $idGrado]]);
} catch (Throwable $e) {
    error_log('crear_grado: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo crear el grado."]);
}
?>
