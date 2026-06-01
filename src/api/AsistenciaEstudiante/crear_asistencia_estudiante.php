<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/AsistenciaEstudiante.php';

$db = (new Database())->getConnection();
$a = new AsistenciaEstudiante($db);

$a->id_estudiante = $_POST['id_estudiante'] ?? null;
$a->huella_id = $_POST['huella_id'] ?? null;
$a->fecha = $_POST['fecha'] ?? date('Y-m-d');
$a->hora = $_POST['hora'] ?? date('H:i:s');
$a->estado = $_POST['estado'] ?? 'PRESENTE';

$resultado = $a->crear();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Asistencia estudiante creada" : "Error al crear asistencia"
]);
?>