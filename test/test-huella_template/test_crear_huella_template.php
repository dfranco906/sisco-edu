<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/HuellaTemplate.php';

$db = (new Database())->getConnection();
$huella = new HuellaTemplate($db);

$huella->user_id_global = "USER_001";
$huella->id_estudiante = 1;
$huella->fingerprint_data = "A1B2C3D4E5F6_TEST_HEX";
$huella->formato = "HEX";

echo $huella->crear() ? "✅ Huella creada" : "❌ Error";
?>