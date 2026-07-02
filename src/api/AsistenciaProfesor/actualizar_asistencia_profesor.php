<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/AsistenciaProfesor.php';

$db = (new Database())->getConnection();
$a = new AsistenciaProfesor($db);

$a->id_asistencia_profesor = $_POST['id_asistencia_profesor'] ?? null;
$a->id_profesor = $_POST['id_profesor'] ?? null;
$a->huella_id = $_POST['huella_id'] ?? null;
$a->fecha = $_POST['fecha'] ?? null;
$a->hora = $_POST['hora'] ?? null;
$a->estado = $_POST['estado'] ?? 'PRESENTE';

$resultado = $a->actualizar();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Asistencia profesor actualizada" : "Error al actualizar"
]);
?>