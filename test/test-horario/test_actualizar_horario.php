<?php
require_once '../src/config/db.php';
require_once '../src/classes/Horario.php';

echo "<h2>TEST UPDATE HORARIO</h2>";

$db = (new Database())->getConnection();
$horario = new Horario($db);

// IDs deben existir en tu BD
$horario->id_horario = 1;
$horario->id_asignacion = 1;
$horario->dia_semana = "Miércoles";
$horario->hora_inicio = "09:00";
$horario->hora_fin = "09:40";
$horario->aula = "A2";

if($horario->actualizar()){
    echo "✅ Horario actualizado correctamente";
}else{
    echo "❌ Error al actualizar horario";
}
?>