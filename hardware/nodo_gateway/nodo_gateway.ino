// ESP GATEWAY: RYLR998 + Wi-Fi + API Sisco-Edu + Portal Web Seguro + Sincronización Dinámica
#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h>
#include <Preferences.h>
#include <DNSServer.h>
#include <ESPmDNS.h>
#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include "configuracion_red.h"
#include "interfaz_web.h"
#include "seguridad_portal.h"

// ============================================================================
// CONSTANTES Y CONTRATO DE INTEGRIDAD BIOMÉTRICA (1536 BYTES / 3072 HEX)
// ============================================================================
constexpr size_t HUELLA_TEMPLATE_BYTES = 1536;
constexpr size_t HUELLA_TEMPLATE_HEX_CHARS = HUELLA_TEMPLATE_BYTES * 2;
constexpr int CHARS_POR_CHUNK = 128;
constexpr int TOTAL_CHUNKS_HUELLA = HUELLA_TEMPLATE_HEX_CHARS / CHARS_POR_CHUNK;
static_assert(HUELLA_TEMPLATE_HEX_CHARS % CHARS_POR_CHUNK == 0,
              "El template debe dividirse en fragmentos LoRa completos");
const int MAX_REINTENTOS = 8;
#define LORA_RX 16
#define LORA_TX 17
#define PIN_BOOT_RESET 0 // Pulsador BOOT para restaurar credenciales de fábrica
constexpr int SCREEN_WIDTH = 128;
constexpr int SCREEN_HEIGHT = 64;

// ============================================================================
// OBJETOS DEL SISTEMA Y SEGURIDAD
// ============================================================================
HardwareSerial loraSerial(2);
WebServer server(80);
DNSServer dnsServer;
Preferences prefs;
SeguridadPortal seguridad("Gateway LoRa");
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);
bool displayOk = false;
unsigned long tiempoRestaurarPantalla = 0;

// Parámetros de red y enlace cargados desde Preferences (con fallback a configuracion_red.h)
String configSsid;
String configPass;
String configBaseUrl;
String configApiKey;
int configLoraId = LORA_ID_GATEWAY;
int configLoraNetId = LORA_RED_ID;

bool modoAP = false;
bool reiniciarPendiente = false;
unsigned long tiempoReinicio = 0;
const char *headerKeys[] = {"X-GATEWAY-KEY", "Cookie"};

// ============================================================================
// PROTOTIPOS DE FUNCIONES
// ============================================================================
void pantalla(const String &linea1, const String &linea2 = "", const String &linea3 = "");
void mostrarEstadoGatewayOnline();
void mostrarModoAP(const String &ssid = "SISCO-GATEWAY-CONFIG", const String &pass = "12345678", const String &ip = "192.168.4.1");
void mostrarGuiaBotonBoot(unsigned long duracionMs);
void notificarPantallaTemporal(const String &titulo, const String &linea1 = "", const String &linea2 = "", unsigned long duracionMs = 2500);

void cargarConfiguracion();
void guardarConfiguracion(const String &ssid, const String &pass, const String &url, const String &key, int loraNet, int loraId);
String normalizarBaseUrl(String url);
String formatearUptime();
void programarReinicio(unsigned long delayMs = 1500);

void conectarWifi();
void iniciarModoAP();

void handleRoot();
void handleGuardar();
void handleEstado();
void handleTestServer();
void handleScanWifi();
void handleReiniciar();
void handleNotFound();

void atenderMensajesLoRa();
void pedirSyncPendiente();
bool pedirSyncAula(int idAula, int loraId, int idHuella = 0);
bool pedirSyncEspecifica(int idHuella, int idAula);
void pedirSyncHuella(int idHuella);
bool enviarHuellaPorLoRa(int loraId, int idSync, int idHuella, int idAula, const String &ci, const String &tipo, const String &huella);
bool enviarFragmentoConAck(int loraId, const String &trama, int chunk, unsigned long timeoutMs = 2800);
bool esperarOKLocal(unsigned long timeoutMs = 500);
bool esperarAckRemoto(int loraId, int chunk, unsigned long timeoutMs = 900);
void enviarComandoLoRa(int destino, const String &payload);
void confirmarSync(int idSync, const String &estado, const String &mensaje);
void confirmarEntregaAula(const String &payload);
void procesarAsistenciaEntrante(const String &payload);
String campo(const String &texto, int indice);
String urlEncode(const String &texto);
bool crc32HexValido(const String &valor);

// ============================================================================
// PANTALLA OLED Y NOTIFICACIONES
// ============================================================================
void pantalla(const String &linea1, const String &linea2, const String &linea3) {
  if (!displayOk) return;
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);

  if (linea3.length() == 0) {
    display.setTextSize(2);
    display.setCursor(0, 6);
    display.println(linea1);

    display.drawLine(0, 26, 127, 26, SSD1306_WHITE);

    display.setTextSize(1);
    display.setCursor(0, 38);
    display.println(linea2);
  } else {
    display.setTextSize(1);
    display.setCursor(0, 2);
    display.println(linea1);
    display.drawLine(0, 13, 127, 13, SSD1306_WHITE);

    display.setCursor(0, 20);
    display.println(linea2);
    display.setCursor(0, 38);
    display.println(linea3);
  }
  display.display();
}

void mostrarEstadoGatewayOnline() {
  if (!displayOk) return;
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);

  // Titulo en tamaño 2: "GATEWAY ON"
  display.setTextSize(2);
  display.setCursor(4, 4);
  display.println("GATEWAY ON");

  display.drawLine(0, 24, 127, 24, SSD1306_WHITE);

  // LoRa ON - Red: Id de la red LoRa
  display.setTextSize(1);
  display.setCursor(0, 31);
  display.printf("LoRa ON - Red: %d\n", configLoraNetId);

  // IP: Ip del gateway
  display.setCursor(0, 47);
  String ipStr = (WiFi.status() == WL_CONNECTED) ? WiFi.localIP().toString() : "Sin conexion";
  display.printf("IP: %s\n", ipStr.c_str());

  display.display();
}

