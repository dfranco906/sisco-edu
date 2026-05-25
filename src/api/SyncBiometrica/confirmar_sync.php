<?php
require_once '../../config/db.php';
require_once '../../classes/SyncBiometrica.php';

$db = (new Database())->getConnection();
$sync = new SyncBiometrica($db);

$sync->id_sync = $_POST['id_sync'] ?? null;

echo $sync->confirmar() ? "✅ Sync confirmado" : "❌ Error";
?>