<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/AsistenciaEstudiante.php';

try {
    $db = (new Database())->getConnection();
    $asistencia = new AsistenciaEstudiante($db);
    $limit = isset($_GET['limit']) ? max(1, min(1000, (int)$_GET['limit'])) : 500;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
    $stmt = $asistencia->leer($limit, $offset);

    echo json_encode(["status" => "success", "message" => "Asistencias de estudiantes obtenidas", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Error interno", "data" => [], "debug" => $e->getMessage()]);
}
?>
