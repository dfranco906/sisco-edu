// ESP AULA: RYLR998 + DY50 + OLED + Portal Cautivo NVS Seguro
#include <Arduino.h>
#include <WiFi.h>
#include <WebServer.h>
#include <DNSServer.h>
#include <Preferences.h>
#include <Adafruit_Fingerprint.h>
#include <Adafruit_SSD1306.h>
#include <Adafruit_GFX.h>
#include "dy50_template_transport.h"
#include "configuracion_red.h"
#include "interfaz_aula.h"
#include "seguridad_portal.h"

// ============================================================================
// CONSTANTES Y HARDWARE
// ============================================================================
constexpr size_t HUELLA_TEMPLATE_BYTES = TAMANO_HUELLA_BYTES;
constexpr size_t HUELLA_TEMPLATE_HEX_CHARS = TAMANO_HUELLA_HEX;
constexpr int CHARS_POR_CHUNK = TAMANO_CHUNK_LORA;
constexpr int MAX_CHUNKS_HUELLA = HUELLA_TEMPLATE_HEX_CHARS / CHARS_POR_CHUNK;
static_assert(Dy50TemplateTransport::TEMPLATE_BYTES == HUELLA_TEMPLATE_BYTES,
              "dy50_template_transport.h desactualizado: se requieren 1536 bytes");
static_assert(HUELLA_TEMPLATE_HEX_CHARS % CHARS_POR_CHUNK == 0,
              "El template debe dividirse en fragmentos LoRa completos");

#define DY50_RX 16
#define DY50_TX 17
#define LORA_RX 26
#define LORA_TX 27
#define PIN_BOTON_SALIDA 4
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

HardwareSerial dy50Serial(2);
HardwareSerial loraSerial(1);
Adafruit_Fingerprint finger(&dy50Serial);
Adafruit_SSD1306 oled(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);
Preferences prefs;
WebServer server(80);
DNSServer dnsServer;
SeguridadPortal seguridad("Nodo de Aula LoRa");

// ============================================================================
// PARÁMETROS DINÁMICOS DE AULA (NVS)
// ============================================================================
int configIdAula = 30;
String configCodigoAula = "3_RO_BTI";
int configLoraId = LORA_ID_AULA_DEFAULT;
int configLoraNetId = LORA_RED_ID;
int configGatewayId = LORA_ID_GATEWAY;

// Estado y diagnóstico de hardware LoRa RYLR998
bool loraOk = false;
String diagLoRa = "No verificado";
String versionLoRa = "";

bool modoSetup = false;
bool reiniciarPendiente = false;
unsigned long tiempoReinicio = 0;
const char *headerKeys[] = {"Cookie"};

enum EstadoSistema { NORMAL, ESPERANDO_PROFE, SALIDA_ACTIVA };
enum EstadoSync { SYNC_IDLE, SYNC_RECIBIENDO, SYNC_PROCESANDO };
EstadoSistema estadoActual = NORMAL;
EstadoSync estadoSync = SYNC_IDLE;
uint32_t tiempoLimiteEstado = 0;
const uint32_t TIEMPO_COOLDOWN = 300000;
uint32_t ultimoRegistroSlot[201] = {0};

struct RegistroUsuario { char ci[15]; char tipo[15]; bool registrado; };
RegistroUsuario dbLocal[201] = {};
char huellaBuffer[HUELLA_TEMPLATE_HEX_CHARS + 1] = {};
int ultimoIndiceProcesado = -1;
int totalChunksEsperados = 0;
size_t longitudHuellaHex = 0;
unsigned long tiempoUltimoChunk = 0;
unsigned long tiempoPrimerChunk = 0;
int idSyncActual = 0, idHuellaActual = 0, idAulaSyncActual = 0;
char ciSyncActual[15] = "", tipoSyncActual[15] = "";

