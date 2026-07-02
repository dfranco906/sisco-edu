// CODIGO ESP AULA-A (con sensor de huella DY50 y pantalla oled 0.96''

#include <esp_now.h>
#include <WiFi.h>
#include <esp_wifi.h>
#include <Adafruit_Fingerprint.h>
#include <Adafruit_SSD1306.h>
#include <Adafruit_GFX.h>
#include "dy50_template_transport.h"
#include <Preferences.h>

Preferences prefs;

// ============================================================================
// CONFIGURACIÓN DE HARDWARE
// ============================================================================
String ROOM_ID = "AULA_A";
uint8_t gatewayMac[] = {0xD4, 0xE9, 0xF4, 0xE6, 0xD6, 0xF4}; // MAC real del Gateway

// Pines Sensor DY50 (UART2)
HardwareSerial dy50Serial(2);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&dy50Serial);

// Pines OLED 0.96
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SSD1306 oled(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

// Botón de Salida
#define PIN_BOTON_SALIDA 4

// Máquina de Estados
enum EstadoSistema { NORMAL, ESPERANDO_PROFE, SALIDA_ACTIVA };
EstadoSistema estadoActual = NORMAL;
uint32_t tiempoLimiteEstado = 0;

// ============================================================================
// ESTRUCTURAS DE DATOS
// ============================================================================
typedef struct __attribute__((packed)) {
  int id_sync;
  int id_huella;
  int total_chunks;
  int chunk_index;
  char ci[15];
  char room_id[16];
  char tipo_persona[15];
  char data[161];
} PaqueteHuella;

typedef struct __attribute__((packed)) {
  char room_id[16];
  char ci[15];
  char tipo_persona[15];
  char estado[15];
  char fecha_hora[20];
} PaqueteAsistencia;

typedef struct __attribute__((packed)) { char msg[4]; } PaquetePing;

struct RegistroUsuario {
  char ci[15];
  char tipo_persona[15]; // "ALUMNO", "PROFESOR", "COORDINADOR"
  bool registrado = false;
};
RegistroUsuario dbLocal[201];

uint32_t ultimoRegistroSlot[201] = {0};
const uint32_t TIEMPO_COOLDOWN = 300000; // 5 min

String huellaReconstruida = "";
int chunkEsperado = 0;
volatile bool pingAckRecibido = false;
volatile bool txStatusStatus = false;

// Variables volatiles para desacoplar el callback ESP-NOW del loop principal
volatile bool flagSyncIniciado = false;
volatile bool flagSyncCompletado = false;
volatile bool flagSyncError = false;
volatile uint32_t tiempoUltimoChunk = 0;
volatile int idSyncSlot = 0;
char ciSync[15] = "";
char tipoPersonaSync[15] = "";

enum EstadoSync { SYNC_IDLE, SYNC_RECIBIENDO, SYNC_PROCESANDO };
EstadoSync estadoSync = SYNC_IDLE;

// ============================================================================
// FUNCIONES DE PANTALLA OLED
// ============================================================================
void msgOled(String t1, String t2 = "") {
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
// PERSISTENCIA NVS (Preferences) — Sobrevive reinicios
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
  prefs.begin("dblocal", true); // modo solo-lectura
  int restaurados = 0;
  for (int i = 0; i < 201; i++) {
    String keyReg = "reg_" + String(i);
    if (prefs.getBool(keyReg.c_str(), false)) {
      String keyCI   = "ci_"   + String(i);
      String keyTipo = "tipo_" + String(i);
      String ci   = prefs.getString(keyCI.c_str(),   "");
      String tipo = prefs.getString(keyTipo.c_str(), "");
      ci.toCharArray(dbLocal[i].ci,           sizeof(dbLocal[i].ci));
      tipo.toCharArray(dbLocal[i].tipo_persona, sizeof(dbLocal[i].tipo_persona));
      dbLocal[i].registrado = true;
      restaurados++;
    }
  }
  prefs.end();
  Serial.printf("[NVS] dbLocal restaurado: %d usuarios cargados desde flash\n", restaurados);
}

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
// CALLBACKS ESP-NOW
// ============================================================================
void onSend(const wifi_tx_info_t *tx_info, esp_now_send_status_t status) {
  txStatusStatus = true;
  pingAckRecibido = (status == ESP_NOW_SEND_SUCCESS);
}

void onReceive(const esp_now_recv_info_t *recv_info, const uint8_t *incomingData, int len) {
  if (len != sizeof(PaqueteHuella)) {
    return;
  }

  PaqueteHuella paquete;
  memcpy(&paquete, incomingData, sizeof(paquete));
  if (String(paquete.room_id) != ROOM_ID) {
    return;
  }

  if (paquete.chunk_index == 0) {
    huellaReconstruida = "";
    huellaReconstruida.reserve(3072); // Pre-asignar memoria para evitar fragmentacion
    chunkEsperado = 0;
    idSyncSlot = paquete.id_huella;
    strncpy(ciSync, paquete.ci, sizeof(ciSync) - 1);
    ciSync[sizeof(ciSync) - 1] = '\0';
    strncpy(tipoPersonaSync, paquete.tipo_persona, sizeof(tipoPersonaSync) - 1);
    tipoPersonaSync[sizeof(tipoPersonaSync) - 1] = '\0';
    flagSyncIniciado = true;
  }

  if (paquete.chunk_index == chunkEsperado) {
    huellaReconstruida += String(paquete.data);
    chunkEsperado++;
    tiempoUltimoChunk = millis();
  } else {
    flagSyncError = true;
    return;
  }

  if (paquete.chunk_index == paquete.total_chunks - 1) {
    flagSyncCompletado = true;
  }
}

// ============================================================================
// AUTO-CANAL (Plug & Play)
// ============================================================================
void escanearYFijarCanalGateway() {
  msgOled("BUSCANDO", "Canal Gateway...");
  PaquetePing ping = {"PNG"};
  bool encontrado = false;

  for (uint8_t canal = 1; canal <= 11; canal++) {
    esp_wifi_set_channel(canal, WIFI_SECOND_CHAN_NONE);
    delay(40);
    txStatusStatus = false;
    pingAckRecibido = false;
    esp_now_send(gatewayMac, (uint8_t*)&ping, sizeof(ping));
   
    uint32_t t0 = millis();
    while (!txStatusStatus && (millis() - t0 < 100)) { delay(1); }

    if (pingAckRecibido) {
      msgOled("CANAL: " + String(canal), "Enlace OK");
      encontrado = true;
      delay(1000);
      break;
    }
  }
  if (!encontrado) {
    msgOled("ERROR", "No hay Gateway");
    esp_wifi_set_channel(1, WIFI_SECOND_CHAN_NONE);
  }
}

// ============================================================================
// PROCESAMIENTO BIOMÉTRICO
// ============================================================================
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
    Serial.printf("[BIOM] Slot %d no tiene datos en dbLocal. Verifica sync.\n", slotMatch);
    delay(1500);
    msgOled("AULA: " + ROOM_ID, "Listo...");
    return;
  }

  String rol = String(dbLocal[slotMatch].tipo_persona);

  // LOGICA DE ESTADOS
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

  PaqueteAsistencia asistencia = {};
  ROOM_ID.toCharArray(asistencia.room_id, sizeof(asistencia.room_id));
  strcpy(asistencia.ci, dbLocal[slotMatch].ci);
  strcpy(asistencia.tipo_persona, dbLocal[slotMatch].tipo_persona);
  estadoEnvio.toCharArray(asistencia.estado, sizeof(asistencia.estado));
  strcpy(asistencia.fecha_hora, "AUTO");

  esp_err_t res = esp_now_send(gatewayMac, (uint8_t*)&asistencia, sizeof(asistencia));

  if (res == ESP_OK) {
    msgOled("ENVIADO", String(asistencia.ci));
    ultimoRegistroSlot[slotMatch] = millis();
    if (estadoActual == SALIDA_ACTIVA) estadoActual = NORMAL;
  } else {
    msgOled("ERROR TX", "Sin respuesta GW");
  }

  delay(2000);
  msgOled("AULA: " + ROOM_ID, "Listo...");
}

