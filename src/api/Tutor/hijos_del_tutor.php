<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Tutor.php';

try {
    $db = (new Database())->getConnection();
    $tutor = new Tutor($db);

    $idTutor = (int)($_GET['id_tutor'] ?? 0);
    if ($idTutor <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "ID de tutor inválido", "data" => []]);
        exit;
    }

    $hijos = $tutor->obtenerEstudiantesVinculados($idTutor);

    echo json_encode([
        "status" => "success",
        "message" => "Hijos vinculados obtenidos",
        "data" => $hijos
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno: " . $e->getMessage(), "data" => []]);
}
