<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/AlcanceUsuario.php';

// Solo el SuperAdmin tiene autoridad total para auditar y segmentar alcances
verificarRol(['SuperAdmin']);

$idUsuario = (int)($_GET['id_usuario'] ?? $_POST['id_usuario'] ?? 0);

if ($idUsuario <= 0) {
    // Si viene como payload JSON
    $body = json_decode(file_get_contents('php://input'), true);
    if (is_array($body) && isset($body['id_usuario'])) {
        $idUsuario = (int)$body['id_usuario'];
    }
}

if ($idUsuario <= 0) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Parámetro id_usuario inválido o ausente"
    ]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $estructura = AlcanceUsuario::obtenerEstructuraAlcance($db, $idUsuario);

    echo json_encode([
        "status" => "success",
        "message" => "Alcance obtenido correctamente",
        "data" => $estructura
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(404);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log("[Usuario::obtener_alcance] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al consultar el alcance del usuario"
    ]);
}
