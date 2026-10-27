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
#include "seguridad_lora.h"

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

// Estado y diagnóstico de hardware LoRa RYLR998
bool loraOk = false;
String loraDiag = "No verificado";
String loraVersion = "";

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

bool ejecutarComandoAT(HardwareSerial &serial, const String &cmd, String &respuesta, unsigned long timeoutMs = 400);
bool sincronizarBaudRateRYLR(HardwareSerial &serial, int rxPin, int txPin);
bool verificarYConfigurarLoRa(HardwareSerial &serial, int rxPin, int txPin, int targetAddr, int targetNetId, String &diagSalida, String &versionSalida);
bool hacerPingLoRa(int loraId, int &emisorDetectado, String &payloadDetectado, int &rssi, int &snr, unsigned long timeoutMs = 2500);
bool vaciarSensorAulaLoRa(int loraId, int idAula, String &mensaje, unsigned long timeoutMs = 4500);

void conectarWifi();
void iniciarModoAP();

void handleRoot();
void handleGuardar();
void handleEstado();
void handleTestServer();
void handleTestLoRa();
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
void confirmarEntregaAula(const String &payload, int rssi = 0, int snr = 0);
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

  // LoRa verificado: OK o FALLO
  display.setTextSize(1);
  display.setCursor(0, 31);
  if (loraOk) {
    display.printf("LoRa: OK - Red: %d\n", configLoraNetId);
  } else {
    display.println("LoRa: FALLO / ERROR");
  }

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

  String badgeLora;
  if (loraOk) {
    badgeLora = "<div class=\"badge-status badge-online\" style=\"margin-top:0;\">🟢 RYLR998: OK (" + (loraVersion.length() > 0 ? loraVersion : "Detectado") + ")</div>";
  } else {
    badgeLora = "<div class=\"badge-status\" style=\"background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.4);margin-top:0;\">🔴 RYLR998: FALLO HW (" + loraDiag + ")</div>";
  }
  pagina.replace("%LORA_STATUS_BADGE%", badgeLora);

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
  doc["lora_ok"] = loraOk;
  doc["lora_diag"] = loraDiag;
  doc["lora_version"] = loraVersion;
  doc["uptime_s"] = millis() / 1000;
  String json;
  serializeJson(doc, json);
  server.send(200, "application/json", json);
}

