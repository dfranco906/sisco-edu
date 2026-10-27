<?php
// src/api/Gateway/procesar_sync_cola.php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/red.php';
require_once __DIR__ . '/../../config/biometria.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../classes/SyncBiometrica.php';

// RBAC: Solo SuperAdmin, Administrador y Administracion
verificarRol(['SuperAdmin', 'Administrador', 'Administracion']);

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = $_POST;
}

$accion = strtolower(trim((string)($input['accion'] ?? 'individual')));

try {
    $db = (new Database())->getConnection();
    $syncObj = new SyncBiometrica($db);

    switch ($accion) {
        case 'individual':
            $idSync = filter_var($input['id_sync'] ?? null, FILTER_VALIDATE_INT);
            if (!$idSync) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Se requiere un ID de sincronización válido (id_sync).'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $detalle = $syncObj->reintentar($idSync, 'Reintento manual desde panel de administración');
            if (!$detalle) {
                http_response_code(404);
                echo json_encode([
                    'status'  => 'error',
                    'message' => "No se encontró el registro de sincronización #{$idSync}."
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $idHuella = (int)$detalle['id_huella'];
            $idAula = !empty($detalle['id_aula']) ? (int)$detalle['id_aula'] : null;

            $gatewayNotificado = avisarGatewaySync($idHuella, $idAula);

            $msgGw = $gatewayNotificado
                ? " Gateway LoRa notificado para transmitir Huella #{$idHuella} a {$detalle['aula_nombre']}."
                : " Encolado en estado PENDIENTE (el Gateway sincronizará al consultar cola).";

            echo json_encode([
                'status'  => 'success',
                'message' => "Sincronización #{$idSync} reprogramada exitosamente.{$msgGw}",
                'data'    => [
                    'id_sync'            => $idSync,
                    'id_huella'          => $idHuella,
                    'id_aula'            => $idAula,
                    'aula_nombre'        => $detalle['aula_nombre'] ?? '',
                    'nombre_completo'    => $detalle['nombre_completo'] ?? '',
                    'tipo_persona'       => $detalle['tipo_persona'] ?? '',
                    'gateway_notificado' => $gatewayNotificado,
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'seleccionados':
            $idsRaw = $input['ids_sync'] ?? [];
            if (!is_array($idsRaw) || empty($idsRaw)) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Debe seleccionar al menos una huella de la lista.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $afectados = $syncObj->reintentarMultiples($idsRaw, 'Reintento por selección múltiple');
            $totalAfectados = count($afectados);

            if ($totalAfectados === 0) {
                http_response_code(404);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'No se encontraron registros válidos para reprogramar.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Si es un solo ítem, podemos notificarlo directamente con id_huella e id_aula
            $gatewayNotificado = false;
            if ($totalAfectados === 1) {
                $item = $afectados[0];
                $gatewayNotificado = avisarGatewaySync((int)$item['id_huella'], !empty($item['id_aula']) ? (int)$item['id_aula'] : null);
            } else {
                // Para múltiples, avisamos al Gateway sin parámetros para que inicie barrido general de pendientes
                $gatewayNotificado = avisarGatewaySync();
            }

            $msgGw = $gatewayNotificado
                ? " Gateway LoRa notificado para procesar el lote."
                : " Encolados como PENDIENTE (se procesarán en la próxima consulta del Gateway).";

            echo json_encode([
                'status'  => 'success',
                'message' => "Se reprogramaron {$totalAfectados} huella(s) seleccionadas.{$msgGw}",
                'data'    => [
                    'total_procesados'   => $totalAfectados,
                    'items'              => $afectados,
                    'gateway_notificado' => $gatewayNotificado,
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        case 'todos_pendientes':
            // Opcionalmente reiniciar los que están en ERROR
            $incluirErrores = filter_var($input['incluir_errores'] ?? true, FILTER_VALIDATE_BOOLEAN);
            if ($incluirErrores) {
                $db->query("
                    UPDATE sync_biometrica 
                    SET estado = 'PENDIENTE', intentos = 0, mensaje = 'Reactivado para envío masivo', fecha_actualizacion = NOW() 
                    WHERE estado = 'ERROR'
                ");
            }

            $pendientes = $syncObj->obtenerPendientesParaProcesar(500);
            $totalPendientes = count($pendientes);

            if ($totalPendientes === 0) {
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'No hay huellas pendientes de sincronización en este momento.',
                    'data'    => [
                        'total_procesados' => 0,
                        'items'            => [],
                    ]
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Notificar al Gateway
            $primerItem = $pendientes[0] ?? null;
            $gatewayNotificado = avisarGatewaySync(
                $primerItem ? (int)$primerItem['id_huella'] : null,
                $primerItem && !empty($primerItem['id_aula']) ? (int)$primerItem['id_aula'] : null
            );

            echo json_encode([
                'status'  => 'success',
                'message' => "Se prepararon {$totalPendientes} huellas pendientes para transmisión LoRa.",
                'data'    => [
                    'total_procesados'   => $totalPendientes,
                    'gateway_notificado' => $gatewayNotificado,
                    'items'              => $pendientes,
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => "Acción '{$accion}' no reconocida. Acciones válidas: individual, seleccionados, todos_pendientes."
            ], JSON_UNESCAPED_UNICODE);
            break;
    }

} catch (Throwable $e) {
    error_log("Error en procesar_sync_cola: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error al procesar la cola de sincronización biométrica.',
        'debug'   => (defined('MODO_ENTORNO') && MODO_ENTORNO === 'local') ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
