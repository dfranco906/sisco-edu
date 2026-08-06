// CODIGO PARA ESP-GATEWAY (LoRa RYLR896/998 AT + HTTP Central)
#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h>

// ============================================================================
// CONFIGURACIÓN DE RED Y HARDWARE
// ============================================================================
const char *ssid = "esp";
const char *password = "123456789";

String BASE = "http://192.168.0.165/sisco-edu/";
String API_KEY = "SISCO_GATEWAY_2026_SECRETO";
String ROOM_ID = "AULA_A";

const char *headerKeys[] = {"X-GATEWAY-KEY"};
const size_t headerKeysCount = 1;

WebServer server(80);

// Pines UART LoRa AT (HardwareSerial 2)
#define LORA_RX 16
#define LORA_TX 17
HardwareSerial loraSerial(2);

// Identificadores LoRa y constantes de transmisión
const int MI_LORA_ID = 100;
const int TOTAL_BYTES_HUELLA = 1408;
const int CHUNK_BYTES = 64;
const int TOTAL_CHUNKS = TOTAL_BYTES_HUELLA / CHUNK_BYTES;
const int MAX_REINTENTOS = 8;

// ============================================================================
// TOPOLOGÍA MULTIPUNTO LORA
// ============================================================================
typedef struct {
  char room_id[16];
  int lora_id;
} DispositivoAula;

const int MAX_AULAS = 5;
DispositivoAula tablaAulas[MAX_AULAS] = {
    {"AULA_A", 101},
    {"AULA_B", 102},
    {"AULA_C", 103}
};

// Prototipos
void pedirSyncPendiente();
bool buscarLoraIdPorAula(String roomId, int &loraId);
bool enviarHuellaPorLoRa(int loraIdDestino, int idSync, int idHuella,
                         String ci, String roomId, String tipo, String huella);
bool esperarOKLocal(unsigned long timeoutMs = 200);
bool esperarAckRemoto(int loraIdDestino, int chunkIdx, unsigned long timeoutMs = 400);
void confirmarSync(int idSync, String estado, String mensaje);
void atenderMensajesLoRa();
void procesarAsistenciaEntrante(String payload);
void procesarEnvioAsistenciaBackend(String roomId, String ci, String tipoPersona, String estado);

// ============================================================================
// SETUP
// ============================================================================
void setup() {
  Serial.begin(115200);
  loraSerial.begin(115200, SERIAL_8N1, LORA_RX, LORA_TX);

  Serial.println("\n==================================================");
  Serial.println("[GATEWAY] INICIALIZANDO NODO CENTRAL LORA (ID 100)");
  Serial.println("==================================================");

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  Serial.print("Conectando Wi-Fi...");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\n[GATEWAY] Wi-Fi Conectado con exito");
  Serial.print("IP Gateway: "); Serial.println(WiFi.localIP());

  server.collectHeaders(headerKeys, headerKeysCount);

  server.on("/sync", HTTP_POST, []() {
    String key = server.header("X-GATEWAY-KEY");
    if (key != API_KEY) {
      server.send(404, "application/json", "{\"status\":\"not_found\"}");
      return;
    }
    server.send(200, "application/json",
                "{\"status\":\"ok\",\"message\":\"Gateway recibio orden de sincronizar\"}");
    pedirSyncPendiente();
  });

  server.begin();
  Serial.println("[GATEWAY] Servidor HTTP listo en /sync");
  Serial.println("[GATEWAY] Escuchando tramas LoRa entrantes...");
}

// ============================================================================
// LOOP PRINCIPAL
// ============================================================================
void loop() {
  server.handleClient();
  atenderMensajesLoRa();
}

// ============================================================================
// PARSEO ASINCRONO DE MENSAJES LORA ENTRANTES
// ============================================================================
void atenderMensajesLoRa() {
  if (!loraSerial.available()) return;

  String linea = loraSerial.readStringUntil('\n');
  linea.trim();

  if (!linea.startsWith("+RCV=")) return;

  int primeraComa = linea.indexOf(',');
  int segundaComa = linea.indexOf(',', primeraComa + 1);
  int terceraComa = linea.indexOf(',', segundaComa + 1);

  if (segundaComa == -1 || terceraComa == -1) return;

  String payload = linea.substring(segundaComa + 1, terceraComa);

  if (payload.startsWith("AST:")) {
    Serial.printf("[LORA RCV AST] Trama de Asistencia: %s\n", payload.c_str());
    procesarAsistenciaEntrante(payload);
  }
}

