<?php
require_once '../src/config/db.php';
require_once '../src/classes/Asignacion.php';

echo "<h2>TEST DESACTIVAR ASIGNACION</h2>";

$db = (new Database())->getConnection();
$asignacion = new Asignacion($db);

$asignacion->id_asignacion = 2;

if($asignacion->desactivar()){
    echo "✅ Asignación desactivada correctamente";
}else{
    echo "❌ Error al desactivar asignación";
}
?>