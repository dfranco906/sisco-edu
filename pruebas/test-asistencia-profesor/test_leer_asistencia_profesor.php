<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/AsistenciaProfesor.php';

$db = (new Database())->getConnection();
$a = new AsistenciaProfesor($db);

$stmt = $a->leer();

echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>