void procesarAsistenciaEntrante(String payload) {
  int p1 = payload.indexOf(':');
  int p2 = payload.indexOf(':', p1 + 1);
  int p3 = payload.indexOf(':', p2 + 1);
  int p4 = payload.indexOf(':', p3 + 1);

  if (p1 != -1 && p2 != -1 && p3 != -1 && p4 != -1) {
    String roomId      = payload.substring(p1 + 1, p2);
    String ci          = payload.substring(p2 + 1, p3);
    String tipoPersona = payload.substring(p3 + 1, p4);
    String estado      = payload.substring(p4 + 1);

    procesarEnvioAsistenciaBackend(roomId, ci, tipoPersona, estado);
  }
}

// ============================================================================
// BUSQUEDA DE RUTAS
// ============================================================================
bool buscarLoraIdPorAula(String roomId, int &loraId) {
  for (int i = 0; i < MAX_AULAS; i++) {
    if (String(tablaAulas[i].room_id) == roomId) {
      loraId = tablaAulas[i].lora_id;
      return true;
    }
  }
  return false;
}

// ============================================================================
// PROTOCOLO ARQ STOP-AND-WAIT
// ============================================================================
bool esperarOKLocal(unsigned long timeoutMs) {
  unsigned long start = millis();
  while (millis() - start < timeoutMs) {
    if (loraSerial.available()) {
      String resp = loraSerial.readStringUntil('\n');
      resp.trim();
      if (resp.indexOf("+OK") != -1) return true;
      if (resp.indexOf("+ERR") != -1) return false;
    }
    yield();
  }
  return false;
}

bool esperarAckRemoto(int loraIdDestino, int chunkIdx, unsigned long timeoutMs) {
  unsigned long start = millis();
  String tokenAckEsperado = "ACK" + String(chunkIdx);

  while (millis() - start < timeoutMs) {
    if (loraSerial.available()) {
      String resp = loraSerial.readStringUntil('\n');
      resp.trim();
      if (resp.indexOf("+RCV=") != -1 && resp.indexOf(tokenAckEsperado) != -1) {
        return true;
      }
    }
    yield();
  }
  return false;
}

void enviarComandoLoRa(int loraIdDestino, const String &payload) {
  loraSerial.print("AT+SEND=");
  loraSerial.print(loraIdDestino);
  loraSerial.print(",");
  loraSerial.print(payload.length());
  loraSerial.print(",");
  loraSerial.print(payload);
  loraSerial.write(0x0D);
  loraSerial.write(0x0A);
}

// ============================================================================
// TRANSMISION DE HUELLA CON PREAMBULO DE METADATOS Y ARQ
// ============================================================================
bool enviarHuellaPorLoRa(int loraIdDestino, int idSync, int idHuella,
                         String ci, String roomId, String tipo, String huella) {
  const int charsPerChunk = 128;
  int totalChunks = (huella.length() + charsPerChunk - 1) / charsPerChunk;

  Serial.printf("\n[LORA ARQ] Transmitiendo rafaga biometrica a LoRa ID %d (%d chunks)\n",
                loraIdDestino, totalChunks);

  unsigned long tiempoInicio = millis();

  for (int chunkIdx = 0; chunkIdx < totalChunks; chunkIdx++) {
    String hexChunk = huella.substring(chunkIdx * charsPerChunk,
                                       min((chunkIdx + 1) * charsPerChunk, (int)huella.length()));

    String payloadConIndice = String(chunkIdx) + ":" + hexChunk;

    // En chunk 0, incrustar metadatos: "0:CI:TIPO:HEX_DATA"
    // El aula detecta los dos puntos adicionales al inicio del chunk 0
    if (chunkIdx == 0) {
      payloadConIndice = "0:" + ci + ":" + tipo + ":" + hexChunk;
    }

    bool paqueteConfirmado = false;
    int reintentos = 0;

    while (!paqueteConfirmado && reintentos < MAX_REINTENTOS) {
      while (loraSerial.available()) { loraSerial.read(); }

      enviarComandoLoRa(loraIdDestino, payloadConIndice);

      if (esperarOKLocal(200)) {
        paqueteConfirmado = esperarAckRemoto(loraIdDestino, chunkIdx, 400);
      }

      if (paqueteConfirmado) {
        Serial.printf("  -> Chunk [%d/%d] ACK OK\n", chunkIdx + 1, totalChunks);
        delay(15);
      } else {
        reintentos++;
        Serial.printf("  -> [REINTENTO %d/%d] Chunk [%d/%d] sin ACK\n",
                      reintentos, MAX_REINTENTOS, chunkIdx + 1, totalChunks);
        delay(80);
      }
    }

    if (!paqueteConfirmado) {
      Serial.printf("[LORA ARQ ERROR] Transmision interrumpida en chunk %d\n", chunkIdx + 1);
      return false;
    }
  }

  float tiempoSeg = (millis() - tiempoInicio) / 1000.0;
  Serial.printf("[LORA ARQ EXITO] Sincronizacion enviada en %.2f segundos\n", tiempoSeg);
  return true;
}

