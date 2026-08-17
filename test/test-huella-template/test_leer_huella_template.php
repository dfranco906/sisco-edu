<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/HuellaTemplate.php';

$db = (new Database())->getConnection();
$huella = new HuellaTemplate($db);

$stmt = $huella->leer();

echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>