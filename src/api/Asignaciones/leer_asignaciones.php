<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Asignacion.php';

try {
    $db = (new Database())->getConnection();
    $asignacion = new Asignacion($db);
    $soloOpciones = filter_var($_GET['opciones'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $stmt = $soloOpciones ? $asignacion->leerOpciones() : $asignacion->leer();

    echo json_encode(["success" => true, "status" => "success", "message" => "Asignaciones obtenidas", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log('leer_asignaciones: ' . $e->getMessage());
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudieron cargar las asignaciones.", "data" => []]);
}
?>
