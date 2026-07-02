// PRUEBA AISLADA - DY50 DESTINO
// Recibe 2816 caracteres HEX (1408 bytes), ejecuta DownChar hacia CharBuffer1,
// guarda en slot 1, relee el template y exige igualdad byte a byte antes de
// habilitar la prueba de reconocimiento.

#include <Adafruit_Fingerprint.h>
#include <WebServer.h>
#include <WiFi.h>

#include "dy50_template_transport.h"

const char *WIFI_SSID = "esp";
const char *WIFI_PASSWORD = "123456789";
constexpr uint16_t TEST_SLOT = 1;

HardwareSerial sensorSerial(2);
Adafruit_Fingerprint finger(&sensorSerial);
WebServer server(80);

bool templateVerified = false;
uint32_t installedCrc32 = 0;

void sendJson(int status, const String &body) {
  server.sendHeader("Access-Control-Allow-Origin", "*");
  server.send(status, "application/json", body);
}

bool installTemplate(const String &templateHex, String &error) {
  static uint8_t templateData[Dy50TemplateTransport::TEMPLATE_BYTES];
  static uint8_t readbackData[Dy50TemplateTransport::TEMPLATE_BYTES];

  size_t decodedBytes = 0;
  if (!Dy50TemplateTransport::decodeHex(templateHex, templateData,
                                        sizeof(templateData), decodedBytes,
                                        error)) {
    return false;
  }
  if (decodedBytes != Dy50TemplateTransport::TEMPLATE_BYTES) {
    error = "LONGITUD_INVALIDA_" + String(decodedBytes);
    return false;
  }

  const uint32_t sourceCrc =
      Dy50TemplateTransport::crc32(templateData, decodedBytes);
  Serial.printf("[TEST] Template HEX válido. Bytes=%u CRC32=%08lX\n",
                static_cast<unsigned>(decodedBytes), sourceCrc);

  Serial.println("[TEST] DownChar hacia CharBuffer1...");
  if (!Dy50TemplateTransport::beginDownChar(sensorSerial, 1, error)) {
    return false;
  }
  if (!Dy50TemplateTransport::sendTemplate(sensorSerial, templateData,
                                           decodedBytes, error)) {
    return false;
  }

  delay(150);
  Serial.println("[TEST] Store CharBuffer1 -> slot 1...");
  const uint8_t storeResult = finger.storeModel(TEST_SLOT);
  if (storeResult != FINGERPRINT_OK) {
    error = "STOREMODEL_FALLO_0x" + String(storeResult, HEX);
    return false;
  }

  Serial.println("[TEST] Load slot 1 para readback...");
  const uint8_t loadResult = finger.loadModel(TEST_SLOT);
  if (loadResult != FINGERPRINT_OK) {
    error = "LOADMODEL_POST_STORE_0x" + String(loadResult, HEX);
    return false;
  }

  Dy50TemplateTransport::drainInput(sensorSerial);
  const uint8_t uploadResult = finger.getModel();
  if (uploadResult != FINGERPRINT_OK) {
    error = "UPCHAR_READBACK_0x" + String(uploadResult, HEX);
    return false;
  }

  size_t readbackBytes = 0;
  if (!Dy50TemplateTransport::readTemplate(sensorSerial, readbackData,
                                           sizeof(readbackData), readbackBytes,
                                           error)) {
    return false;
  }
  if (readbackBytes != decodedBytes) {
    error = "READBACK_LENGTH_MISMATCH";
    return false;
  }

  const uint32_t readbackCrc =
      Dy50TemplateTransport::crc32(readbackData, readbackBytes);
  if (readbackCrc != sourceCrc ||
      memcmp(readbackData, templateData, decodedBytes) != 0) {
    error = "READBACK_CHECKSUM_MISMATCH";
    return false;
  }

  templateVerified = true;
  installedCrc32 = readbackCrc;
  Serial.printf("[TEST] INSTALACIÓN VERIFICADA EN SLOT 1. CRC32=%08lX\n",
                installedCrc32);
  Serial.println("[TEST] Coloque el mismo dedo para probar reconocimiento");
  return true;
}

