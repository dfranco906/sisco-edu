<?php
require_once '../src/config/db.php';
require_once '../src/classes/Asistencia.php';

echo "<h2>TEST DELETE ASISTENCIA</h2>";

$db = (new Database())->getConnection();
$asistencia = new Asistencia($db);

// ⚠️ Este ID debe existir y SOLO borra esta asistencia
$asistencia->id_asistencia = 1;

if($asistencia->eliminar()){
    echo "✅ Asistencia eliminada correctamente";
}else{
    echo "❌ Error al eliminar asistencia";
}
?>