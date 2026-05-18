<?php
require_once '../../config/db.php';
require_once '../../classes/NodoEsp32.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();
$nodo = new NodoEsp32($db);

$stmt = $nodo->leer();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>