<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Profesor.php';

try {
    $db = (new Database())->getConnection();
    $profesor = new Profesor($db);
    $stmt = $profesor->leer();

    echo json_encode(["status" => "success", "message" => "Profesores obtenidos", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Error interno", "data" => [], "debug" => $e->getMessage()]);
}
?>
