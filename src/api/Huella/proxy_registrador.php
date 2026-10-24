<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/red.php';

$accion = trim($_GET['accion'] ?? '');
$accionesValidas = ['registrar', 'estado', 'obtener_template', 'limpiar'];

if (!in_array($accion, $accionesValidas, true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Acción de proxy inválida']);
    exit;
}

$ipRegistrador = defined('IP_ESP32_REGISTRADOR') ? IP_ESP32_REGISTRADOR : '192.168.100.39';
$url = "http://{$ipRegistrador}/{$accion}";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => ($accion === 'estado' ? 3 : 15),
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_HTTPHEADER => ['Accept: application/json']
]);

$respuesta = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($respuesta === false || $httpCode === 0) {
    http_response_code(502);
    echo json_encode([
        'status' => 'error',
        'message' => "No se pudo comunicar con el sensor registrador en {$ipRegistrador}: {$curlError}"
    ]);
    exit;
}

http_response_code($httpCode >= 200 && $httpCode < 400 ? 200 : $httpCode);
echo $respuesta;