// ============================================================================
// PROTOTIPOS
// ============================================================================
void msgOled(const String &t1, const String &t2 = "");
void mostrarPantallaReposoAula();
void cargarParametrosAula();
void guardarParametrosAula(int idAula, const String &codigo, int loraId, int loraNet, int gwId);
void iniciarModoSetupAP();
void handleRootAula();
void handleGuardarAula();
bool ejecutarComandoAT(HardwareSerial &serial, const String &cmd, String &respuesta, unsigned long timeoutMs = 400);
bool sincronizarBaudRateRYLR(HardwareSerial &serial, int rxPin, int txPin);
bool verificarYConfigurarLoRa(HardwareSerial &serial, int rxPin, int txPin, int targetAddr, int targetNetId, String &diagSalida, String &versionSalida);
void enviarComandoLoRa(int destino, const String &payload);
void enviarAckLoRa(int chunk);
void atenderComandosLoRa();
void verificarLecturaHuella();
void enviarAsistenciaPorLoRa(const String &ci, const String &tipo, const String &estado);
bool guardarHuellaDY50(const String &hex, int slot, String &crcHex, String &error);
void cargarDbLocalDesdeNVS();
void guardarSlotEnNVS(int slot);
void limpiarSyncRecibida();
void notificarResultadoSync(bool exito, int slot, const String &detalle);
String campo(const String &texto, int indice);

// ============================================================================
// CARGA Y PERSISTENCIA NVS
// ============================================================================
bool configAulaInicializada = false;

void cargarParametrosAula() {
  prefs.begin("sisco_aula", true);
  configIdAula = prefs.getInt("id_aula", 0);
  configCodigoAula = prefs.getString("codigo", "");
  configLoraId = prefs.getInt("lora_id", 0);
  configLoraNetId = prefs.getInt("lora_net", LORA_RED_ID);
  configGatewayId = prefs.getInt("gw_id", LORA_ID_GATEWAY);
  configAulaInicializada = prefs.getBool("configurado", false);
  prefs.end();

  // Si no está configurado explícitamente o tiene valores antiguos de prueba (18 / 101), usar valores estándar (Aula 30 / LoRa 110)
  if (!configAulaInicializada || configIdAula == 0 || (configIdAula == 18 && configLoraId == 101)) {
    configIdAula = 30;
    configCodigoAula = "3_RO_BTI";
    configLoraId = LORA_ID_AULA_DEFAULT;
  }

  Serial.println("[NVS-AULA] Parametros cargados:");
  Serial.printf("  - ID Aula    : %d (Configurado: %s)\n", configIdAula, configAulaInicializada ? "SI" : "NO");
  Serial.printf("  - Codigo RF  : %s\n", configCodigoAula.c_str());
  Serial.printf("  - LoRa ID    : %d\n", configLoraId);
  Serial.printf("  - Red LoRa   : %d\n", configLoraNetId);
  Serial.printf("  - Gateway ID : %d\n", configGatewayId);
}

void guardarParametrosAula(int idAula, const String &codigo, int loraId, int loraNet, int gwId) {
  prefs.begin("sisco_aula", false);
  prefs.putInt("id_aula", idAula);
  prefs.putString("codigo", codigo);
  prefs.putInt("lora_id", loraId);
  prefs.putInt("lora_net", loraNet);
  prefs.putInt("gw_id", gwId);
  prefs.putBool("configurado", true);
  prefs.end();

  configIdAula = idAula;
  configCodigoAula = codigo;
  configLoraId = loraId;
  configLoraNetId = loraNet;
  configGatewayId = gwId;
  configAulaInicializada = true;
  Serial.println("[NVS-AULA] Parametros de aula guardados exitosamente como CONFIGURADO.");
}

// ============================================================================
// PORTAL CAUTIVO DE APROVISIONAMIENTO
// ============================================================================
void iniciarModoSetupAP() {
  modoSetup = true;
  WiFi.mode(WIFI_AP);
  const char *apSsid = "SISCO-AULA-CONFIG";
  const char *apPass = "12345678";
  IPAddress apIP(192, 168, 4, 1);
  IPAddress netMsk(255, 255, 255, 0);
  WiFi.softAP(apSsid, apPass);
  WiFi.softAPConfig(apIP, apIP, netMsk);

  dnsServer.setErrorReplyCode(DNSReplyCode::NoError);
  dnsServer.start(53, "*", apIP);

  server.collectHeaders(headerKeys, 1);
  seguridad.begin(server);

  server.on("/", HTTP_GET, handleRootAula);
  server.on("/guardar", HTTP_POST, handleGuardarAula);
  server.onNotFound([]() {
    server.sendHeader("Location", "http://192.168.4.1/", true);
    server.send(302, "text/plain", "");
  });

  server.begin();

  msgOled("SETUP AULA", "AP: 192.168.4.1");
  Serial.println("\n[SETUP] Modo Aprovisionamiento AP Activo:");
  Serial.printf("  - Conectarse a Wi-Fi: %s (Clave: %s)\n", apSsid, apPass);
  Serial.println("  - Abrir navegador en: http://192.168.4.1/\n");
}

