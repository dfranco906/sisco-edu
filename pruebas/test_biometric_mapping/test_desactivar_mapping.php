<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/BiometricMapping.php';

$db = (new Database())->getConnection();
$mapping = new BiometricMapping($db);

$mapping->id_mapping = 1;

echo $mapping->desactivar() ? "✅ Mapping desactivado" : "❌ Error";
?>