// ============================================================================
// SETUP
// ============================================================================
void setup() {
  Serial.begin(115200);
 
  // Inicializar OLED
  if(!oled.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    for(;;);
  }
  oled.setTextColor(SSD1306_WHITE);
  msgOled("INICIANDO", "Sisco-Edu v1.0");

  pinMode(PIN_BOTON_SALIDA, INPUT_PULLUP);

  WiFi.mode(WIFI_STA);
  esp_wifi_start();

  delay(1500); // Pausa de cortesía para que el monitor serial de tu PC se estabilice
  Serial.print("DIRECCION MAC FISICA DE ESTA AULA: ");
  Serial.println(WiFi.macAddress());

  if (esp_now_init() != ESP_OK) return;

  esp_now_register_send_cb(onSend);
  esp_now_register_recv_cb(onReceive);

  esp_now_peer_info_t peerInfo = {};
  memset(&peerInfo, 0, sizeof(peerInfo));
  memcpy(peerInfo.peer_addr, gatewayMac, 6);
  peerInfo.ifidx = WIFI_IF_STA;
  esp_now_add_peer(&peerInfo);

  escanearYFijarCanalGateway();

  dy50Serial.begin(57600, SERIAL_8N1, 16, 17);
  finger.begin(57600);
  if (finger.verifyPassword()) {
    msgOled("DY50: OK", "Cargando DB...");
    cargarDbLocalDesdeNVS(); // Restaurar metadatos de huellas desde flash
    msgOled("DY50: OK", "Sistema Listo");
  } else {
    msgOled("ERROR", "DY50 no hallado");
  }
  delay(1500);
  msgOled("AULA: " + ROOM_ID, "Esperando huellas");
}

