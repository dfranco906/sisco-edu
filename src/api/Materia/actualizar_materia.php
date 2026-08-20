<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Materia.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$idMateria = filter_var($_POST['id_materia'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim((string) ($_POST['nombre'] ?? ''));

if (!$idMateria || $nombre === '') {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "La materia y su nombre son obligatorios."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $materia = new Materia($db);
    $materia->id_materia = $idMateria;
    $materia->nombre = $nombre;
    $resultado = $materia->actualizar();

    echo json_encode(["success" => (bool) $resultado, "status" => $resultado ? "success" : "error", "message" => $resultado ? "Materia actualizada correctamente." : "No se pudo actualizar la materia.", "data" => $resultado ? ["id_materia" => $idMateria] : null]);
} catch (Throwable $e) {
    error_log('actualizar_materia: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo actualizar la materia."]);
}
?>
