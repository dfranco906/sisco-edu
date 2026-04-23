<?php
require_once '../src/config/db.php';
require_once '../src/classes/Horario.php';

echo "<h2>TEST CREAR HORARIO</h2>";

$db = (new Database())->getConnection();
$horario = new Horario($db);

// ⚠️ ESTE ID DEBE EXISTIR EN asignacion_docente
$horario->id_asignacion = 2;
$horario->dia_semana = "Martes";
$horario->hora_inicio = "08:00";
$horario->hora_fin = "08:40";
$horario->aula = "B1";

if($horario->crear()){
    echo "✅ Horario creado correctamente";
}else{
    echo "❌ Error al crear horario";
}
?>