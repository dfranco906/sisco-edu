<?php
require_once '../config/db.php';
require_once '../classes/Materia.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$materia = new Materia($db);

$stmt = $materia->leer();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
?>