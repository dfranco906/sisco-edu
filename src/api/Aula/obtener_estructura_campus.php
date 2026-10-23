<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Aula.php';

try {
    $db = (new Database())->getConnection();
    $aula = new Aula($db);

    $estructura = $aula->obtenerEstructuraCampus();

    echo json_encode([
        "status" => "success",
        "data" => $estructura
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al obtener la estructura del campus: " . $e->getMessage()
    ]);
}
