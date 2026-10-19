<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ConfiguracionSistema.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad RBAC: SuperAdmin o Administración pueden modificar parámetros del sistema
$rolUsuario = strtolower($_SESSION['rol'] ?? '');
if (!in_array($rolUsuario, ['superadmin', 'administracion', 'administrador'], true)) {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "message" => "Acceso denegado. Se requieren privilegios administrativos."
    ]);
    exit();
}

$camposPermitidos = [
    'ip_servidor',
    'url_servidor',
    'ip_registrador',
    'ip_gateway',
    'gateway_sync_url',
    'gateway_api_key',
    'lora_red_id',
    'lora_id_gateway'
];

$datosAActualizar = [];
$errores = [];

foreach ($camposPermitidos as $campo) {
    if (isset($_POST[$campo])) {
        $valor = trim($_POST[$campo]);

        // Validaciones semánticas defensivas
        if (in_array($campo, ['ip_servidor', 'ip_registrador', 'ip_gateway'], true)) {
            // Permitir IP válida o nombre de host local (ej. sisco-server.local)
            $esIp = filter_var($valor, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            $esHost = preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-\.]{0,61}[a-zA-Z0-9])?$/', $valor);
            if (!$esIp && !$esHost) {
                $errores[] = "El campo '{$campo}' debe ser una IP válida o un nombre de host válido.";
            }
        } elseif ($campo === 'gateway_sync_url') {
            if (!empty($valor) && !filter_var($valor, FILTER_VALIDATE_URL)) {
                $errores[] = "El campo 'gateway_sync_url' debe ser una URL válida (ej. http://192.168.100.17/sync).";
            }
        } elseif ($campo === 'gateway_api_key') {
            if (strlen($valor) < 8) {
                $errores[] = "La clave API del Gateway debe tener al menos 8 caracteres.";
            }
        } elseif ($campo === 'lora_red_id' || $campo === 'lora_id_gateway') {
            if (!ctype_digit($valor) || (int)$valor < 0) {
                $errores[] = "El campo '{$campo}' debe ser un número entero positivo.";
            }
        }

        $datosAActualizar[$campo] = $valor;
    }
}

// Si se actualizó la IP del Gateway y no se especificó URL sync, recalcular automáticamente
if (isset($datosAActualizar['ip_gateway']) && empty($datosAActualizar['gateway_sync_url'])) {
    $datosAActualizar['gateway_sync_url'] = 'http://' . $datosAActualizar['ip_gateway'] . '/sync';
}

if (!empty($errores)) {
    http_response_code(422);
    echo json_encode([
        "status" => "error",
        "message" => "Errores de validación en los datos enviados.",
        "errors" => $errores
    ]);
    exit();
}

if (empty($datosAActualizar)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "No se enviaron campos válidos para actualizar."
    ]);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $configModel = new ConfiguracionSistema($db);
    $configModel->asegurarTabla();

    if ($configModel->actualizarLote($datosAActualizar)) {
        echo json_encode([
            "status" => "success",
            "message" => "Configuraciones del sistema actualizadas exitosamente en la base de datos.",
            "data" => $datosAActualizar
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Ocurrió un error al guardar las configuraciones en la base de datos."
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al procesar la actualización",
        "debug" => $e->getMessage()
    ]);
}
