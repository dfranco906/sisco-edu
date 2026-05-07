<?php
require_once '../src/config/db.php';
require_once '../src/classes/Usuario.php';

echo "<h2>TEST LEER USUARIOS</h2>";

$db = (new Database())->getConnection();
$usuario = new Usuario($db);

$stmt = $usuario->leer();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($data) > 0){
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}else{
    echo "No hay usuarios.";
}
?>