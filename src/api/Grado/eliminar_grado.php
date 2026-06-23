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

$estudiantesAsociados = $grado->contarEstudiantesAsociados();

if ($estudiantesAsociados > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "No se puede eliminar el grado porque tiene estudiantes asociados"
    ]);
    exit;
}

$resultado = $grado->eliminar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Grado eliminado correctamente" : "No se encontro el grado para eliminar"
]);
?>
