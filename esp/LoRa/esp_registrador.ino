/*
 * Nodo registrador biometrico Sisco-Edu.
 * Exporta templates DY50/AS608 en HEX (1536 bytes / 3072 caracteres),
 * formato requerido por guardar_template.php y el transporte LoRa RYLR998.
 */
#include <Arduino.h>
#include <WiFi.h>
#include <WebServer.h>
#include <Adafruit_Fingerprint.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include "dy50_template_transport.h"

constexpr size_t HUELLA_TEMPLATE_BYTES = 1536;
static_assert(Dy50TemplateTransport::TEMPLATE_BYTES == HUELLA_TEMPLATE_BYTES,
              "dy50_template_transport.h desactualizado: se requieren 1536 bytes");

constexpr char WIFI_SSID[] = "esp";
constexpr char WIFI_PASSWORD[] = "123456789";
constexpr uint16_t SLOT_TEMPORAL = 1;
constexpr int DY50_RX = 16;
constexpr int DY50_TX = 17;
constexpr int SCREEN_WIDTH = 128;
constexpr int SCREEN_HEIGHT = 64;

WebServer server(80);
HardwareSerial dy50Serial(2);
Adafruit_Fingerprint finger(&dy50Serial);
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

bool enrolamientoPendiente = false;
bool templateDisponible = false;
String ultimoError;

void pantalla(const String &linea1, const String &linea2 = "") {
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.setTextSize(2); display.setCursor(0, 8); display.println(linea1);
  display.setTextSize(1); display.setCursor(0, 48); display.println(linea2);
  display.display();
}

void cors() { server.sendHeader("Access-Control-Allow-Origin", "*"); }

void jsonError(int codigo, const String &mensaje) {
  server.send(codigo, "application/json", "{\"status\":\"error\",\"message\":\"" + mensaje + "\"}");
}

bool esperarDedo(const String &titulo, const String &subtitulo, uint32_t timeoutMs = 30000) {
  pantalla(titulo, subtitulo);
  const uint32_t inicio = millis();
  while (millis() - inicio < timeoutMs) {
    const uint8_t resultado = finger.getImage();
    if (resultado == FINGERPRINT_OK) return true;
    if (resultado == FINGERPRINT_PACKETRECIEVEERR) { ultimoError = "ERROR_UART_DY50"; return false; }
    delay(80);
  }
  ultimoError = "TIMEOUT_DEDO";
  return false;
}

bool esperarRetiroDedo() {
  pantalla("RETIRE", "El dedo");
  const uint32_t inicio = millis();
  while (millis() - inicio < 15000) {
    const uint8_t resultado = finger.getImage();
    if (resultado == FINGERPRINT_NOFINGER) return true;
    if (resultado == FINGERPRINT_PACKETRECIEVEERR) { ultimoError = "ERROR_UART_DY50"; return false; }
    delay(80);
  }
  ultimoError = "TIMEOUT_RETIRO";
  return false;
}

bool enrolarHuella() {
  templateDisponible = false;
  ultimoError = "";
  if (!esperarDedo("PONGA DEDO", "Muestra 1/2")) return false;
  if (finger.image2Tz(1) != FINGERPRINT_OK) { ultimoError = "MUESTRA_1_INVALIDA"; return false; }
  if (!esperarRetiroDedo()) return false;
  if (!esperarDedo("PONGA DEDO", "Muestra 2/2")) return false;
  if (finger.image2Tz(2) != FINGERPRINT_OK) { ultimoError = "MUESTRA_2_INVALIDA"; return false; }
  pantalla("PROCESANDO", "Comparando muestras");
  if (finger.createModel() != FINGERPRINT_OK) { ultimoError = "HUELLAS_NO_COINCIDEN"; return false; }
  if (finger.storeModel(SLOT_TEMPORAL) != FINGERPRINT_OK) { ultimoError = "NO_SE_PUDO_GUARDAR"; return false; }
  templateDisponible = true;
  pantalla("CAPTURADA", "Lista para exportar");
  return true;
}

