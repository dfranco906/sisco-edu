<?php
require_once '../config/db.php';
require_once '../classes/SyncBiometrica.php';

$db = (new Database())->getConnection();
$sync = new SyncBiometrica($db);

$sync->id_sync = $_POST['id_sync'] ?? null;
$sync->estado = "ERROR";

echo $sync->marcarError()
    ? "✅ Sync marcado como ERROR"
    : "❌ Error al marcar sync";
?>