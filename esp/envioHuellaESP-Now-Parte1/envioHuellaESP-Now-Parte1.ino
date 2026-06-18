#include <WiFi.h>
#include <HTTPClient.h>
#include <esp_now.h>
#include <ArduinoJson.h>
#include <WebServer.h>

const char* ssid = "esp";
const char* password = "123456789";

String BASE = "http://192.168.100.110/tiago3roBTI2026/sisco-edu/";
String API_KEY = "SISCO_GATEWAY_2026_SECRETO";
String ROOM_ID = "GENERAL";

// MAC real del ESP Aula
uint8_t aulaMac[] = {0xB0, 0xCB, 0xD8, 0x8E, 0x72, 0x74};

const char* headerKeys[] = {"X-GATEWAY-KEY"};
const size_t headerKeysCount = 1;

WebServer server(80);

// Control de flujo síncrono para asegurar la entrega entre pisos
volatile bool txStatusReceived = false;
volatile bool txSuccess = false;

// ESTRUCTURA OPTIMIZADA: Peso total = 218 bytes (Seguro dentro del límite de 250 bytes)
typedef struct {
  int id_sync;
  int id_huella;
  int total_chunks;
  int chunk_index;
  char ci[15];          // Ajustado para C.I. de Paraguay (Max 15 chars)
  char room_id[16];     // Ajustado (Ej: "AULA_1")
  char tipo_persona[15]; // Ajustado (Ej: "ALUMNO")
  char data[161];       // Chunks de 160 bytes para no saturar ESP-NOW
} PaqueteHuella;

// Firma del callback corregida para Core 3.3.8
void onSend(const wifi_tx_info_t *tx_info, esp_now_send_status_t status) {
  txStatusReceived = true;
  txSuccess = (status == ESP_NOW_SEND_SUCCESS);
}

void pedirSyncPendiente();
bool enviarHuellaPorEspNow(int idSync, int idHuella, String ci, String roomId, String tipo, String huella);
void confirmarSync(int idSync, String estado, String mensaje);

void setup() {
  Serial.begin(115200);

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  Serial.println("Conectando WiFi...");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println("\nGateway conectado");
  Serial.print("IP Gateway: "); Serial.println(WiFi.localIP());
  Serial.print("Canal de Wi-Fi del Router: "); Serial.println(WiFi.channel());
  Serial.print("MAC Gateway: "); Serial.println(WiFi.macAddress());

  server.collectHeaders(headerKeys, headerKeysCount);

  if (esp_now_init() != ESP_OK) {
    Serial.println("Error iniciando ESP-NOW");
    return;
  }

  esp_now_register_send_cb(onSend);

  // Configuración obligatoria para evitar errores de interfaz en Core 3.x
  esp_now_peer_info_t peerInfo = {};
  memset(&peerInfo, 0, sizeof(peerInfo));
  memcpy(peerInfo.peer_addr, aulaMac, 6);
  peerInfo.channel = 0;
  peerInfo.encrypt = false;
  peerInfo.ifidx = WIFI_IF_STA; // Interfaz explícita fija

  if (esp_now_add_peer(&peerInfo) != ESP_OK) {
    Serial.println("Error agregando ESP Aula como peer");
    return;
  }

  Serial.println("ESP-NOW listo y enlazado al aula.");

  server.on("/sync", HTTP_POST, []() {
    String key = server.header("X-GATEWAY-KEY");
    if (key != API_KEY) {
      server.send(404, "application/json", "{\"status\":\"not_found\"}");
      return;
    }
    server.send(200, "application/json", "{\"status\":\"ok\",\"message\":\"Gateway recibio orden de sincronizar\"}");
    pedirSyncPendiente();
  });

  server.begin();
  Serial.println("Servidor HTTP Gateway listo en /sync");
}

void loop() {
  server.handleClient();
}

