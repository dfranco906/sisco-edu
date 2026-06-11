<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Estudiante.php';

$db = (new Database())->getConnection();
$estudiante = new Estudiante($db);

$stmt = $estudiante->leer();

echo json_encode([
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>