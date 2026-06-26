<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/SyncBiometrica.php';

$db = (new Database())->getConnection();
$sync = new SyncBiometrica($db);

$stmt = $sync->leer();

echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>