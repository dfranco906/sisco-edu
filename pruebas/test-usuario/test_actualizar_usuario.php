<?php
require_once '../src/config/db.php';
require_once '../src/classes/Usuario.php';

echo "<h2>TEST UPDATE USUARIO</h2>";

$db = (new Database())->getConnection();
$usuario = new Usuario($db);

// Este ID debe existir
$usuario->id_usuario = 1;
$usuario->nombre = "Admin Actualizado";
$usuario->apellido = "Principal";
$usuario->usuario = "admin1";
$usuario->email = "adminupdate@test.com";
$usuario->celular = "0987654321";
$usuario->rol = "SuperAdmin";

if($usuario->actualizar()){
    echo "✅ Usuario actualizado correctamente";
}else{
    echo "❌ Error al actualizar usuario";
}
?>