<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/RecesoInstitucional.php';

verificarRol(['SuperAdmin', 'Administrador', 'Administracion', 'Coordinador', 'Profesor']);

try {
    $db = (new Database())->getConnection();
    $recesoModel = new RecesoInstitucional($db);

    $modo = trim((string)($_GET['modo'] ?? ''));
    $idGrado = filter_input(INPUT_GET, 'id_grado', FILTER_VALIDATE_INT) ?: null;
    $diaSemana = !empty($_GET['dia_semana']) ? trim((string)$_GET['dia_semana']) : null;

    if ($modo === 'curso' && $idGrado !== null) {
        // Modo específico: retorna todos los recesos aplicables para un curso dado
        $recesos = $recesoModel->obtenerRecesosParaCurso($idGrado, $diaSemana);
    } else {
        // Modo catálogo: listado general con filtros
        $idTurno = filter_input(INPUT_GET, 'id_turno', FILTER_VALIDATE_INT) ?: null;
        $idNivel = filter_input(INPUT_GET, 'id_nivel', FILTER_VALIDATE_INT) ?: null;
        $idBachillerato = filter_input(INPUT_GET, 'id_bachillerato', FILTER_VALIDATE_INT) ?: null;

        $recesos = $recesoModel->listar($idTurno, $idNivel, $idBachillerato, $diaSemana, $idGrado);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Recesos institucionales obtenidos correctamente",
        "total" => count($recesos),
        "data" => $recesos
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("[leer_recesos] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al consultar recesos institucionales",
        "data" => []
    ], JSON_UNESCAPED_UNICODE);
}
