<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Estudiante.php';
require_once __DIR__ . '/../../classes/AlcanceUsuario.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = (new Database())->getConnection();
    $estudiante = new Estudiante($db);

    $rol = $_SESSION['rol'] ?? null;
    $idUsuarioSesion = (int)($_SESSION['id_usuario'] ?? 0);

    $idGrado = filter_input(INPUT_GET, 'id_grado', FILTER_VALIDATE_INT) ?: null;
    $idTurno = filter_input(INPUT_GET, 'id_turno', FILTER_VALIDATE_INT) ?: null;
    $idNivel = filter_input(INPUT_GET, 'id_nivel', FILTER_VALIDATE_INT) ?: null;
    $idBachillerato = filter_input(INPUT_GET, 'id_bachillerato', FILTER_VALIDATE_INT) ?: null;

    // Segmentación granular de cursos para Administradores y Coordinadores
    $gradosPermitidos = AlcanceUsuario::obtenerGradosPermitidos($db, $idUsuarioSesion, $rol);

    $stmt = $estudiante->leer($idGrado, $idTurno, $idNivel, $idBachillerato, $gradosPermitidos);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "message" => "Estudiantes obtenidos correctamente",
        "total" => count($data),
        "data" => $data
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en Estudiante/leer_estudiantes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al obtener estudiantes",
        "data" => []
    ], JSON_UNESCAPED_UNICODE);
}
