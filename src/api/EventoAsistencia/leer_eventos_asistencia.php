<?php
require_once '../../config/db.php';
require_once '../../classes/EventoAsistencia.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$evento = new EventoAsistencia($db);

$stmt = $evento->leer();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>