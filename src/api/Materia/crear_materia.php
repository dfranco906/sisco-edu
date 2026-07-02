<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Materia.php';

$db = (new Database())->getConnection();
$materia = new Materia($db);

$materia->nombre = $_POST['nombre'] ?? null;
$materia->descripcion = $_POST['descripcion'] ?? '';
$materia->carga_horaria_semanal = $_POST['carga_horaria_semanal'] ?? $_POST['carga_horaria'] ?? null;

if (!$materia->nombre || !$materia->carga_horaria_semanal) {
    echo json_encode([
        "status" => "error",
        "message" => "Datos incompletos"
    ]);
    exit;
}

$resultado = $materia->crear();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Materia creada correctamente" : "Error al crear materia"
]);
?>