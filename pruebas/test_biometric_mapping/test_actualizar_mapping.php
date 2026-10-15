<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/BiometricMapping.php';

$db = (new Database())->getConnection();
$mapping = new BiometricMapping($db);

$mapping->id_mapping = 1;
$mapping->user_id_global = "USER_001";
$mapping->room_id = "AULA_1";
$mapping->sensor_slot = 2;

echo $mapping->actualizar() ? "✅ Mapping actualizado" : "❌ Error";
?>