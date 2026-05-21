<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/SyncBiometrica.php';

$db = (new Database())->getConnection();
$sync = new SyncBiometrica($db);

$sync->id_sync = 1;
$sync->id_huella = 1;
$sync->room_id = "AULA_1";
$sync->estado = "ENVIADO";
$sync->intentos = 1;

echo $sync->actualizar() ? "✅ Sync actualizado" : "❌ Error";
?>