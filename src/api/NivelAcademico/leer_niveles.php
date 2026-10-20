<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/NivelAcademico.php';

try {
    $db = (new Database())->getConnection();
    $nivel = new NivelAcademico($db);
    $stmt = $nivel->leer();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode(["status" => "success", "message" => "Niveles obtenidos", "data" => $data]);
} catch (Throwable $e) {
    error_log("Error en leer_niveles: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al obtener niveles"]);
}
