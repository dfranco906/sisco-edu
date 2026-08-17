// ESP AULA: RYLR998 + DY50 + OLED
#include <Arduino.h>
#include <Adafruit_Fingerprint.h>
#include <Adafruit_SSD1306.h>
#include <Adafruit_GFX.h>
#include <Preferences.h>
#include "dy50_template_transport.h"

// Debe coincidir con aulas.id_aula; el codigo es solo para la pantalla.
const int ID_AULA = 18;
const char *CODIGO_AULA = "AULA_A";
const int MI_LORA_ID = 101;
const int GATEWAY_LORA_ID = 100;
const int LORA_NETWORK_ID = 18;
constexpr size_t HUELLA_TEMPLATE_BYTES = 1536;
constexpr size_t HUELLA_TEMPLATE_HEX_CHARS = HUELLA_TEMPLATE_BYTES * 2;
constexpr int CHARS_POR_CHUNK = 128;
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

void msgOled(const String &t1, const String &t2 = "");
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

void setup() {
  Serial.begin(115200);
  delay(300);
  Serial.printf("[BIOMETRIA] Contrato compilado: %u bytes / %u HEX / %d fragmentos\n",
                (unsigned int)HUELLA_TEMPLATE_BYTES,
                (unsigned int)HUELLA_TEMPLATE_HEX_CHARS,
                MAX_CHUNKS_HUELLA);
  pinMode(PIN_BOTON_SALIDA, INPUT_PULLUP);
  if (!oled.begin(SSD1306_SWITCHCAPVCC, 0x3C)) for (;;) delay(1000);
  oled.setTextColor(SSD1306_WHITE);
  msgOled("INICIANDO", "Sisco-Edu LoRa");

  loraSerial.begin(115200, SERIAL_8N1, LORA_RX, LORA_TX);
  loraSerial.setTimeout(120);
  // ADDRESS y NETWORKID son persistentes en el RYLR998. PARAMETER/frecuencia
  // se conservan para no cambiar la configuracion RF regional instalada.
  loraSerial.printf("AT+ADDRESS=%d\r\n", MI_LORA_ID);
  delay(100); while (loraSerial.available()) loraSerial.read();
  loraSerial.printf("AT+NETWORKID=%d\r\n", LORA_NETWORK_ID);
  delay(100); while (loraSerial.available()) loraSerial.read();

  dy50Serial.begin(57600, SERIAL_8N1, DY50_RX, DY50_TX);
  finger.begin(57600);
  delay(1000);
  if (finger.verifyPassword()) {
    if (finger.getParameters() == FINGERPRINT_OK) {
      Serial.printf("[DY50] system=0x%04X capacity=%u security=%u packet_len=%u baud=%lu\n",
                    finger.system_id, finger.capacity, finger.security_level,
                    finger.packet_len, (unsigned long)finger.baud_rate);
    }
    cargarDbLocalDesdeNVS();
    msgOled("DY50: OK", "Sistema listo");
  } else msgOled("ERROR HW", "DY50 no hallado");
  delay(1200);
  msgOled(String(CODIGO_AULA), "LoRa ID: " + String(MI_LORA_ID));
}

void loop() {
  atenderComandosLoRa();
  if (digitalRead(PIN_BOTON_SALIDA) == LOW && estadoActual == NORMAL && estadoSync == SYNC_IDLE) {
    estadoActual = ESPERANDO_PROFE; tiempoLimiteEstado = millis() + 15000;
    msgOled("SALIDA", "Dedo de profe"); delay(400);
  }
  if (estadoActual != NORMAL && (int32_t)(millis() - tiempoLimiteEstado) > 0) {
    estadoActual = NORMAL; msgOled("CANCELADO", "Tiempo expirado"); delay(1200);
    msgOled(String(CODIGO_AULA), "Listo");
  }
  if (estadoSync == SYNC_RECIBIENDO && millis() - tiempoUltimoChunk > 5000) {
    Serial.println("[SYNC] timeout de fragmentos");
    notificarResultadoSync(false, 0, "TIMEOUT_CHUNKS");
    limpiarSyncRecibida(); estadoSync = SYNC_IDLE; msgOled(String(CODIGO_AULA), "Listo");
  }
  if (estadoSync == SYNC_IDLE) verificarLecturaHuella();
  delay(10);
}

