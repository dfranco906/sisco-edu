<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Materia.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));

if ($nombre === '') {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "El nombre de la materia es obligatorio."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $materia = new Materia($db);
    $materia->nombre = $nombre;
    $resultado = $materia->crear();
    if (!$resultado) throw new RuntimeException('La inserción no se completó.');

    $idMateria = (int) $db->lastInsertId();
    http_response_code(201);
    echo json_encode(["success" => true, "status" => "success", "message" => "Materia creada correctamente.", "data" => ["id_materia" => $idMateria]]);
} catch (Throwable $e) {
    error_log('crear_materia: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo crear la materia."]);
}
?>
