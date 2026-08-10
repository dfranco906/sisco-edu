// CODIGO ESP AULA-A (LoRa RYLR896/998 AT + Sensor DY50 + OLED + NVS)

#include <Arduino.h>
#include <Adafruit_Fingerprint.h>
#include <Adafruit_SSD1306.h>
#include <Adafruit_GFX.h>
#include <Preferences.h>
#include "dy50_template_transport.h"

// ============================================================================
// CONFIGURACIÓN DE HARDWARE Y NODO AULA
// ============================================================================
String ROOM_ID = "AULA_A";
const int MI_LORA_ID = 101;        // ID LoRa de esta aula
const int GATEWAY_LORA_ID = 100;   // ID LoRa del Gateway Central
const int CHARS_POR_CHUNK = 128;
const int TOTAL_CHUNKS_ESPERADOS =
    (Dy50TemplateTransport::TEMPLATE_BYTES * 2 + CHARS_POR_CHUNK - 1) /
    CHARS_POR_CHUNK;

// Pines Sensor DY50 (HardwareSerial 2)
#define DY50_RX 16
#define DY50_TX 17
HardwareSerial dy50Serial(2);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&dy50Serial);

// Pines Módulo LoRa AT (HardwareSerial 1)
#define LORA_RX 26
#define LORA_TX 27
HardwareSerial loraSerial(1);

