<?php
require_once '../config/db.php';
require_once '../classes/BiometricMapping.php';

$db = (new Database())->getConnection();
$mapping = new BiometricMapping($db);

$mapping->id_mapping = $_POST['id_mapping'] ?? null;

echo $mapping->desactivar() ? "✅ Mapping desactivado" : "❌ Error";
?>