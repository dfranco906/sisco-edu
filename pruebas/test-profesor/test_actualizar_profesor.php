<?php
require_once '../src/config/db.php';
require_once '../src/classes/Profesor.php';

echo "<h2>TEST UPDATE PROFESOR</h2>";

$db = (new Database())->getConnection();
$profesor = new Profesor($db);

// ⚠️ ID debe existir en tu BD
$profesor->id_profesor = 1;
$profesor->nombre = "Juan Actualizado";
$profesor->apellido = "Perez Update";
$profesor->cedula_identidad = "9999999";
$profesor->huella_id = 10;

if($profesor->actualizar()){
    echo "✅ Profesor actualizado";
}else{
    echo "❌ Error al actualizar";
}
?>