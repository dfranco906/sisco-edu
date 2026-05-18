<?php
require_once '../config/db.php';
require_once '../classes/HuellaTemplate.php';

$db = (new Database())->getConnection();
$huella = new HuellaTemplate($db);

$huella->user_id_global = $_POST['user_id_global'] ?? null;
$huella->id_estudiante = $_POST['id_estudiante'] ?? null;
$huella->fingerprint_data = $_POST['fingerprint_data'] ?? null;
$huella->formato = $_POST['formato'] ?? 'HEX';

echo $huella->crear()
    ? "✅ Huella guardada correctamente"
    : "❌ Error al guardar huella";
?>