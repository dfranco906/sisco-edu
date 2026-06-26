<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Grado.php';

$db = (new Database())->getConnection();
$grado = new Grado($db);

$grado->id_grado = $_POST["id_grado"] ?? null;

if (!$grado->id_grado) {
    echo json_encode(["status" => "error", "message" => "Falta id_grado"]);
    exit;
}

$resultado = $grado->desactivar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Grado desactivado correctamente" : "Error al desactivar grado"
]);
?>