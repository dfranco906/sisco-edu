<?php
require_once '../../config/db.php';
require_once '../../classes/HuellaTemplate.php';

$db = (new Database())->getConnection();
$huella = new HuellaTemplate($db);

$huella->id_huella = $_POST['id_huella'] ?? null;
$huella->user_id_global = $_POST['user_id_global'] ?? null;
$huella->id_estudiante = $_POST['id_estudiante'] ?? null;
$huella->fingerprint_data = $_POST['fingerprint_data'] ?? null;
$huella->formato = $_POST['formato'] ?? 'HEX';
$huella->pendiente_sync = $_POST['pendiente_sync'] ?? 1;

echo $huella->actualizar()
    ? "✅ Huella actualizada correctamente"
    : "❌ Error al actualizar huella";
?>