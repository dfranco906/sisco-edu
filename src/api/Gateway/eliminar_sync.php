<?php
// src/api/Gateway/eliminar_sync.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/SyncBiometrica.php';

// RBAC Estricto: Únicamente accesible para usuarios con rol de Administrador o SuperAdmin
$rolActual = $_SESSION['rol'] ?? '';
if (!in_array($rolActual, ['Administrador', 'SuperAdmin'], true)) {
    http_response_code(403);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Acceso denegado: solo los usuarios con el rol de Administrador pueden eliminar intentos de sincronización.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$idSync = filter_var($input['id_sync'] ?? null, FILTER_VALIDATE_INT);
$idsSync = $input['ids_sync'] ?? null;

if (!$idSync && (!is_array($idsSync) || empty($idsSync))) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Se requiere un ID de sincronización (id_sync) o una lista (ids_sync).'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $syncObj = new SyncBiometrica($db);

    if ($idSync) {
        $eliminado = $syncObj->eliminar($idSync);
        if (!$eliminado) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => "No se encontró el intento de sincronización #{$idSync} para eliminar."
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'status'  => 'success',
            'message' => "Intento de sincronización #{$idSync} eliminado exitosamente.",
            'data'    => [
                'id_sync'    => $idSync,
                'eliminados' => 1
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (is_array($idsSync)) {
        $totalEliminados = $syncObj->eliminarMultiples($idsSync);
        if ($totalEliminados === 0) {
            http_response_code(404);
            echo json_encode([
                'status'  => 'error',
                'message' => 'No se encontraron registros válidos para eliminar.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode([
            'status'  => 'success',
            'message' => "Se eliminaron {$totalEliminados} intento(s) de sincronización seleccionados.",
            'data'    => [
                'eliminados' => $totalEliminados
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

} catch (Throwable $e) {
    error_log("Error en eliminar_sync.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error interno al procesar la eliminación del intento de sincronización.',
        'debug'   => (defined('MODO_ENTORNO') && MODO_ENTORNO === 'local') ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
