<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/red.php';
require_once __DIR__ . '/../../classes/ConfiguracionSistema.php';

// Verificación por cabecera de enlace de Gateway
$headers = function_exists('getallheaders') ? getallheaders() : [];
$headerKey = $headers['X-GATEWAY-KEY'] ?? $headers['x-gateway-key'] ?? ($_SERVER['HTTP_X_GATEWAY_KEY'] ?? '');
if (!empty($headerKey) && defined('GATEWAY_API_KEY') && hash_equals(GATEWAY_API_KEY, $headerKey)) {
    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "online" => true,
        "dispositivo" => "servidor",
        "message" => "Conexión exitosa con el servidor SISCO-EDU"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Verificación de sesión de usuario administrativo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolUsuario = strtolower($_SESSION['rol'] ?? '');
if (!in_array($rolUsuario, ['superadmin', 'administracion', 'administrador'], true)) {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "online" => false,
        "message" => "Acceso restringido. Se requieren privilegios administrativos."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$tipo = trim($_GET['tipo'] ?? 'registrador');
$ipPersonalizada = trim($_GET['ip'] ?? '');

$db = (new Database())->getConnection();
$configModel = new ConfiguracionSistema($db);

$ip = $ipPersonalizada;
if (empty($ip)) {
    if ($tipo === 'registrador') {
        $ip = $configModel->obtener('ip_registrador', '192.168.100.125');
    } elseif ($tipo === 'gateway') {
        $ip = $configModel->obtener('ip_gateway', '192.168.100.17');
    } else {
        $ip = $configModel->obtener('ip_servidor', '192.168.100.109');
    }
}

// Normalizar IP o Hostname eliminando esquemas http(s):// o barras finales
$ipLimpia = preg_replace('#^https?://#i', '', $ip);
$ipLimpia = trim(rtrim($ipLimpia, '/'));
$puerto = 80;

if (strpos($ipLimpia, ':') !== false) {
    $partes = explode(':', $ipLimpia, 2);
    $ipHost = $partes[0];
    if (!empty($partes[1]) && is_numeric($partes[1])) {
        $puerto = (int)$partes[1];
    }
} else {
    $ipHost = $ipLimpia;
}

$timeoutSegundos = 2.5;
$inicio = microtime(true);

$errno = 0;
$errstr = '';
$conexion = @fsockopen($ipHost, $puerto, $errno, $errstr, $timeoutSegundos);
$duracionMs = round((microtime(true) - $inicio) * 1000);

if (is_resource($conexion)) {
    fclose($conexion);

    $extraInfo = "";
    // Si es el registrador o gateway, intentar consultar /estado para confirmar servicio
    if (($tipo === 'registrador' || $tipo === 'gateway') && function_exists('curl_init')) {
        $ch = curl_init("http://{$ipHost}:{$puerto}/estado");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_CONNECTTIMEOUT => 2
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200 && $resp) {
            $data = json_decode($resp, true);
            if (isset($data['status']) && $data['status'] === 'success') {
                $extraInfo = ($tipo === 'registrador')
                    ? " — Sensor DY50 y firmware listos"
                    : " — Gateway LoRa concentrador listo";
            }
        }
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "online" => true,
        "dispositivo" => $tipo,
        "ip" => $ipHost,
        "puerto" => $puerto,
        "latencia_ms" => $duracionMs,
        "message" => "Dispositivo activo en {$ipHost}:{$puerto}{$extraInfo}",
        "data" => [
            "online" => true,
            "dispositivo" => $tipo,
            "ip" => $ipHost,
            "puerto" => $puerto,
            "latencia_ms" => $duracionMs
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
} else {
    // DISPOSITIVO FUERA DE LÍNEA / INACCESIBLE
    $errstrLimpio = '';
    if (!empty($errstr)) {
        $errstrLimpio = function_exists('mb_convert_encoding') 
            ? mb_convert_encoding(trim($errstr), 'UTF-8', 'auto') 
            : trim($errstr);
    }

    // Diagnóstico claro y amigable en español
    $motivoDetalle = "No responde en {$ipHost}:{$puerto}.";
    if ($errno === 10060 || $errno === 110) {
        $motivoDetalle = "Tiempo de espera agotado. El nodo está apagado o fuera del alcance de la red Wi-Fi.";
    } elseif ($errno === 10061 || $errno === 111) {
        $motivoDetalle = "Conexión rechazada. El nodo está en la red pero el puerto {$puerto} está cerrado o el servicio HTTP no inició.";
    } elseif ($errno === 10065 || $errno === 113) {
        $motivoDetalle = "Host inalcanzable. No existe ruta de red hacia la dirección IP {$ipHost}.";
    } elseif ($errno === 10051 || $errno === 101) {
        $motivoDetalle = "Red inalcanzable. Verifique la conexión física o Wi-Fi del servidor.";
    } elseif (!empty($errstrLimpio)) {
        $motivoDetalle .= " Detalle: {$errstrLimpio}";
    }

    $mensajeError = "No se pudo conectar con {$ipHost}:{$puerto} — {$motivoDetalle} (Error {$errno})";

    http_response_code(503);
    echo json_encode([
        "status" => "error",
        "online" => false,
        "dispositivo" => $tipo,
        "ip" => $ipHost,
        "puerto" => $puerto,
        "latencia_ms" => $duracionMs,
        "errno" => $errno,
        "error_raw" => $errstrLimpio,
        "message" => $mensajeError,
        "data" => [
            "online" => false,
            "dispositivo" => $tipo,
            "ip" => $ipHost,
            "puerto" => $puerto,
            "latencia_ms" => $duracionMs,
            "errno" => $errno,
            "error_raw" => $errstrLimpio,
            "diagnostico" => $motivoDetalle
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
