<?php
require_once '../src/config/db.php';
require_once '../src/classes/Usuario.php';

echo "<h2>TEST DESACTIVAR USUARIO</h2>";

$db = (new Database())->getConnection();
$usuario = new Usuario($db);

$usuario->id_usuario = 0;

if($usuario->desactivar()){
    echo "✅ Usuario desactivado correctamente";
}else{
    echo "❌ Error al desactivar usuario";
}
?>