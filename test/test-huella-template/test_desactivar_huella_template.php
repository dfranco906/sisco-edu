<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/HuellaTemplate.php';

$db = (new Database())->getConnection();
$huella = new HuellaTemplate($db);

$huella->id_huella = 1;

echo $huella->desactivar() ? "✅ Huella desactivada" : "❌ Error";
?>