void mostrarModoAP(const String &ssid, const String &pass, const String &ip) {
  if (!displayOk) return;
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);

  display.setTextSize(2);
  display.setCursor(16, 2);
  display.println("SETUP AP");

  display.drawLine(0, 19, 127, 19, SSD1306_WHITE);

  display.setTextSize(1);
  if (ssid.length() <= 17) {
    display.setCursor(0, 23);
    display.printf("AP  : %s\n", ssid.c_str());
    display.setCursor(0, 37);
    display.printf("Pass: %s\n", pass.c_str());
    display.setCursor(0, 51);
    display.printf("Web : http://%s/\n", ip.c_str());
  } else {
    display.setCursor(0, 22);
    display.println("AP: " + ssid.substring(0, 17));
    display.setCursor(0, 32);
    display.println("    " + ssid.substring(17));
    display.setCursor(0, 43);
    display.printf("Pass: %s\n", pass.c_str());
    display.setCursor(0, 54);
    display.printf("Web : http://%s/\n", ip.c_str());
  }
  display.display();
}

void mostrarGuiaBotonBoot(unsigned long duracionMs) {
  if (!displayOk) return;
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);

  if (duracionMs < 2500) {
    display.setTextSize(1);
    display.setCursor(16, 2);
    display.println(">> BOTON BOOT <<");
    display.drawLine(0, 14, 127, 14, SSD1306_WHITE);

    display.setCursor(0, 19);
    display.println("Mantenga para Modo AP");
    display.setCursor(0, 31);
    float segs = duracionMs / 1000.0f;
    display.printf("Tiempo: %.1fs / 2.5s\n", segs);

    // Barra de progreso interactiva (0 - 2.5s)
    int anchoBarra = map(min((unsigned long)2500, duracionMs), 0, 2500, 0, 120);
    display.drawRect(3, 45, 122, 14, SSD1306_WHITE);
    display.fillRect(5, 47, max(0, min(anchoBarra, 118)), 10, SSD1306_WHITE);
  } else if (duracionMs < 8000) {
    display.setTextSize(1);
    display.setCursor(10, 2);
    display.println("** SOLTAR -> AP **");
    display.drawLine(0, 14, 127, 14, SSD1306_WHITE);

    display.setCursor(0, 19);
    display.println("Suelte: Entra a Setup");
    display.setCursor(0, 31);
    float segs = duracionMs / 1000.0f;
    display.printf("Reset Fabrica: %.1fs/8s\n", segs);

    // Barra de progreso interactiva (2.5s - 8s)
    int anchoBarra = map(min((unsigned long)8000, duracionMs) - 2500, 0, 5500, 0, 120);
    display.drawRect(3, 45, 122, 14, SSD1306_WHITE);
    display.fillRect(5, 47, max(0, min(anchoBarra, 118)), 10, SSD1306_WHITE);
  } else {
    display.setTextSize(1);
    display.setCursor(8, 2);
    display.println("!! RESET FABRICA !!");
    display.drawLine(0, 14, 127, 14, SSD1306_WHITE);

    display.setCursor(0, 19);
    display.println("Restaurando valores");
    display.setCursor(0, 33);
    display.println("de fabrica en flash...");
    display.setCursor(0, 48);
    display.println("Reiniciando Gateway...");
  }
  display.display();
}

void notificarPantallaTemporal(const String &titulo, const String &linea1, const String &linea2, unsigned long duracionMs) {
  if (!displayOk) return;
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);

  display.setTextSize(1);
  display.setCursor(0, 2);
  display.println(titulo);
  display.drawLine(0, 13, 127, 13, SSD1306_WHITE);

  display.setCursor(0, 20);
  display.println(linea1);
  display.setCursor(0, 38);
  display.println(linea2);

  display.display();

  tiempoRestaurarPantalla = millis() + duracionMs;
}

// ============================================================================
// GESTIÓN DE CONFIGURACIÓN NVS
// ============================================================================
String normalizarBaseUrl(String url) {
  url.trim();
  if (url.length() == 0) return "";
  if (!url.startsWith("http://") && !url.startsWith("https://")) {
    url = "http://" + url;
  }
  int slashPos = url.indexOf('/', 8);
  if (slashPos < 0) {
    url += "/sisco-edu/";
  } else if (slashPos == (int)url.length() - 1) {
    url += "sisco-edu/";
  }
  if (!url.endsWith("/")) {
    url += "/";
  }
  return url;
}

void cargarConfiguracion() {
  prefs.begin("sisco_gw", true);
  configSsid = prefs.getString("ssid", "");
  configPass = prefs.getString("pass", "");
  configBaseUrl = prefs.getString("server_url", SERVIDOR_BASE_URL);
  configApiKey = prefs.getString("api_key", CLAVE_API_GATEWAY);
  configLoraId = prefs.getInt("lora_id", LORA_ID_GATEWAY);
  configLoraNetId = prefs.getInt("lora_net", LORA_RED_ID);
  prefs.end();

  // Si no hay SSID guardado en NVS y la clave de acceso ya no es la de fábrica,
  // se permite usar los valores de respaldo de configuracion_red.h:
  if (configSsid.length() == 0 && !seguridad.debeCambiarPassword()) {
    configSsid = RED_WIFI_SSID;
    configPass = RED_WIFI_PASS;
  }

  configBaseUrl = normalizarBaseUrl(configBaseUrl);
  Serial.println("[NVS] Parametros cargados:");
  Serial.printf("  - Wi-Fi SSID  : %s\n", configSsid.c_str());
  Serial.printf("  - Servidor URL: %s\n", configBaseUrl.c_str());
  Serial.printf("  - LoRa Red ID : %d | Gateway Addr: %d\n", configLoraNetId, configLoraId);
}

void guardarConfiguracion(const String &ssid, const String &pass, const String &url, const String &key, int loraNet, int loraId) {
  prefs.begin("sisco_gw", false);
  prefs.putString("ssid", ssid);
  prefs.putString("pass", pass);
  prefs.putString("server_url", normalizarBaseUrl(url));
  prefs.putString("api_key", key);
  prefs.putInt("lora_net", loraNet);
  prefs.putInt("lora_id", loraId);
  prefs.end();
  Serial.println("[NVS] Parametros actualizados permanentemente en flash.");
}

String formatearUptime() {
  unsigned long segs = millis() / 1000;
  unsigned long mins = segs / 60;
  unsigned long horas = mins / 60;
  segs %= 60;
  mins %= 60;
  char buf[32];
  if (horas > 0) {
    snprintf(buf, sizeof(buf), "%luh %lum %lus", horas, mins, segs);
  } else if (mins > 0) {
    snprintf(buf, sizeof(buf), "%lum %lus", mins, segs);
  } else {
    snprintf(buf, sizeof(buf), "%lus", segs);
  }
  return String(buf);
}

void programarReinicio(unsigned long delayMs) {
  reiniciarPendiente = true;
  tiempoReinicio = millis() + delayMs;
}

