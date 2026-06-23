<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Asignacion.php';

try {
    $db = (new Database())->getConnection();
    $asignacion = new Asignacion($db);
    $stmt = $asignacion->leerDesactivadas();

    echo json_encode(["status" => "success", "message" => "Asignaciones desactivadas obtenidas", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Error interno", "data" => [], "debug" => $e->getMessage()]);
}
?>
