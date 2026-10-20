<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Grado.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $db = (new Database())->getConnection();
    $grado = new Grado($db);

    $rol = $_SESSION['rol'] ?? null;
    $idProfesorSesion = $_SESSION['id_profesor'] ?? null;

    $id_profesor = filter_input(INPUT_GET, 'id_profesor', FILTER_VALIDATE_INT) ?: null;

    // Si el usuario es Profesor, forzar el filtro a los grados en los que está designado
    if ($rol === 'Profesor') {
        $id_profesor = $idProfesorSesion ? (int)$idProfesorSesion : -1;
    }

    $stmt = $grado->leer($id_profesor);

    echo json_encode([
        "status" => "success",
        "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en Grado/leer_grados: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al obtener grados: " . $e->getMessage(),
        "data" => []
    ], JSON_UNESCAPED_UNICODE);
}
