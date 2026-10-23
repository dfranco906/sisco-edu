/*
 * ============================================================================
 * SISCO-EDU: NODO REGISTRADOR BIOMÉTRICO
 * ============================================================================
 * Exporta templates DY50/AS608 en HEX (1536 bytes / 3072 caracteres),
 * formato requerido por guardar_template.php y el transporte LoRa RYLR998.
 *
 * Características añadidas:
 *  - Portal cautivo SoftAP "SISCO-REGISTRADOR-CONFIG" (192.168.4.1) para aprovisionamiento Wi-Fi.
 *  - Persistencia de parámetros en memoria Flash NVS (Preferences namespace "sisco_reg").
 *  - Autenticación y cambio obligatorio de contraseña en primer acceso (seguridad_portal.h).
 *  - Restauración de fábrica física mediante botón BOOT (GPIO 0).
 */
#include <Arduino.h>
#include <WiFi.h>
#include <WebServer.h>
#include <DNSServer.h>
#include <ESPmDNS.h>
#include <Preferences.h>
#include <Adafruit_Fingerprint.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include "dy50_template_transport.h"
#include "configuracion_red.h"
#include "seguridad_portal.h"
#include "interfaz_registrador.h"

constexpr size_t HUELLA_TEMPLATE_BYTES = TAMANO_HUELLA_BYTES;
constexpr uint16_t SLOT_TEMPORAL_REGISTRO = 1;
static_assert(Dy50TemplateTransport::TEMPLATE_BYTES == HUELLA_TEMPLATE_BYTES,
              "dy50_template_transport.h desactualizado: se requieren 1536 bytes");

constexpr int DY50_RX = 16;
constexpr int DY50_TX = 17;
constexpr int PIN_BOOT_RESET = 0; // Pulsador BOOT (GPIO 0) para forzar AP o reset de fábrica
constexpr int SCREEN_WIDTH = 128;
constexpr int SCREEN_HEIGHT = 64;

// ============================================================================
// OBJETOS DEL SISTEMA
// ============================================================================
WebServer server(80);
DNSServer dnsServer;
Preferences prefs;
SeguridadPortal seguridad("Nodo Registrador");

HardwareSerial dy50Serial(2);
Adafruit_Fingerprint finger(&dy50Serial);
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

// Parámetros de red persistentes
String configSsid;
String configPass;
bool modoAP = false;
bool reiniciarPendiente = false;
unsigned long tiempoReinicio = 0;
bool sensorDy50Ok = false;

// Estado del enrolamiento biométrico
bool enrolamientoPendiente = false;
bool templateDisponible = false;
String ultimoError;
const char *headerKeys[] = {"Cookie"};

// ============================================================================
// PANTALLA OLED Y UTILIDADES
// ============================================================================
void pantalla(const String &linea1, const String &linea2 = "") {
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
  display.setTextSize(2); display.setCursor(0, 8); display.println(linea1);
  display.setTextSize(1); display.setCursor(0, 48); display.println(linea2);
  display.display();
}

String formatearUptime() {
  unsigned long segs = millis() / 1000;
  unsigned long mins = segs / 60;
  unsigned long horas = mins / 60;
  segs %= 60;
  mins %= 60;
  char buf[32];
  snprintf(buf, sizeof(buf), "%lu h %lu m %lu s", horas, mins, segs);
  return String(buf);
}

void cors() {
  server.sendHeader("Access-Control-Allow-Origin", "*");
  server.sendHeader("Access-Control-Allow-Methods", "GET, POST, OPTIONS");
  server.sendHeader("Access-Control-Allow-Headers", "Content-Type, Authorization, X-Requested-With");
}

void jsonError(int codigo, const String &mensaje) {
  server.send(codigo, "application/json", "{\"status\":\"error\",\"message\":\"" + mensaje + "\"}");
}

