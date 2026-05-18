<?php
require_once '../../src/config/db.php';
require_once '../../src/classes/Aula.php';

$db = (new Database())->getConnection();
$aula = new Aula($db);

$stmt = $aula->leer();

echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
?>