// ============================================================================
// GESTIÓN DE CONEXIÓN WI-FI
// ============================================================================
void iniciarModoAP() {
  modoAP = true;
  tiempoRestaurarPantalla = 0;
  WiFi.disconnect();
  delay(100);
  WiFi.mode(WIFI_AP);
  const char *apSsid = "SISCO-GATEWAY-CONFIG";
  const char *apPass = "12345678";
  IPAddress apIP(192, 168, 4, 1);
  IPAddress netMsk(255, 255, 255, 0);
  WiFi.softAP(apSsid, apPass);
  WiFi.softAPConfig(apIP, apIP, netMsk);

  dnsServer.setErrorReplyCode(DNSReplyCode::NoError);
  dnsServer.start(53, "*", apIP);

  Serial.println("\n[PORTAL] ========================================");
  Serial.println("[PORTAL] MODO PUNTO DE ACCESO (AP) ACTIVADO");
  Serial.printf("[PORTAL] Red Wi-Fi : %s\n", apSsid);
  Serial.printf("[PORTAL] Clave     : %s\n", apPass);
  Serial.printf("[PORTAL] Abrir en  : http://%s\n", apIP.toString().c_str());
  Serial.println("[PORTAL] ========================================\n");

  mostrarModoAP(apSsid, apPass, apIP.toString());
}

void conectarWifi() {
  if (configSsid.length() == 0) {
    Serial.println("[WIFI] No hay red Wi-Fi configurada en memoria. Iniciando modo AP...");
    iniciarModoAP();
    return;
  }

  WiFi.mode(WIFI_STA);
  Serial.printf("[WIFI] Conectando a \"%s\"...", configSsid.c_str());
  pantalla("CONECTANDO", "Red: " + configSsid, "Espere por favor...");
  WiFi.begin(configSsid.c_str(), configPass.c_str());

  const unsigned long limite = millis() + 15000;
  while (WiFi.status() != WL_CONNECTED && (int32_t)(millis() - limite) < 0) {
    delay(300);
    Serial.print('.');
  }

  if (WiFi.status() == WL_CONNECTED) {
    modoAP = false;
    tiempoRestaurarPantalla = 0;
    Serial.println(" conectado");
    Serial.printf("[WIFI] Direccion IP: %s\n", WiFi.localIP().toString().c_str());
    Serial.printf("[WIFI] Senal RSSI  : %d dBm\n", WiFi.RSSI());
    if (MDNS.begin("sisco-gateway")) {
      MDNS.addService("http", "tcp", 80);
      Serial.println("[MDNS] Disponible en: http://sisco-gateway.local");
    }
    mostrarEstadoGatewayOnline();
  } else {
    Serial.println(" sin conexion");
    Serial.println("[WIFI] Fallo de conexion. Activando punto de acceso de configuracion...");
    pantalla("FALLO WIFI", "Red no alcanzable", "Activando Modo AP...");
    delay(1500);
    iniciarModoAP();
  }
}

// ============================================================================
// HANDLERS DEL SERVIDOR WEB EMBEBIDO
// ============================================================================
void handleRoot() {
  // Guardia de seguridad: login y cambio obligatorio de clave
  if (!seguridad.verificarAcceso()) return;

  String pagina = FPSTR(PAGINA_CONFIGURACION);

  pagina.replace("%MODO_CLASE%", modoAP ? "badge-ap" : "badge-online");
  if (modoAP) {
    pagina.replace("%MODO_TEXTO%", "🟠 MODO AP: SISCO-GATEWAY-CONFIG (192.168.4.1)");
  } else {
    pagina.replace("%MODO_TEXTO%", "🟢 ONLINE Wi-Fi: " + configSsid + " (IP: " + WiFi.localIP().toString() + ")");
  }

  IPAddress clientIP = server.client().remoteIP();
  String strClientIP = clientIP.toString();
  bool mostrarAuto = (strClientIP != "0.0.0.0" && strClientIP != "255.255.255.255" && strClientIP != WiFi.localIP().toString() && strClientIP != "192.168.4.1");
  pagina.replace("%AUTO_IP_DISPLAY%", mostrarAuto ? "flex" : "none");
  pagina.replace("%CLIENT_IP%", strClientIP);

  pagina.replace("%SERVER_URL%", configBaseUrl);
  pagina.replace("%API_KEY%", configApiKey);
  pagina.replace("%SSID%", configSsid);
  pagina.replace("%PASS%", configPass);
  pagina.replace("%LORA_NET%", String(configLoraNetId));
  pagina.replace("%LORA_ADDR%", String(configLoraId));
  pagina.replace("%MAC%", WiFi.macAddress());
  pagina.replace("%UPTIME%", formatearUptime());

  server.send(200, "text/html", pagina);
}

void handleGuardar() {
  if (!seguridad.verificarAcceso()) return;

  String ssid = server.arg("ssid");
  String pass = server.arg("pass");
  String serverUrl = server.arg("server_url");
  String apiKey = server.arg("api_key");
  int loraNet = server.arg("lora_net_id").toInt();
  int loraAddr = server.arg("lora_addr").toInt();

  if (ssid.length() > 0) configSsid = ssid;
  configPass = pass;
  if (serverUrl.length() > 0) configBaseUrl = normalizarBaseUrl(serverUrl);
  if (apiKey.length() > 0) configApiKey = apiKey;
  if (loraNet > 0) configLoraNetId = loraNet;
  if (loraAddr > 0) configLoraId = loraAddr;

  guardarConfiguracion(configSsid, configPass, configBaseUrl, configApiKey, configLoraNetId, configLoraId);

  pantalla("CONFIG GUARDADA", "Reiniciando Gateway...", "Espere por favor...");
  server.send_P(200, "text/html", PAGINA_GUARDADO);
  programarReinicio(2000);
}

void handleEstado() {
  DynamicJsonDocument doc(512);
  doc["status"] = "success";
  doc["dispositivo"] = "gateway";
  doc["modo"] = modoAP ? "AP" : "STA";
  doc["ip"] = modoAP ? WiFi.softAPIP().toString() : WiFi.localIP().toString();
  doc["mac"] = WiFi.macAddress();
  doc["ssid"] = configSsid;
  doc["servidor"] = configBaseUrl;
  doc["lora_net"] = configLoraNetId;
  doc["lora_id"] = configLoraId;
  doc["uptime_s"] = millis() / 1000;
  String json;
  serializeJson(doc, json);
  server.send(200, "application/json", json);
}

