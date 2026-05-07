<?php
require_once '../src/config/db.php';
require_once '../src/classes/Profesor.php';

echo "<h2>TEST DESACTIVAR PROFESOR</h2>";

$db = (new Database())->getConnection();
$profesor = new Profesor($db);

// Cambiá este ID por uno que exista
$profesor->id_profesor = 1;

if($profesor->desactivar()){
    echo "✅ Profesor desactivado correctamente";
}else{
    echo "❌ Error al desactivar profesor";
}
?>