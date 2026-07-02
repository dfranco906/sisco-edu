<?php
require_once '../../config/db.php';
require_once '../../classes/HuellaTemplate.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$huella = new HuellaTemplate($db);

$stmt = $huella->leer();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>