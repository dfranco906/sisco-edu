<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Tutor.php';

try {
    $db = (new Database())->getConnection();
    $tutor = new Tutor($db);

    $idTutor = (int)($_POST['id_tutor'] ?? 0);
    $ok = $tutor->desactivar($idTutor);

    echo json_encode([
        "status" => $ok ? "success" : "error",
        "message" => $ok ? "Tutor desactivado correctamente" : "No se pudo desactivar el tutor"
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno: " . $e->getMessage()]);
}
