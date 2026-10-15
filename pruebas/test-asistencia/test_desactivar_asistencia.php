<?php
require_once '../src/config/db.php';
require_once '../src/classes/Asistencia.php';

echo "<h2>TEST DESACTIVAR ASISTENCIA</h2>";

$db = (new Database())->getConnection();
$asistencia = new Asistencia($db);

// Cambiá este ID por uno que exista
$asistencia->id_asistencia = 2;

if($asistencia->desactivar()){
    echo "✅ Asistencia desactivada correctamente";
}else{
    echo "❌ Error al desactivar asistencia";
}
?>