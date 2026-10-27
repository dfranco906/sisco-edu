#pragma once

/**
 * ============================================================================
 * SISCO-EDU: CONFIGURACIÓN CENTRALIZADA DE RED Y HARDWARE
 * ============================================================================
 * Este archivo centraliza todas las direcciones IP, credenciales Wi-Fi y
 * parámetros de radio LoRa para los firmwares de los microcontroladores ESP32.
 * 
 * Si cambias de red Wi-Fi o servidor local, únicamente debes modificar
 * los valores en este archivo antes de compilar y flashear.
 */

// 1. Red Wi-Fi (Gateway y Registrador)
constexpr char RED_WIFI_SSID[] = "esp";
constexpr char RED_WIFI_PASS[] = "123456789";

// 2. Servidor Central SISCO-EDU (XAMPP / Apache / PHP)
// Modifica esta URL con la IP de la máquina host del servidor web
constexpr char SERVIDOR_BASE_URL[] = "http://192.168.100.52/sisco-edu/";

// 3. Seguridad de Enlace API (Cabecera X-GATEWAY-KEY)
constexpr char CLAVE_API_GATEWAY[] = "SISCO_GATEWAY_2026_SECRETO";

// 4. Parámetros de Red LoRa (Módulos Reyax RYLR998)
constexpr int LORA_RED_ID = 18;
constexpr int LORA_ID_GATEWAY = 100;
constexpr int LORA_ID_AULA_DEFAULT = 110;
constexpr unsigned long LORA_BANDA_DEFAULT = 915000000;
constexpr char LORA_PARAM_DEFAULT[] = "7,9,1,12";
constexpr int LORA_CRFOP_DEFAULT = 22;

// 5. Parámetros Biométricos Normalizados (Contrato 1536 bytes)
constexpr size_t TAMANO_HUELLA_BYTES = 1536;
constexpr size_t TAMANO_HUELLA_HEX = TAMANO_HUELLA_BYTES * 2;
constexpr int TAMANO_CHUNK_LORA = 128;