void msgOled(const String &t1, const String &t2) {
  oled.clearDisplay(); oled.setCursor(0, 10); oled.setTextSize(2); oled.println(t1);
  oled.setTextSize(1); oled.setCursor(0, 45); oled.println(t2); oled.display();
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
  if (resultado != FINGERPRINT_OK) { error = "STORE_FAIL_0x" + String(resultado, HEX); return false; }

  delay(100);
  const uint8_t carga = finger.loadModel(slot);
  if (carga != FINGERPRINT_OK) {
    error = "READBACK_LOAD_0x" + String(carga, HEX);
    finger.deleteModel(slot);
    return false;
  }
  Dy50TemplateTransport::drainInput(dy50Serial);
  const uint8_t subida = finger.getModel();
  if (subida != FINGERPRINT_OK) {
    error = "READBACK_UPCHAR_0x" + String(subida, HEX);
    finger.deleteModel(slot);
    return false;
  }
  size_t bytesReleidos = 0;
  if (!Dy50TemplateTransport::readTemplate(dy50Serial, readback, sizeof(readback), bytesReleidos, error)) {
    error = "READBACK_" + error;
    finger.deleteModel(slot);
    return false;
  }
  const uint32_t crcReleido = Dy50TemplateTransport::crc32(readback, bytesReleidos);
  if (bytesReleidos != bytes || crcReleido != crcOrigen || memcmp(data, readback, bytes) != 0) {
    error = "READBACK_MISMATCH";
    finger.deleteModel(slot);
    return false;
  }
  crcHex = String(crcReleido, HEX);
  while (crcHex.length() < 8) crcHex = "0" + crcHex;
  crcHex.toUpperCase();
  Serial.printf("[BIOMETRIA] Slot %d verificado byte a byte. CRC32=%s\n", slot, crcHex.c_str());
  return true;
}

void enviarComandoLoRa(int destino, const String &payload) {
  loraSerial.printf("AT+SEND=%d,%u,%s\r\n", destino, payload.length(), payload.c_str());
}
void enviarAckLoRa(int chunk) { enviarComandoLoRa(GATEWAY_LORA_ID, "ACK" + String(chunk)); }

String campo(const String &texto, int indice) {
  int inicio = 0;
  for (int i = 0; i < indice; ++i) { inicio = texto.indexOf(':', inicio); if (inicio < 0) return ""; ++inicio; }
  int fin = texto.indexOf(':', inicio);
  return fin < 0 ? texto.substring(inicio) : texto.substring(inicio, fin);
}

void limpiarSyncRecibida() {
  memset(huellaBuffer, 0, sizeof(huellaBuffer)); ultimoIndiceProcesado = -1;
  totalChunksEsperados = 0; longitudHuellaHex = 0; idSyncActual = idHuellaActual = idAulaSyncActual = 0;
  ciSyncActual[0] = tipoSyncActual[0] = '\0';
}

void notificarResultadoSync(bool exito, int slot, const String &detalle) {
  if (!idSyncActual || !idHuellaActual || !idAulaSyncActual) return;
  String msg = (exito ? "SYNCOK:" : "SYNCERR:") + String(idSyncActual) + ":" + String(idHuellaActual) + ":" + String(idAulaSyncActual) + ":" + String(slot) + ":" + String(ciSyncActual) + ":" + String(tipoSyncActual) + ":" + detalle;
  enviarComandoLoRa(GATEWAY_LORA_ID, msg);
}

