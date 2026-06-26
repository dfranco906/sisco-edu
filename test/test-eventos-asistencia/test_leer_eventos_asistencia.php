<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/EventoAsistencia.php';

$db = (new Database())->getConnection();
$evento = new EventoAsistencia($db);

$stmt = $evento->leer();

echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>