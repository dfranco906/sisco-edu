<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';

verificarRol(['SuperAdmin', 'Administrador', 'Coordinador', 'Profesor']);

try {
    $db = (new Database())->getConnection();

    $stmtProc = $db->query("SELECT id_procedimiento, nombre FROM procedimientos_evaluativos ORDER BY id_procedimiento ASC");
    $procedimientos = $stmtProc->fetchAll(PDO::FETCH_ASSOC);

    $stmtInst = $db->query("SELECT id_instrumento, nombre FROM instrumentos_evaluativos ORDER BY id_instrumento ASC");
    $instrumentos = $stmtInst->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Recursos de evaluación obtenidos",
        "data" => [
            "procedimientos" => $procedimientos,
            "instrumentos" => $instrumentos
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en PlanesAnuales/recursos_evaluacion: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error al obtener recursos evaluativos"], JSON_UNESCAPED_UNICODE);
}
