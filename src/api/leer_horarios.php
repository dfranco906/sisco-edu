<?php
require_once '../config/db.php';
require_once '../classes/Horario.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$horario = new Horario($db);

$stmt = $horario->leer();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
?>