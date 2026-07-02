<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/AsistenciaEstudiante.php';

$db = (new Database())->getConnection();
$a = new AsistenciaEstudiante($db);

$a->id_asistencia_estudiante = 1;

echo $a->desactivar() ? "✅ Asistencia estudiante desactivada" : "❌ Error";
?>