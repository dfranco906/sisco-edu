<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Tutor.php';

try {
    $db = (new Database())->getConnection();
    $tutor = new Tutor($db);
    $data = $tutor->leer();

    echo json_encode([
        "status" => "success",
        "message" => "Tutores obtenidos",
        "data" => $data
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al obtener tutores: " . $e->getMessage(),
        "data" => []
    ]);
}
