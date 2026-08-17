<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Grado.php';

$db = (new Database())->getConnection();
$grado = new Grado($db);

$stmt = $grado->leer();

echo json_encode([
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>