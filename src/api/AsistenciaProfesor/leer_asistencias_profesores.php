<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/AsistenciaProfesor.php';

$db = (new Database())->getConnection();
$a = new AsistenciaProfesor($db);

$stmt = $a->leer();

echo json_encode([
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>