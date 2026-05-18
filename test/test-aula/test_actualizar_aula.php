<?php
require_once '../../src/config/db.php';
require_once '../../src/classes/Aula.php';

$db = (new Database())->getConnection();
$aula = new Aula($db);

$aula->id_aula = 1;
$aula->nombre = "Aula 1 Actualizada";
$aula->codigo = "AULA_1";
$aula->ubicacion = "Bloque B";

echo $aula->actualizar() ? "✅ Aula actualizada" : "❌ Error";
?>