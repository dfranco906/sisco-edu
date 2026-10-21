<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Profesor.php';

try {
    $db = (new Database())->getConnection();
    $profesor = new Profesor($db);

    $idGrado = isset($_GET['id_grado']) && is_numeric($_GET['id_grado']) ? (int)$_GET['id_grado'] : null;
    $idTurno = isset($_GET['id_turno']) && is_numeric($_GET['id_turno']) ? (int)$_GET['id_turno'] : null;
    $idNivel = isset($_GET['id_nivel']) && is_numeric($_GET['id_nivel']) ? (int)$_GET['id_nivel'] : null;
    $idBachillerato = isset($_GET['id_bachillerato']) && is_numeric($_GET['id_bachillerato']) ? (int)$_GET['id_bachillerato'] : null;

    $stmt = $profesor->leer($idGrado, $idTurno, $idNivel, $idBachillerato);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "message" => "Profesores obtenidos correctamente",
        "total" => count($data),
        "data" => $data
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log("Error en Profesor/leer_profesores: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al obtener profesores",
        "data" => []
    ], JSON_UNESCAPED_UNICODE);
}
