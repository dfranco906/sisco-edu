<?php
require_once '../../src/config/db.php';
require_once '../../src/classes/Aula.php';

$db = (new Database())->getConnection();
$aula = new Aula($db);

$aula->nombre = "Aula 1";
$aula->codigo = "AULA_1";
$aula->ubicacion = "Bloque A";

echo $aula->crear() ? "✅ Aula creada" : "❌ Error";
?>