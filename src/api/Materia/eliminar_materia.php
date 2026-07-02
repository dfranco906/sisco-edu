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

if ($materia->contarDependencias() > 0) {
    echo json_encode(["status" => "error", "message" => "No se puede eliminar la materia porque tiene asignaciones asociadas"]);
    exit;
}

$resultado = $materia->eliminar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Materia eliminada correctamente" : "No se encontro la materia para eliminar"
]);
?>
