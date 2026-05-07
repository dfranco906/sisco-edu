<?php
require_once '../src/config/db.php';
require_once '../src/classes/Horario.php';

echo "<h2>TEST DESACTIVAR HORARIO</h2>";

$db = (new Database())->getConnection();
$horario = new Horario($db);

$horario->id_horario = 18;

if($horario->desactivar()){
    echo "✅ Horario desactivado correctamente";
}else{
    echo "❌ Error al desactivar horario";
}
?>