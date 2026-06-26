<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/AsistenciaProfesor.php';

$db = (new Database())->getConnection();
$a = new AsistenciaProfesor($db);

$a->id_asistencia_profesor = 1;
$a->id_profesor = 1;
$a->huella_id = 1;
$a->fecha = date('Y-m-d');
$a->hora = date('H:i:s');
$a->estado = "TARDANZA";

echo $a->actualizar() ? "✅ Asistencia profesor actualizada" : "❌ Error";
?>