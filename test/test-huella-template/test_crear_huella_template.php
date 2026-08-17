<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/HuellaTemplate.php';

$db = (new Database())->getConnection();
$huella = new HuellaTemplate($db);

$huella->user_id_global = "USER_001";
$huella->fingerprint_data = str_repeat("a1", HUELLA_TEMPLATE_BYTES);
$huella->formato = "HEX";
$huella->slot_index = 1;

echo $huella->crear() ? "✅ Huella creada" : "❌ Error";
?>