// ============================================================================
// LOOP PRINCIPAL
// ============================================================================
void loop() {
  // 1. Boton presionado para Salida Anticipada
  if (digitalRead(PIN_BOTON_SALIDA) == LOW && estadoActual == NORMAL && estadoSync == SYNC_IDLE) {
    estadoActual = ESPERANDO_PROFE;
    tiempoLimiteEstado = millis() + 15000;
    msgOled("SALIDA", "Dedo de Profe...");
    delay(500);
  }

  // 2. Timeout de estados
  if (estadoActual != NORMAL && millis() > tiempoLimiteEstado) {
    estadoActual = NORMAL;
    msgOled("CANCELADO", "Tiempo expirado");
    delay(1500);
    msgOled("AULA: " + ROOM_ID, "Listo...");
  }

  // 3. Desacoplamiento de Sincronizacion (fuera de la interrupcion ESP-NOW)
  if (flagSyncIniciado) {
    flagSyncIniciado = false;
    estadoActual = NORMAL; // Cancelamos cualquier flujo de asistencia
    msgOled("SYNC...", "CI: " + String(ciSync));
    Serial.printf("[SYNC] Iniciando recepcion huella para CI: %s en slot: %d\n", ciSync, idSyncSlot);
    estadoSync = SYNC_RECIBIENDO;
    tiempoUltimoChunk = millis();
  }

  if (estadoSync == SYNC_RECIBIENDO) {
    if (flagSyncError) {
      flagSyncError = false;
      estadoSync = SYNC_IDLE;
      huellaReconstruida = String();
      msgOled("ERR SYNC", "Secuencia rota");
      Serial.println("[SYNC] Error: secuencia de chunks rota");
      delay(2000);
      msgOled("AULA: " + ROOM_ID, "Listo...");
    }
    else if (flagSyncCompletado) {
      flagSyncCompletado = false;
      estadoSync = SYNC_PROCESANDO;
      Serial.printf("[SYNC] Grabando huella completa en DY50 slot %d...\n", idSyncSlot);
      msgOled("GUARDANDO", "En sensor...");

      String error;
      if (guardarHuellaDY50(huellaReconstruida, idSyncSlot, error)) {
        strcpy(dbLocal[idSyncSlot].ci, ciSync);
        strcpy(dbLocal[idSyncSlot].tipo_persona, tipoPersonaSync);
        dbLocal[idSyncSlot].registrado = true;
        guardarSlotEnNVS(idSyncSlot); // Persiste en flash para sobrevivir reinicios
        msgOled("SYNC OK", "Slot: " + String(idSyncSlot));
        Serial.println("[SYNC] Huella guardada con exito en DY50 y NVS");
      } else {
        msgOled("ERROR DY50", error);
        Serial.printf("[SYNC] Fallo guardando huella: %s\n", error.c_str());
      }

      huellaReconstruida = String();
      estadoSync = SYNC_IDLE;
      delay(2000);
      msgOled("AULA: " + ROOM_ID, "Listo...");
    }
    else if (millis() - tiempoUltimoChunk > 5000) {
      // Timeout tras 5 segundos sin recibir chunks
      estadoSync = SYNC_IDLE;
      huellaReconstruida = String();
      msgOled("TIMEOUT", "Cancelando sync");
      Serial.println("[SYNC] Timeout: Se perdieron paquetes en la transmision");
      delay(2000);
      msgOled("AULA: " + ROOM_ID, "Listo...");
    }
  }

  // 4. Sensor Biometrico (solo si no estamos sincronizando)
  if (estadoSync == SYNC_IDLE) {
    verificarLecturaHuella();
  }
 
  delay(30);
}