<?php
// src/api/Gateway/auditar_aula.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/red.php';
require_once __DIR__ . '/../../config/biometria.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/NodoEsp32.php';

// RBAC: Solo Administrador y SuperAdmin
$rolActual = $_SESSION['rol'] ?? '';
if (!in_array($rolActual, ['Administrador', 'SuperAdmin'], true)) {
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Acceso denegado: solo los usuarios administradores pueden ejecutar la auditoría de hardware biométrico.'
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

    $resultado = $nodoModel->auditarAulaHuellas($idAula);

    if (!$resultado['ok']) {
        http_response_code(404);
        echo json_encode([
            'status'  => 'error',
            'message' => $resultado['message'] ?? 'Error al auditar el aula solicitada.',
            'data'    => $resultado
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'status'  => 'success',
        'message' => 'Auditoría de aula ejecutada exitosamente.',
        'data'    => $resultado
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("[auditar_aula.php] Error crítico: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error interno al procesar la auditoría del sensor de aula.'
    ], JSON_UNESCAPED_UNICODE);
}
