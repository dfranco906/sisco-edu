<?php
require_once '../config/db.php';
require_once '../classes/Aula.php';

$db = (new Database())->getConnection();
$aula = new Aula($db);

$aula->id_aula = $_POST['id_aula'] ?? null;

if ($aula->desactivar()) {
    echo "✅ Aula desactivada correctamente";
} else {
    echo "❌ Error al desactivar aula";
}
?>