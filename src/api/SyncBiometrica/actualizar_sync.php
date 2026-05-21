<?php
require_once '../config/db.php';
require_once '../classes/SyncBiometrica.php';

$db = (new Database())->getConnection();
$sync = new SyncBiometrica($db);

$sync->id_sync = $_POST['id_sync'] ?? null;
$sync->id_huella = $_POST['id_huella'] ?? null;
$sync->room_id = $_POST['room_id'] ?? null;
$sync->estado = $_POST['estado'] ?? 'PENDIENTE';
$sync->intentos = $_POST['intentos'] ?? 0;

echo $sync->actualizar() ? "✅ Sync actualizado" : "❌ Error";
?>