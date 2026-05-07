<?php
require_once '../src/config/db.php';
require_once '../src/classes/Horario.php';

echo "<h2>TEST LEER HORARIOS</h2>";

$db = (new Database())->getConnection();
$horario = new Horario($db);

$stmt = $horario->leer();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($data);
echo "</pre>";
?>