void handleTestServer() {
  if (!seguridad.verificarAcceso()) return;

  String url = server.hasArg("url") ? server.arg("url") : configBaseUrl;
  String key = server.hasArg("key") ? server.arg("key") : configApiKey;
  url = normalizarBaseUrl(url);

  if (WiFi.status() != WL_CONNECTED) {
    server.send(200, "application/json", "{\"status\":\"error\",\"message\":\"El Gateway no está conectado a una red Wi-Fi para alcanzar al servidor.\"}");
    return;
  }

  HTTPClient http;
  String testUrl = url + "src/api/Configuracion/probar_conexion.php?tipo=gateway";
  http.begin(testUrl);
  http.setTimeout(3500);
  http.addHeader("X-GATEWAY-KEY", key);
  unsigned long t0 = millis();
  int code = http.GET();
  unsigned long ms = millis() - t0;
  http.end();

  DynamicJsonDocument doc(256);
  if (code == 200) {
    doc["status"] = "success";
    doc["codigo"] = code;
    doc["latencia_ms"] = ms;
    doc["message"] = "Conexión exitosa con SISCO-EDU (" + url + ")";
  } else {
    doc["status"] = "error";
    doc["codigo"] = code;
    doc["latencia_ms"] = ms;
    doc["message"] = (code > 0) ? ("Servidor respondió con código HTTP " + String(code)) : "No se pudo alcanzar el host (Verifica la IP y que Apache/XAMPP esté activo)";
  }
  String json;
  serializeJson(doc, json);
  server.send(200, "application/json", json);
}

void handleScanWifi() {
  if (!seguridad.verificarAcceso()) return;

  int n = WiFi.scanNetworks();
  DynamicJsonDocument doc(2048);
  JsonArray arr = doc.to<JsonArray>();
  for (int i = 0; i < n && i < 15; ++i) {
    JsonObject obj = arr.createNestedObject();
    obj["ssid"] = WiFi.SSID(i);
    obj["rssi"] = WiFi.RSSI(i);
    obj["secure"] = (WiFi.encryptionType(i) != WIFI_AUTH_OPEN);
  }
  String json;
  serializeJson(doc, json);
  server.send(200, "application/json", json);
}

void handleReiniciar() {
  if (!seguridad.verificarAcceso()) return;
  pantalla("REINICIANDO", "Solicitud Web", "Espere por favor...");
  server.send(200, "application/json", "{\"status\":\"success\",\"message\":\"Reiniciando Gateway en 1 segundo...\"}");
  programarReinicio(1000);
}

void handleNotFound() {
  if (modoAP) {
    handleRoot();
    return;
  }
  server.send(404, "text/plain", "404 Not Found");
}

// ============================================================================
// SETUP Y LOOP
// ============================================================================
void setup() {
  Serial.begin(115200);
  delay(500);
  Serial.println("\n========================================");
  Serial.println("  SISCO-EDU - GATEWAY LoRa / Wi-Fi");
  Serial.println("========================================");

  pinMode(PIN_BOOT_RESET, INPUT_PULLUP);

  // Inicialización de Pantalla OLED SSD1306 (SDA: GPIO 21, SCL: GPIO 22)
  Wire.begin();
  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println("[ERROR] Display SSD1306 no responde en direccion I2C 0x3C");
    displayOk = false;
  } else {
    displayOk = true;
    display.clearDisplay();
    display.display();
    pantalla("SISCO-EDU", "Iniciando Gateway...", "Por favor espere...");
  }

  // Verificación interactiva de pulsador BOOT al encender (con guía visual)
  bool forzarApAlInicio = false;
  if (digitalRead(PIN_BOOT_RESET) == LOW) {
    Serial.println("[BOOT] Pulsador presionado al encender. Monitoreando duracion...");
    unsigned long t0 = millis();
    while (digitalRead(PIN_BOOT_RESET) == LOW) {
      unsigned long duracion = millis() - t0;
      mostrarGuiaBotonBoot(duracion);
      if (duracion >= 8000) {
        Serial.println("[RESET] 8s confirmados al encender: Restaurando fabrica...");
        mostrarGuiaBotonBoot(8000);
        seguridad.restablecerFabrica();
        prefs.begin("sisco_gw", false);
        prefs.clear();
        prefs.end();
        delay(1200);
        ESP.restart();
      }
      delay(40);
    }
    unsigned long duracionFinal = millis() - t0;
    if (duracionFinal >= 2500) {
      forzarApAlInicio = true;
      Serial.println("[BOOT] 2.5s alcanzados al encender: Se forzara Modo AP.");
    }
  }

  // Cargar credenciales maestras y estado de cambio de clave
  seguridad.cargarCredenciales();

  // Cargar configuración de red y enlace
  cargarConfiguracion();

  pantalla("CONFIG LORA", "Iniciando RYLR998...", "Red: " + String(configLoraNetId) + " Addr: " + String(configLoraId));

  Serial.printf("[BIOMETRIA] Contrato compilado: %u bytes / %u HEX / %d fragmentos\n",
                (unsigned int)HUELLA_TEMPLATE_BYTES,
                (unsigned int)HUELLA_TEMPLATE_HEX_CHARS,
                TOTAL_CHUNKS_HUELLA);
  Serial.printf("[INICIO] Configurando LoRa: RX=%d, TX=%d, direccion=%d, red=%d\n",
                LORA_RX, LORA_TX, configLoraId, configLoraNetId);
  loraSerial.begin(115200, SERIAL_8N1, LORA_RX, LORA_TX);
  loraSerial.setTimeout(120);
  loraSerial.printf("AT+ADDRESS=%d\r\n", configLoraId);
  delay(100);
  while (loraSerial.available()) loraSerial.read();
  loraSerial.printf("AT+NETWORKID=%d\r\n", configLoraNetId);
  delay(100);
  while (loraSerial.available()) loraSerial.read();
  Serial.println("[LORA] Modulo RYLR998 configurado");

  // Si se presiona el botón BOOT al arranque, si debe cambiar la contraseña de fábrica,
  // o si no hay Wi-Fi configurada en memoria: FORZAR MODO AP INMEDIATAMENTE
  if (forzarApAlInicio || seguridad.debeCambiarPassword() || configSsid.length() == 0) {
    Serial.println("[INICIO] Activando modo Punto de Acceso (AP) para configuracion inicial y cambio de clave...");
    iniciarModoAP();
  } else {
    conectarWifi();
  }

  // Configuración de rutas HTTP
  server.collectHeaders(headerKeys, 2);

  // Inicializar seguridad (Login, cambio obligatorio de pass y sesiones)
  seguridad.begin(server);

  server.on("/", HTTP_GET, handleRoot);
  server.on("/config", HTTP_GET, handleRoot);
  server.on("/guardar", HTTP_POST, handleGuardar);
  server.on("/estado", HTTP_GET, handleEstado);
  server.on("/test_server", HTTP_GET, handleTestServer);
  server.on("/scan_wifi", HTTP_GET, handleScanWifi);
  server.on("/reiniciar", HTTP_GET, handleReiniciar);

  // Endpoint de sincronización backend (protegido por token X-GATEWAY-KEY)
  server.on("/sync", HTTP_POST, []() {
    Serial.printf("[HTTP] Solicitud de sincronizacion recibida desde %s\n",
                  server.client().remoteIP().toString().c_str());
    if (server.header("X-GATEWAY-KEY") != configApiKey) {
      Serial.println("[HTTP] Solicitud rechazada: clave de gateway invalida");
      server.send(404, "application/json", "{\"status\":\"not_found\"}");
      return;
    }

    int idHuella = server.hasArg("id_huella") ? server.arg("id_huella").toInt() : 0;
    int idAula = server.hasArg("id_aula") ? server.arg("id_aula").toInt() : 0;

    server.send(202, "application/json", "{\"status\":\"accepted\",\"id_huella\":" + String(idHuella) + ",\"id_aula\":" + String(idAula) + "}");

    if (idHuella > 0 && idAula > 0) {
      Serial.printf("[SYNC DIRIGIDO] Huella ID %d dirigida al Aula %d...\n", idHuella, idAula);
      pedirSyncEspecifica(idHuella, idAula);
    } else if (idHuella > 0) {
      Serial.printf("[SYNC DIRIGIDO] Huella ID %d dirigida a sus aulas asignadas...\n", idHuella);
      pedirSyncHuella(idHuella);
    } else {
      Serial.println("[SYNC] Consultando aulas activas con huellas pendientes...");
      pedirSyncPendiente();
    }
  });

  // Portal cautivo redirecciones
  server.on("/generate_204", handleRoot);
  server.on("/fwlink", handleRoot);
  server.on("/hotspot-detect.html", handleRoot);
  server.onNotFound(handleNotFound);

  server.begin();
  Serial.println("[HTTP] Servidor Web iniciado en el puerto 80");
  if (!modoAP) {
    Serial.printf("[HTTP] Portal Web local: http://%s/\n", WiFi.localIP().toString().c_str());
    Serial.printf("[HTTP] Endpoint sync   : http://%s/sync\n", WiFi.localIP().toString().c_str());
  } else {
    Serial.println("[HTTP] Portal Web AP   : http://192.168.4.1/");
  }

  Serial.println("[LISTO] Sistema Gateway Operativo:");
  Serial.println("  - Portal Web protegido con autenticacion y cambio obligatorio de clave");
  Serial.println("  - Sincronizacion dinamica de aulas LoRa desde base de datos");
  Serial.println("  - Reenvio bidireccional LoRa <-> API REST Sisco-Edu");
  Serial.println("========================================\n");
}