void configurarRutas() {
  server.on("/registrar", HTTP_GET, []() {
    cors();
    if (enrolamientoPendiente) { server.send(409, "application/json", "{\"status\":\"error\",\"message\":\"Captura en progreso\"}"); return; }
    templateDisponible = false;
    ultimoError = "";
    enrolamientoPendiente = true;
    server.send(202, "application/json", "{\"status\":\"success\",\"message\":\"Captura iniciada\"}");
  });

  server.on("/estado", HTTP_GET, []() {
    cors();
    server.send(200, "application/json", "{\"status\":\"success\",\"capturando\":" + String(enrolamientoPendiente ? "true" : "false") + ",\"template_disponible\":" + String(templateDisponible ? "true" : "false") + ",\"ultimo_error\":\"" + ultimoError + "\"}");
  });

  server.on("/obtener_template", HTTP_GET, []() {
    cors();
    if (enrolamientoPendiente) { server.send(409, "application/json", "{\"status\":\"error\",\"message\":\"Captura en progreso\"}"); return; }
    if (!templateDisponible) { jsonError(404, "No hay huella capturada"); return; }
    if (finger.loadModel(SLOT_TEMPORAL) != FINGERPRINT_OK) { templateDisponible = false; jsonError(404, "Template temporal no encontrado"); return; }
    if (finger.getModel() != FINGERPRINT_OK) { jsonError(500, "No se pudo iniciar la exportacion"); return; }

    // No vaciar el UART aqui: getModel() deja los paquetes PID_DATA del template
    // listos para que readTemplate() los valide y reconstruya.
    static uint8_t datos[Dy50TemplateTransport::TEMPLATE_BYTES];
    size_t bytesLeidos = 0;
    String error;
    if (!Dy50TemplateTransport::readTemplate(dy50Serial, datos, sizeof(datos), bytesLeidos, error)) {
      pantalla("ERR TRANS", error); jsonError(500, error); return;
    }
    const String hex = Dy50TemplateTransport::encodeHex(datos, bytesLeidos);
    String crc = String(Dy50TemplateTransport::crc32(datos, bytesLeidos), HEX);
    while (crc.length() < 8) crc = "0" + crc;
    pantalla("EXPORTADO", String(bytesLeidos) + " bytes HEX");
    server.send(200, "application/json", "{\"status\":\"success\",\"format\":\"HEX\",\"bytes\":" + String(bytesLeidos) + ",\"crc32\":\"" + crc + "\",\"template\":\"" + hex + "\"}");
  });

  server.on("/limpiar", HTTP_GET, []() {
    cors();
    const uint8_t resultado = finger.deleteModel(SLOT_TEMPORAL);
    if (resultado == FINGERPRINT_OK || resultado == FINGERPRINT_NOTFOUND) {
      templateDisponible = false;
      pantalla("ONLINE", WiFi.localIP().toString());
      server.send(200, "application/json", "{\"status\":\"success\",\"message\":\"Ranura temporal limpia\"}");
    } else jsonError(500, "No se pudo limpiar la ranura temporal");
  });

  server.onNotFound([]() { cors(); jsonError(404, "Ruta no encontrada"); });
}

void setup() {
  Serial.begin(115200);
  delay(300);
  Serial.printf("[BIOMETRIA] Contrato compilado: %u bytes / %u HEX\n",
                (unsigned int)HUELLA_TEMPLATE_BYTES,
                (unsigned int)(HUELLA_TEMPLATE_BYTES * 2));
  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) for (;;) delay(1000);
  pantalla("INICIANDO", "Nodo registrador");
  dy50Serial.begin(57600, SERIAL_8N1, DY50_RX, DY50_TX);
  finger.begin(57600);
  delay(800);
  if (!finger.verifyPassword()) { pantalla("ERROR HW", "DY50 no hallado"); for (;;) delay(1000); }

  WiFi.mode(WIFI_STA); WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  const uint32_t limite = millis() + 20000;
  while (WiFi.status() != WL_CONNECTED && (int32_t)(millis() - limite) < 0) { pantalla("WIFI", "Conectando..."); delay(300); }
  if (WiFi.status() != WL_CONNECTED) { pantalla("ERR WIFI", "Reinicie nodo"); for (;;) delay(1000); }
  configurarRutas(); server.begin();
  pantalla("ONLINE", WiFi.localIP().toString());
}

void loop() {
  server.handleClient();
  if (enrolamientoPendiente) {
    enrolamientoPendiente = false;
    if (!enrolarHuella()) pantalla("ERROR", ultimoError);
    delay(800);
    if (!templateDisponible) pantalla("ONLINE", WiFi.localIP().toString());
  }
  delay(5);
}
