<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Aula.php';

$db = (new Database())->getConnection();
$aula = new Aula($db);

$stmt = $aula->leerDesactivadas();

echo json_encode([
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>
