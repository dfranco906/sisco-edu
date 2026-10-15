<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/AsistenciaEstudiante.php';

$db = (new Database())->getConnection();
$a = new AsistenciaEstudiante($db);

$a->id_estudiante = 2;
$a->huella_id = 2;
$a->fecha = date('Y-m-d');
$a->hora = date('H:i:s');
$a->estado = "PRESENTE";

echo $a->crear() ? "✅ Asistencia estudiante creada" : "❌ Error";
?>