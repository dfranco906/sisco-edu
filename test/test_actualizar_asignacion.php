<?php
require_once '../src/config/db.php';
require_once '../src/classes/Asignacion.php';

echo "<h2>TEST UPDATE ASIGNACION</h2>";

$db = (new Database())->getConnection();
$asignacion = new Asignacion($db);

// Estos IDs deben existir
$asignacion->id_asignacion = 1;
$asignacion->id_profesor = 1;
$asignacion->id_materia = 1;
$asignacion->año_lectivo = 2026;

if($asignacion->actualizar()){
    echo "✅ Asignación actualizada correctamente";
}else{
    echo "❌ Error al actualizar asignación";
}
?>