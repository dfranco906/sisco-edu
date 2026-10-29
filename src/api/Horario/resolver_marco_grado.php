<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/MarcoHorario.php';

try {
    $db = (new Database())->getConnection();
    $marcoModel = new MarcoHorario($db);

    $idGrado = filter_input(INPUT_GET, 'id_grado', FILTER_VALIDATE_INT) ?: null;
    $idAula = filter_input(INPUT_GET, 'id_aula', FILTER_VALIDATE_INT) ?: null;
    $diaSemana = !empty($_GET['dia_semana']) ? trim((string)$_GET['dia_semana']) : null;
    $horaActual = !empty($_GET['hora']) ? trim((string)$_GET['hora']) : date('H:i:s');

    $resultado = null;
    if ($idGrado !== null && $idGrado > 0) {
        $resultado = $marcoModel->resolverMarcoParaCurso($idGrado, $diaSemana, $horaActual);
    } elseif ($idAula !== null && $idAula > 0) {
        $resultado = $marcoModel->resolverMarcoParaAula($idAula, $diaSemana, $horaActual);
    } else {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Debe especificar id_grado o id_aula"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!$resultado) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "No se encontró un marco horario aplicable para el curso/aula y día solicitado",
            "data" => null
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "message" => "Marco horario institucional resuelto exitosamente",
        "data" => $resultado
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("[resolver_marco_grado] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al resolver marco horario"
    ], JSON_UNESCAPED_UNICODE);
}
