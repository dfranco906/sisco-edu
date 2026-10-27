<?php
// src/api/Gateway/listar_sync_pendientes.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/red.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/SyncBiometrica.php';

// RBAC: Solo usuarios administrativos autorizados
verificarRol(['SuperAdmin', 'Administrador', 'Administracion']);

try {
    $db = (new Database())->getConnection();
    $syncObj = new SyncBiometrica($db);

    $filtros = [
        'estado'   => isset($_GET['estado']) ? trim((string)$_GET['estado']) : 'TODOS',
        'busqueda' => isset($_GET['busqueda']) ? trim((string)$_GET['busqueda']) : '',
        'id_aula'  => filter_var($_GET['id_aula'] ?? null, FILTER_VALIDATE_INT) ?: null,
        'limit'    => filter_var($_GET['limit'] ?? 200, FILTER_VALIDATE_INT) ?: 200,
        'offset'   => filter_var($_GET['offset'] ?? 0, FILTER_VALIDATE_INT) ?: 0,
    ];

    $items = $syncObj->obtenerCola($filtros);
    $stats = $syncObj->obtenerEstadisticas();

    $gatewayInfo = [
        'ip'       => defined('IP_ESP32_GATEWAY') ? IP_ESP32_GATEWAY : '',
        'sync_url' => defined('GATEWAY_SYNC_URL') ? GATEWAY_SYNC_URL : '',
    ];

    echo json_encode([
        'status'  => 'success',
        'message' => 'Cola de sincronización obtenida correctamente',
        'data'    => [
            'total_items' => count($items),
            'filtros'     => $filtros,
            'stats'       => $stats,
            'gateway'     => $gatewayInfo,
            'items'       => $items,
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error en listar_sync_pendientes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error al obtener la cola de sincronización biométrica',
        'debug'   => (defined('MODO_ENTORNO') && MODO_ENTORNO === 'local') ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
