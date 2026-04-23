<?php
require_once '../src/config/db.php';
require_once '../src/classes/Asistencia.php';

echo "<h2>TEST LEER ASISTENCIAS</h2>";

$db = (new Database())->getConnection();
$asistencia = new Asistencia($db);

$stmt = $asistencia->leer();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($data);
echo "</pre>";
?>