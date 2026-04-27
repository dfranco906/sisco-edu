<?php
require_once '../src/config/db.php';
require_once '../src/classes/Estudiante.php';

echo "<h2>TEST UPDATE ESTUDIANTE</h2>";

$db = (new Database())->getConnection();
$estudiante = new Estudiante($db);

$estudiante->id_estudiante = 1;
$estudiante->nombre = "Carlos Update";
$estudiante->apellido = "Gomez";
$estudiante->cedula_identidad = "8888888";
$estudiante->huella_id = 5;

if($estudiante->actualizar()){
    echo "✅ Actualizado correctamente";
}else{
    echo "❌ Error";
}
?>