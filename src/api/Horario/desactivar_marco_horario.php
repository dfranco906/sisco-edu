<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/MarcoHorario.php';

verificarRol(['SuperAdmin', 'Administrador', 'Administracion']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

$idMarco = (int)($_POST['id_marco'] ?? 0);
if ($idMarco <= 0) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded['id_marco'])) {
            $idMarco = (int)$decoded['id_marco'];
        }
    }
}

if ($idMarco <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID de marco horario inválido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $marcoModel = new MarcoHorario($db);

    $ok = $marcoModel->desactivar($idMarco);

    echo json_encode([
        "status" => $ok ? "success" : "error",
        "message" => $ok ? "Marco horario institucional desactivado correctamente" : "No se pudo desactivar el marco horario"
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("[desactivar_marco_horario] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al desactivar marco horario"]);
}
