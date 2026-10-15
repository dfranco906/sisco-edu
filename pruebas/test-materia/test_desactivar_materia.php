<?php
require_once '../src/config/db.php';
require_once '../src/classes/Materia.php';

echo "<h2>TEST DESACTIVAR MATERIA</h2>";

$db = (new Database())->getConnection();
$materia = new Materia($db);

// Cambiá por un ID real
$materia->id_materia = 1;

if($materia->desactivar()){
    echo "✅ Materia desactivada correctamente";
}else{
    echo "❌ Error al desactivar materia";
}
?>