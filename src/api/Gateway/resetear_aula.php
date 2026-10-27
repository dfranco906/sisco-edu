<?php
// src/api/Gateway/resetear_aula.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/red.php';
require_once __DIR__ . '/../../config/biometria.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/NodoEsp32.php';

// RBAC Estricto: Solo Administrador y SuperAdmin
$rolActual = $_SESSION['rol'] ?? '';
if (!in_array($rolActual, ['Administrador', 'SuperAdmin'], true)) {
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Acceso denegado: solo los usuarios con el rol de Administrador pueden resetear huellas de aulas.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$idAula = filter_var($input['id_aula'] ?? null, FILTER_VALIDATE_INT);
$forzar = filter_var($input['forzar'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (!$idAula || $idAula <= 0) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Se requiere un ID de aula válido (id_aula).'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $nodoModel = new NodoEsp32($db);

    $resultado = $nodoModel->resetearAulaHuellas($idAula, $forzar);

    if (!$resultado['ok']) {
        http_response_code(502);
        echo json_encode([
            'status'  => 'error',
            'message' => $resultado['message'],
            'data'    => $resultado
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'status'  => 'success',
        'message' => $resultado['message'],
        'data'    => $resultado
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error en resetear_aula.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error interno al procesar el vaciado del aula.',
        'debug'   => (defined('MODO_ENTORNO') && MODO_ENTORNO === 'local') ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