void atenderComandosLoRa() {
  if (!loraSerial.available()) return;
  String linea = loraSerial.readStringUntil('\n'); linea.trim();
  if (!linea.startsWith("+RCV=")) return;
  const int c1 = linea.indexOf(','), c2 = linea.indexOf(',', c1 + 1), c3 = linea.indexOf(',', c2 + 1);
  if (c1 < 0 || c2 < 0 || c3 < 0 || linea.substring(5, c1).toInt() != GATEWAY_LORA_ID) return;
  const String payload = linea.substring(c2 + 1, c3);
  const int sep = payload.indexOf(':'); if (sep <= 0) return;
  const int chunk = payload.substring(0, sep).toInt();
  const String resto = payload.substring(sep + 1);

  if (chunk == 0 && estadoSync == SYNC_IDLE) {
    const int idSync = campo(resto, 0).toInt(), idHuella = campo(resto, 1).toInt(), idAula = campo(resto, 2).toInt();
    const String ci = campo(resto, 3), tipo = campo(resto, 4), totalTexto = campo(resto, 5), hex = campo(resto, 6);
    const int total = totalTexto.toInt();
    const bool tipoValido = tipo == "estudiante" || tipo == "profesor";
    if (!idSync || !idHuella || idAula != ID_AULA || ci.length() == 0 || ci.length() >= sizeof(ciSyncActual) || !tipoValido || tipo.length() >= sizeof(tipoSyncActual) || total != MAX_CHUNKS_HUELLA || hex.length() != CHARS_POR_CHUNK) {
      Serial.printf("[SYNC] Inicio rechazado: sync=%d, huella=%d, aula=%d/%d, total=%d/%d, primer_chunk=%u/%d, ci=%s, tipo=%s\n",
                    idSync, idHuella, idAula, ID_AULA, total, MAX_CHUNKS_HUELLA,
                    (unsigned int)hex.length(), CHARS_POR_CHUNK,
                    ci.length() ? "OK" : "FALTA", tipoValido ? "OK" : "INVALIDO");
      return;
    }
    limpiarSyncRecibida();
    idSyncActual = idSync; idHuellaActual = idHuella; idAulaSyncActual = idAula;
    ci.toCharArray(ciSyncActual, sizeof(ciSyncActual)); tipo.toCharArray(tipoSyncActual, sizeof(tipoSyncActual));
    memcpy(huellaBuffer, hex.c_str(), hex.length()); longitudHuellaHex = hex.length();
    totalChunksEsperados = total; ultimoIndiceProcesado = 0; tiempoPrimerChunk = tiempoUltimoChunk = millis(); estadoSync = SYNC_RECIBIENDO;
    Serial.printf("[SYNC] Inicio aceptado: sync=%d, huella=%d, %d fragmentos de %d caracteres\n",
                  idSyncActual, idHuellaActual, totalChunksEsperados, CHARS_POR_CHUNK);
    enviarAckLoRa(0); msgOled("SYNC", "Recibiendo..."); return;
  }

  if (estadoSync != SYNC_RECIBIENDO || chunk < 0 || chunk >= totalChunksEsperados) {
    Serial.printf("[SYNC] Fragmento %d rechazado: estado=%d, total=%d\n", chunk, estadoSync, totalChunksEsperados);
    return;
  }
  if (chunk <= ultimoIndiceProcesado) { enviarAckLoRa(chunk); return; }
  if (chunk != ultimoIndiceProcesado + 1 || resto.length() != CHARS_POR_CHUNK) {
    Serial.printf("[SYNC] Fragmento %d rechazado: esperado=%d, longitud=%u/%d\n",
                  chunk, ultimoIndiceProcesado + 1, (unsigned int)resto.length(), CHARS_POR_CHUNK);
    return;
  }
  const size_t offset = (size_t)chunk * CHARS_POR_CHUNK;
  if (offset + resto.length() > HUELLA_TEMPLATE_HEX_CHARS) { notificarResultadoSync(false, 0, "BUFFER_OVERFLOW"); limpiarSyncRecibida(); estadoSync = SYNC_IDLE; return; }
  memcpy(huellaBuffer + offset, resto.c_str(), resto.length()); longitudHuellaHex = offset + resto.length(); ultimoIndiceProcesado = chunk; tiempoUltimoChunk = millis(); enviarAckLoRa(chunk);
  if (chunk != totalChunksEsperados - 1) return;

  estadoSync = SYNC_PROCESANDO; msgOled("GUARDANDO", "En DY50");
  if (longitudHuellaHex != HUELLA_TEMPLATE_HEX_CHARS) {
    notificarResultadoSync(false, 0, "LONGITUD_INVALIDA");
  } else {
    huellaBuffer[longitudHuellaHex] = '\0';
    int slot = -1;
    for (int s = 1; s <= 200; ++s) if (dbLocal[s].registrado && String(dbLocal[s].ci) == String(ciSyncActual)) { slot = s; break; }
    if (slot < 0) for (int s = 1; s <= 200; ++s) if (!dbLocal[s].registrado) { slot = s; break; }
    String crcVerificado, error;
    if (slot > 0 && guardarHuellaDY50(String(huellaBuffer), slot, crcVerificado, error)) {
      String(ciSyncActual).toCharArray(dbLocal[slot].ci, sizeof(dbLocal[slot].ci));
      String(tipoSyncActual).toCharArray(dbLocal[slot].tipo, sizeof(dbLocal[slot].tipo)); dbLocal[slot].registrado = true; guardarSlotEnNVS(slot);
      notificarResultadoSync(true, slot, "CRC32_" + crcVerificado); msgOled("SYNC OK", "Slot " + String(slot));
    } else { notificarResultadoSync(false, 0, slot < 0 ? "SIN_SLOTS" : error); msgOled("ERROR DY50", error); }
  }
  delay(1000); limpiarSyncRecibida(); estadoSync = SYNC_IDLE; msgOled(String(CODIGO_AULA), "Listo");
}

