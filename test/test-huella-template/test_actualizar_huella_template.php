<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/HuellaTemplate.php';

$db = (new Database())->getConnection();
$huella = new HuellaTemplate($db);

$huella->id_huella = 1;
$huella->user_id_global = "USER_001";
$huella->fingerprint_data = str_repeat("b2", HUELLA_TEMPLATE_BYTES);
$huella->formato = "HEX";
$huella->pendiente_sync = 1;
$huella->slot_index = 1;

echo $huella->actualizar() ? "✅ Huella actualizada" : "❌ Error";
?>
