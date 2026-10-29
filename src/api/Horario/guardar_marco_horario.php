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

$input = [];
$raw = file_get_contents('php://input');
if (!empty($raw)) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}
if (empty($input)) {
    $input = $_POST;
}

try {
    $db = (new Database())->getConnection();
    $marcoModel = new MarcoHorario($db);

    $idMarco = $marcoModel->guardar($input);

    echo json_encode([
        "status" => "success",
        "message" => "Marco horario institucional guardado correctamente",
        "data" => ["id_marco" => $idMarco]
    ], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("[guardar_marco_horario] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al guardar el marco horario institucional"
    ], JSON_UNESCAPED_UNICODE);
}