void pedirSyncPendiente() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi desconectado");
    return;
  }

  HTTPClient http;
  String url = BASE + "src/api/Gateway/obtener_sync_aula.php?room_id=" + ROOM_ID;

  http.begin(url);
  http.addHeader("X-GATEWAY-KEY", API_KEY);

  int code = http.GET();
  String payload = http.getString();

  Serial.println("\n--- RESPUESTA BACKEND ---");
  Serial.println(payload);

  if (code != 200 || payload.indexOf("\"status\":\"success\"") < 0) {
    Serial.println("No hay sync pendiente o hubo error");
    http.end();
    return;
  }

  DynamicJsonDocument doc(60000);
  DeserializationError error = deserializeJson(doc, payload);

  if (error) {
    Serial.println("Error leyendo JSON");
    http.end();
    return;
  }

  int idSync = doc["data"]["id_sync"];
  int idHuella = doc["data"]["id_huella"];
  String ci = doc["data"]["ci"] | "";
  String roomId = doc["data"]["room_id"] | "";
  String tipo = doc["data"]["tipo_persona"] | "";
  String huella = doc["data"]["huella_base64"] | ""; // O formato Hexadecimal

  Serial.printf("Procesando ID Sync: %d | Alumno CI: %s\n", idSync, ci.c_str());

  bool enviado = enviarHuellaPorEspNow(idSync, idHuella, ci, roomId, tipo, huella);

  if (enviado) {
    confirmarSync(idSync, "CONFIRMADO", "Huella enviada e instalada en el aula de forma integra");
  } else {
    confirmarSync(idSync, "ERROR", "Fallo el enlace de radio de ESP-NOW entre pisos");
  }

  http.end();
}

bool enviarHuellaPorEspNow(int idSync, int idHuella, String ci, String roomId, String tipo, String huella) {
  const int chunkSize = 160; // Ajustado al tamaño de nuestro buffer optimizado
  int total = (huella.length() + chunkSize - 1) / chunkSize;

  Serial.println("\n--- INICIANDO EMISION FRAGMENTADA VIA ESP-NOW ---");
  Serial.printf("Tamaño total string: %d bytes | Total Chunks: %d\n", huella.length(), total);

  for (int i = 0; i < total; i++) {
    PaqueteHuella p = {};

    p.id_sync = idSync;
    p.id_huella = idHuella;
    p.total_chunks = total;
    p.chunk_index = i;

    ci.toCharArray(p.ci, sizeof(p.ci));
    roomId.toCharArray(p.room_id, sizeof(p.room_id));
    tipo.toCharArray(p.tipo_persona, sizeof(p.tipo_persona));

    String parte = huella.substring(i * chunkSize, min((i + 1) * chunkSize, (int)huella.length()));
    parte.toCharArray(p.data, sizeof(p.data));

    // Resetear banderas de control antes de disparar el paquete
    txStatusReceived = false;
    txSuccess = false;

    esp_err_t result = esp_now_send(aulaMac, (uint8_t*)&p, sizeof(p));

    if (result != ESP_OK) {
      Serial.printf("Error Critico local al encolar chunk %d\n", i);
      return false;
    }

    // CONTROL INDUSTRIAL: Esperar el ACK fisico real que viene del Aula
    uint32_t startWait = millis();
    while (!txStatusReceived && (millis() - startWait < 1500)) {
      delay(1); // Pequeña espera sin bloquear el procesador por completo
    }

    if (!txSuccess) {
      Serial.printf("ACKS Perdido en el aire. El Aula no recibio el chunk %d. Abortando.\n", i);
      return false;
    }

    Serial.printf("Chunk [%d/%d] entregado con exito en el Aula.\n", i + 1, total);
    delay(30); // Respiro de seguridad para la pila de radio del receptor
  }

  Serial.println("¡Transmision ESP-NOW completada de manera exitosa!");
  return true;
}

void confirmarSync(int idSync, String estado, String mensaje) {
  HTTPClient http;
  String url = BASE + "src/api/Gateway/confirmar_sync.php";

  http.begin(url);
  http.addHeader("X-GATEWAY-KEY", API_KEY);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  String body = "id_sync=" + String(idSync) +
                "&estado=" + estado +
                "&mensaje=" + mensaje;

  int code = http.POST(body);
  String resp = http.getString();

  Serial.printf("\n--- NOTIFICACION BACKEND CENTRAL [HTTP %d] ---\n", code);
  http.end();
}