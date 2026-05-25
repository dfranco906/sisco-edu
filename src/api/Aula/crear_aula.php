<?php
require_once '../../config/db.php';
require_once '../../classes/Aula.php';

$db = (new Database())->getConnection();
$aula = new Aula($db);

$aula->nombre = $_POST['nombre'] ?? null;
$aula->codigo = $_POST['codigo'] ?? null;
$aula->ubicacion = $_POST['ubicacion'] ?? null;

if ($aula->crear()) {
    echo "✅ Aula creada correctamente";
} else {
    echo "❌ Error al crear aula";
}
?>