<?php
require_once '../src/config/db.php';
require_once '../src/classes/Asistencia.php';

echo "<h2>TEST UPDATE ASISTENCIA</h2>";

$db = (new Database())->getConnection();
$asistencia = new Asistencia($db);

// Este ID debe existir
$asistencia->id_asistencia = 1;
$asistencia->huella_id = 1;
$asistencia->tipo_usuario = "Profesor";
$asistencia->estado = "Llegada Tardia";

if($asistencia->actualizar()){
    echo "✅ Asistencia actualizada correctamente";
}else{
    echo "❌ Error al actualizar asistencia";
}
?>