void verifyFinger() {
  if (!templateVerified)
    return;

  int result = finger.getImage();
  if (result == FINGERPRINT_NOFINGER)
    return;
  if (result != FINGERPRINT_OK) {
    Serial.printf("[TEST] getImage error 0x%02X\n", result);
    delay(500);
    return;
  }

  result = finger.image2Tz(1);
  if (result != FINGERPRINT_OK) {
    Serial.printf("[TEST] image2Tz error 0x%02X\n", result);
    delay(1000);
    return;
  }

  result = finger.fingerFastSearch();
  if (result == FINGERPRINT_OK && finger.fingerID == TEST_SLOT) {
    Serial.printf("[TEST] RECONOCIMIENTO OK: slot=%u confianza=%u\n",
                  finger.fingerID, finger.confidence);
  } else if (result == FINGERPRINT_OK) {
    Serial.printf("[TEST] MATCH EN SLOT INESPERADO: %u\n", finger.fingerID);
  } else {
    Serial.printf("[TEST] RECONOCIMIENTO FALLÓ: 0x%02X\n", result);
  }
  delay(1500);
}

void setup() {
  Serial.begin(115200);
  sensorSerial.begin(57600, SERIAL_8N1, 16, 17);
  finger.begin(57600);

  if (!finger.verifyPassword()) {
    Serial.println("[TEST] ERROR: DY50 destino no responde");
    while (true)
      delay(1000);
  }

  finger.getParameters();
  Serial.printf("[TEST] DY50 destino OK. Capacidad reportada: %u\n",
                finger.capacity);

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print('.');
  }
  Serial.printf("\n[TEST] Importador: http://%s\n",
                WiFi.localIP().toString().c_str());

  server.on("/estado", HTTP_GET, []() {
    String crcHex = String(installedCrc32, HEX);
    while (crcHex.length() < 8)
      crcHex = "0" + crcHex;
    sendJson(200, "{\"status\":\"success\",\"slot_local\":1,"
                  "\"template_verified\":" +
                      String(templateVerified ? "true" : "false") +
                      ",\"crc32\":\"" + crcHex + "\"}");
  });

  server.on("/importar_template", HTTP_POST, []() {
    const bool allowOverwrite = server.hasArg("allow_overwrite") &&
                                server.arg("allow_overwrite") == "1";

    if (!allowOverwrite && finger.loadModel(TEST_SLOT) == FINGERPRINT_OK) {
      sendJson(409, "{\"status\":\"error\",\"code\":\"SLOT_OCUPADO\","
                    "\"message\":\"Slot 1 ya contiene una huella. Use un "
                    "sensor de prueba vacío o allow_overwrite=1.\"}");
      return;
    }

    const String templateHex = server.arg("plain");
    if (templateHex.length() != Dy50TemplateTransport::TEMPLATE_BYTES * 2) {
      sendJson(422, "{\"status\":\"error\",\"code\":\"LONGITUD_INVALIDA\","
                    "\"received_chars\":" +
                        String(templateHex.length()) + "}");
      return;
    }

    templateVerified = false;
    String error;
    if (!installTemplate(templateHex, error)) {
      Serial.println("[TEST] IMPORTACIÓN FALLÓ: " + error);
      sendJson(422, "{\"status\":\"error\",\"code\":\"IMPORT_FAILED\","
                    "\"message\":\"" +
                        error + "\"}");
      return;
    }

    String crcHex = String(installedCrc32, HEX);
    while (crcHex.length() < 8)
      crcHex = "0" + crcHex;
    sendJson(200, "{\"status\":\"success\",\"slot_local\":1,"
                  "\"bytes\":1408,\"readback_verified\":true,"
                  "\"crc32\":\"" +
                      crcHex + "\"}");
  });

  server.begin();
}

void loop() {
  server.handleClient();
  verifyFinger();
  delay(20);
}
