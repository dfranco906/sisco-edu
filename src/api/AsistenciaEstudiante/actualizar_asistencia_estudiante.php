<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/AsistenciaEstudiante.php';

$db = (new Database())->getConnection();
$a = new AsistenciaEstudiante($db);

$a->id_asistencia_estudiante = $_POST['id_asistencia_estudiante'] ?? null;
$a->id_estudiante = $_POST['id_estudiante'] ?? null;
$a->huella_id = $_POST['huella_id'] ?? null;
$a->fecha = $_POST['fecha'] ?? null;
$a->hora = $_POST['hora'] ?? null;
$a->estado = $_POST['estado'] ?? 'PRESENTE';

$resultado = $a->actualizar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Asistencia estudiante actualizada" : "Error al actualizar"
]);
?>