// ============================================================================
// GESTIÓN DE CONFIGURACIÓN NVS
// ============================================================================
void cargarConfiguracion() {
  prefs.begin("sisco_reg", true);
  configSsid = prefs.getString("ssid", RED_WIFI_SSID);
  configPass = prefs.getString("pass", RED_WIFI_PASS);
  prefs.end();

  Serial.println("[NVS-REG] Credenciales Wi-Fi cargadas:");
  Serial.printf("  - SSID: %s\n", configSsid.c_str());
}

void guardarConfiguracion(const String &ssid, const String &pass) {
  prefs.begin("sisco_reg", false);
  prefs.putString("ssid", ssid);
  prefs.putString("pass", pass);
  prefs.end();

  configSsid = ssid;
  configPass = pass;
  Serial.println("[NVS-REG] Credenciales guardadas exitosamente.");
}

// ============================================================================
// CONEXIÓN WI-FI Y PORTAL CAUTIVO
// ============================================================================
void iniciarModoAP() {
  modoAP = true;
  WiFi.disconnect();
  delay(100);
  WiFi.mode(WIFI_AP);

  const char *apSsid = "SISCO-REGISTRADOR-CONFIG";
  const char *apPass = "12345678";
  IPAddress apIP(192, 168, 4, 1);
  IPAddress netMsk(255, 255, 255, 0);

  WiFi.softAP(apSsid, apPass);
  WiFi.softAPConfig(apIP, apIP, netMsk);

  dnsServer.setErrorReplyCode(DNSReplyCode::NoError);
  dnsServer.start(53, "*", apIP);

  pantalla("SETUP REG", "AP: 192.168.4.1");
  Serial.println("\n[PORTAL] Modo Punto de Acceso (AP) Activado:");
  Serial.printf("  - Red Wi-Fi: %s (Clave: %s)\n", apSsid, apPass);
  Serial.printf("  - URL      : http://%s/\n\n", apIP.toString().c_str());
}

void conectarWifi() {
  if (configSsid.length() == 0) {
    Serial.println("[WIFI] SSID vacio. Iniciando modo AP...");
    iniciarModoAP();
    return;
  }

  WiFi.mode(WIFI_STA);
  Serial.printf("[WIFI] Conectando a \"%s\"...\n", configSsid.c_str());
  WiFi.begin(configSsid.c_str(), configPass.c_str());

  const unsigned long limite = millis() + 15000;
  while (WiFi.status() != WL_CONNECTED && (int32_t)(millis() - limite) < 0) {
    pantalla("WIFI", "Conectando...");
    delay(300);
    Serial.print('.');
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    modoAP = false;
    Serial.printf("[WIFI] Conectado exitosamente. IP: %s\n", WiFi.localIP().toString().c_str());
    pantalla("ONLINE", WiFi.localIP().toString());
    if (MDNS.begin("sisco-registrador")) {
      MDNS.addService("http", "tcp", 80);
      Serial.println("[MDNS] Disponible en: http://sisco-registrador.local");
    }
  } else {
    Serial.println("[WIFI] Fallo de conexion. Iniciando punto de acceso...");
    iniciarModoAP();
  }
}

// ============================================================================
// HANDLERS WEB DE CONFIGURACIÓN Y SEGURIDAD
// ============================================================================
void handleRootConfig() {
  if (!seguridad.verificarAcceso()) return;

  String p = FPSTR(PAGINA_CONFIG_REGISTRADOR);
  p.replace("%MODO_CLASE%", modoAP ? "badge-ap" : "badge-online");
  if (modoAP) {
    p.replace("%MODO_TEXTO%", "🟠 MODO AP (192.168.4.1)");
  } else {
    p.replace("%MODO_TEXTO%", "🟢 ONLINE: " + WiFi.localIP().toString());
  }

  p.replace("%SSID%", configSsid);
  p.replace("%PASS%", configPass);
  p.replace("%ESTADO_SENSOR%", sensorDy50Ok ? "Operativo (57600 baud)" : "ERROR: No detectado");
  p.replace("%MAC%", WiFi.macAddress());
  p.replace("%UPTIME%", formatearUptime());

  server.send(200, "text/html", p);
}