// Pantalla OLED 0.96" I2C
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SSD1306 oled(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

// Botón de Salida Anticipada
#define PIN_BOTON_SALIDA 4

// Persistencia NVS
Preferences prefs;

// ============================================================================
// MÁQUINA DE ESTADOS Y ESTRUCTURAS DE DATOS
// ============================================================================
enum EstadoSistema { NORMAL, ESPERANDO_PROFE, SALIDA_ACTIVA };
EstadoSistema estadoActual = NORMAL;
uint32_t tiempoLimiteEstado = 0;

struct RegistroUsuario {
  char ci[15];
  char tipo_persona[15]; // "ALUMNO", "PROFESOR", "COORDINADOR"
  bool registrado = false;
};
RegistroUsuario dbLocal[201];

uint32_t ultimoRegistroSlot[201] = {0};
const uint32_t TIEMPO_COOLDOWN = 300000; // 5 min

// Buffer para la reconstrucción de huellas (3072 HEX + null terminator)
char huellaBuffer[Dy50TemplateTransport::TEMPLATE_BYTES * 2 + 1];
bool chunksRecibidos[TOTAL_CHUNKS_ESPERADOS] = {false};
int chunksGuardadosValidos = 0;
int ultimoIndiceProcesado = -1;
unsigned long tiempoPrimerChunk = 0;
unsigned long tiempoUltimoChunk = 0;

int idSyncSlotActual = 1;
char ciSyncActual[15] = "";
char tipoSyncActual[15] = "";

enum EstadoSync { SYNC_IDLE, SYNC_RECIBIENDO, SYNC_PROCESANDO };
EstadoSync estadoSync = SYNC_IDLE;

// Prototipos de funciones
void msgOled(String t1, String t2 = "");
void guardarSlotEnNVS(int slot);
void cargarDbLocalDesdeNVS();
bool guardarHuellaDY50(const String &templateHex, int slot, String &error);
void atenderComandosLoRa();
void enviarAckLoRa(int chunkIdx);
void enviarAsistenciaPorLoRa(String ci, String tipoPersona, String estado);
void verificarLecturaHuella();

// ============================================================================
// SETUP
// ============================================================================
void setup() {
  Serial.begin(115200);

  // Inicializar OLED
  if (!oled.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    for (;;);
  }
  oled.setTextColor(SSD1306_WHITE);
  msgOled("INICIANDO", "Sisco-Edu v2.0 LoRa");

  pinMode(PIN_BOTON_SALIDA, INPUT_PULLUP);

  // Inicializar comunicación LoRa (HardwareSerial 1)
  loraSerial.begin(115200, SERIAL_8N1, LORA_RX, LORA_TX);

  // Inicializar comunicación DY50 (HardwareSerial 2)
  dy50Serial.begin(57600, SERIAL_8N1, DY50_RX, DY50_TX);
  finger.begin(57600);

  delay(1000);

  if (finger.verifyPassword()) {
    msgOled("DY50: OK", "Cargando DB...");
    cargarDbLocalDesdeNVS();
    msgOled("DY50: OK", "Sistema Ready");
  } else {
    msgOled("ERROR HW", "DY50 no hallado");
  }

  delay(1500);
  msgOled("AULA: " + ROOM_ID, "LoRa ID: " + String(MI_LORA_ID));
  Serial.printf("\n[AULA %s] Nodo LoRa ID %d Operativo.\n", ROOM_ID.c_str(), MI_LORA_ID);
}

// ============================================================================
// LOOP PRINCIPAL
// ============================================================================
void loop() {
  // 1. Lectura asíncrona de comandos radio LoRa
  atenderComandosLoRa();

  // 2. Control de Botón para Salida Anticipada
  if (digitalRead(PIN_BOTON_SALIDA) == LOW && estadoActual == NORMAL && estadoSync == SYNC_IDLE) {
    estadoActual = ESPERANDO_PROFE;
    tiempoLimiteEstado = millis() + 15000;
    msgOled("SALIDA", "Dedo de Profe...");
    delay(500);
  }

  // 3. Timeout de estado de salida anticipada
  if (estadoActual != NORMAL && millis() > tiempoLimiteEstado) {
    estadoActual = NORMAL;
    msgOled("CANCELADO", "Tiempo expirado");
    delay(1500);
    msgOled("AULA: " + ROOM_ID, "Listo...");
  }

  // 4. Timeout de sincronización por radio (5 segundos sin recibir chunks)
  if (estadoSync == SYNC_RECIBIENDO && (millis() - tiempoUltimoChunk > 5000)) {
    estadoSync = SYNC_IDLE;
    memset(huellaBuffer, 0, sizeof(huellaBuffer));
    chunksGuardadosValidos = 0;
    ultimoIndiceProcesado = -1;
    msgOled("TIMEOUT", "Sync cancelada");
    Serial.println("[SYNC LORA] Timeout: Chunks incompletos");
    delay(2000);
    msgOled("AULA: " + ROOM_ID, "Listo...");
  }

  // 5. Verificación de Huella Biométrica (solo si el radio está libre)
  if (estadoSync == SYNC_IDLE) {
    verificarLecturaHuella();
  }

  delay(10);
}

// ============================================================================
// PANTALLA OLED
// ============================================================================
void msgOled(String t1, String t2) {
  oled.clearDisplay();
  oled.setCursor(0, 10);
  oled.setTextSize(2);
  oled.println(t1);
  oled.setTextSize(1);
  oled.setCursor(0, 45);
  oled.println(t2);
  oled.display();
}

// ============================================================================
// PERSISTENCIA NVS (Preferences)
// ============================================================================
void guardarSlotEnNVS(int slot) {
  prefs.begin("dblocal", false);
  String keyCI   = "ci_"   + String(slot);
  String keyTipo = "tipo_" + String(slot);
  String keyReg  = "reg_"  + String(slot);
  prefs.putString(keyCI.c_str(),   dbLocal[slot].ci);
  prefs.putString(keyTipo.c_str(), dbLocal[slot].tipo_persona);
  prefs.putBool(keyReg.c_str(),    dbLocal[slot].registrado);
  prefs.end();
  Serial.printf("[NVS] Slot %d guardado en flash (CI:%s, Tipo:%s)\n",
                slot, dbLocal[slot].ci, dbLocal[slot].tipo_persona);
}

void cargarDbLocalDesdeNVS() {
  prefs.begin("dblocal", true);
  int restaurados = 0;
  for (int i = 0; i < 201; i++) {
    String keyReg = "reg_" + String(i);
    if (prefs.getBool(keyReg.c_str(), false)) {
      String keyCI   = "ci_"   + String(i);
      String keyTipo = "tipo_" + String(i);
      String ci   = prefs.getString(keyCI.c_str(), "");
      String tipo = prefs.getString(keyTipo.c_str(), "");
      ci.toCharArray(dbLocal[i].ci, sizeof(dbLocal[i].ci));
      tipo.toCharArray(dbLocal[i].tipo_persona, sizeof(dbLocal[i].tipo_persona));
      dbLocal[i].registrado = true;
      restaurados++;
    }
  }
  prefs.end();
  Serial.printf("[NVS] dbLocal restaurado: %d usuarios cargados\n", restaurados);
}

// ============================================================================
// GRAVADO FÍSICO DE HUELLA EN SENSOR DY50
// ============================================================================
bool guardarHuellaDY50(const String &templateHex, int slot, String &error) {
  static uint8_t templateData[Dy50TemplateTransport::TEMPLATE_BYTES];
  size_t decodedBytes = 0;

  if (!Dy50TemplateTransport::decodeHex(templateHex, templateData, sizeof(templateData), decodedBytes, error)) {
    return false;
  }
  if (decodedBytes != Dy50TemplateTransport::TEMPLATE_BYTES) {
    error = "LONG_INVALIDA_" + String(decodedBytes);
    return false;
  }
  if (!Dy50TemplateTransport::beginDownChar(dy50Serial, 1, error)) return false;
  if (!Dy50TemplateTransport::sendTemplate(dy50Serial, templateData, decodedBytes, error)) return false;

  delay(150);
  uint8_t storeResult = finger.storeModel(slot);
  if (storeResult != FINGERPRINT_OK) {
    error = "STORE_FAIL_0x" + String(storeResult, HEX);
    return false;
  }
  return true;
}

// ============================================================================
// ATENCIÓN DE TRAMAS ENTRANTES LORA AT (+RCV=...)
// ============================================================================
void atenderComandosLoRa() {
  if (!loraSerial.available()) return;

  String linea = loraSerial.readStringUntil('\n');
  linea.trim();

  if (!linea.startsWith("+RCV=")) return;

  // Formato: +RCV=<SENDER_ID>,<LEN>,<PAYLOAD>,<RSSI>,<SNR>
  // Ejemplo: +RCV=100,132,0:A1B2C3...,-65,12
  int primeraComa = linea.indexOf(',');
  int segundaComa = linea.indexOf(',', primeraComa + 1);
  int terceraComa = linea.indexOf(',', segundaComa + 1);

  if (segundaComa == -1 || terceraComa == -1) return;

  int emisorId = linea.substring(5, primeraComa).toInt();
  if (emisorId != GATEWAY_LORA_ID) return; // Filtrar emisores desconocidos

  String payload = linea.substring(segundaComa + 1, terceraComa);
  int dosPuntos = payload.indexOf(':');

  if (dosPuntos == -1) return;

  int chunkIdx = payload.substring(0, dosPuntos).toInt();
  String hexChunk = payload.substring(dosPuntos + 1);

  // 1. Responder ACK Inmediatamente por radio al Gateway (ID 100)
  enviarAckLoRa(chunkIdx);

  // 2. Control de ráfaga e integración de datos
  tiempoUltimoChunk = millis();

  if (chunkIdx == 0 && estadoSync == SYNC_IDLE) {
    memset(huellaBuffer, 0, sizeof(huellaBuffer));
    memset(chunksRecibidos, false, sizeof(chunksRecibidos));
    chunksGuardadosValidos = 0;
    ultimoIndiceProcesado = -1;
    tiempoPrimerChunk = millis();
    estadoSync = SYNC_RECIBIENDO;
    msgOled("SYNC...", "Recibiendo radio");
    Serial.println("\n[LORA] >>> Iniciando recepción de huella biométrica... <<<");
  }

  if (chunkIdx > ultimoIndiceProcesado && chunkIdx < TOTAL_CHUNKS_ESPERADOS) {
    int offset = chunkIdx * CHARS_POR_CHUNK;
    int len = hexChunk.length();
    if (offset + len < sizeof(huellaBuffer)) {
      memcpy(huellaBuffer + offset, hexChunk.c_str(), len);
    }

    chunksGuardadosValidos++;
    ultimoIndiceProcesado = chunkIdx;

    Serial.printf("  Chunk #%02d/%02d [ACK OK] | RSSI/SNR: %s\n",
                  chunksGuardadosValidos, TOTAL_CHUNKS_ESPERADOS,
                  linea.substring(terceraComa + 1).c_str());

    // Al acumular los 24 chunks esperados, procesar grabado
    if (chunksGuardadosValidos == TOTAL_CHUNKS_ESPERADOS) {
      estadoSync = SYNC_PROCESANDO;
      msgOled("GUARDANDO", "En sensor...");

      huellaBuffer[sizeof(huellaBuffer) - 1] = '\0';
      String hexLimpio = String(huellaBuffer);
      hexLimpio.trim();

      String error;
      if (guardarHuellaDY50(hexLimpio, idSyncSlotActual, error)) {
        strcpy(dbLocal[idSyncSlotActual].ci, ciSyncActual);
        strcpy(dbLocal[idSyncSlotActual].tipo_persona, tipoSyncActual);
        dbLocal[idSyncSlotActual].registrado = true;
        guardarSlotEnNVS(idSyncSlotActual);

        msgOled("SYNC OK", "Slot: " + String(idSyncSlotActual));
        Serial.printf("[LORA ÉXITO] Huella grabada en slot %d en %.2f segundos\n",
                      idSyncSlotActual, (millis() - tiempoPrimerChunk) / 1000.0);
      } else {
        msgOled("ERROR DY50", error);
        Serial.printf("[LORA ERROR] Fallo grabado DY50: %s\n", error.c_str());
      }

      memset(huellaBuffer, 0, sizeof(huellaBuffer));
      chunksGuardadosValidos = 0;
      ultimoIndiceProcesado = -1;
      estadoSync = SYNC_IDLE;
      delay(2000);
      msgOled("AULA: " + ROOM_ID, "Listo...");
    }
  } else {
    Serial.printf("  Chunk duplicado ignorado (#%02d) -> [ACK RE-ENVIADO]\n", chunkIdx);
  }
}

void enviarAckLoRa(int chunkIdx) {
  String ackMsg = "ACK" + String(chunkIdx);
  loraSerial.print("AT+SEND=");
  loraSerial.print(GATEWAY_LORA_ID);
  loraSerial.print(",");
  loraSerial.print(ackMsg.length());
  loraSerial.print(",");
  loraSerial.print(ackMsg);
  loraSerial.write(0x0D);
  loraSerial.write(0x0A);
}

// ----------------------------------------------------------------------------
// ENVÍO DE ASISTENCIA VÍA LORA HACIA EL GATEWAY CENTRAL
// ----------------------------------------------------------------------------
void enviarAsistenciaPorLoRa(String ci, String tipoPersona, String estado) {
  // Payload: "AST:<ROOM_ID>:<CI>:<TIPO_PERSONA>:<ESTADO>"
  String payload = "AST:" + ROOM_ID + ":" + ci + ":" + tipoPersona + ":" + estado;

  Serial.printf("\n[LORA AST] Transmitiendo asistencia al Gateway (ID %d)...\n", GATEWAY_LORA_ID);
  Serial.printf("Payload: %s\n", payload.c_str());

  loraSerial.print("AT+SEND=");
  loraSerial.print(GATEWAY_LORA_ID);
  loraSerial.print(",");
  loraSerial.print(payload.length());
  loraSerial.print(",");
  loraSerial.print(payload);
  loraSerial.write(0x0D);
  loraSerial.write(0x0A);

  msgOled("ENVIADO", ci);
}

// ----------------------------------------------------------------------------
// PROCESAMIENTO BIOMÉTRICO LOCAL
// ----------------------------------------------------------------------------
void verificarLecturaHuella() {
  int status = finger.getImage();
  if (status != FINGERPRINT_OK) return;

  status = finger.image2Tz();
  if (status != FINGERPRINT_OK) return;

  status = finger.fingerFastSearch();
  if (status != FINGERPRINT_OK) {
    msgOled("ERROR", "No reconocido");
    delay(1500);
    msgOled("AULA: " + ROOM_ID, "Listo...");
    return;
  }

  int slotMatch = finger.fingerID;
  Serial.printf("[BIOM] Match en slot: %d (confianza: %d)\n", slotMatch, finger.confidence);

  if (slotMatch < 0 || slotMatch > 200 || !dbLocal[slotMatch].registrado) {
    msgOled("SIN DATOS", "Slot:" + String(slotMatch));
    Serial.printf("[BIOM] Slot %d no tiene registro en dbLocal.\n", slotMatch);
    delay(1500);
    msgOled("AULA: " + ROOM_ID, "Listo...");
    return;
  }

  String rol = String(dbLocal[slotMatch].tipo_persona);

  // Lógica de salida anticipada
  if (estadoActual == ESPERANDO_PROFE) {
    if (rol == "PROFESOR" || rol == "COORDINADOR") {
      estadoActual = SALIDA_ACTIVA;
      tiempoLimiteEstado = millis() + 20000;
      msgOled("AUTORIZADO", "Ponga dedo Alumno");
    } else {
      msgOled("ERROR", "No es Profe");
      delay(1500);
      estadoActual = NORMAL;
      msgOled("AULA: " + ROOM_ID);
    }
    return;
  }

  if (estadoActual == NORMAL && (millis() - ultimoRegistroSlot[slotMatch] < TIEMPO_COOLDOWN)) {
    msgOled("YA MARCADO", dbLocal[slotMatch].ci);
    delay(1500);
    msgOled("AULA: " + ROOM_ID);
    return;
  }

  String estadoEnvio = (estadoActual == SALIDA_ACTIVA) ? "RET_ANTICIPADO" : "PRESENTE";

  enviarAsistenciaPorLoRa(String(dbLocal[slotMatch].ci),
                         String(dbLocal[slotMatch].tipo_persona),
                         estadoEnvio);

  ultimoRegistroSlot[slotMatch] = millis();
  if (estadoActual == SALIDA_ACTIVA) estadoActual = NORMAL;

  delay(2000);
  msgOled("AULA: " + ROOM_ID, "Listo...");
}
