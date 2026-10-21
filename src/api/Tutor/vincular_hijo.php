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
    $parentesco = trim((string)($_POST['parentesco'] ?? $jsonData['parentesco'] ?? 'Tutor/a'));
    $esPrincipal = (!empty($_POST['es_principal']) || !empty($jsonData['es_principal'])) ? 1 : 0;

    if ($idTutor <= 0 || $idEstudiante <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Debe seleccionar un tutor y un estudiante."]);
        exit;
    }

    $ok = $tutor->vincularEstudiante($idTutor, $idEstudiante, $parentesco, $esPrincipal);

    http_response_code($ok ? 200 : 422);
    echo json_encode([
        "status" => $ok ? "success" : "error",
        "message" => $ok ? "Estudiante vinculado exitosamente al tutor." : "No se pudo vincular al estudiante."
    ]);
} catch (Throwable $e) {
    error_log("Error en Tutor/vincular_hijo: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al vincular estudiante"]);
}
