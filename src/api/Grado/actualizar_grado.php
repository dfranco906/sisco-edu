<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Grado.php';

$db = (new Database())->getConnection();
$grado = new Grado($db);

$grado->id_grado = $_POST["id_grado"] ?? null;
$grado->nombre = $_POST["nombre"] ?? null;
$grado->id_aula = $_POST["id_aula"] ?? null;

if (!$grado->id_grado || !$grado->nombre || !$grado->id_aula) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    exit;
}

$resultado = $grado->actualizar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Grado actualizado correctamente" : "Error al actualizar grado"
]);
?>