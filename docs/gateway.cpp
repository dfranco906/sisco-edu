// CODIGO PARA ESP-GATEWAY
#include <WiFi.h>
#include <HTTPClient.h>
#include <esp_now.h>
#include <ArduinoJson.h>
#include <WebServer.h>

const char* ssid = "esp";
const char* password = "123456789";

String BASE = "http://192.168.100.109/tiago3roBTI2026/sisco-edu/&quot;;
String API_KEY = "SISCO_GATEWAY_2026_SECRETO";
String ROOM_ID = "AULA_A"; // Filtro inicial para buscar sync pendientes

const char* headerKeys[] = {"X-GATEWAY-KEY"};
const size_t headerKeysCount = 1;

WebServer server(80);

// ============================================================================
// TOPOLOGÍA MULTIPUNTO: Estructura y Tabla de Enrutamiento de Aulas
// ============================================================================
typedef struct {
  char room_id[16];
  uint8_t mac[6];
} DispositivoAula;

// Lista de aulas autorizadas. Podés añadir más líneas aquí abajo según crezca la institución.
const int MAX_AULAS = 5;
DispositivoAula tablaAulas[MAX_AULAS] = {
  {"AULA_A", {0xB0, 0xCB, 0xD8, 0x8E, 0x72, 0x74}},  // MAC de AULA_A para la prueba
  {"AULA_B",  {0x00, 0x00, 0x00, 0x00, 0x00, 0x00}}, // Siguiente mac para aula B
  {"AULA_C",  {0x00, 0x00, 0x00, 0x00, 0x00, 0x00}}  // mac para aula C
};

// ============================================================================
// FORMATOS DE PAQUETES (ESP-NOW)
// ============================================================================
// 1. Estructura de salida: Envío de Huellas (Modificada a 161 bytes de holgura)
typedef struct {
  int id_sync;
  int id_huella;
  int total_chunks;
  int chunk_index;
  char ci[15];
  char room_id[16];
  char tipo_persona[15];
  char data[161];
} PaqueteHuella;

// 2. Estructura de entrada: Recepción de Asistencia (Peso: 81 bytes, seguro)
typedef struct {
  char room_id[16];
  char ci[15];
  char tipo_persona[15];
  char estado[15];
  char fecha_hora[20]; // Formato esperado: YYYY-MM-DD HH:MM:SS
} PaqueteAsistencia;

// Variables de control de flujo síncrono para el envío de huellas
volatile bool txStatusReceived = false;
volatile bool txSuccess = false;

// Variables globales para la cola segura de asistencias (ISR a Loop)
volatile bool hayAsistenciaPendiente = false;
PaqueteAsistencia asistenciaRecibida;

// Prototipos de funciones
void pedirSyncPendiente();
bool enviarHuellaPorEspNow(uint8_t* macDestino, int idSync, int idHuella, String ci, String roomId, String tipo, String huella);
void confirmarSync(int idSync, String estado, String mensaje);
bool buscarMacPorAula(String roomId, uint8_t* macDestino);
void procesarEnvioAsistenciaBackend(PaqueteAsistencia datos);

// ============================================================================
// CALLBACKS DE ESP-NOW (Core 3.3.8)
// ============================================================================
void onSend(const wifi_tx_info_t *tx_info, esp_now_send_status_t status) {
  txStatusReceived = true;
  txSuccess = (status == ESP_NOW_SEND_SUCCESS);
}

void onReceive(const esp_now_recv_info_t *recv_info, const uint8_t *incomingData, int len) {
  // Validamos si el tamaño coincide con un paquete de asistencia
  if (len == sizeof(PaqueteAsistencia)) {
    memcpy((uint8_t*)&asistenciaRecibida, incomingData, sizeof(PaqueteAsistencia));
    hayAsistenciaPendiente = true; // Despierta la acción en el loop() principal de forma segura
  }
}

// ============================================================================
// SETUP
// ============================================================================
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
  esp_now_register_recv_cb(onReceive);

  // Registro dinámico inicial de todos los peers declarados en la tabla
  for (int i = 0; i < MAX_AULAS; i++) {
    // Evitamos registrar direcciones vacías
    if (tablaAulas[i].mac[0] == 0x00 && tablaAulas[i].mac[5] == 0x00) continue;

    esp_now_peer_info_t peerInfo = {};
    memset(&peerInfo, 0, sizeof(peerInfo));
    memcpy(peerInfo.peer_addr, tablaAulas[i].mac, 6);
    peerInfo.channel = 0;
    peerInfo.encrypt = false;
    peerInfo.ifidx = WIFI_IF_STA;

    if (!esp_now_is_peer_exist(tablaAulas[i].mac)) {
      if (esp_now_add_peer(&peerInfo) != ESP_OK) {
        Serial.printf("Error enlazando Aula: %s\n", tablaAulas[i].room_id);
      } else {
        Serial.printf("Peer registrado con éxito: %s\n", tablaAulas[i].room_id);
      }
    }
  }

  Serial.println("ESP-NOW operativo en modo emisor/receptor.");

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

