<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/NivelAcademico.php';

try {
    $db = (new Database())->getConnection();
    $nivel = new NivelAcademico($db);
    $nivel->id_nivel = (int)($_POST['id_nivel'] ?? 0);

    if (!$nivel->id_nivel) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "ID no proporcionado"]);
        exit;
    }

    $res = $nivel->eliminar();
    http_response_code(200);
    echo json_encode([
        "status" => $res ? "success" : "error",
        "message" => $res ? "Nivel/Ciclo eliminado definitivamente" : "No se pudo eliminar el nivel"
    ]);
} catch (Throwable $e) {
    error_log("Error en eliminar_nivel: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al eliminar nivel"]);
}