void handleGuardarConfig() {
  if (!seguridad.verificarAcceso()) return;

  String ssid = server.arg("ssid");
  String pass = server.arg("pass");
  ssid.trim();

  if (ssid.length() == 0) {
    server.send(400, "text/plain", "El nombre de red SSID no puede estar vacio.");
    return;
  }

  guardarConfiguracion(ssid, pass);
  server.send_P(200, "text/html", PAGINA_GUARDADO_REGISTRADOR);

  reiniciarPendiente = true;
  tiempoReinicio = millis() + 2000;
}

// ============================================================================
// LÓGICA BIOMÉTRICA DY50 / AS608
// ============================================================================
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
  
  // Persistir y recargar el modelo antes de exportarlo. Esta normalizacion
  // evita distribuir el contenido transitorio de CharBuffer1.
  uint8_t resultado = finger.storeModel(SLOT_TEMPORAL_REGISTRO);
  if (resultado != FINGERPRINT_OK) {
    ultimoError = "STORE_TEMP_0x" + String(resultado, HEX);
    return false;
  }
  resultado = finger.loadModel(SLOT_TEMPORAL_REGISTRO);
  if (resultado != FINGERPRINT_OK) {
    ultimoError = "LOAD_TEMP_0x" + String(resultado, HEX);
    return false;
  }
  Serial.printf("[BIOMETRIA] Template normalizado en slot temporal %u\n", SLOT_TEMPORAL_REGISTRO);
  templateDisponible = true;
  pantalla("CAPTURADA", "Lista para exportar");
  return true;
}

// ============================================================================
// CONFIGURACIÓN DE RUTAS HTTP
// ============================================================================
void configurarRutas() {
  // Manejo de preflight CORS
  server.on("/registrar", HTTP_OPTIONS, []() { cors(); server.send(204); });
  server.on("/estado", HTTP_OPTIONS, []() { cors(); server.send(204); });
  server.on("/obtener_template", HTTP_OPTIONS, []() { cors(); server.send(204); });
  server.on("/limpiar", HTTP_OPTIONS, []() { cors(); server.send(204); });

  // Rutas de administración y portal cautivo (protegidas por seguridad_portal)
  server.on("/", HTTP_GET, handleRootConfig);
  server.on("/config", HTTP_GET, handleRootConfig);
  server.on("/guardar", HTTP_POST, handleGuardarConfig);

  // Endpoints biométricos (consumidos por el navegador web en la vista de enrolamiento)
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
    if (finger.loadModel(SLOT_TEMPORAL_REGISTRO) != FINGERPRINT_OK) { jsonError(500, "No se pudo recargar el template temporal"); return; }
    if (finger.getModel() != FINGERPRINT_OK) { jsonError(500, "No se pudo iniciar la exportacion"); return; }

    static uint8_t datos[Dy50TemplateTransport::TEMPLATE_BYTES];
    size_t bytesLeidos = 0;
    String error;
    if (!Dy50TemplateTransport::readTemplate(dy50Serial, datos, sizeof(datos), bytesLeidos, error)) {
      pantalla("ERR TRANS", error); jsonError(500, error); return;
    }
    const String hex = Dy50TemplateTransport::encodeHex(datos, bytesLeidos);
    String crc = String(Dy50TemplateTransport::crc32(datos, bytesLeidos), HEX);
    while (crc.length() < 8) crc = "0" + crc;
    crc.toUpperCase();
    Serial.printf("[BIOMETRIA] Exportacion verificada: bytes=%u CRC32=%s\n",
                  (unsigned int)bytesLeidos, crc.c_str());
    pantalla("EXPORTADO", String(bytesLeidos) + " bytes HEX");
    server.send(200, "application/json", "{\"status\":\"success\",\"format\":\"HEX\",\"bytes\":" + String(bytesLeidos) + ",\"crc32\":\"" + crc + "\",\"template\":\"" + hex + "\"}");
  });

  server.on("/limpiar", HTTP_GET, []() {
    cors();
    const uint8_t borrado = finger.deleteModel(SLOT_TEMPORAL_REGISTRO);
    if (borrado != FINGERPRINT_OK && borrado != FINGERPRINT_BADLOCATION) {
      jsonError(500, "No se pudo liberar el slot temporal: 0x" + String(borrado, HEX));
      return;
    }
    templateDisponible = false;
    ultimoError = "";
    pantalla("ONLINE", modoAP ? "192.168.4.1" : WiFi.localIP().toString());
    server.send(200, "application/json", "{\"status\":\"success\",\"message\":\"Template temporal liberado\"}");
  });

  server.onNotFound([]() {
    if (modoAP) {
      server.sendHeader("Location", "http://192.168.4.1/", true);
      server.send(302, "text/plain", "");
    } else {
      cors();
      jsonError(404, "Ruta no encontrada");
    }
  });
}