void handleRootAula() {
  if (!seguridad.verificarAcceso()) return;

  String p = FPSTR(PAGINA_CONFIG_AULA);
  p.replace("%ID_AULA%", String(configIdAula));
  p.replace("%CODIGO_AULA%", configCodigoAula);
  p.replace("%LORA_ID%", String(configLoraId));
  p.replace("%LORA_RED_ID%", String(configLoraNetId));
  p.replace("%GATEWAY_ID%", String(configGatewayId));
  p.replace("%MAC%", WiFi.macAddress());

  String badgeLora;
  if (loraOk) {
    badgeLora = "<span style=\"font-size:0.75rem;padding:0.25rem 0.6rem;border-radius:9999px;background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.4);\">🟢 LoRa: OK (" + (versionLoRa.length() > 0 ? versionLoRa : "Detectado") + ")</span>";
  } else {
    badgeLora = "<span style=\"font-size:0.75rem;padding:0.25rem 0.6rem;border-radius:9999px;background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.4);\">🔴 LoRa: FALLO HW (" + diagLoRa + ")</span>";
  }
  p.replace("%LORA_STATUS%", badgeLora);

  server.send(200, "text/html", p);
}

void handleGuardarAula() {
  if (!seguridad.verificarAcceso()) return;

  int idAula = server.arg("id_aula").toInt();
  String codigo = server.arg("codigo_aula");
  codigo.trim();
  int loraId = server.arg("lora_id").toInt();
  int loraNet = server.arg("lora_red_id").toInt();
  int gwId = server.arg("gateway_id").toInt();

  if (idAula <= 0 || codigo.length() == 0 || loraId <= 0) {
    server.send(400, "text/plain", "Datos invalidos");
    return;
  }

  guardarParametrosAula(idAula, codigo, loraId, loraNet, gwId);

  server.send_P(200, "text/html", PAGINA_GUARDADO_AULA);
  reiniciarPendiente = true;
  tiempoReinicio = millis() + 2000;
}

