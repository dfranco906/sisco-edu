<?php
require_once '../src/config/db.php';
require_once '../src/classes/Estudiante.php';

echo "<h2>TEST CREAR ESTUDIANTE</h2>";

$db = (new Database())->getConnection();
$estudiante = new Estudiante($db);

// Datos de prueba (cambiá la cédula cada vez para evitar duplicados)
$estudiante->nombre = "Carlos";
$estudiante->apellido = "Gomez";
$estudiante->cedula_identidad = "5555555";
$estudiante->huella_id = 2;

if($estudiante->crear()){
    echo "✅ Estudiante creado correctamente";
}else{
    echo "❌ Error al crear estudiante";
}
?>