<?php
require_once '../../config/db.php';
require_once '../../classes/HuellaTemplate.php';

$db = (new Database())->getConnection();
$huella = new HuellaTemplate($db);

$huella->user_id_global = $_POST['user_id_global'] ?? null;
$huella->fingerprint_data = $_POST['fingerprint_data'] ?? null;
$huella->formato = $_POST['formato'] ?? 'HEX';
$huella->slot_index = $_POST['slot_index'] ?? 0;

echo $huella->crear()
    ? "✅ Huella guardada correctamente"
    : "❌ Error al guardar huella";
?>