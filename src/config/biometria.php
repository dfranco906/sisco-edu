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