void loop() {
  if (modoAP) {
    dnsServer.processNextRequest();
  }
  server.handleClient();
  atenderMensajesLoRa();

  // Restauración de pantalla tras notificación temporal
  if (tiempoRestaurarPantalla > 0 && millis() >= tiempoRestaurarPantalla) {
    tiempoRestaurarPantalla = 0;
    if (modoAP) {
      mostrarModoAP();
    } else {
      mostrarEstadoGatewayOnline();
    }
  }

  // Monitoreo del botón BOOT (GPIO 0) en tiempo de ejecución con guía visual interactiva:
  // - Mantener presionado: muestra barra de progreso en vivo
  // - Soltar entre 2.5s y 8s: Activa inmediatamente el Modo AP (Setup)
  // - Mantener >= 8s: Restablece a valores de fábrica y reinicia
  static unsigned long tiempoPresionadoBoot = 0;
  static unsigned long ultimoRefrescoBoton = 0;
  static bool accionBootEjecutada = false;

  if (digitalRead(PIN_BOOT_RESET) == LOW) {
    if (tiempoPresionadoBoot == 0) {
      tiempoPresionadoBoot = millis();
      accionBootEjecutada = false;
      ultimoRefrescoBoton = 0;
    }
    unsigned long duracion = millis() - tiempoPresionadoBoot;

    if (millis() - ultimoRefrescoBoton >= 40) {
      ultimoRefrescoBoton = millis();
      mostrarGuiaBotonBoot(duracion);
    }

    if (duracion >= 8000 && !accionBootEjecutada) {
      Serial.println("\n[BOOT] 8 segundos detectados: RESETEANDO A FABRICA...");
      accionBootEjecutada = true;
      mostrarGuiaBotonBoot(8000);
      seguridad.restablecerFabrica();
      prefs.begin("sisco_gw", false);
      prefs.clear();
      prefs.end();
      delay(1200);
      ESP.restart();
    }
  } else {
    if (tiempoPresionadoBoot > 0) {
      unsigned long duracion = millis() - tiempoPresionadoBoot;
      if (!accionBootEjecutada && duracion >= 2500 && !modoAP) {
        Serial.println("\n[BOOT] Pulsador liberado tras 2.5s: ACTIVANDO MODO AP...");
        iniciarModoAP();
      } else if (!accionBootEjecutada) {
        // Se soltó antes de los 2.5s requeridos: cancelar y restaurar pantalla
        if (modoAP) {
          mostrarModoAP();
        } else {
          mostrarEstadoGatewayOnline();
        }
      }
      tiempoPresionadoBoot = 0;
      accionBootEjecutada = false;
    }
  }

  if (reiniciarPendiente && millis() >= tiempoReinicio) {
    Serial.println("[SISTEMA] Reiniciando microcontrolador...");
    delay(100);
    ESP.restart();
  }

  if (!modoAP) {
    static unsigned long ultimoReintentoWifi = 0;
    static wl_status_t ultimoEstadoWifi = WiFi.status();
    const wl_status_t estadoWifi = WiFi.status();
    if (estadoWifi != ultimoEstadoWifi) {
      if (estadoWifi == WL_CONNECTED) {
        Serial.printf("[WIFI] Conexion recuperada. IP: %s\n", WiFi.localIP().toString().c_str());
        mostrarEstadoGatewayOnline();
      } else if (ultimoEstadoWifi == WL_CONNECTED) {
        Serial.println("[WIFI] Conexion perdida con el router Wi-Fi");
        notificarPantallaTemporal("WIFI DESCONECTADO", "Reintentando enlace...", "", 4000);
      }
      ultimoEstadoWifi = estadoWifi;
    }
    if (estadoWifi != WL_CONNECTED && millis() - ultimoReintentoWifi > 15000) {
      ultimoReintentoWifi = millis();
      Serial.println("[WIFI] Intentando reconectar a la red...");
      WiFi.reconnect();
    }

    // Sondeo periódico automático de huellas pendientes (cada 45 segundos)
    static unsigned long ultimoSondeoSyncAuto = 0;
    if (estadoWifi == WL_CONNECTED && millis() - ultimoSondeoSyncAuto >= 45000) {
      ultimoSondeoSyncAuto = millis();
      Serial.println("[AUTO-SYNC] Comprobando sincronizaciones pendientes en segundo plano...");
      pedirSyncPendiente();
    }
  }
}

