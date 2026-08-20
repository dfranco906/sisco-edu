<?php
require_once '../src/config/db.php';
require_once '../src/classes/Materia.php';

echo "<h2>TEST UPDATE MATERIA</h2>";

$db = (new Database())->getConnection();
$materia = new Materia($db);

$materia->id_materia = 1;
$materia->nombre = "Matemática Actualizada";
if($materia->actualizar()){
    echo "✅ Materia actualizada";
}else{
    echo "❌ Error";
}
?>