// ============================================================================
// LOOP PRINCIPAL
// ============================================================================
void loop() {
  server.handleClient();

  // Control Asíncrono de Seguridad: Despacha la asistencia fuera de la interrupción de radio
  if (hayAsistenciaPendiente) {
    procesarEnvioAsistenciaBackend(asistenciaRecibida);
    hayAsistenciaPendiente = false; // Reset de bandera
  }
}

// ============================================================================
// FUNCIONES DE CONTROL LOGÍCO
// ============================================================================

bool buscarMacPorAula(String roomId, uint8_t* macDestino) {
  for (int i = 0; i < MAX_AULAS; i++) {
    if (String(tablaAulas[i].room_id) == roomId) {
      memcpy(macDestino, tablaAulas[i].mac, 6);
      return true;
    }
  }
  return false;
}

void pedirSyncPendiente() {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  String url = BASE + "src/api/Gateway/obtener_sync_aula.php?room_id=" + ROOM_ID;

  http.begin(url);
  http.addHeader("X-GATEWAY-KEY", API_KEY);

  int code = http.GET();
  String payload = http.getString();

  if (code != 200 || payload.indexOf("\"status\":\"success\"") < 0) {
    Serial.println("No hay sincronizaciones pendientes en el Backend.");
    http.end();
    return;
  }

  DynamicJsonDocument doc(60000);
  deserializeJson(doc, payload);

  int idSync = doc["data"]["id_sync"];
  int idHuella = doc["data"]["id_huella"];
  String ci = doc["data"]["ci"] | "";
  String roomId = doc["data"]["room_id"] | "";
  String tipo = doc["data"]["tipo_persona"] | "";
  String huella = doc["data"]["huella_base64"] | "";

  Serial.printf("\n--- NUEVA ORDEN RECIBIDA ---\nDestino: %s | Alumno CI: %s\n", roomId.c_str(), ci.c_str());

  // BÚSQUEDA DINÁMICA DE MAC POR EL ROOM_ID RETORNADO POR EL BACKEND
  uint8_t macObjetivo[6];
  if (!buscarMacPorAula(roomId, macObjetivo)) {
    Serial.printf("Error Critico: No existe registro de direccion MAC para el aula '%s'\n", roomId.c_str());
    confirmarSync(idSync, "ERROR", "El Gateway no tiene mapeada la direccion MAC de esa aula");
    http.end();
    return;
  }

  // Ejecución del disparo pasándole la MAC encontrada en la tabla
  bool enviado = enviarHuellaPorEspNow(macObjetivo, idSync, idHuella, ci, roomId, tipo, huella);

  if (enviado) {
    confirmarSync(idSync, "CONFIRMADO", "Huella enviada e instalada en el aula de forma integra");
  } else {
    confirmarSync(idSync, "ERROR", "Fallo el enlace de radio de ESP-NOW entre pisos");
  }

  http.end();
}

bool enviarHuellaPorEspNow(uint8_t* macDestino, int idSync, int idHuella, String ci, String roomId, String tipo, String huella) {
  const int chunkSize = 160;
  int total = (huella.length() + chunkSize - 1) / chunkSize;

  Serial.printf("Disparando ráfaga hacia la MAC: %02X:%02X:%02X:%02X:%02X:%02X\n",
                macDestino[0], macDestino[1], macDestino[2], macDestino[3], macDestino[4], macDestino[5]);

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

    txStatusReceived = false;
    txSuccess = false;

    esp_err_t result = esp_now_send(macDestino, (uint8_t*)&p, sizeof(p));
    if (result != ESP_OK) return false;

    uint32_t startWait = millis();
    while (!txStatusReceived && (millis() - startWait < 1500)) { delay(1); }

    if (!txSuccess) return false;
    delay(25);
  }
  return true;
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

// ============================================================================
// CANALIZACIÓN DE ASISTENCIAS PROVENIENTES DEL AULA HACIA EL BACKEND
// ============================================================================
void procesarEnvioAsistenciaBackend(PaqueteAsistencia datos) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Imposible procesar asistencia: Conexión Wi-Fi perdida temporalmente.");
    return;
  }

  HTTPClient http;
  // Endpoint configurado siguiendo el estándar del proyecto
  String url = BASE + "src/api/Gateway/registrar_asistencia.php";

  http.begin(url);
  http.addHeader("X-GATEWAY-KEY", API_KEY);
  http.addHeader("Content-Type", "application/x-www-form-urlencoded");

  // Armamos el cuerpo del POST urlencoded mapeando los datos de la huella ingresada
  String body = "room_id=" + String(datos.room_id) +
                "&ci=" + String(datos.ci) +
                "&tipo_persona=" + String(datos.tipo_persona) +
                "&estado=" + String(datos.estado) +
                "&fecha_hora=" + String(datos.fecha_hora);

  Serial.println("\n--- REENVIANDO REGISTRO DE ASISTENCIA AL BACKEND ---");
  Serial.printf("Aula: %s | C.I: %s | Evento: %s\n", datos.room_id, datos.ci, datos.estado);

  int code = http.POST(body);
  String respuesta = http.getString();

  Serial.printf("Respuesta Servidor Central [HTTP %d]: %s\n\n", code, respuesta.c_str());
  http.end();
}