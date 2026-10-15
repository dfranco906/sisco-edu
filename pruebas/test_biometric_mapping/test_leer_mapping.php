<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/BiometricMapping.php';

$db = (new Database())->getConnection();
$mapping = new BiometricMapping($db);

$stmt = $mapping->leer();

echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>