// ============================================================================
// INICIALIZACIÓN (SETUP)
// ============================================================================
void setup() {
  Serial.begin(115200);
  delay(300);
  Serial.printf("[BIOMETRIA] Contrato compilado: %u bytes / %u HEX\n",
                (unsigned int)HUELLA_TEMPLATE_BYTES,
                (unsigned int)(HUELLA_TEMPLATE_BYTES * 2));

  // Inicialización de hardware
  pinMode(PIN_BOOT_RESET, INPUT_PULLUP);

  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println("[ERROR] Display SSD1306 no responde");
  }
  pantalla("INICIANDO", "Nodo registrador");

  // Detección de pulsación de botón físico de reset al arrancar
  if (seguridad.verificarBotonResetFisico(PIN_BOOT_RESET, 10000)) {
    pantalla("RESET FABRICA", "Restaurando...");
    prefs.begin("sisco_reg", false);
    prefs.clear();
    prefs.end();
    delay(2000);
    ESP.restart();
  }

  // Cargar configuración persistente
  cargarConfiguracion();

  // Inicializar sensor DY50
  dy50Serial.begin(57600, SERIAL_8N1, DY50_RX, DY50_TX);
  finger.begin(57600);
  delay(800);
  if (finger.verifyPassword()) {
    sensorDy50Ok = true;
    Serial.println("[DY50] Sensor biometrico detectado y autenticado OK.");
  } else {
    sensorDy50Ok = false;
    pantalla("ERROR HW", "DY50 no hallado");
    Serial.println("[DY50] ADVERTENCIA: Sensor DY50 no hallado en UART2.");
  }

  // Si se mantiene presionado el botón BOOT por 2 segundos durante el inicio, forzar AP
  if (digitalRead(PIN_BOOT_RESET) == LOW) {
    pantalla("FORZANDO AP", "Modo Setup...");
    delay(1000);
    iniciarModoAP();
  } else {
    conectarWifi();
  }

  // Registrar cookies y seguridad en servidor web
  server.collectHeaders(headerKeys, 1);
  seguridad.begin(server);
  configurarRutas();
  server.begin();

  Serial.println("[INIT] Servidor web iniciado en puerto 80.");
}

// ============================================================================
// BUCLE PRINCIPAL (LOOP)
// ============================================================================
void loop() {
  if (modoAP) {
    dnsServer.processNextRequest();
  }

  server.handleClient();

  if (reiniciarPendiente && (int32_t)(millis() - tiempoReinicio) >= 0) {
    Serial.println("[SISTEMA] Reiniciando nodo por solicitud...");
    ESP.restart();
  }

  if (enrolamientoPendiente) {
    enrolamientoPendiente = false;
    if (!enrolarHuella()) {
      pantalla("ERROR", ultimoError);
    }
    delay(800);
    if (!templateDisponible) {
      pantalla("ONLINE", modoAP ? "192.168.4.1" : WiFi.localIP().toString());
    }
  }

  delay(5);
}
