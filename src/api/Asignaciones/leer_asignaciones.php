<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Asignacion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = (new Database())->getConnection();
    $asignacion = new Asignacion($db);

    $rol = $_SESSION['rol'] ?? null;
    $idProfesorSesion = $_SESSION['id_profesor'] ?? null;

    $id_profesor = filter_input(INPUT_GET, 'id_profesor', FILTER_VALIDATE_INT) ?: null;
    $anho = filter_input(INPUT_GET, 'anho', FILTER_VALIDATE_INT)
         ?: (filter_input(INPUT_GET, 'año', FILTER_VALIDATE_INT)
         ?: (filter_input(INPUT_GET, 'anho_lectivo', FILTER_VALIDATE_INT) ?: null));
    $id_nivel = filter_input(INPUT_GET, 'id_nivel', FILTER_VALIDATE_INT) ?: null;

    // Si el rol es Profesor, forzar filtro a sus propias asignaciones
    if ($rol === 'Profesor') {
        $id_profesor = $idProfesorSesion ? (int)$idProfesorSesion : -1;
    }

    $stmt = $asignacion->leer($id_profesor, $anho, $id_nivel);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "message" => "Asignaciones obtenidas",
        "data" => $data
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en Asignaciones/leer_asignaciones: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al obtener asignaciones: " . $e->getMessage(),
        "data" => []
    ], JSON_UNESCAPED_UNICODE);
}
