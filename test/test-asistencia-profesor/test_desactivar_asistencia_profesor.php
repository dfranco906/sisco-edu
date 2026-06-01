<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/AsistenciaProfesor.php';

$db = (new Database())->getConnection();
$a = new AsistenciaProfesor($db);

$a->id_asistencia_profesor = 1;

echo $a->desactivar() ? "✅ Asistencia profesor desactivada" : "❌ Error";
?>