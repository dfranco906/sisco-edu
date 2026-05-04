<?php
require_once '../src/config/db.php';
require_once '../src/classes/Estudiante.php';

echo "<h2>TEST DELETE ESTUDIANTE</h2>";

$db = (new Database())->getConnection();
$estudiante = new Estudiante($db);

// ⚠️ Cambiá este ID por uno que exista
$estudiante->id_estudiante = 1;

if($estudiante->eliminar()){
    echo "✅ Estudiante eliminado correctamente";
}else{
    echo "❌ Error al eliminar estudiante";
}
?>