// ============================================================================
// SETUP Y LOOP
// ============================================================================
void setup() {
  Serial.begin(115200);
  delay(300);
  Serial.println("\n========================================");
  Serial.println("  SISCO-EDU - NODO DE AULA");
  Serial.println("========================================");

  pinMode(PIN_BOTON_SALIDA, INPUT_PULLUP);
  delay(50); // Estabilizar pull-up interno

  if (!oled.begin(SSD1306_SWITCHCAPVCC, 0x3C)) for (;;) delay(1000);
  oled.setTextColor(SSD1306_WHITE);
  msgOled("INICIANDO", "Sisco-Edu LoRa");

  // Detección de pulsación sostenida de 10s para reset de fábrica total
  if (seguridad.verificarBotonReset(PIN_BOTON_SALIDA, 10000, false)) {
    Serial.println("[RESET] Restaurando credenciales y parametros de aula a fabrica...");
    msgOled("RESET FABRICA", "Restaurando...");
    prefs.begin("sisco_aula", false);
    prefs.clear();
    prefs.end();
    delay(1000);
    ESP.restart();
  }

  // IMPORTANTE: Cargar credenciales maestras antes de evaluar seguridad.debeCambiarPassword()
  seguridad.cargarCredenciales();

  cargarParametrosAula();

  // Detección de pulsación de 2.5s para forzar modo setup manualmente al arrancar
  bool forzarSetupPorBoton = false;
  if (digitalRead(PIN_BOTON_SALIDA) == LOW) {
    Serial.println("[BOTON] Pulsador presionado al encender. Mantener 2.5s para forzar Setup AP...");
    msgOled("MODO SETUP?", "Mantenga presionado");
    unsigned long t0 = millis();
    while (digitalRead(PIN_BOTON_SALIDA) == LOW && millis() - t0 < 2500) {
      delay(50);
    }
    if (millis() - t0 >= 2500) {
      forzarSetupPorBoton = true;
      Serial.println("[BOTON] Confirmado: Entrando a Setup AP por pulsador.");
    }
  }

  // Entrar a Setup AP en cualquiera de estos 3 casos:
  // 1. Si el usuario mantuvo presionado el botón por 2.5s al arrancar.
  // 2. Si aún no se han guardado los parámetros del aula en NVS (!configAulaInicializada).
  // 3. Si aún debe cambiarse la contraseña root de fábrica.
  if (forzarSetupPorBoton || !configAulaInicializada || seguridad.debeCambiarPassword()) {
    Serial.println("[INICIO] Entrando a Modo Aprovisionamiento Web...");
    iniciarModoSetupAP();
    return;
  }

  // En modo operativo normal, apagar Wi-Fi para no consumir corriente ni emitir RF 2.4 GHz
  WiFi.mode(WIFI_OFF);

  Serial.printf("[BIOMETRIA] Contrato: %u bytes / %u HEX / %d fragmentos\n",
                (unsigned int)HUELLA_TEMPLATE_BYTES,
                (unsigned int)HUELLA_TEMPLATE_HEX_CHARS,
                MAX_CHUNKS_HUELLA);

  // 1. Diagnóstico y configuración del módulo LoRa RYLR998
  msgOled("VERIF LORA...", "Iniciando RYLR");
  loraOk = verificarYConfigurarLoRa(loraSerial, LORA_RX, LORA_TX, configLoraId, configLoraNetId, diagLoRa, versionLoRa);
  if (loraOk) {
    msgOled("LORA: OK", "Red: " + String(configLoraNetId) + " ID: " + String(configLoraId));
    delay(1200);
  } else {
    msgOled("LORA: FALLO", "Sin resp RYLR998");
    delay(3000);
  }

  // 2. Diagnóstico del sensor biométrico DY50
  msgOled("VERIF DY50...", "Probando sensor");
  dy50Serial.begin(57600, SERIAL_8N1, DY50_RX, DY50_TX);
  finger.begin(57600);
  delay(800);
  if (finger.verifyPassword()) {
    if (finger.getParameters() == FINGERPRINT_OK) {
      Serial.printf("[DY50] cap=%u sec=%u baud=%lu\n",
                    finger.capacity, finger.security_level, (unsigned long)finger.baud_rate);
    }
    cargarDbLocalDesdeNVS();
    msgOled("DY50: OK", "Sensor listo");
  } else {
    msgOled("DY50: FALLO", "No detectado");
  }
  delay(1200);

  // Pantalla principal de reposo
  mostrarPantallaReposoAula();
}

