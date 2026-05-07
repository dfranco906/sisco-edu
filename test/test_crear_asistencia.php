<?php
require_once '../src/config/db.php';
require_once '../src/classes/Asistencia.php';

echo "<h2>TEST CREAR ASISTENCIA</h2>";

$db = (new Database())->getConnection();
$asistencia = new Asistencia($db);

$asistencia->huella_id = 1;
$asistencia->tipo_usuario = "Profesor"; // o Estudiante
$asistencia->estado = "Presente";

if($asistencia->crear()){
    echo "✅ Asistencia creada correctamente";
}else{
    echo "❌ Error al crear asistencia";
}
?>