// ============================================================================
// SOLICITUD DE SYNCS PENDIENTES AL BACKEND PHP
// ============================================================================
void pedirSyncPendiente() {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  String url = BASE + "src/api/Gateway/obtener_sync_aula.php?room_id=" + ROOM_ID;

  http.begin(url);
  http.addHeader("X-GATEWAY-KEY", API_KEY);

  int code = http.GET();
  String payload = http.getString();

  if (code != 200 || payload.indexOf("\"status\":\"success\"") < 0) {
    Serial.println("[GATEWAY] No hay sincronizaciones pendientes en el Backend.");
    http.end();
    return;
  }

  DynamicJsonDocument doc(16384);
  deserializeJson(doc, payload);

  int idSync     = doc["data"]["id_sync"];
  int idHuella   = doc["data"]["id_huella"];
  String ci      = doc["data"]["ci"] | "";
  String roomId  = doc["data"]["room_id"] | "";
  String tipo    = doc["data"]["tipo_persona"] | "";
  String huella  = doc["data"]["huella_base64"] | "";
  huella.trim();

  Serial.printf("\n--- NUEVA ORDEN DE SYNCRONIZACION RECIBIDA ---\n");
  Serial.printf("Destino Aula: %s | CI: %s | ID Huella: %d\n", roomId.c_str(), ci.c_str(), idHuella);

  int loraIdObjetivo = 0;
  if (!buscarLoraIdPorAula(roomId, loraIdObjetivo)) {
    Serial.printf("[ERROR] No existe ID LoRa asignado para el aula '%s'\n", roomId.c_str());
    confirmarSync(idSync, "ERROR", "El Gateway no tiene mapeado el ID LoRa de esa aula");
    http.end();
    return;
  }

  bool enviado = enviarHuellaPorLoRa(loraIdObjetivo, idSync, idHuella, ci, roomId, tipo, huella);

  if (enviado) {
    confirmarSync(idSync, "CONFIRMADO", "Huella enviada e instalada en el aula via LoRa");
  } else {
    confirmarSync(idSync, "ERROR", "Fallo el enlace de radio LoRa ARQ entre pisos");
  }

  http.end();
}

void confirmarSync(int idSync, String estado, String mensaje) {
  HTTPClient http;
  String url = BASE + "src/api/Gateway/confirmar_sync.php";
  http.begin(url);
  http.addHeader("X-GATEWAY-KEY", API_KEY);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String body = "id_sync=" + String(idSync) + "&estado=" + estado + "&mensaje=" + mensaje;
  http.POST(body);
  http.end();
}

void procesarEnvioAsistenciaBackend(String roomId, String ci, String tipoPersona, String estado) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[ERROR] No hay Wi-Fi para retransmitir la asistencia al Backend.");
    return;
  }

  HTTPClient http;
  String url = BASE + "src/api/Gateway/registrar_asistencia.php";

  http.begin(url);
  http.addHeader("X-GATEWAY-KEY", API_KEY);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String body = "room_id=" + roomId +
                "&ci=" + ci +
                "&tipo_persona=" + tipoPersona +
                "&estado=" + estado +
                "&fecha_hora=AUTO";

  Serial.println("\n--- REENVIANDO ASISTENCIA RECIBIDA POR LORA AL BACKEND ---");
  Serial.printf("Aula: %s | C.I: %s | Rol: %s | Evento: %s\n",
                roomId.c_str(), ci.c_str(), tipoPersona.c_str(), estado.c_str());

  int code = http.POST(body);
  String respuesta = http.getString();

  Serial.printf("Respuesta Servidor Central [HTTP %d]: %s\n\n", code, respuesta.c_str());
  http.end();
}