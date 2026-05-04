<?php
require_once '../src/config/db.php';
require_once '../src/classes/Horario.php';

echo "<h2>TEST DELETE HORARIO</h2>";

$db = (new Database())->getConnection();
$horario = new Horario($db);

// ⚠️ Usar un ID que exista
$horario->id_horario = 1;

if($horario->eliminar()){
    echo "✅ Horario eliminado correctamente";
}else{
    echo "❌ Error al eliminar horario";
}
?>