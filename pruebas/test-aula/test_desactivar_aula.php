<?php
require_once '../../src/config/db.php';
require_once '../../src/classes/Aula.php';

$db = (new Database())->getConnection();
$aula = new Aula($db);

$aula->id_aula = 1;

echo $aula->desactivar() ? "✅ Aula desactivada" : "❌ Error";
?>