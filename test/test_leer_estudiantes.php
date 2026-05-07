<?php
require_once '../src/config/db.php';
require_once '../src/classes/Estudiante.php';

echo "<h2>TEST LEER ESTUDIANTES</h2>";

$db = (new Database())->getConnection();
$estudiante = new Estudiante($db);

$stmt = $estudiante->leer();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($data) > 0){
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}else{
    echo "No hay estudiantes cargados.";
}
?>