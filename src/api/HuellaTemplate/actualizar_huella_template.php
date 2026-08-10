<?php
require_once '../../config/db.php';
require_once '../../config/biometria.php';
require_once '../../classes/HuellaTemplate.php';

$db = (new Database())->getConnection();
$huella = new HuellaTemplate($db);

$huella->id_huella = $_POST['id_huella'] ?? null;
$huella->user_id_global = $_POST['user_id_global'] ?? null;
$template = trim($_POST['fingerprint_data'] ?? '');
$formato = strtoupper(trim($_POST['formato'] ?? 'HEX'));
if ($formato !== 'HEX' || !es_template_huella_hex_valido($template)) {
    http_response_code(422);
    echo "Template HEX invalido: se requieren 1536 bytes (3072 caracteres)";
    exit;
}
$huella->fingerprint_data = strtolower($template);
$huella->formato = 'HEX';
$huella->pendiente_sync = $_POST['pendiente_sync'] ?? 1;
$huella->slot_index = $_POST['slot_index'] ?? 0;

echo $huella->actualizar()
    ? "✅ Huella actualizada correctamente"
    : "❌ Error al actualizar huella";
?>
