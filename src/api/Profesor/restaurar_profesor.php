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

$resultado = $profesor->restaurar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Profesor restaurado correctamente" : "Error al restaurar profesor"
]);
?>
