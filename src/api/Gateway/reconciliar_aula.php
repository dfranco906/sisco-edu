<?php
// src/api/Gateway/reconciliar_aula.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/NodoEsp32.php';

// RBAC: Solo Administrador y SuperAdmin
$rolActual = $_SESSION['rol'] ?? '';
if (!in_array($rolActual, ['Administrador', 'SuperAdmin'], true)) {
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Acceso denegado: solo administradores pueden reconciliar huellas.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$idAula = filter_var($input['id_aula'] ?? null, FILTER_VALIDATE_INT);

if (!$idAula || $idAula <= 0) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Se requiere un identificador de aula válido (id_aula).'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $nodoModel = new NodoEsp32($db);

    $resultado = $nodoModel->reencolarHuellasAula($idAula);

    echo json_encode([
        'status'  => 'success',
        'message' => $resultado['message'],
        'data'    => $resultado
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("[reconciliar_aula.php] Error crítico: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error interno al reconciliar huellas del aula.'
    ], JSON_UNESCAPED_UNICODE);
}
