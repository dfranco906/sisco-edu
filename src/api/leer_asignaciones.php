<?php
require_once '../config/db.php';
require_once '../classes/Asignacion.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$asignacion = new Asignacion($db);

$stmt = $asignacion->leer();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
?>