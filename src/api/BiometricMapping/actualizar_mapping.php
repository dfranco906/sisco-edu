<?php
require_once '../../config/db.php';
require_once '../../classes/BiometricMapping.php';

$db = (new Database())->getConnection();
$mapping = new BiometricMapping($db);

$mapping->id_mapping = $_POST['id_mapping'] ?? null;
$mapping->user_id_global = $_POST['user_id_global'] ?? null;
$mapping->id_aula = $_POST['id_aula'] ?? null;
$mapping->sensor_slot = $_POST['sensor_slot'] ?? null;

echo $mapping->actualizar() ? "✅ Mapping actualizado" : "❌ Error";
?>