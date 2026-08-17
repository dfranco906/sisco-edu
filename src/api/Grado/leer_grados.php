<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Grado.php';

$db = (new Database())->getConnection();
$grado = new Grado($db);

$soloOpciones = filter_var($_GET['opciones'] ?? false, FILTER_VALIDATE_BOOLEAN);
$stmt = $soloOpciones ? $grado->leerOpciones() : $grado->leer();

echo json_encode([
    "success" => true,
    "status" => "success",
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
?>
