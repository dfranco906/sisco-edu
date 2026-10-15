<?php
require_once '../src/config/db.php';
require_once '../src/classes/Usuario.php';

echo "<h2>TEST CREAR USUARIO</h2>";

$db = (new Database())->getConnection();
$usuario = new Usuario($db);

$usuario->nombre = "Admin";
$usuario->apellido = "Principal";
$usuario->usuario = "admin1";
$usuario->email = "admin@test.com";
$usuario->celular = "0981234567";
$usuario->password = "123456";
$usuario->rol = "SuperAdmin";

if($usuario->crear()){
    echo "✅ Usuario creado correctamente";
}else{
    echo "❌ Error al crear usuario";
}
?>