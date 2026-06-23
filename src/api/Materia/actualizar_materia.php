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
$materia->nombre = $_POST['nombre'] ?? null;
$materia->descripcion = $_POST['descripcion'] ?? null;
$materia->carga_horaria_semanal = $_POST['carga_horaria_semanal'] ?? null;

if (!$materia->id_materia || !$materia->nombre || !$materia->carga_horaria_semanal) {
    echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
    exit;
}

$resultado = $materia->actualizar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Materia actualizada correctamente" : "Error al actualizar materia"
]);
?>
