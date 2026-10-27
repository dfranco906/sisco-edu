<?php

const HUELLA_TEMPLATE_BYTES = 1536;
const HUELLA_TEMPLATE_HEX_CHARS = HUELLA_TEMPLATE_BYTES * 2;

function es_template_huella_hex_valido(string $template): bool
{
    return strlen($template) === HUELLA_TEMPLATE_HEX_CHARS
        && preg_match('/\A[0-9a-fA-F]+\z/D', $template) === 1;
}

/**
 * Notifica al Gateway ESP32 (vía HTTP POST con X-GATEWAY-KEY) que hay una huella
 * específica o lote pendiente para transmitir hacia las aulas vía LoRa.
 * 
 * @param int|null $idHuella ID específico de la huella seleccionada para enviar.
 * @param int|null $idAula ID específico del aula destino (o null si aplica a varias).
 */
function avisarGatewaySync(?int $idHuella = null, ?int $idAula = null): bool {
    if (!function_exists('curl_init') || !defined('GATEWAY_SYNC_URL') || empty(GATEWAY_SYNC_URL)) {
        return false;
    }
    $ch = curl_init(GATEWAY_SYNC_URL);
    if (!$ch) return false;

    $postData = [];
    if ($idHuella !== null && $idHuella > 0) {
        $postData['id_huella'] = $idHuella;
    }
    if ($idAula !== null && $idAula > 0) {
        $postData['id_aula'] = $idAula;
    }

    $cuerpo = !empty($postData) ? http_build_query($postData) : '';

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $cuerpo,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-GATEWAY-KEY: ' . (defined('GATEWAY_API_KEY') ? GATEWAY_API_KEY : ''),
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT => 5,
        CURLOPT_CONNECTTIMEOUT => 3
    ]);
    $respuesta = curl_exec($ch);
    $codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $respuesta !== false && $codigo >= 200 && $codigo < 300;
}

/**
 * Solicita al Gateway ESP32 (vía HTTP POST con X-GATEWAY-KEY) el vaciado físico
 * del sensor DY50 y la NVS del nodo de aula correspondiente por radiofrecuencia LoRa.
 * 
 * @param int $idAula ID del aula
 * @param int $loraId Dirección LoRa del nodo de aula
 * @return array ['ok' => bool, 'message' => string, 'codigo' => int]
 */
function solicitarVaciadoAulaGateway(int $idAula, int $loraId): array {
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'message' => 'cURL no disponible en el servidor PHP', 'codigo' => 500];
    }

    $gatewayIp = defined('IP_ESP32_GATEWAY') ? IP_ESP32_GATEWAY : '';
    if (empty($gatewayIp)) {
        return ['ok' => false, 'message' => 'IP del Gateway LoRa no configurada', 'codigo' => 500];
    }

    $url = "http://{$gatewayIp}/clear_aula";
    $ch = curl_init($url);
    if (!$ch) {
        return ['ok' => false, 'message' => 'No se pudo inicializar cURL', 'codigo' => 500];
    }

    $postData = http_build_query([
        'id_aula' => $idAula,
        'lora_id' => $loraId
    ]);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-GATEWAY-KEY: ' . (defined('GATEWAY_API_KEY') ? GATEWAY_API_KEY : ''),
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT => 7,
        CURLOPT_CONNECTTIMEOUT => 3
    ]);

    $respuesta = curl_exec($ch);
    $codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($respuesta === false) {
        return [
            'ok'      => false,
            'message' => 'Sin conexión con el Gateway LoRa (' . ($curlErr ?: 'Timeout') . ')',
            'codigo'  => $codigo ?: 504
        ];
    }

    $json = json_decode($respuesta, true);
    $ok = ($codigo >= 200 && $codigo < 300) && ($json['status'] ?? '') === 'success';

    return [
        'ok'      => $ok,
        'message' => $json['message'] ?? ($ok ? 'Sensor vaciado exitosamente' : 'Fallo al vaciar sensor en el aula'),
        'codigo'  => $codigo,
        'data'    => $json
    ];
}

/**
 * Envía una solicitud HTTP al nodo Gateway ESP32 para que consulte
 * mediante LoRa la cantidad de plantillas biométricas y estado del sensor DY50 en el aula.
 *
 * @param int $idAula ID del aula
 * @param int $loraId Dirección LoRa del nodo de aula
 * @return array ['ok' => bool, 'message' => string, 'total_huellas' => int, 'rssi' => int|null, 'snr' => int|null, 'codigo' => int]
 */
function solicitarAuditoriaAulaGateway(int $idAula, int $loraId): array {
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'message' => 'cURL no disponible en el servidor PHP', 'total_huellas' => 0, 'codigo' => 500];
    }

    $gatewayIp = defined('IP_ESP32_GATEWAY') ? IP_ESP32_GATEWAY : '';
    if (empty($gatewayIp)) {
        return ['ok' => false, 'message' => 'IP del Gateway LoRa no configurada', 'total_huellas' => 0, 'codigo' => 500];
    }

    $url = "http://{$gatewayIp}/audit_aula";
    $ch = curl_init($url);
    if (!$ch) {
        return ['ok' => false, 'message' => 'No se pudo inicializar cURL', 'total_huellas' => 0, 'codigo' => 500];
    }

    $postData = http_build_query([
        'id_aula' => $idAula,
        'lora_id' => $loraId
    ]);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-GATEWAY-KEY: ' . (defined('GATEWAY_API_KEY') ? GATEWAY_API_KEY : ''),
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_TIMEOUT => 7,
        CURLOPT_CONNECTTIMEOUT => 3
    ]);

    $respuesta = curl_exec($ch);
    $codigo = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($respuesta === false) {
        return [
            'ok'            => false,
            'message'       => 'Sin conexión con el Gateway LoRa (' . ($curlErr ?: 'Timeout') . ')',
            'total_huellas' => 0,
            'codigo'        => $codigo ?: 504
        ];
    }

    $json = json_decode($respuesta, true);
    $ok = ($codigo >= 200 && $codigo < 300) && ($json['status'] ?? '') === 'success';

    return [
        'ok'            => $ok,
        'message'       => $json['message'] ?? ($ok ? 'Auditoría LoRa completada' : 'Fallo al auditar sensor en el aula'),
        'total_huellas' => (int)($json['total_huellas'] ?? 0),
        'rssi'          => isset($json['rssi']) ? (int)$json['rssi'] : null,
        'snr'           => isset($json['snr']) ? (int)$json['snr'] : null,
        'codigo'        => $codigo,
        'data'          => $json
    ];
}
