<?php
require_once '../src/config/db.php';
require_once '../src/classes/Asignacion.php';

echo "<h2>TEST CREAR ASIGNACION</h2>";

$db = (new Database())->getConnection();
$asignacion = new Asignacion($db);

// ⚠️ Estos IDs deben existir en tu BD
$asignacion->id_profesor = 1;
$asignacion->id_materia = 1;
$asignacion->año_lectivo = 2026;

if($asignacion->crear()){
    echo "✅ Asignación creada correctamente";
}else{
    echo "❌ Error al crear asignación";
}
?>