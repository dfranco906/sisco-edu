<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/AsistenciaEstudiante.php';

$db = (new Database())->getConnection();
$a = new AsistenciaEstudiante($db);

$a->id_asistencia_estudiante = $_POST['id_asistencia_estudiante'] ?? null;

$resultado = $a->desactivar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Asistencia estudiante desactivada" : "Error al desactivar"
]);
?>