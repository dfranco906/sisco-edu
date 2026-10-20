<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Tutor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $tutor = new Tutor($db);

    $idTutor = (int)($_POST['id_tutor'] ?? 0);
    if ($idTutor <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "ID de tutor inválido"]);
        exit;
    }

    $ok = $tutor->actualizar($idTutor, $_POST);

    echo json_encode([
        "status" => $ok ? "success" : "error",
        "message" => $ok ? "Tutor actualizado correctamente" : "No se pudo actualizar el tutor"
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno: " . $e->getMessage()]);
}
