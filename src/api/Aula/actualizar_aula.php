<?php
require_once '../../config/db.php';
require_once '../../classes/Aula.php';

$db = (new Database())->getConnection();
$aula = new Aula($db);

$aula->id_aula = $_POST['id_aula'] ?? null;
$aula->nombre = $_POST['nombre'] ?? null;
$aula->codigo = $_POST['codigo'] ?? null;
$aula->ubicacion = $_POST['ubicacion'] ?? null;

if ($aula->actualizar()) {
    echo "✅ Aula actualizada correctamente";
} else {
    echo "❌ Error al actualizar aula";
}
?>