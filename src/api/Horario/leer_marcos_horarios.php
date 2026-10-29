<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/MarcoHorario.php';

verificarRol(['SuperAdmin', 'Administrador', 'Administracion', 'Coordinador', 'Profesor']);

try {
    $db = (new Database())->getConnection();
    $marcoModel = new MarcoHorario($db);

    $idTurno = filter_input(INPUT_GET, 'id_turno', FILTER_VALIDATE_INT) ?: null;
    $idNivel = filter_input(INPUT_GET, 'id_nivel', FILTER_VALIDATE_INT) ?: null;
    $idBachillerato = filter_input(INPUT_GET, 'id_bachillerato', FILTER_VALIDATE_INT) ?: null;
    $diaSemana = !empty($_GET['dia_semana']) ? trim((string)$_GET['dia_semana']) : null;

    $marcos = $marcoModel->listar($idTurno, $idNivel, $idBachillerato, $diaSemana);

    echo json_encode([
        "status" => "success",
        "message" => "Marcos horarios obtenidos correctamente",
        "total" => count($marcos),
        "data" => $marcos
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("[leer_marcos_horarios] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al consultar marcos horarios institucionales",
        "data" => []
    ], JSON_UNESCAPED_UNICODE);
}
