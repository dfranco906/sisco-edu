<?php
require_once '../src/config/db.php';
require_once '../src/classes/Asignacion.php';

echo "<h2>TEST LEER ASIGNACIONES</h2>";

$db = (new Database())->getConnection();
$asignacion = new Asignacion($db);

$stmt = $asignacion->leer();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($data);
echo "</pre>";
?>