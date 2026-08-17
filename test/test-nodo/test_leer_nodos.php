<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/NodoEsp32.php';

$db = (new Database())->getConnection();
$nodo = new NodoEsp32($db);

$stmt = $nodo->leer();

echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>