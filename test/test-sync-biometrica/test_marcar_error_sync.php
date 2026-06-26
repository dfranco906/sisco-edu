<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/SyncBiometrica.php';

$db = (new Database())->getConnection();
$sync = new SyncBiometrica($db);

$sync->id_sync = 1;

echo $sync->marcarError() ? "✅ Sync marcado como ERROR" : "❌ Error";
?>