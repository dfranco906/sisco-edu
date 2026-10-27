#pragma once
#include <Arduino.h>
#include <mbedtls/md.h>

/**
 * ============================================================================
 * SISCO-EDU: SEGURIDAD Y FIRMA CRIPTOGRÁFICA EN ENLACE LoRa (ANTI-SPOOFING)
 * ============================================================================
 * Genera y valida tokens de autenticación HMAC-SHA256 compactos (8 caracteres hex / 4 bytes)
 * para proteger la red de radiofrecuencia LoRa a 915 MHz contra:
 *   1. Inyección no autorizada de comandos críticos (ej. CMD:CLEAR para vaciar sensores).
 *   2. Falsificación de registros de asistencia de estudiantes o profesores (tramas AST).
 *   3. Replay o suplantación de confirmaciones de entrega de huellas (tramas SYNCOK).
 *
 * Utiliza la biblioteca criptográfica nativa mbedTLS integrada en el core del ESP32.
 */

namespace SeguridadLoRa {

/**
 * Genera un token HMAC-SHA256 truncado a 8 caracteres hexadecimales.
 */
inline String calcularToken(const String &mensaje, const char *clave) {
  uint8_t hmacResultado[32];
  mbedtls_md_context_t ctx;
  mbedtls_md_type_t mdType = MBEDTLS_MD_SHA256;

  mbedtls_md_init(&ctx);
  mbedtls_md_setup(&ctx, mbedtls_md_info_from_type(mdType), 1);
  mbedtls_md_hmac_starts(&ctx, (const unsigned char *)clave, strlen(clave));
  mbedtls_md_hmac_update(&ctx, (const unsigned char *)mensaje.c_str(), mensaje.length());
  mbedtls_md_hmac_finish(&ctx, hmacResultado);
  mbedtls_md_free(&ctx);

  char tokenHex[9];
  snprintf(tokenHex, sizeof(tokenHex), "%02x%02x%02x%02x",
           hmacResultado[0], hmacResultado[1], hmacResultado[2], hmacResultado[3]);
  return String(tokenHex);
}

/**
 * Valida si un token recibido coincide de forma exacta con la firma esperada.
 */
inline bool validarToken(const String &mensaje, const String &tokenRecibido, const char *clave) {
  if (tokenRecibido.length() != 8) {
    return false;
  }
  String esperado = calcularToken(mensaje, clave);
  return esperado.equalsIgnoreCase(tokenRecibido);
}

/**
 * Devuelve el mensaje firmado agregando el token como último campo separado por dos puntos:
 * "<mensaje>:<token>"
 */
inline String firmar(const String &mensaje, const char *clave) {
  String token = calcularToken(mensaje, clave);
  return mensaje + ":" + token;
}

} // namespace SeguridadLoRa
