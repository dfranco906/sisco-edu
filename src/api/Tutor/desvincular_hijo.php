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

    $jsonRaw = file_get_contents('php://input');
    $jsonData = json_decode($jsonRaw, true) ?? [];

    $idTutor = (int)($_POST['id_tutor'] ?? $jsonData['id_tutor'] ?? 0);
    $idEstudiante = (int)($_POST['id_estudiante'] ?? $jsonData['id_estudiante'] ?? 0);

    if ($idTutor <= 0 || $idEstudiante <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Parámetros incompletos para desvincular."]);
        exit;
    }

    $ok = $tutor->desvincularEstudiante($idTutor, $idEstudiante);

    http_response_code($ok ? 200 : 422);
    echo json_encode([
        "status" => $ok ? "success" : "error",
        "message" => $ok ? "Vínculo familiar retirado correctamente." : "No se pudo retirar el vínculo."
    ]);
} catch (Throwable $e) {
    error_log("Error en Tutor/desvincular_hijo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al desvincular estudiante"]);
}