void handleTestLoRa() {
  if (!seguridad.verificarAcceso()) return;

  int destino = server.hasArg("destino") ? server.arg("destino").toInt() : 110;
  if (destino < 0 || destino > 65535) {
    server.send(400, "application/json", "{\"status\":\"error\",\"message\":\"Destino LoRa invalido (rango 0 a 65535)\"}");
    return;
  }

  if (!loraOk) {
    server.send(200, "application/json", "{\"status\":\"error\",\"message\":\"El modulo LoRa local del Gateway reporta FALLO de comunicacion AT. Verifique conexiones fisicas (RX=GPIO 16, TX=GPIO 17).\"}");
    return;
  }

  int rssi = 0, snr = 0, emisorDetectado = 0;
  String payloadDetectado = "";
  notificarPantallaTemporal("TEST LORA PING", (destino == 0 ? "Ping Broadcast 0" : ("Hacia Nodo #" + String(destino))), "Espere...", 1500);

  unsigned long t0 = millis();
  bool exito = hacerPingLoRa(destino, emisorDetectado, payloadDetectado, rssi, snr, 2500);
  unsigned long latencia = millis() - t0;

  DynamicJsonDocument doc(512);
  if (exito) {
    doc["status"] = "success";
    doc["destino"] = destino;
    doc["emisor_real"] = emisorDetectado;
    doc["payload"] = payloadDetectado;
    doc["pong"] = true;
    doc["latencia_ms"] = latencia;
    doc["rssi"] = rssi;
    doc["snr"] = snr;

    int idAulaDetectada = 0;
    if (payloadDetectado.startsWith("PONG:")) {
      idAulaDetectada = campo(payloadDetectado, 1).toInt();
    }

    String detalle = "";
    if (destino == 0) {
      detalle = " [Detectado via Broadcast: Aula #" + String(idAulaDetectada) + ", LoRa ID #" + String(emisorDetectado) + "]";
    } else if (emisorDetectado != destino) {
      detalle = " [¡AVISO: Respondio Nodo #" + String(emisorDetectado) + " pero se pidio #" + String(destino) + "!]";
    }

    String aulaInfo = (idAulaDetectada > 0) ? ("Aula #" + String(idAulaDetectada) + " (Nodo #" + String(emisorDetectado) + ")") : ("Nodo #" + String(emisorDetectado));
    doc["message"] = "Enlace LoRa operativo con " + aulaInfo + detalle + " (RSSI: " + String(rssi) + " dBm, SNR: " + String(snr) + " dB, RTT: " + String(latencia) + " ms)";
    notificarPantallaTemporal("PING: OK", "Resp: #" + String(emisorDetectado), "RSSI: " + String(rssi) + " dBm", 2500);
  } else {
    doc["status"] = "error";
    doc["destino"] = destino;
    doc["pong"] = false;
    doc["message"] = "Sin respuesta PONG del Nodo #" + String(destino) + " tras 2.5s. Si desconoce el ID del aula, use la direccion 0 (Broadcast) para detectarlo.";
    notificarPantallaTemporal("PING: FALLO", "Sin respuesta", "Nodo #" + String(destino), 2500);
  }

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

  // Verificación y diagnóstico de hardware LoRa RYLR998
  pantalla("VERIF LORA...", "Iniciando RYLR998", "RX: " + String(LORA_RX) + " TX: " + String(LORA_TX));
  Serial.printf("[BIOMETRIA] Contrato compilado: %u bytes / %u HEX / %d fragmentos\n",
                (unsigned int)HUELLA_TEMPLATE_BYTES,
                (unsigned int)HUELLA_TEMPLATE_HEX_CHARS,
                TOTAL_CHUNKS_HUELLA);

  loraOk = verificarYConfigurarLoRa(loraSerial, LORA_RX, LORA_TX, configLoraId, configLoraNetId, loraDiag, loraVersion);
  if (loraOk) {
    pantalla("LORA: OK", "Red: " + String(configLoraNetId), "Addr: " + String(configLoraId));
    delay(1200);
  } else {
    pantalla("LORA: FALLO", "Sin resp RYLR998", "Rev. RX=" + String(LORA_RX) + " TX=" + String(LORA_TX));
    delay(3000);
  }

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
  server.on("/test_lora", HTTP_GET, handleTestLoRa);
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

  // Endpoint de vaciado remoto del sensor de un aula (protegido por X-GATEWAY-KEY)
  server.on("/clear_aula", HTTP_POST, []() {
    Serial.printf("[HTTP] Solicitud de vaciado de aula recibida desde %s\n",
                  server.client().remoteIP().toString().c_str());
    if (server.header("X-GATEWAY-KEY") != configApiKey) {
      Serial.println("[HTTP] Solicitud rechazada: clave de gateway invalida");
      server.send(404, "application/json", "{\"status\":\"not_found\"}");
      return;
    }

    int idAula = server.hasArg("id_aula") ? server.arg("id_aula").toInt() : 0;
    int loraId = server.hasArg("lora_id") ? server.arg("lora_id").toInt() : 0;

    if (idAula <= 0 || loraId <= 0) {
      server.send(400, "application/json", "{\"status\":\"error\",\"message\":\"Faltan parametros id_aula y lora_id validos\"}");
      return;
    }

    String detalle;
    bool exito = vaciarSensorAulaLoRa(loraId, idAula, detalle, 4500);

    DynamicJsonDocument doc(256);
    doc["status"] = exito ? "success" : "error";
    doc["id_aula"] = idAula;
    doc["lora_id"] = loraId;
    doc["message"] = detalle;

    String json;
    serializeJson(doc, json);
    server.send(exito ? 200 : 502, "application/json", json);
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

  // Comandos de diagnóstico desde Monitor Serial (115200 baud)
  if (Serial.available()) {
    String cmdSerial = Serial.readStringUntil('\n');
    cmdSerial.trim();
    if (cmdSerial.equalsIgnoreCase("PING") || cmdSerial.startsWith("PING ") || cmdSerial.startsWith("ping ")) {
      int destino = 110;
      int sp = cmdSerial.indexOf(' ');
      if (sp > 0) destino = cmdSerial.substring(sp + 1).toInt();
      Serial.printf("[MONITOR-SERIAL] Transmitiendo PING hacia LoRa ID %d...\n", destino);
      notificarPantallaTemporal("PING LORA", "Hacia #" + String(destino), "Espere...", 1500);
      int rssi = 0, snr = 0, emisor = 0;
      String payload = "";
      bool r = hacerPingLoRa(destino, emisor, payload, rssi, snr, 2500);
      if (r) {
        Serial.printf("[MONITOR-SERIAL] PONG recibido de #%d: RSSI=%d dBm, SNR=%d dB, Payload='%s'\n", emisor, rssi, snr, payload.c_str());
        notificarPantallaTemporal("PING: OK!", "RSSI: " + String(rssi) + " dBm", "Nodo #" + String(emisor), 3000);
      } else {
        Serial.printf("[MONITOR-SERIAL] Sin respuesta PONG de #%d tras timeout\n", destino);
        notificarPantallaTemporal("PING: FALLO", "Sin respuesta", "Nodo #" + String(destino), 3000);
      }
    } else if (cmdSerial.equalsIgnoreCase("LORA") || cmdSerial.equalsIgnoreCase("STATUS")) {
      Serial.println("[MONITOR-SERIAL] Ejecutando diagnostico de hardware LoRa...");
      loraOk = verificarYConfigurarLoRa(loraSerial, LORA_RX, LORA_TX, configLoraId, configLoraNetId, loraDiag, loraVersion);
      if (modoAP) mostrarModoAP(); else mostrarEstadoGatewayOnline();
    }
  }
}

// ============================================================================
// VERIFICACIÓN Y DIAGNÓSTICO DE HARDWARE LoRa RYLR998
// ============================================================================
bool ejecutarComandoAT(HardwareSerial &serial, const String &cmd, String &respuesta, unsigned long timeoutMs) {
  while (serial.available()) serial.read();
  serial.print(cmd + "\r\n");

  const unsigned long t0 = millis();
  respuesta = "";
  while (millis() - t0 < timeoutMs) {
    if (serial.available()) {
      String linea = serial.readStringUntil('\n');
      linea.trim();
      if (linea.length() > 0) {
        respuesta = linea;
        return true;
      }
    }
    delay(2);
  }
  return false;
}

bool sincronizarBaudRateRYLR(HardwareSerial &serial, int rxPin, int txPin) {
  serial.setRxBufferSize(1024);
  const unsigned long bauds[] = {115200, 9600, 57600};
  for (unsigned long b : bauds) {
    serial.begin(b, SERIAL_8N1, rxPin, txPin);
    serial.setTimeout(150);
    delay(40);
    while (serial.available()) serial.read();

    for (int intento = 0; intento < 2; ++intento) {
      serial.print("AT\r\n");
      const unsigned long t0 = millis();
      while (millis() - t0 < 250) {
        if (serial.available()) {
          String resp = serial.readStringUntil('\n');
          resp.trim();
          if (resp == "+OK") {
            if (b != 115200) {
              Serial.printf("[LORA] Modulo respondio a %lu baud. Reconfigurando a 115200...\n", b);
              serial.print("AT+IPR=115200\r\n");
              delay(150);
              serial.begin(115200, SERIAL_8N1, rxPin, txPin);
              delay(40);
            }
            return true;
          }
        }
        delay(2);
      }
      delay(40);
    }
  }
  serial.begin(115200, SERIAL_8N1, rxPin, txPin);
  return false;
}

bool verificarYConfigurarLoRa(HardwareSerial &serial, int rxPin, int txPin, int targetAddr, int targetNetId, String &diagSalida, String &versionSalida) {
  Serial.println("\n[LORA] ========================================");
  Serial.println("[LORA] DIAGNOSTICO DE HARDWARE RYLR998 (GATEWAY)");
  Serial.printf("[LORA] Pines UART : RX=GPIO %d, TX=GPIO %d\n", rxPin, txPin);
  Serial.printf("[LORA] Esperado   : Addr=%d, Red=%d\n", targetAddr, targetNetId);

  bool respondeAT = sincronizarBaudRateRYLR(serial, rxPin, txPin);
  if (!respondeAT) {
    diagSalida = "ERROR: Sin resp AT (Pines RX=" + String(rxPin) + " TX=" + String(txPin) + " y 3.3V)";
    versionSalida = "DESCONOCIDO";
    Serial.println("[LORA-FALLO] " + diagSalida);
    Serial.println("[LORA-FALLO] CAUSAS POSIBLES:");
    Serial.println("  1. Pines RX/TX cruzados o desconectados (ESP32 GPIO 16 RX -> RYLR998 TX, GPIO 17 TX -> RYLR998 RX).");
    Serial.println("  2. Modulo sin alimentacion (VCC 3.3V / GND).");
    Serial.println("  3. Pin RST del RYLR998 conectado a GND en lugar de libre o HIGH.");
    Serial.println("[LORA] ========================================\n");
    return false;
  }
  Serial.println("[LORA-OK] Modulo responde comandos AT (+OK)");

  // Consultar Versión
  versionSalida = "";
  if (ejecutarComandoAT(serial, "AT+VER?", versionSalida, 300)) {
    Serial.printf("[LORA] Firmware RYLR998: %s\n", versionSalida.c_str());
  }

  // 1. Configurar Frecuencia de Banda RF ISM 915 MHz (Semtech SX1262 / RYLR998)
  String respBand = "";
  ejecutarComandoAT(serial, "AT+BAND=" + String(LORA_BANDA_DEFAULT), respBand, 500);
  if (respBand != "+OK") {
    Serial.printf("[LORA-WARN] Respuesta configurando BAND (%lu Hz): %s\n", LORA_BANDA_DEFAULT, respBand.c_str());
  }

  // 2. Configurar Parámetros RF estándar (SF=7, BW=500kHz, CR=4/5, Preamble=12 para biometría rápida)
  String respParam = "";
  ejecutarComandoAT(serial, "AT+PARAMETER=" + String(LORA_PARAM_DEFAULT), respParam, 500);
  if (respParam != "+OK") {
    Serial.printf("[LORA-WARN] Respuesta configurando PARAMETER (%s): %s\n", LORA_PARAM_DEFAULT, respParam.c_str());
  }

  // 3. Configurar Potencia de Emisión Máxima (22 dBm)
  String respPwr = "";
  ejecutarComandoAT(serial, "AT+CRFOP=" + String(LORA_CRFOP_DEFAULT), respPwr, 400);

  // 4. Configurar Address y Network ID
  String respAddr = "";
  ejecutarComandoAT(serial, "AT+ADDRESS=" + String(targetAddr), respAddr, 400);

  String respNet = "";
  ejecutarComandoAT(serial, "AT+NETWORKID=" + String(targetNetId), respNet, 400);

  // Verificación mediante lectura (Readback)
  String leidoAddr = "", leidoNet = "", leidoBand = "", leidoParam = "";
  ejecutarComandoAT(serial, "AT+ADDRESS?", leidoAddr, 300);
  ejecutarComandoAT(serial, "AT+NETWORKID?", leidoNet, 300);
  ejecutarComandoAT(serial, "AT+BAND?", leidoBand, 300);
  ejecutarComandoAT(serial, "AT+PARAMETER?", leidoParam, 300);

  Serial.println("[LORA] Parametros verificados en memoria del modulo:");
  Serial.printf("  - Direccion (AT+ADDRESS?)  : %s\n", leidoAddr.c_str());
  Serial.printf("  - Red LoRa  (AT+NETWORKID?): %s\n", leidoNet.c_str());
  Serial.printf("  - Frecuencia(AT+BAND?)     : %s\n", leidoBand.c_str());
  Serial.printf("  - Parametros RF            : %s\n", leidoParam.c_str());
  Serial.println("[LORA] ========================================\n");

  diagSalida = "OK (Addr " + String(targetAddr) + ", Red " + String(targetNetId) + ")";
  return true;
}

bool hacerPingLoRa(int loraId, int &emisorDetectado, String &payloadDetectado, int &rssi, int &snr, unsigned long timeoutMs) {
  if (!loraOk) {
    Serial.println("[PING-ERR] Imposible enviar Ping: Modulo LoRa local en FALLO");
    return false;
  }
  while (loraSerial.available()) loraSerial.read();

  Serial.printf("[PING] Transmitiendo PING a LoRa ID %d...\n", loraId);
  enviarComandoLoRa(loraId, "PING:" + String(configLoraId));

  const unsigned long inicio = millis();
  bool okLocal = false;
  rssi = 0; snr = 0;
  emisorDetectado = 0;
  payloadDetectado = "";

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
    if (linea.startsWith("+RCV=")) {
      int c1 = linea.indexOf(',');
      int c2 = linea.indexOf(',', c1 + 1);
      int c3 = linea.indexOf(',', c2 + 1);
      int c4 = linea.indexOf(',', c3 + 1);
      if (c1 > 0 && c2 > 0 && c3 > 0) {
        int emisor = linea.substring(5, c1).toInt();
        String payload = linea.substring(c2 + 1, c3);
        payload.trim();
        if ((loraId == 0 || emisor == loraId) && (payload.startsWith("PONG") || payload == "PONG")) {
          if (c4 > 0) {
            rssi = linea.substring(c3 + 1, c4).toInt();
            snr = linea.substring(c4 + 1).toInt();
          }
          emisorDetectado = emisor;
          payloadDetectado = payload;
          Serial.printf("[PING] PONG recibido de Nodo %d! (Payload: %s, RSSI: %d dBm, SNR: %d dB, RTT: %lu ms)\n",
                        emisor, payload.c_str(), rssi, snr, millis() - inicio);
          return true;
        }
      }
    }
  }
  Serial.printf("[PING] Sin respuesta PONG de LoRa ID %d (+OK local=%s)\n", loraId, okLocal ? "SI" : "NO");
  return false;
}

bool vaciarSensorAulaLoRa(int loraId, int idAula, String &mensaje, unsigned long timeoutMs) {
  if (!loraOk) {
    mensaje = "Modulo LoRa del Gateway en fallo de comunicacion AT";
    return false;
  }
  while (loraSerial.available()) loraSerial.read();

  String cmdBase = "CMD:CLEAR:" + String(idAula);
  String cmdFirmado = SeguridadLoRa::firmar(cmdBase, configApiKey.c_str());
  Serial.printf("[CLEAR-AULA] Transmitiendo comando CLEAR firmado a Aula ID %d (LoRa ID %d)...\n", idAula, loraId);
  enviarComandoLoRa(loraId, cmdFirmado);

  const unsigned long inicio = millis();
  notificarPantallaTemporal("VACIAR AULA", "Aula #" + String(idAula), "Espere ACK...", 2000);

  while (millis() - inicio < timeoutMs) {
    if (!loraSerial.available()) {
      delay(4);
      continue;
    }
    String linea = loraSerial.readStringUntil('\n');
    linea.trim();
    if (linea.length() == 0) continue;

    if (linea.startsWith("+RCV=")) {
      int c1 = linea.indexOf(',');
      int c2 = linea.indexOf(',', c1 + 1);
      int c3 = linea.indexOf(',', c2 + 1);
      if (c1 > 0 && c2 > 0 && c3 > 0) {
        int emisor = linea.substring(5, c1).toInt();
        String payload = linea.substring(c2 + 1, c3);
        payload.trim();

        if (emisor == loraId || loraId == 0) {
          if (payload.startsWith("CLEAR_OK")) {
            // Formato: CLEAR_OK:idAula[:token]
            int aulaResp = campo(payload, 1).toInt();
            String tokenResp = campo(payload, 2);
            if (tokenResp.length() > 0) {
              String baseResp = "CLEAR_OK:" + String(aulaResp);
              if (!SeguridadLoRa::validarToken(baseResp, tokenResp, configApiKey.c_str())) {
                Serial.printf("[CLEAR-AULA] Alerta: CLEAR_OK con token HMAC invalido ('%s') de emisor %d\n", tokenResp.c_str(), emisor);
                continue;
              }
            }
            Serial.printf("[CLEAR-AULA] Exito autenticado: Aula %d (Nodo %d) confirmo vaciado completo del sensor DY50\n", idAula, emisor);
            mensaje = "Sensor DY50 y NVS vaciados exitosamente por el nodo de aula #" + String(emisor);
            notificarPantallaTemporal("CLEAR AULA: OK", "Aula #" + String(idAula), "Sensor vaciado", 2500);
            return true;
          } else if (payload.startsWith("CLEAR_ERR")) {
            String codErr = campo(payload, 2);
            if (codErr.length() == 0) codErr = payload.substring(10);
            Serial.printf("[CLEAR-AULA] Error reportado por Aula %d: %s\n", idAula, codErr.c_str());
            mensaje = "El sensor DY50 del aula reporto error al vaciar: " + codErr;
            notificarPantallaTemporal("CLEAR: ERROR", "Aula #" + String(idAula), codErr, 2500);
            return false;
          }
        }
      }
    }
  }

  mensaje = "Tiempo de espera agotado (4.5s) sin respuesta de vaciado del Aula #" + String(idAula) + " (LoRa #" + String(loraId) + ")";
  Serial.println("[CLEAR-AULA] " + mensaje);
  notificarPantallaTemporal("CLEAR: TIMEOUT", "Sin respuesta", "Nodo #" + String(loraId), 2500);
  return false;
}

// ============================================================================
// COMUNICACIÓN LoRa RYLR998
// ============================================================================
void enviarComandoLoRa(int destino, const String &payload) {
  loraSerial.print("AT+SEND=");
  loraSerial.print(destino);
  loraSerial.print(",");
  loraSerial.print(payload.length());
  loraSerial.print(",");
  loraSerial.print(payload);
  loraSerial.print("\r\n");
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

int ultimoChunkNackSolicitado = -1;

bool enviarFragmentoConAck(int loraId, const String &trama, int chunk, unsigned long timeoutMs) {
  while (loraSerial.available()) loraSerial.read();
  enviarComandoLoRa(loraId, trama);

  const String esperado = "ACK" + String(chunk);
  const unsigned long inicio = millis();
  ultimoChunkNackSolicitado = -1;
  bool okLocal = false;
  String ultimaLinea = "";

  while (millis() - inicio < timeoutMs) {
    if (!loraSerial.available()) {
      delay(3);
      continue;
    }
    String linea = loraSerial.readStringUntil('\n');
    linea.trim();
    if (linea.length() == 0) continue;
    ultimaLinea = linea;

    if (linea.indexOf("+OK") != -1) {
      okLocal = true;
      continue;
    }
    if (linea.indexOf("+ERR") != -1) {
      Serial.printf("[LORA] Error local RYLR998 al transmitir: %s\n", linea.c_str());
      return false;
    }
    int idxRcv = linea.indexOf("+RCV=");
    if (idxRcv != -1) {
      String datos = linea.substring(idxRcv + 5);
      int c1 = datos.indexOf(',');
      int c2 = datos.indexOf(',', c1 + 1);
      int c3 = datos.indexOf(',', c2 + 1);
      if (c1 > 0 && c2 > 0) {
        int emisor = datos.substring(0, c1).toInt();
        String payload = (c3 > 0) ? datos.substring(c2 + 1, c3) : datos.substring(c2 + 1);
        payload.trim();
        if ((loraId == 0 || emisor == loraId)) {
          // Si recibimos el ACK exacto esperado
          if (payload == esperado || payload.indexOf(esperado) != -1) {
            return true;
          }
          // Si el aula ya procesó y confirmó un chunk superior (ACK acumulativo)
          if (payload.startsWith("ACK")) {
            int ackNum = payload.substring(3).toInt();
            if (ackNum >= chunk) {
              Serial.printf("[LORA] ACK acumulativo recibido #%d (esperado ACK%d)\n", ackNum, chunk);
              return true;
            }
          }
          // Detección de NACK selectivo por fragmento faltante
          if (payload.startsWith("NACK:")) {
            int nackNum = payload.substring(5).toInt();
            Serial.printf("[LORA] NACK selectivo recibido de Aula #%d para chunk #%d\n", emisor, nackNum);
            ultimoChunkNackSolicitado = nackNum;
            return false;
          }
        }
      }
    }
  }
  Serial.printf("[LORA] Timeout esperando ACK%d de LoRa ID %d (+OK local=%s, ultima resp='%s')\n",
                chunk, loraId, okLocal ? "SI" : "NO", ultimaLinea.c_str());
  if (!okLocal) {
    Serial.println("[LORA-ALERTA] El modulo RYLR998 local no respondio +OK al comando AT+SEND. Revise conexion serial.");
  }
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
  Serial.printf("[LORA] Transmitiendo Huella #%d a Aula ID %d (LoRa ID %d, %d fragmentos con ARQ selectivo)...\n",
                idHuella, idAula, loraId, total);

  int chunk = 0;
  int reintentos = 0;

  while (chunk < total) {
    const String datos = huella.substring(chunk * CHARS_POR_CHUNK, min((chunk + 1) * CHARS_POR_CHUNK, (int)huella.length()));
    const String trama = (chunk == 0)
      ? "0:" + String(idSync) + ":" + String(idHuella) + ":" + String(idAula) + ":" + ci + ":" + tipo + ":" + String(total) + ":" + datos
      : String(chunk) + ":" + datos;

    if (trama.length() > 240) {
      Serial.println("[LORA] Trama excede limite RYLR998");
      return false;
    }

    bool confirmado = enviarFragmentoConAck(loraId, trama, chunk, 2800);

    if (confirmado) {
      reintentos = 0;
      chunk++;
      delay(20);
      continue;
    }

    // Si el aula notificó NACK selectivo solicitando un fragmento anterior faltante:
    if (ultimoChunkNackSolicitado >= 0 && ultimoChunkNackSolicitado < total) {
      Serial.printf("[LORA] Aula solicito retransmision selectiva del chunk #%d (NACK)\n", ultimoChunkNackSolicitado);
      chunk = ultimoChunkNackSolicitado;
      reintentos = 0;
      delay(40);
      continue;
    }

    // Reintento ordinario por pérdida o falta de respuesta
    reintentos++;
    if (reintentos < MAX_REINTENTOS) {
      Serial.printf("[LORA] Fragmento %d/%d sin ACK; reintento %d/%d hacia LoRa ID %d\n",
                    chunk + 1, total, reintentos, MAX_REINTENTOS, loraId);
      delay(120);
    } else {
      Serial.printf("[LORA] Envio cancelado: sin ACK en fragmento %d tras %d intentos (Destino LoRa ID %d)\n",
                    chunk, MAX_REINTENTOS, loraId);
      if (!loraOk) {
        notificarPantallaTemporal("ERROR LORA GW", "Modulo local fallo", "Revise pines 16/17", 3500);
      } else {
        notificarPantallaTemporal("SIN ACK LORA", "Destino #" + String(loraId), "Aula no responde", 3500);
      }
      return false;
    }
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

void confirmarEntregaAula(const String &payload, int rssi, int snr) {
  // SYNCOK:id_sync:id_huella:id_aula:slot:ci:tipo:CRC32_xxxxxxxx[:token]
  const int idSync = campo(payload, 1).toInt(), idHuella = campo(payload, 2).toInt(), idAula = campo(payload, 3).toInt(), slot = campo(payload, 4).toInt();
  const String ci = campo(payload, 5), tipo = campo(payload, 6);
  const String detalle = campo(payload, 7);
  const String token = campo(payload, 8);

  String crc = detalle.startsWith("CRC32_") ? detalle.substring(6) : "";
  crc.toLowerCase();
  if (!idSync || !idHuella || !idAula || !slot || !ci.length() || !tipo.length() || !crc32HexValido(crc)) {
    Serial.println("[SYNC] Confirmacion LoRa de aula invalida");
    return;
  }

  // Verificación de autenticidad criptográfica HMAC anti-spoofing
  if (token.length() > 0) {
    String baseValidar = "SYNCOK:" + String(idSync) + ":" + String(idHuella) + ":" + String(idAula) + ":" + String(slot) + ":" + ci + ":" + tipo + ":" + detalle;
    if (!SeguridadLoRa::validarToken(baseValidar, token, configApiKey.c_str())) {
      Serial.printf("[LORA-ALERTA] SYNCOK rechazado para Aula %d: Token HMAC invalido ('%s')\n", idAula, token.c_str());
      return;
    }
  }

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[SYNC] Confirmacion no enviada: Wi-Fi desconectado");
    return;
  }
  Serial.printf("[SYNC] Aula %d confirmo huella %d en slot %d (CRC32=%s, HMAC=%s, RSSI=%d, SNR=%d)\n",
                idAula, idHuella, slot, crc.c_str(), token.length() ? token.c_str() : "N/A", rssi, snr);
  notificarPantallaTemporal("HUELLA OK", "Aula: " + String(idAula) + " - Slot: " + String(slot), "CRC32: " + crc, 3000);
  HTTPClient http;
  if (!http.begin(configBaseUrl + "src/api/Gateway/confirmar_entrega_aula.php")) return;
  http.addHeader("X-GATEWAY-KEY", configApiKey);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  String cuerpo = "id_sync=" + String(idSync) + "&id_huella=" + String(idHuella) + "&id_aula=" + String(idAula) + "&slot_local=" + String(slot) + "&ci=" + urlEncode(ci) + "&tipo_persona=" + urlEncode(tipo) + "&crc32=" + crc;
  if (rssi != 0 || snr != 0) {
    cuerpo += "&rssi=" + String(rssi) + "&snr=" + String(snr);
  }
  const int codigo = http.POST(cuerpo);
  http.end();
  Serial.printf("[SYNC] Confirmacion de entrega enviada a la API (HTTP %d)\n", codigo);
}

void procesarAsistenciaEntrante(const String &payload) {
  // AST:id_aula:ci:tipo:estado[:token]
  const int idAula = campo(payload, 1).toInt();
  const String ci = campo(payload, 2), tipo = campo(payload, 3), estado = campo(payload, 4);
  const String token = campo(payload, 5);

  if (!idAula || !ci.length() || !tipo.length() || !estado.length()) {
    Serial.println("[ASISTENCIA] Trama LoRa invalida");
    return;
  }

  // Verificación de autenticidad criptográfica HMAC anti-spoofing
  if (token.length() > 0) {
    String baseValidar = "AST:" + String(idAula) + ":" + ci + ":" + tipo + ":" + estado;
    if (!SeguridadLoRa::validarToken(baseValidar, token, configApiKey.c_str())) {
      Serial.printf("[LORA-ALERTA] Asistencia rechazada para Aula %d: Token HMAC invalido ('%s')\n", idAula, token.c_str());
      return;
    }
  }

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[ASISTENCIA] Registro omitido: Wi-Fi desconectado");
    return;
  }
  Serial.printf("[ASISTENCIA] Recibida autenticada desde aula %d: CI=%s, tipo=%s, estado=%s\n",
                idAula, ci.c_str(), tipo.c_str(), estado.c_str());
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
  int c1 = linea.indexOf(','), c2 = linea.indexOf(',', c1 + 1), c3 = linea.indexOf(',', c2 + 1), c4 = linea.indexOf(',', c3 + 1);
  if (c1 < 0 || c2 < 0 || c3 < 0) return;
  const String payload = linea.substring(c2 + 1, c3);
  int rssi = (c4 > 0) ? linea.substring(c3 + 1, c4).toInt() : 0;
  int snr = (c4 > 0) ? linea.substring(c4 + 1).toInt() : 0;

  const int finTipo = payload.indexOf(':');
  const String tipoMensaje = finTipo < 0 ? payload : payload.substring(0, finTipo);
  Serial.printf("[LORA] Mensaje %s recibido del dispositivo %d (RSSI: %d dBm, SNR: %d dB)\n",
                tipoMensaje.c_str(), linea.substring(5, c1).toInt(), rssi, snr);
  if (payload.startsWith("AST:")) procesarAsistenciaEntrante(payload);
  else if (payload.startsWith("SYNCOK:")) confirmarEntregaAula(payload, rssi, snr);
  else if (payload.startsWith("SYNCERR:")) confirmarSync(campo(payload, 1).toInt(), "ERROR", campo(payload, 7) + " (RSSI: " + String(rssi) + " dBm)");
  else if (payload.startsWith("PING")) {
    int emisor = linea.substring(5, c1).toInt();
    Serial.printf("[LORA] PING recibido de Nodo ID %d. Respondiendo PONG...\n", emisor);
    enviarComandoLoRa(emisor, "PONG:" + String(configLoraId));
    notificarPantallaTemporal("PING LORA", "De Nodo #" + String(emisor), "PONG enviado", 2000);
  }
  else if (payload.startsWith("PONG")) {
    int emisor = linea.substring(5, c1).toInt();
    Serial.printf("[LORA] PONG recibido de Nodo ID %d (Payload: %s)\n", emisor, payload.c_str());
    notificarPantallaTemporal("PONG LORA", "De Nodo #" + String(emisor), "Enlace OK", 2500);
  }
  else Serial.println("[LORA] Tipo de mensaje no reconocido");
}
