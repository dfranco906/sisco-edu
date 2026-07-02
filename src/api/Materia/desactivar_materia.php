<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Materia.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metodo no permitido"]);
    exit;
}

$db = (new Database())->getConnection();
$materia = new Materia($db);
$materia->id_materia = $_POST['id_materia'] ?? null;

if (!$materia->id_materia) {
    echo json_encode(["status" => "error", "message" => "ID de materia requerido"]);
    exit;
}

$resultado = $materia->desactivar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Materia desactivada correctamente" : "Error al desactivar materia"
]);
?>
