<?php
require_once '../src/config/db.php';
require_once '../src/classes/Materia.php';

echo "<h2>TEST LEER MATERIAS</h2>";

$db = (new Database())->getConnection();
$materia = new Materia($db);

$stmt = $materia->leer();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($data);
echo "</pre>";
?>