void loop() {
  if (modoSetup) {
    dnsServer.processNextRequest();
    server.handleClient();
    if (reiniciarPendiente && millis() >= tiempoReinicio) {
      delay(100);
      ESP.restart();
    }
    return;
  }

  atenderComandosLoRa();

  // Comandos de diagnóstico desde Monitor Serial (115200 baud)
  if (Serial.available()) {
    String entradaSerial = Serial.readStringUntil('\n');
    entradaSerial.trim();
    if (entradaSerial.equalsIgnoreCase("LORA") || entradaSerial.equalsIgnoreCase("STATUS")) {
      Serial.println("[MONITOR-SERIAL] Ejecutando chequeo de modulo LoRa...");
      loraOk = verificarYConfigurarLoRa(loraSerial, LORA_RX, LORA_TX, configLoraId, configLoraNetId, diagLoRa, versionLoRa);
      mostrarPantallaReposoAula();
    } else if (entradaSerial.equalsIgnoreCase("PING") || entradaSerial.startsWith("PING ") || entradaSerial.startsWith("ping ")) {
      int destino = configGatewayId;
      int sp = entradaSerial.indexOf(' ');
      if (sp > 0) destino = entradaSerial.substring(sp + 1).toInt();
      Serial.printf("[MONITOR-SERIAL] Enviando PING a Gateway ID %d...\n", destino);
      enviarComandoLoRa(destino, "PING:" + String(configLoraId));
      msgOled("PING LORA", "Hacia GW: #" + String(destino));
      delay(800);
      mostrarPantallaReposoAula();
    }
  }

  if (digitalRead(PIN_BOTON_SALIDA) == LOW && estadoActual == NORMAL && estadoSync == SYNC_IDLE) {
    estadoActual = ESPERANDO_PROFE;
    tiempoLimiteEstado = millis() + 15000;
    msgOled("SALIDA", "Dedo de profe");
    delay(400);
  }

  if (estadoActual != NORMAL && (int32_t)(millis() - tiempoLimiteEstado) > 0) {
    estadoActual = NORMAL;
    msgOled("CANCELADO", "Tiempo expirado");
    delay(1200);
    mostrarPantallaReposoAula();
  }

  if (estadoSync == SYNC_RECIBIENDO && millis() - tiempoUltimoChunk > 5000) {
    Serial.println("[SYNC] timeout de fragmentos");
    notificarResultadoSync(false, 0, "TIMEOUT_CHUNKS");
    limpiarSyncRecibida();
    estadoSync = SYNC_IDLE;
    mostrarPantallaReposoAula();
  }

  if (estadoSync == SYNC_IDLE) {
    verificarLecturaHuella();
  }
  delay(10);
}

// ============================================================================
// FUNCIONES AUXILIARES Y BIOMÉTRICAS (PRESERVADAS)
// ============================================================================
void msgOled(const String &t1, const String &t2) {
  oled.clearDisplay();
  oled.setCursor(0, 10);
  oled.setTextSize(2);
  oled.println(t1);
  oled.setTextSize(1);
  oled.setCursor(0, 45);
  oled.println(t2);
  oled.display();
}

void mostrarPantallaReposoAula() {
  String lineaEstado = loraOk ? ("LoRa: OK (ID " + String(configLoraId) + ")") : "LoRa: FALLO HW (!)";
  msgOled(configCodigoAula, lineaEstado);
}

void guardarSlotEnNVS(int slot) {
  prefs.begin("dblocal", false);
  prefs.putString(("ci_" + String(slot)).c_str(), dbLocal[slot].ci);
  prefs.putString(("tipo_" + String(slot)).c_str(), dbLocal[slot].tipo);
  prefs.putBool(("reg_" + String(slot)).c_str(), dbLocal[slot].registrado);
  prefs.end();
}

void cargarDbLocalDesdeNVS() {
  prefs.begin("dblocal", true);
  for (int i = 1; i <= 200; ++i) {
    if (!prefs.getBool(("reg_" + String(i)).c_str(), false)) continue;
    prefs.getString(("ci_" + String(i)).c_str(), "").toCharArray(dbLocal[i].ci, sizeof(dbLocal[i].ci));
    prefs.getString(("tipo_" + String(i)).c_str(), "").toCharArray(dbLocal[i].tipo, sizeof(dbLocal[i].tipo));
    dbLocal[i].registrado = true;
  }
  prefs.end();
}

bool guardarHuellaDY50(const String &hex, int slot, String &crcHex, String &error) {
  static uint8_t data[HUELLA_TEMPLATE_BYTES];
  static uint8_t readback[HUELLA_TEMPLATE_BYTES];
  size_t bytes = 0;
  if (!Dy50TemplateTransport::decodeHex(hex, data, sizeof(data), bytes, error) || bytes != sizeof(data)) {
    if (error.length() == 0) error = "TAMANO_TEMPLATE_INVALIDO";
    return false;
  }
  const uint32_t crcOrigen = Dy50TemplateTransport::crc32(data, bytes);
  if (!Dy50TemplateTransport::beginDownChar(dy50Serial, 1, error)) return false;
  if (!Dy50TemplateTransport::sendTemplate(dy50Serial, data, bytes, error)) return false;
  delay(150);
  const uint8_t resultado = finger.storeModel(slot);
  if (resultado != FINGERPRINT_OK) {
    error = "STORE_FAIL_0x" + String(resultado, HEX);
    return false;
  }

  delay(100);
  const uint8_t carga = finger.loadModel(slot);
  if (carga != FINGERPRINT_OK) {
    error = "READBACK_LOAD_0x" + String(carga, HEX);
    finger.deleteModel(slot);
    return false;
  }
  if (!Dy50TemplateTransport::beginUpChar(dy50Serial, 1, error)) {
    finger.deleteModel(slot);
    return false;
  }
  size_t bytesLeidos = 0;
  if (!Dy50TemplateTransport::receiveTemplate(dy50Serial, readback, sizeof(readback), bytesLeidos, error) || bytesLeidos != sizeof(readback)) {
    finger.deleteModel(slot);
    return false;
  }
  const uint32_t crcLeido = Dy50TemplateTransport::crc32(readback, bytesLeidos);
  if (crcOrigen != crcLeido) {
    error = "CRC_MISMATCH";
    finger.deleteModel(slot);
    return false;
  }
  crcHex = Dy50TemplateTransport::formatCrc32(crcLeido);
  return true;
}

