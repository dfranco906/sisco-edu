<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Materia.php';

$db = (new Database())->getConnection();
$materia = new Materia($db);
$materia->id_materia = $_POST['id_materia'] ?? null;

if (!$materia->id_materia) {
    echo json_encode(["status" => "error", "message" => "ID de materia requerido"]);
    exit;
}

$resultado = $materia->restaurar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Materia restaurada correctamente" : "Error al restaurar materia"
]);
?>
