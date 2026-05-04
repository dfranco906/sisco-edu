<?php
require_once '../src/config/db.php';
require_once '../src/classes/Asignacion.php';

echo "<h2>TEST DELETE ASIGNACION</h2>";

$db = (new Database())->getConnection();
$asignacion = new Asignacion($db);

// ⚠️ ID debe existir
$asignacion->id_asignacion = 1;

if($asignacion->eliminar()){
    echo "✅ Asignación eliminada correctamente";
}else{
    echo "❌ Error al eliminar asignación";
}
?>