void limpiarSyncRecibida() {
  memset(huellaBuffer, 0, sizeof(huellaBuffer));
  ultimoIndiceProcesado = -1;
  totalChunksEsperados = 0;
  longitudHuellaHex = 0;
  tiempoUltimoChunk = 0;
  tiempoPrimerChunk = 0;
  idSyncActual = idHuellaActual = idAulaSyncActual = 0;
  ciSyncActual[0] = tipoSyncActual[0] = '\0';
}

void notificarResultadoSync(bool exito, int slot, const String &detalle) {
  if (exito) {
    enviarComandoLoRa(configGatewayId, "SYNCOK:" + String(idSyncActual) + ":" + String(idHuellaActual) + ":" + String(configIdAula) + ":" + String(slot) + ":" + String(ciSyncActual) + ":" + String(tipoSyncActual) + ":CRC32_" + detalle);
  } else {
    enviarComandoLoRa(configGatewayId, "SYNCERR:" + String(idSyncActual) + ":" + String(idHuellaActual) + ":" + String(configIdAula) + ":0:" + String(ciSyncActual) + ":" + String(tipoSyncActual) + ":" + detalle);
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
              Serial.printf("[LORA-AULA] Modulo respondio a %lu baud. Reconfigurando a 115200...\n", b);
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
  Serial.println("[LORA] DIAGNOSTICO DE HARDWARE RYLR998 (NODO AULA)");
  Serial.printf("[LORA] Pines UART : RX=GPIO %d, TX=GPIO %d\n", rxPin, txPin);
  Serial.printf("[LORA] Esperado   : Addr=%d, Red=%d (Aula ID: %d)\n", targetAddr, targetNetId, configIdAula);

  bool respondeAT = sincronizarBaudRateRYLR(serial, rxPin, txPin);
  if (!respondeAT) {
    diagSalida = "ERROR: Sin resp AT (Pines RX=" + String(rxPin) + " TX=" + String(txPin) + " y 3.3V)";
    versionSalida = "DESCONOCIDO";
    Serial.println("[LORA-FALLO] " + diagSalida);
    Serial.println("[LORA-FALLO] CAUSAS POSIBLES:");
    Serial.println("  1. Pines RX/TX cruzados o desconectados (ESP32 GPIO 26 RX -> RYLR998 TX, GPIO 27 TX -> RYLR998 RX).");
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

  diagSalida = "OK (Addr " + String(targetAddr) + ", Red " + String(targetNetId) + ", SF7/BW500)";
  return true;
}

void enviarComandoLoRa(int destino, const String &payload) {
  loraSerial.printf("AT+SEND=%d,%u,%s\r\n", destino, payload.length(), payload.c_str());
}

void enviarAckLoRa(int chunk) {
  enviarComandoLoRa(configGatewayId, "ACK" + String(chunk));
}

void enviarAsistenciaPorLoRa(const String &ci, const String &tipo, const String &estado) {
  enviarComandoLoRa(configGatewayId, "AST:" + String(configIdAula) + ":" + ci + ":" + tipo + ":" + estado);
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

void atenderComandosLoRa() {
  if (!loraSerial.available()) return;
  String linea = loraSerial.readStringUntil('\n');
  linea.trim();
  if (!linea.startsWith("+RCV=")) return;
  int c1 = linea.indexOf(','), c2 = linea.indexOf(',', c1 + 1), c3 = linea.indexOf(',', c2 + 1);
  int c4 = linea.indexOf(',', c3 + 1);
  if (c1 < 0 || c2 < 0 || c3 < 0) return;
  int emisor = linea.substring(5, c1).toInt();
  const String payload = linea.substring(c2 + 1, c3);

  // ATENCIÓN DE PING DIAGNÓSTICO
  if (payload.startsWith("PING")) {
    String rssiStr = (c4 > 0) ? linea.substring(c3 + 1, c4) : "N/A";
    String snrStr = (c4 > 0) ? linea.substring(c4 + 1) : "N/A";
    Serial.printf("[LORA-AULA] PING recibido de Nodo ID %d (RSSI: %s, SNR: %s). Respondiendo PONG...\n",
                  emisor, rssiStr.c_str(), snrStr.c_str());
    enviarComandoLoRa(emisor, "PONG:" + String(configIdAula) + ":" + String(configLoraId));
    msgOled("PING LORA", "GW #" + String(emisor) + " OK");
    delay(1200);
    mostrarPantallaReposoAula();
    return;
  }
  if (payload.startsWith("PONG")) {
    Serial.printf("[LORA-AULA] PONG recibido de Nodo ID %d\n", emisor);
    msgOled("PONG LORA", "De ID: #" + String(emisor));
    delay(1200);
    mostrarPantallaReposoAula();
    return;
  }

  const int sep = payload.indexOf(':');
  if (sep <= 0) return;
  const int chunk = payload.substring(0, sep).toInt();
  const String resto = payload.substring(sep + 1);

  if (chunk == 0) {
    const int idSync = campo(resto, 0).toInt(), idHuella = campo(resto, 1).toInt(), idAula = campo(resto, 2).toInt();
    const String ci = campo(resto, 3), tipo = campo(resto, 4), totalTexto = campo(resto, 5), hex = campo(resto, 6);
    const int total = totalTexto.toInt();
    const bool tipoValido = tipo == "estudiante" || tipo == "profesor";

    Serial.printf("[LORA-AULA] Chunk 0 recibido: sync=%d huella=%d aula=%d ci=%s tipo=%s\n",
                  idSync, idHuella, idAula, ci.c_str(), tipo.c_str());

    // FILTRO ESTRICTO: Si no le pertenece a este aula, registrar e ignorar
    if (idAula != configIdAula) {
      Serial.printf("[LORA-AULA] Trama ignorada: Aula recibida %d != Aula local (%d - %s)\n",
                    idAula, configIdAula, configCodigoAula.c_str());
      return;
    }
    if (!idSync || !idHuella || ci.length() == 0 || !tipoValido || total != MAX_CHUNKS_HUELLA || hex.length() != CHARS_POR_CHUNK) {
      Serial.printf("[LORA-AULA] Trama ignorada: parametros invalidos (total=%d, hexLen=%u)\n",
                    total, (unsigned int)hex.length());
      return;
    }
    limpiarSyncRecibida();
    idSyncActual = idSync; idHuellaActual = idHuella; idAulaSyncActual = idAula;
    ci.toCharArray(ciSyncActual, sizeof(ciSyncActual));
    tipo.toCharArray(tipoSyncActual, sizeof(tipoSyncActual));
    memcpy(huellaBuffer, hex.c_str(), hex.length());
    longitudHuellaHex = hex.length();
    totalChunksEsperados = total;
    ultimoIndiceProcesado = 0;
    tiempoPrimerChunk = tiempoUltimoChunk = millis();
    estadoSync = SYNC_RECIBIENDO;

    enviarAckLoRa(0);
    Serial.println("[LORA-AULA] ACK0 emitido hacia el Gateway");
    msgOled("SYNC", "Recibiendo...");
    return;
  }

  if (estadoSync != SYNC_RECIBIENDO || chunk < 0 || chunk >= totalChunksEsperados) return;
  if (chunk <= ultimoIndiceProcesado) {
    enviarAckLoRa(chunk);
    return;
  }
  if (chunk != ultimoIndiceProcesado + 1 || resto.length() != CHARS_POR_CHUNK) return;

  const size_t offset = (size_t)chunk * CHARS_POR_CHUNK;
  if (offset + resto.length() > HUELLA_TEMPLATE_HEX_CHARS) {
    notificarResultadoSync(false, 0, "BUFFER_OVERFLOW");
    limpiarSyncRecibida();
    estadoSync = SYNC_IDLE;
    return;
  }
  memcpy(huellaBuffer + offset, resto.c_str(), resto.length());
  longitudHuellaHex = offset + resto.length();
  ultimoIndiceProcesado = chunk;
  tiempoUltimoChunk = millis();
  enviarAckLoRa(chunk);
  Serial.printf("[LORA-AULA] Chunk %d/%d recibido y ACK emitido\n", chunk + 1, totalChunksEsperados);

  if (chunk != totalChunksEsperados - 1) return;

  estadoSync = SYNC_PROCESANDO;
  msgOled("GUARDANDO", "En DY50");
  if (longitudHuellaHex != HUELLA_TEMPLATE_HEX_CHARS) {
    notificarResultadoSync(false, 0, "LONGITUD_INVALIDA");
  } else {
    huellaBuffer[longitudHuellaHex] = '\0';
    int slot = -1;
    for (int s = 1; s <= 200; ++s) {
      if (dbLocal[s].registrado && String(dbLocal[s].ci) == String(ciSyncActual)) {
        slot = s; break;
      }
    }
    if (slot < 0) {
      for (int s = 1; s <= 200; ++s) {
        if (!dbLocal[s].registrado) { slot = s; break; }
      }
    }
    String crcVerificado, error;
    if (slot > 0 && guardarHuellaDY50(String(huellaBuffer), slot, crcVerificado, error)) {
      strncpy(dbLocal[slot].ci, ciSyncActual, sizeof(dbLocal[slot].ci) - 1);
      strncpy(dbLocal[slot].tipo, tipoSyncActual, sizeof(dbLocal[slot].tipo) - 1);
      dbLocal[slot].registrado = true;
      guardarSlotEnNVS(slot);
      notificarResultadoSync(true, slot, crcVerificado);
      msgOled("HUELLA OK", "CI: " + String(ciSyncActual));
    } else {
      notificarResultadoSync(false, 0, error.length() ? error : "SIN_SLOT");
      msgOled("ERROR SYNC", error);
    }
  }
  delay(1200);
  limpiarSyncRecibida();
  estadoSync = SYNC_IDLE;
  mostrarPantallaReposoAula();
}

void verificarLecturaHuella() {
  if (finger.getImage() != FINGERPRINT_OK) return;
  if (finger.image2Tz() != FINGERPRINT_OK) return;
  if (finger.fingerFastSearch() != FINGERPRINT_OK) {
    msgOled("DESCONOCIDO", "No registrado");
    delay(1500);
    mostrarPantallaReposoAula();
    return;
  }
  const int slot = finger.fingerID;
  if (slot < 1 || slot > 200 || !dbLocal[slot].registrado) return;
  const String ci = dbLocal[slot].ci;
  const String tipo = dbLocal[slot].tipo;

  if (estadoActual == ESPERANDO_PROFE) {
    if (tipo == "profesor") {
      estadoActual = SALIDA_ACTIVA;
      tiempoLimiteEstado = millis() + 30000;
      msgOled("PERMITIDO", "Salida habilitada");
      delay(1500);
    } else {
      msgOled("DENEGADO", "Solo profesor");
      delay(1500);
    }
    mostrarPantallaReposoAula();
    return;
  }

  if (millis() - ultimoRegistroSlot[slot] < TIEMPO_COOLDOWN) {
    msgOled("YA MARCADO", "Espere 5 min");
    delay(1500);
    mostrarPantallaReposoAula();
    return;
  }
  ultimoRegistroSlot[slot] = millis();
  const String estado = estadoActual == SALIDA_ACTIVA ? "SALIDA" : "PRESENTE";
  enviarAsistenciaPorLoRa(ci, tipo, estado);
  msgOled(ci, estado);
  delay(1500);
  mostrarPantallaReposoAula();
}