// ============================================================================
// COMUNICACIÓN LoRa RYLR998
// ============================================================================
void enviarComandoLoRa(int destino, const String &payload) {
  loraSerial.printf("AT+SEND=%d,%u,%s\r\n", destino, payload.length(), payload.c_str());
}

String campo(const String &texto, int indice) {
  int inicio = 0;
  for (int i = 0; i < indice; ++i) {
    inicio = texto.indexOf(':', inicio);
    if (inicio < 0) return "";
    ++inicio;
  }
  int fin = texto.indexOf(':', inicio);
  return fin < 0 ? texto.substring(inicio) : texto.substring(inicio, fin);
}

bool crc32HexValido(const String &valor) {
  if (valor.length() != 8) return false;
  for (size_t i = 0; i < valor.length(); ++i) {
    const char c = valor[i];
    if (!((c >= '0' && c <= '9') || (c >= 'a' && c <= 'f') || (c >= 'A' && c <= 'F'))) return false;
  }
  return true;
}

String urlEncode(const String &texto) {
  const char *hex = "0123456789ABCDEF";
  String salida;
  for (size_t i = 0; i < texto.length(); ++i) {
    unsigned char c = texto[i];
    if (isalnum(c) || c == '-' || c == '_' || c == '.') {
      salida += (char)c;
    } else {
      salida += '%';
      salida += hex[c >> 4];
      salida += hex[c & 15];
    }
  }
  return salida;
}

bool esperarOKLocal(unsigned long timeoutMs) {
  const unsigned long inicio = millis();
  while (millis() - inicio < timeoutMs) {
    if (!loraSerial.available()) {
      delay(1);
      continue;
    }
    String respuesta = loraSerial.readStringUntil('\n');
    respuesta.trim();
    if (respuesta == "+OK") return true;
    if (respuesta.startsWith("+ERR")) return false;
  }
  return false;
}

bool esperarAckRemoto(int loraId, int chunk, unsigned long timeoutMs) {
  const String esperado = "ACK" + String(chunk);
  const unsigned long inicio = millis();
  while (millis() - inicio < timeoutMs) {
    if (!loraSerial.available()) {
      delay(1);
      continue;
    }
    String linea = loraSerial.readStringUntil('\n');
    linea.trim();
    if (!linea.startsWith("+RCV=")) continue;
    int c1 = linea.indexOf(','), c2 = linea.indexOf(',', c1 + 1), c3 = linea.indexOf(',', c2 + 1);
    if (c1 < 0 || c2 < 0 || c3 < 0) continue;
    if (linea.substring(5, c1).toInt() == loraId && linea.substring(c2 + 1, c3) == esperado) return true;
  }
  return false;
}

bool enviarFragmentoConAck(int loraId, const String &trama, int chunk, unsigned long timeoutMs) {
  while (loraSerial.available()) loraSerial.read();
  enviarComandoLoRa(loraId, trama);

  const String esperado = "ACK" + String(chunk);
  const unsigned long inicio = millis();
  bool okLocal = false;

  while (millis() - inicio < timeoutMs) {
    if (!loraSerial.available()) {
      delay(4);
      continue;
    }
    String linea = loraSerial.readStringUntil('\n');
    linea.trim();
    if (linea.length() == 0) continue;

    if (linea == "+OK") {
      okLocal = true;
      continue;
    }
    if (linea.startsWith("+ERR")) {
      Serial.printf("[LORA] Error local RYLR998 al transmitir: %s\n", linea.c_str());
      return false;
    }
    if (linea.startsWith("+RCV=")) {
      int c1 = linea.indexOf(',');
      int c2 = linea.indexOf(',', c1 + 1);
      int c3 = linea.indexOf(',', c2 + 1);
      if (c1 > 0 && c2 > 0 && c3 > 0) {
        int emisor = linea.substring(5, c1).toInt();
        String payload = linea.substring(c2 + 1, c3);
        payload.trim();
        if (emisor == loraId && payload == esperado) {
          return true;
        }
      }
    }
  }
  Serial.printf("[LORA] Timeout esperando ACK%d de LoRa ID %d (+OK local=%s)\n",
                chunk, loraId, okLocal ? "SI" : "NO");
  return false;
}

bool enviarHuellaPorLoRa(int loraId, int idSync, int idHuella, int idAula, const String &ci, const String &tipo, const String &huella) {
  if (huella.length() != HUELLA_TEMPLATE_HEX_CHARS || (huella.length() & 1)) {
    Serial.println("[LORA] Template HEX invalido");
    return false;
  }
  const int total = (huella.length() + CHARS_POR_CHUNK - 1) / CHARS_POR_CHUNK;
  if (total != TOTAL_CHUNKS_HUELLA) {
    Serial.println("[LORA] Cantidad de fragmentos invalida");
    return false;
  }
  Serial.printf("[LORA] Transmitiendo Huella #%d a Aula ID %d (LoRa ID %d, %d fragmentos)...\n",
                idHuella, idAula, loraId, total);
  for (int chunk = 0; chunk < total; ++chunk) {
    const String datos = huella.substring(chunk * CHARS_POR_CHUNK, min((chunk + 1) * CHARS_POR_CHUNK, (int)huella.length()));
    const String trama = chunk == 0
      ? "0:" + String(idSync) + ":" + String(idHuella) + ":" + String(idAula) + ":" + ci + ":" + tipo + ":" + String(total) + ":" + datos
      : String(chunk) + ":" + datos;
    if (trama.length() > 240) {
      Serial.println("[LORA] Trama excede limite RYLR998");
      return false;
    }
    bool confirmado = false;
    for (int intento = 0; intento < MAX_REINTENTOS && !confirmado; ++intento) {
      confirmado = enviarFragmentoConAck(loraId, trama, chunk, 2800);
      if (!confirmado) {
        Serial.printf("[LORA] Fragmento %d/%d sin ACK; reintento %d/%d\n",
                      chunk + 1, total, intento + 1, MAX_REINTENTOS);
        delay(120);
      }
    }
    if (!confirmado) {
      Serial.printf("[LORA] Envio cancelado: sin ACK en fragmento %d (Destino LoRa ID %d)\n", chunk, loraId);
      return false;
    }
    delay(20);
  }
  Serial.printf("[LORA] Huella #%d enviada completa a LoRa ID %d; esperando confirmacion de almacenamiento del aula\n", idHuella, loraId);
  return true;
}

