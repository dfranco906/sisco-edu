<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Horario.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';
$id_profesor = null;

if ($rol === 'Profesor' && !empty($_SESSION['id_profesor'])) {
    $id_profesor = (int)$_SESSION['id_profesor'];
} elseif (isset($_GET['id_profesor']) && is_numeric($_GET['id_profesor'])) {
    $id_profesor = (int)$_GET['id_profesor'];
}

$id_grado = null;
if (isset($_GET['id_grado']) && is_numeric($_GET['id_grado'])) {
    $id_grado = (int)$_GET['id_grado'];
}

try {
    $db = (new Database())->getConnection();
    $horario = new Horario($db);
    $stmt = $horario->leerDesactivados($id_profesor, $id_grado);

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Horarios desactivados obtenidos",
        "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $e) {
    error_log("Error en leer_horarios_desactivados: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error al obtener horarios desactivados",
        "data" => []
    ]);
}