void enviarAsistenciaPorLoRa(const String &ci, const String &tipo, const String &estado) {
  enviarComandoLoRa(GATEWAY_LORA_ID, "AST:" + String(ID_AULA) + ":" + ci + ":" + tipo + ":" + estado);
  msgOled("ENVIADO", ci);
}

void verificarLecturaHuella() {
  const uint8_t imagen = finger.getImage();
  if (imagen == FINGERPRINT_NOFINGER) return;
  if (imagen != FINGERPRINT_OK) { Serial.printf("[BIOMETRIA] getImage fallo: 0x%02X\n", imagen); return; }
  const uint8_t conversion = finger.image2Tz();
  if (conversion != FINGERPRINT_OK) { Serial.printf("[BIOMETRIA] image2Tz fallo: 0x%02X\n", conversion); return; }
  const uint8_t busqueda = finger.fingerFastSearch();
  if (busqueda != FINGERPRINT_OK) {
    Serial.printf("[BIOMETRIA] fingerFastSearch fallo: 0x%02X\n", busqueda);
    msgOled("ERROR", "No reconocido"); delay(1000); msgOled(String(CODIGO_AULA), "Listo"); return;
  }
  const int slot = finger.fingerID;
  Serial.printf("[BIOMETRIA] Reconocida: slot=%d confianza=%u\n", slot, finger.confidence);
  if (slot < 1 || slot > 200 || !dbLocal[slot].registrado) { msgOled("SIN DATOS", "Slot " + String(slot)); delay(1000); return; }
  String rol = String(dbLocal[slot].tipo); rol.toLowerCase();
  if (estadoActual == ESPERANDO_PROFE) {
    if (rol == "profesor" || rol == "coordinador") { estadoActual = SALIDA_ACTIVA; tiempoLimiteEstado = millis() + 20000; msgOled("AUTORIZADO", "Dedo alumno"); }
    else { estadoActual = NORMAL; msgOled("ERROR", "No es profesor"); delay(1000); }
    return;
  }
  if (estadoActual == NORMAL && millis() - ultimoRegistroSlot[slot] < TIEMPO_COOLDOWN) { msgOled("YA MARCADO", dbLocal[slot].ci); delay(1000); return; }
  enviarAsistenciaPorLoRa(dbLocal[slot].ci, dbLocal[slot].tipo, estadoActual == SALIDA_ACTIVA ? "RET_ANTICIPADO" : "PRESENTE");
  ultimoRegistroSlot[slot] = millis(); estadoActual = NORMAL; delay(1000); msgOled(String(CODIGO_AULA), "Listo");
}