// ============================================================================
// COMUNICACIÓN CON LA API BACKEND SISCO-EDU (SINCRONIZACIÓN DINÁMICA)
// ============================================================================
void pedirSyncPendiente() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[SYNC] Consulta omitida: Wi-Fi no conectado");
    return;
  }

  HTTPClient http;
  const String url = configBaseUrl + "src/api/Gateway/obtener_aulas_sync.php";
  if (!http.begin(url)) {
    Serial.println("[SYNC] Error al inicializar peticion a obtener_aulas_sync.php");
    return;
  }
  http.addHeader("X-GATEWAY-KEY", configApiKey);
  const int codigo = http.GET();
  const String respuesta = http.getString();
  http.end();

  if (codigo != 200) {
    Serial.printf("[SYNC] API obtener_aulas_sync respondio HTTP %d\n", codigo);
    return;
  }

  DynamicJsonDocument doc(4096);
  if (deserializeJson(doc, respuesta) || String(doc["status"] | "") != "success") {
    Serial.println("[SYNC] Respuesta JSON de aulas no valida");
    return;
  }

  JsonArray aulas = doc["data"]["aulas"].as<JsonArray>();
  Serial.printf("[SYNC] %u aulas registradas en la base de datos\n", aulas.size());

  for (JsonObject aula : aulas) {
    const int idAula = aula["id_aula"] | 0;
    const int loraId = aula["lora_id"] | 0;
    const int pendientes = aula["pendientes"] | 0;
    const String nombre = aula["nombre_aula"] | "";

    if (idAula > 0 && loraId > 0 && pendientes > 0) {
      Serial.printf("[SYNC] Aula %s (ID %d, LoRa %d) tiene %d huellas pendientes\n",
                    nombre.c_str(), idAula, loraId, pendientes);
      pedirSyncAula(idAula, loraId);
    }
  }

  Serial.println("[SYNC] Consulta dinamica finalizada");
}

bool pedirSyncAula(int idAula, int loraId, int idHuella) {
  Serial.printf("[SYNC] Consultando huella para aula %d (direccion LoRa %d, huella %d)\n", idAula, loraId, idHuella);
  HTTPClient http;
  String url = configBaseUrl + "src/api/Gateway/obtener_sync_aula.php?id_aula=" + String(idAula);
  if (idHuella > 0) {
    url += "&id_huella=" + String(idHuella);
  }

  if (!http.begin(url)) {
    Serial.println("[SYNC] No se pudo iniciar la solicitud HTTP");
    return false;
  }
  http.addHeader("X-GATEWAY-KEY", configApiKey);
  const int codigo = http.GET();
  const String respuesta = http.getString();
  http.end();

  if (codigo != 200) {
    Serial.printf("[SYNC] Aula %d: API respondio HTTP %d\n", idAula, codigo);
    return false;
  }
  DynamicJsonDocument doc(8192);
  if (deserializeJson(doc, respuesta) || String(doc["status"] | "") != "success") {
    Serial.printf("[SYNC] Aula %d: no hay sincronizacion pendiente valida\n", idAula);
    return false;
  }
  const int idSync = doc["data"]["id_sync"] | 0, idHuellaObtenida = doc["data"]["id_huella"] | 0;
  const int templateBytes = doc["data"]["bytes"] | 0;
  const int loraIdResp = doc["data"]["lora_id"] | 0;
  const int loraIdFinal = (loraIdResp > 0) ? loraIdResp : loraId;
  const String ci = doc["data"]["ci"] | "", tipo = doc["data"]["tipo_persona"] | "", huella = doc["data"]["huella_base64"] | "";
  if (!idSync || !idHuellaObtenida || templateBytes != HUELLA_TEMPLATE_BYTES || !ci.length() || !tipo.length() || huella.length() != HUELLA_TEMPLATE_HEX_CHARS) {
    Serial.printf("[SYNC] Aula %d: datos invalidos (sync=%d, huella=%d, bytes=%d/%u, hex=%u/%u)\n",
                  idAula, idSync, idHuellaObtenida, templateBytes,
                  (unsigned int)HUELLA_TEMPLATE_BYTES,
                  (unsigned int)huella.length(),
                  (unsigned int)HUELLA_TEMPLATE_HEX_CHARS);
    confirmarSync(idSync, "ERROR", "DATOS_SYNC_INVALIDOS");
    return false;
  }
  if (loraIdFinal <= 0) {
    Serial.printf("[SYNC] Aula %d no tiene un nodo LoRa ID valido asignado\n", idAula);
    notificarPantallaTemporal("ERROR LORA", "Aula sin LoRa ID", "Aula: " + String(idAula), 3500);
    confirmarSync(idSync, "ERROR", "AULA_SIN_LORA_ID");
    return false;
  }
  notificarPantallaTemporal("SYNC LORA", "Aula: " + String(idAula) + " (CI: " + ci + ")", "LoRa #" + String(loraIdFinal) + " H#" + String(idHuellaObtenida), 3500);
  if (!enviarHuellaPorLoRa(loraIdFinal, idSync, idHuellaObtenida, idAula, ci, tipo, huella)) {
    Serial.printf("[SYNC] Aula %d (LoRa ID %d): fallo el enlace LoRa\n", idAula, loraIdFinal);
    notificarPantallaTemporal("ERROR LORA", "Sin ACK LoRa #" + String(loraIdFinal), "Aula: " + String(idAula), 3500);
    confirmarSync(idSync, "ERROR", "FALLO_ENLACE_LORA (Sin ACK LoRa #" + String(loraIdFinal) + ")");
    return false;
  }
  return true;
}

bool pedirSyncEspecifica(int idHuella, int idAula) {
  return pedirSyncAula(idAula, 0, idHuella);
}

