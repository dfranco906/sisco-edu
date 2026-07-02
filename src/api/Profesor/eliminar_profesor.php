<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Profesor.php';

$db = (new Database())->getConnection();
$profesor = new Profesor($db);
$profesor->id_profesor = $_POST['id_profesor'] ?? null;

if (!$profesor->id_profesor) {
    echo json_encode(["status" => "error", "message" => "ID de profesor requerido"]);
    exit;
}

if ($profesor->contarDependencias() > 0) {
    echo json_encode(["status" => "error", "message" => "No se puede eliminar el profesor porque tiene registros asociados"]);
    exit;
}

$resultado = $profesor->eliminar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Profesor eliminado correctamente" : "No se encontro el profesor para eliminar"
]);
?>
