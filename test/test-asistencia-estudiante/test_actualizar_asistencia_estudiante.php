<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/AsistenciaEstudiante.php';

$db = (new Database())->getConnection();
$a = new AsistenciaEstudiante($db);

$a->id_asistencia_estudiante = 1;
$a->id_estudiante = 2;
$a->huella_id = 2;
$a->fecha = date('Y-m-d');
$a->hora = date('H:i:s');
$a->estado = "TARDANZA";

echo $a->actualizar() ? "✅ Asistencia estudiante actualizada" : "❌ Error";
?>