void pedirSyncHuella(int idHuella) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[SYNC] Consulta omitida: Wi-Fi no conectado");
    return;
  }

  HTTPClient http;
  const String url = configBaseUrl + "src/api/Gateway/obtener_aulas_sync.php?id_huella=" + String(idHuella);
  if (!http.begin(url)) {
    Serial.println("[SYNC] Error al inicializar peticion a obtener_aulas_sync.php");
    return;
  }
  http.addHeader("X-GATEWAY-KEY", configApiKey);
  const int codigo = http.GET();
  const String respuesta = http.getString();
  http.end();

  if (codigo != 200) {
    Serial.printf("[SYNC] API obtener_aulas_sync respondio HTTP %d\n", codigo);
    return;
  }

  DynamicJsonDocument doc(4096);
  if (deserializeJson(doc, respuesta) || String(doc["status"] | "") != "success") {
    Serial.println("[SYNC] Respuesta JSON de aulas no valida");
    return;
  }

  JsonArray aulas = doc["data"]["aulas"].as<JsonArray>();
  Serial.printf("[SYNC] %u aulas encontradas para Huella ID %d\n", aulas.size(), idHuella);

  for (JsonObject aula : aulas) {
    const int idAula = aula["id_aula"] | 0;
    const int loraId = aula["lora_id"] | 0;
    if (idAula > 0) {
      pedirSyncAula(idAula, loraId, idHuella);
      delay(800);
    }
  }
}

void confirmarSync(int idSync, const String &estado, const String &mensaje) {
  if (!idSync || WiFi.status() != WL_CONNECTED) {
    Serial.printf("[SYNC] No se pudo confirmar sync %d: datos invalidos o Wi-Fi desconectado\n", idSync);
    return;
  }
  HTTPClient http;
  if (!http.begin(configBaseUrl + "src/api/Gateway/confirmar_sync.php")) return;
  http.addHeader("X-GATEWAY-KEY", configApiKey);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  const int codigo = http.POST("id_sync=" + String(idSync) + "&estado=" + urlEncode(estado) + "&mensaje=" + urlEncode(mensaje));
  http.end();
  Serial.printf("[SYNC] Estado de sync %d enviado a la API: %s (HTTP %d)\n",
                idSync, estado.c_str(), codigo);
}

void confirmarEntregaAula(const String &payload) {
  // SYNCOK:id_sync:id_huella:id_aula:slot:ci:tipo:CRC32_xxxxxxxx
  const int idSync = campo(payload, 1).toInt(), idHuella = campo(payload, 2).toInt(), idAula = campo(payload, 3).toInt(), slot = campo(payload, 4).toInt();
  const String ci = campo(payload, 5), tipo = campo(payload, 6);
  const String detalle = campo(payload, 7);
  String crc = detalle.startsWith("CRC32_") ? detalle.substring(6) : "";
  crc.toLowerCase();
  if (!idSync || !idHuella || !idAula || !slot || !ci.length() || !tipo.length() || !crc32HexValido(crc)) {
    Serial.println("[SYNC] Confirmacion LoRa de aula invalida");
    return;
  }
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[SYNC] Confirmacion no enviada: Wi-Fi desconectado");
    return;
  }
  Serial.printf("[SYNC] Aula %d confirmo huella %d en slot %d (CRC32=%s)\n", idAula, idHuella, slot, crc.c_str());
  notificarPantallaTemporal("HUELLA OK", "Aula: " + String(idAula) + " - Slot: " + String(slot), "CRC32: " + crc, 3000);
  HTTPClient http;
  if (!http.begin(configBaseUrl + "src/api/Gateway/confirmar_entrega_aula.php")) return;
  http.addHeader("X-GATEWAY-KEY", configApiKey);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  const String cuerpo = "id_sync=" + String(idSync) + "&id_huella=" + String(idHuella) + "&id_aula=" + String(idAula) + "&slot_local=" + String(slot) + "&ci=" + urlEncode(ci) + "&tipo_persona=" + urlEncode(tipo) + "&crc32=" + crc;
  const int codigo = http.POST(cuerpo);
  http.end();
  Serial.printf("[SYNC] Confirmacion de entrega enviada a la API (HTTP %d)\n", codigo);
}

void procesarAsistenciaEntrante(const String &payload) {
  // AST:id_aula:ci:tipo:estado
  const int idAula = campo(payload, 1).toInt();
  const String ci = campo(payload, 2), tipo = campo(payload, 3), estado = campo(payload, 4);
  if (!idAula || !ci.length() || !tipo.length() || !estado.length()) {
    Serial.println("[ASISTENCIA] Trama LoRa invalida");
    return;
  }
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[ASISTENCIA] Registro omitido: Wi-Fi desconectado");
    return;
  }
  Serial.printf("[ASISTENCIA] Recibida desde aula %d: tipo=%s, estado=%s\n",
                idAula, tipo.c_str(), estado.c_str());
  notificarPantallaTemporal("ASISTENCIA", "Aula: " + String(idAula) + " CI: " + ci, tipo + " [" + estado + "]", 3000);
  HTTPClient http;
  if (!http.begin(configBaseUrl + "src/api/Gateway/registrar_asistencia.php")) return;
  http.addHeader("X-GATEWAY-KEY", configApiKey);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  const int codigo = http.POST("id_aula=" + String(idAula) + "&ci=" + urlEncode(ci) + "&tipo_persona=" + urlEncode(tipo) + "&estado=" + urlEncode(estado));
  http.end();
  Serial.printf("[ASISTENCIA] Registro enviado a la API (HTTP %d)\n", codigo);
}

void atenderMensajesLoRa() {
  if (!loraSerial.available()) return;
  String linea = loraSerial.readStringUntil('\n');
  linea.trim();
  if (!linea.startsWith("+RCV=")) return;
  int c1 = linea.indexOf(','), c2 = linea.indexOf(',', c1 + 1), c3 = linea.indexOf(',', c2 + 1);
  if (c1 < 0 || c2 < 0 || c3 < 0) return;
  const String payload = linea.substring(c2 + 1, c3);
  const int finTipo = payload.indexOf(':');
  const String tipoMensaje = finTipo < 0 ? payload : payload.substring(0, finTipo);
  Serial.printf("[LORA] Mensaje %s recibido del dispositivo %d\n",
                tipoMensaje.c_str(), linea.substring(5, c1).toInt());
  if (payload.startsWith("AST:")) procesarAsistenciaEntrante(payload);
  else if (payload.startsWith("SYNCOK:")) confirmarEntregaAula(payload);
  else if (payload.startsWith("SYNCERR:")) confirmarSync(campo(payload, 1).toInt(), "ERROR", campo(payload, 7));
  else Serial.println("[LORA] Tipo de mensaje no reconocido");
}
