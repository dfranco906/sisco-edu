<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/RecesoInstitucional.php';

verificarRol(['SuperAdmin', 'Administrador', 'Administracion']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

$idReceso = (int)($_POST['id_receso'] ?? 0);
if ($idReceso <= 0) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded['id_receso'])) {
            $idReceso = (int)$decoded['id_receso'];
        }
    }
}

if ($idReceso <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "ID de receso inválido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $recesoModel = new RecesoInstitucional($db);

    $ok = $recesoModel->desactivar($idReceso);

    echo json_encode([
        "status" => $ok ? "success" : "error",
        "message" => $ok ? "Receso institucional desactivado correctamente" : "No se pudo desactivar el receso institucional"
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("[desactivar_receso] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al desactivar el receso"]);
}
