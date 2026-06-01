<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/AsistenciaProfesor.php';

$db = (new Database())->getConnection();
$a = new AsistenciaProfesor($db);

$a->id_asistencia_profesor = $_POST['id_asistencia_profesor'] ?? null;

$resultado = $a->desactivar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Asistencia profesor desactivada" : "Error al desactivar"
]);
?>