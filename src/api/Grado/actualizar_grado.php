<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Grado.php';

$db = (new Database())->getConnection();
$grado = new Grado($db);

$grado->id_grado = !empty($_POST["id_grado"]) ? (int)$_POST["id_grado"] : null;
$grado->nombre = $_POST["nombre"] ?? null;
$grado->id_aula = !empty($_POST["id_aula"]) ? (int)$_POST["id_aula"] : null;
$grado->id_turno = !empty($_POST["id_turno"]) ? (int)$_POST["id_turno"] : null;
$grado->id_nivel = !empty($_POST["id_nivel"]) ? (int)$_POST["id_nivel"] : null;
$grado->id_bachillerato = !empty($_POST["id_bachillerato"]) ? (int)$_POST["id_bachillerato"] : null;

if (!$grado->id_grado || !$grado->nombre || !$grado->id_aula) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Datos incompletos para actualizar el grado."]);
    exit;
}

$resultado = $grado->actualizar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Grado actualizado correctamente" : "Error al actualizar grado"
]);
