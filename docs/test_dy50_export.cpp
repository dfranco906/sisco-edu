// PRUEBA AISLADA - DY50 ORIGEN
// Enrola en slot 1 y exporta exclusivamente los 1536 bytes de payload del
// template. No incluye headers, longitudes ni checksums del framing UART.

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

bool enrollmentRequested = false;

void sendJson(int status, const String &body) {
  server.sendHeader("Access-Control-Allow-Origin", "*");
  server.send(status, "application/json", body);
}

bool enrollSlotOne(String &error) {
  int result = -1;
  Serial.println("[TEST] Coloque el dedo - muestra 1/2");
  while (result != FINGERPRINT_OK) {
    result = finger.getImage();
    if (result == FINGERPRINT_PACKETRECIEVEERR) {
      error = "CAPTURE_1_COMM_ERROR";
      return false;
    }
    delay(30);
  }

  result = finger.image2Tz(1);
  if (result != FINGERPRINT_OK) {
    error = "IMAGE2TZ_1_0x" + String(result, HEX);
    return false;
  }

  Serial.println("[TEST] Retire el dedo");
  do {
    delay(50);
    result = finger.getImage();
  } while (result != FINGERPRINT_NOFINGER);

  Serial.println("[TEST] Coloque nuevamente el mismo dedo - muestra 2/2");
  do {
    result = finger.getImage();
    if (result == FINGERPRINT_PACKETRECIEVEERR) {
      error = "CAPTURE_2_COMM_ERROR";
      return false;
    }
    delay(30);
  } while (result != FINGERPRINT_OK);

  result = finger.image2Tz(2);
  if (result != FINGERPRINT_OK) {
    error = "IMAGE2TZ_2_0x" + String(result, HEX);
    return false;
  }

  result = finger.createModel();
  if (result != FINGERPRINT_OK) {
    error = "CREATE_MODEL_0x" + String(result, HEX);
    return false;
  }

  result = finger.storeModel(TEST_SLOT);
  if (result != FINGERPRINT_OK) {
    error = "STORE_MODEL_0x" + String(result, HEX);
    return false;
  }

  Serial.println("[TEST] Modelo almacenado en slot 1 del sensor origen");
  return true;
}

bool exportTemplate(String &templateHex, String &crcHex, String &error) {
  uint8_t result = finger.loadModel(TEST_SLOT);
  if (result != FINGERPRINT_OK) {
    error = "LOAD_MODEL_0x" + String(result, HEX);
    return false;
  }

  Dy50TemplateTransport::drainInput(sensorSerial);
  result = finger.getModel();
  if (result != FINGERPRINT_OK) {
    error = "UPCHAR_0x" + String(result, HEX);
    return false;
  }

  static uint8_t templateData[Dy50TemplateTransport::TEMPLATE_BYTES];
  size_t bytesRead = 0;
  if (!Dy50TemplateTransport::readTemplate(
          sensorSerial, templateData, sizeof(templateData), bytesRead, error)) {
    return false;
  }

  templateHex = Dy50TemplateTransport::encodeHex(templateData, bytesRead);
  crcHex = String(Dy50TemplateTransport::crc32(templateData, bytesRead), HEX);
  while (crcHex.length() < 8)
    crcHex = "0" + crcHex;
  return true;
}

void setup() {
  Serial.begin(115200);
  sensorSerial.begin(57600, SERIAL_8N1, 16, 17);
  finger.begin(57600);

  if (!finger.verifyPassword()) {
    Serial.println("[TEST] ERROR: DY50 origen no responde");
    while (true)
      delay(1000);
  }

  finger.getParameters();
  Serial.printf("[TEST] DY50 origen OK. Capacidad reportada: %u\n",
                finger.capacity);

  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print('.');
  }
  Serial.printf("\n[TEST] Exportador: http://%s\n",
                WiFi.localIP().toString().c_str());

  server.on("/registrar", HTTP_GET, []() {
    enrollmentRequested = true;
    sendJson(202, "{\"status\":\"accepted\",\"slot_local\":1}");
  });

  server.on("/obtener_template", HTTP_GET, []() {
    String templateHex;
    String crcHex;
    String error;
    if (!exportTemplate(templateHex, crcHex, error)) {
      sendJson(500, "{\"status\":\"error\",\"message\":\"" + error + "\"}");
      return;
    }

    const String response =
        "{\"status\":\"success\",\"format\":\"HEX\","
        "\"bytes\":" +
        String(Dy50TemplateTransport::TEMPLATE_BYTES) +
        ",\"chars\":" + String(Dy50TemplateTransport::TEMPLATE_BYTES * 2) +
        ",\"crc32\":\"" + crcHex + "\",\"template\":\"" + templateHex + "\"}";
    sendJson(200, response);
  });

  server.begin();
}

void loop() {
  server.handleClient();
  if (enrollmentRequested) {
    enrollmentRequested = false;
    String error;
    if (!enrollSlotOne(error)) {
      Serial.println("[TEST] Enrollment ERROR: " + error);
    }
  }
  delay(10);
}
