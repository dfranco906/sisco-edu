<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Tutor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $tutor = new Tutor($db);

    $q = trim((string)($_GET['q'] ?? ''));
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;

    $resultados = $tutor->buscar($q, $limit);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Tutores obtenidos correctamente",
        "data" => $resultados
    ]);
} catch (Throwable $e) {
    error_log("Error en Tutor/buscar_tutores: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al buscar tutores"
    ]);
}
