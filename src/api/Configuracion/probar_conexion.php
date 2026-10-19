<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ConfiguracionSistema.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rolUsuario = strtolower($_SESSION['rol'] ?? '');
if (!in_array($rolUsuario, ['superadmin', 'administracion', 'administrador'], true)) {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "message" => "Acceso restringido. Se requieren privilegios administrativos."
    ]);
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

$puerto = 80;
$timeoutSegundos = 2.0;
$inicio = microtime(true);

$errno = 0;
$errstr = '';
$conexion = @fsockopen($ip, $puerto, $errno, $errstr, $timeoutSegundos);
$duracionMs = round((microtime(true) - $inicio) * 1000);

if (is_resource($conexion)) {
    fclose($conexion);

    $extraInfo = "";
    // Si es el registrador, intentar leer /estado
    if ($tipo === 'registrador' && function_exists('curl_init')) {
        $ch = curl_init("http://{$ip}/estado");
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
                $extraInfo = " (Sensor DY50 responde correctamente)";
            }
        }
    }

    echo json_encode([
        "status" => "success",
        "online" => true,
        "dispositivo" => $tipo,
        "ip" => $ip,
        "latencia_ms" => $duracionMs,
        "message" => "Dispositivo activo en {$ip}:80 {$extraInfo}"
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "online" => false,
        "dispositivo" => $tipo,
        "ip" => $ip,
        "latencia_ms" => $duracionMs,
        "message" => "No se pudo conectar con {$ip}:80 (Error {$errno}: {$errstr})"
    ]);
}
