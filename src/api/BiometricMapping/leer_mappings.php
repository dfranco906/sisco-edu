<?php
require_once '../config/db.php';
require_once '../classes/BiometricMapping.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$mapping = new BiometricMapping($db);

$stmt = $mapping->leer();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>