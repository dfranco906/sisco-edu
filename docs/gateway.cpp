#include <WiFi.h>
#include <HTTPClient.h>
#include <esp_now.h>
#include <ArduinoJson.h>
#include <WebServer.h>

const char* ssid = "TU_WIFI";
const char* password = "TU_PASS";

String BASE = "http://IP_PC/tiago3roBTI2026/sisco-edu/";
String API_KEY = "SISCO_GATEWAY_2026_SECRETO";
String ROOM_ID = "AULA_1";

// MAC del ESP Aula
uint8_t aulaMac[] = {0x24, 0x6F, 0x28, 0xAA, 0xBB, 0xCC};

typedef struct {
  int id_sync;
  int id_huella;
  int total_chunks;
  int chunk_index;
  char ci[20];
  char room_id[30];
  char tipo_persona[20];
  char data[180];
} PaqueteHuella;

bool aulaConfirmo = false;
WebServer server(80);

void setup() {
  Serial.begin(115200);

  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println(WiFi.localIP());

  server.on("/sync", HTTP_POST, []() {
    String key = server.header("X-GATEWAY-KEY");

    if (key != API_KEY) {
      server.send(404, "application/json", "{\"status\":\"not_found\"}");
      return;
    }

    server.send(200, "application/json", "{\"status\":\"ok\",\"message\":\"Gateway despertado\"}");

    pedirSyncPendiente(); // acá recién extrae la huella
  });

  server.begin();
}

void loop() {
  server.handleClient();
}

void pedirSyncPendiente() {
  HTTPClient http;

  String url = BASE + "src/api/Gateway/obtener_sync_aula.php?room_id=" + ROOM_ID;

  http.begin(url);
  http.addHeader("X-GATEWAY-KEY", API_KEY);

  int code = http.GET();
  String payload = http.getString();

  Serial.println(payload);

  if (code != 200 || payload.indexOf("\"status\":\"success\"") < 0) {
    http.end();
    return;
  }

  DynamicJsonDocument doc(60000);
  DeserializationError error = deserializeJson(doc, payload);

  if (error) {
    Serial.println("Error JSON");
    http.end();
    return;
  }

  int idSync = doc["data"]["id_sync"];
  int idHuella = doc["data"]["id_huella"];
  String ci = doc["data"]["ci"] | "";
  String roomId = doc["data"]["room_id"] | "";
  String tipo = doc["data"]["tipo_persona"] | "";
  String huella = doc["data"]["huella_base64"] | "";

  bool ok = enviarHuellaPorEspNow(idSync, idHuella, ci, roomId, tipo, huella);

  if (ok) {
    confirmarSync(idSync, "CONFIRMADO", "Huella enviada al aula por ESP-NOW");
  } else {
    confirmarSync(idSync, "ERROR", "Error enviando huella al aula");
  }

  http.end();
}

bool enviarHuellaPorEspNow(int idSync, int idHuella, String ci, String roomId, String tipo, String huella) {
  int chunkSize = 180;
  int total = (huella.length() + chunkSize - 1) / chunkSize;

  for (int i = 0; i < total; i++) {
    PaqueteHuella p = {};

    p.id_sync = idSync;
    p.id_huella = idHuella;
    p.total_chunks = total;
    p.chunk_index = i;

    ci.toCharArray(p.ci, sizeof(p.ci));
    roomId.toCharArray(p.room_id, sizeof(p.room_id));
    tipo.toCharArray(p.tipo_persona, sizeof(p.tipo_persona));

    String parte = huella.substring(i * chunkSize, (i + 1) * chunkSize);
    parte.toCharArray(p.data, sizeof(p.data));

    esp_err_t result = esp_now_send(aulaMac, (uint8_t*)&p, sizeof(p));

    if (result != ESP_OK) {
      Serial.println("Error enviando chunk");
      return false;
    }

    delay(80);
  }

  Serial.println("Huella enviada completa al aula");
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

  Serial.println(resp);
  http.end();
}

void onSend(const uint8_t *mac_addr, esp_now_send_status_t status) {
  Serial.println(status == ESP_NOW_SEND_SUCCESS ? "Chunk enviado" : "Fallo chunk");
}