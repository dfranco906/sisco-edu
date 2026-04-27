<?php
require_once '../src/config/db.php';
require_once '../src/classes/Materia.php';

echo "<h2>TEST CREAR MATERIA</h2>";

$db = (new Database())->getConnection();
$materia = new Materia($db);

$materia->nombre = "Historia";
$materia->descripcion = "Historia Universal";
$materia->carga_horaria_semanal = 3;

if($materia->crear()){
    echo "✅ Materia creada correctamente";
}else{
    echo "❌ Error al crear materia";
}
?>