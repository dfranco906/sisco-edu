// ESP GATEWAY: RYLR998 + Wi-Fi + API Sisco-Edu
#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h>

const char *ssid = "esp";
const char *password = "123456789";
const char *BASE = "http://192.168.100.109/tiago3roBTI2026/sisco-edu/";
const char *API_KEY = "SISCO_GATEWAY_2026_SECRETO";
const int MI_LORA_ID = 100;
const int LORA_NETWORK_ID = 18;
constexpr size_t HUELLA_TEMPLATE_BYTES = 1536;
constexpr size_t HUELLA_TEMPLATE_HEX_CHARS = HUELLA_TEMPLATE_BYTES * 2;
constexpr int CHARS_POR_CHUNK = 128;
constexpr int TOTAL_CHUNKS_HUELLA = HUELLA_TEMPLATE_HEX_CHARS / CHARS_POR_CHUNK;
static_assert(HUELLA_TEMPLATE_HEX_CHARS % CHARS_POR_CHUNK == 0,
              "El template debe dividirse en fragmentos LoRa completos");
const int MAX_REINTENTOS = 8;
#define LORA_RX 16
#define LORA_TX 17
HardwareSerial loraSerial(2);
WebServer server(80);
const char *headerKeys[] = {"X-GATEWAY-KEY"};

struct DispositivoAula { int idAula; int loraId; };
// Mantener esta tabla alineada con aulas.id_aula y las direcciones RYLR998.
DispositivoAula tablaAulas[] = {{18, 101}, {15, 102}, {16, 103}};
const size_t CANTIDAD_AULAS = sizeof(tablaAulas) / sizeof(tablaAulas[0]);

void atenderMensajesLoRa();
void pedirSyncPendiente();
bool pedirSyncAula(const DispositivoAula &aula);
bool enviarHuellaPorLoRa(int loraId, int idSync, int idHuella, int idAula, const String &ci, const String &tipo, const String &huella);
bool esperarOKLocal(unsigned long timeoutMs = 500);
bool esperarAckRemoto(int loraId, int chunk, unsigned long timeoutMs = 900);
void enviarComandoLoRa(int destino, const String &payload);
void confirmarSync(int idSync, const String &estado, const String &mensaje);
void confirmarEntregaAula(const String &payload);
void procesarAsistenciaEntrante(const String &payload);
String campo(const String &texto, int indice);
String urlEncode(const String &texto);

void setup() {
  Serial.begin(115200);
  delay(500);
  Serial.println("\n========================================");
  Serial.println("  SISCO-EDU - GATEWAY LoRa / Wi-Fi");
  Serial.println("========================================");
  Serial.printf("[BIOMETRIA] Contrato compilado: %u bytes / %u HEX / %d fragmentos\n",
                (unsigned int)HUELLA_TEMPLATE_BYTES,
                (unsigned int)HUELLA_TEMPLATE_HEX_CHARS,
                TOTAL_CHUNKS_HUELLA);
  Serial.printf("[INICIO] Configurando LoRa: RX=%d, TX=%d, direccion=%d, red=%d\n",
                LORA_RX, LORA_TX, MI_LORA_ID, LORA_NETWORK_ID);
  loraSerial.begin(115200, SERIAL_8N1, LORA_RX, LORA_TX);
  loraSerial.setTimeout(120);
  loraSerial.printf("AT+ADDRESS=%d\r\n", MI_LORA_ID);
  delay(100);
  while (loraSerial.available()) loraSerial.read();
  loraSerial.printf("AT+NETWORKID=%d\r\n", LORA_NETWORK_ID);
  delay(100);
  while (loraSerial.available()) loraSerial.read();
  Serial.println("[LORA] Modulo RYLR998 configurado");

  WiFi.mode(WIFI_STA);
  Serial.printf("[WIFI] Direccion MAC: %s\n", WiFi.macAddress().c_str());
  Serial.printf("[WIFI] Conectando a la red \"%s\"", ssid);
  WiFi.begin(ssid, password);
  const unsigned long limite = millis() + 20000;
  while (WiFi.status() != WL_CONNECTED && (int32_t)(millis() - limite) < 0) { delay(300); Serial.print('.'); }
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println(" conectado");
    Serial.printf("[WIFI] Direccion IP: %s\n", WiFi.localIP().toString().c_str());
    Serial.printf("[WIFI] Potencia de senal: %d dBm\n", WiFi.RSSI());
  } else {
    Serial.println(" sin conexion");
    Serial.println("[WIFI] Se intentara reconectar cada 10 segundos");
  }

  server.collectHeaders(headerKeys, 1);
  server.on("/sync", HTTP_POST, []() {
    Serial.printf("[HTTP] Solicitud de sincronizacion recibida desde %s\n",
                  server.client().remoteIP().toString().c_str());
    if (server.header("X-GATEWAY-KEY") != API_KEY) {
      Serial.println("[HTTP] Solicitud rechazada: clave de gateway invalida");
      server.send(404, "application/json", "{\"status\":\"not_found\"}");
      return;
    }
    server.send(202, "application/json", "{\"status\":\"accepted\"}");
    Serial.println("[SYNC] Buscando huellas pendientes para las aulas configuradas");
    pedirSyncPendiente();
  });
  server.begin();
  Serial.println("[HTTP] Servidor del gateway iniciado en el puerto 80");
  Serial.printf("[HTTP] API central configurada: %s\n", BASE);
  if (WiFi.status() == WL_CONNECTED) {
    Serial.printf("[HTTP] Endpoint de sincronizacion: http://%s/sync\n",
                  WiFi.localIP().toString().c_str());
  }

  Serial.println("[LISTO] Acciones habilitadas:");
  Serial.println("  - Recibir avisos HTTP POST /sync desde Sisco-Edu");
  Serial.println("  - Consultar huellas pendientes de cada aula");
  Serial.println("  - Enviar huellas fragmentadas por LoRa y esperar sus ACK");
  Serial.println("  - Recibir confirmaciones de almacenamiento desde las aulas");
  Serial.println("  - Recibir asistencias por LoRa y registrarlas en la API");
  Serial.println("  - Recuperar automaticamente la conexion Wi-Fi");
  Serial.println("========================================\n");
}

void loop() {
  server.handleClient();
  atenderMensajesLoRa();
  static unsigned long ultimoReintentoWifi = 0;
  static wl_status_t ultimoEstadoWifi = WiFi.status();
  const wl_status_t estadoWifi = WiFi.status();
  if (estadoWifi != ultimoEstadoWifi) {
    if (estadoWifi == WL_CONNECTED) {
      Serial.printf("[WIFI] Conexion recuperada. IP: %s\n", WiFi.localIP().toString().c_str());
      Serial.printf("[HTTP] Endpoint de sincronizacion: http://%s/sync\n",
                    WiFi.localIP().toString().c_str());
    } else if (ultimoEstadoWifi == WL_CONNECTED) {
      Serial.println("[WIFI] Conexion perdida");
    }
    ultimoEstadoWifi = estadoWifi;
  }
  if (estadoWifi != WL_CONNECTED && millis() - ultimoReintentoWifi > 10000) {
    ultimoReintentoWifi = millis();
    Serial.println("[WIFI] Intentando reconectar...");
    WiFi.reconnect();
  }
}

void enviarComandoLoRa(int destino, const String &payload) { loraSerial.printf("AT+SEND=%d,%u,%s\r\n", destino, payload.length(), payload.c_str()); }

String campo(const String &texto, int indice) {
  int inicio = 0;
  for (int i = 0; i < indice; ++i) { inicio = texto.indexOf(':', inicio); if (inicio < 0) return ""; ++inicio; }
  int fin = texto.indexOf(':', inicio); return fin < 0 ? texto.substring(inicio) : texto.substring(inicio, fin);
}
String urlEncode(const String &texto) {
  const char *hex = "0123456789ABCDEF"; String salida;
  for (size_t i = 0; i < texto.length(); ++i) { unsigned char c = texto[i]; if (isalnum(c) || c == '-' || c == '_' || c == '.') salida += (char)c; else { salida += '%'; salida += hex[c >> 4]; salida += hex[c & 15]; } }
  return salida;
}

bool esperarOKLocal(unsigned long timeoutMs) {
  const unsigned long inicio = millis();
  while (millis() - inicio < timeoutMs) {
    if (!loraSerial.available()) { delay(1); continue; }
    String respuesta = loraSerial.readStringUntil('\n'); respuesta.trim();
    if (respuesta == "+OK") return true;
    if (respuesta.startsWith("+ERR")) return false;
  }
  return false;
}

bool esperarAckRemoto(int loraId, int chunk, unsigned long timeoutMs) {
  const String esperado = "ACK" + String(chunk); const unsigned long inicio = millis();
  while (millis() - inicio < timeoutMs) {
    if (!loraSerial.available()) { delay(1); continue; }
    String linea = loraSerial.readStringUntil('\n'); linea.trim();
    if (!linea.startsWith("+RCV=")) continue;
    int c1 = linea.indexOf(','), c2 = linea.indexOf(',', c1 + 1), c3 = linea.indexOf(',', c2 + 1);
    if (c1 < 0 || c2 < 0 || c3 < 0) continue;
    if (linea.substring(5, c1).toInt() == loraId && linea.substring(c2 + 1, c3) == esperado) return true;
  }
  return false;
}

bool enviarHuellaPorLoRa(int loraId, int idSync, int idHuella, int idAula, const String &ci, const String &tipo, const String &huella) {
  if (huella.length() != HUELLA_TEMPLATE_HEX_CHARS || (huella.length() & 1)) { Serial.println("[LORA] Template HEX invalido"); return false; }
  const int total = (huella.length() + CHARS_POR_CHUNK - 1) / CHARS_POR_CHUNK;
  if (total != TOTAL_CHUNKS_HUELLA) { Serial.println("[LORA] Cantidad de fragmentos invalida"); return false; }
  Serial.printf("[LORA] Enviando huella %d al aula %d (destino %d, %d fragmentos)\n",
                idHuella, idAula, loraId, total);
  for (int chunk = 0; chunk < total; ++chunk) {
    const String datos = huella.substring(chunk * CHARS_POR_CHUNK, min((chunk + 1) * CHARS_POR_CHUNK, (int)huella.length()));
    const String trama = chunk == 0
      ? "0:" + String(idSync) + ":" + String(idHuella) + ":" + String(idAula) + ":" + ci + ":" + tipo + ":" + String(total) + ":" + datos
      : String(chunk) + ":" + datos;
    if (trama.length() > 240) { Serial.println("[LORA] Trama excede limite RYLR998"); return false; }
    bool confirmado = false;
    for (int intento = 0; intento < MAX_REINTENTOS && !confirmado; ++intento) {
      while (loraSerial.available()) loraSerial.read();
      enviarComandoLoRa(loraId, trama);
      confirmado = esperarOKLocal() && esperarAckRemoto(loraId, chunk);
      if (!confirmado) {
        Serial.printf("[LORA] Fragmento %d/%d sin ACK; reintento %d/%d\n",
                      chunk + 1, total, intento + 1, MAX_REINTENTOS);
        delay(100);
      }
    }
    if (!confirmado) { Serial.printf("[LORA] Envio cancelado: sin ACK en fragmento %d\n", chunk); return false; }
  }
  Serial.printf("[LORA] Huella %d enviada; esperando confirmacion de almacenamiento del aula\n", idHuella);
  return true;
}

void pedirSyncPendiente() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("[SYNC] Consulta omitida: Wi-Fi no conectado");
    return;
  }
  for (size_t i = 0; i < CANTIDAD_AULAS; ++i) pedirSyncAula(tablaAulas[i]);
  Serial.println("[SYNC] Consulta de aulas finalizada");
}

bool pedirSyncAula(const DispositivoAula &aula) {
  Serial.printf("[SYNC] Consultando aula %d (direccion LoRa %d)\n", aula.idAula, aula.loraId);
  HTTPClient http;
  const String url = String(BASE) + "src/api/Gateway/obtener_sync_aula.php?id_aula=" + String(aula.idAula);
  if (!http.begin(url)) {
    Serial.println("[SYNC] No se pudo iniciar la solicitud HTTP");
    return false;
  }
  http.addHeader("X-GATEWAY-KEY", API_KEY);
  const int codigo = http.GET(); const String respuesta = http.getString(); http.end();
  if (codigo != 200) {
    Serial.printf("[SYNC] Aula %d: API respondio HTTP %d\n", aula.idAula, codigo);
    return false;
  }
  DynamicJsonDocument doc(8192);
  if (deserializeJson(doc, respuesta) || String(doc["status"] | "") != "success") {
    Serial.printf("[SYNC] Aula %d: no hay una sincronizacion valida para procesar\n", aula.idAula);
    return false;
  }
  const int idSync = doc["data"]["id_sync"] | 0, idHuella = doc["data"]["id_huella"] | 0, idAula = doc["data"]["id_aula"] | 0;
  const int templateBytes = doc["data"]["bytes"] | 0;
  const String ci = doc["data"]["ci"] | "", tipo = doc["data"]["tipo_persona"] | "", huella = doc["data"]["huella_base64"] | "";
  if (!idSync || !idHuella || idAula != aula.idAula || templateBytes != HUELLA_TEMPLATE_BYTES || !ci.length() || !tipo.length() || huella.length() != HUELLA_TEMPLATE_HEX_CHARS) {
    Serial.printf("[SYNC] Aula %d: datos invalidos (sync=%d, huella=%d, id_aula=%d, bytes=%d/%u, ci=%s, tipo=%s, hex=%u/%u)\n",
                  aula.idAula, idSync, idHuella, idAula, templateBytes,
                  (unsigned int)HUELLA_TEMPLATE_BYTES,
                  ci.length() ? "OK" : "FALTA", tipo.length() ? "OK" : "FALTA",
                  (unsigned int)huella.length(),
                  (unsigned int)HUELLA_TEMPLATE_HEX_CHARS);
    confirmarSync(idSync, "ERROR", "DATOS_SYNC_INVALIDOS");
    return false;
  }
  if (!enviarHuellaPorLoRa(aula.loraId, idSync, idHuella, idAula, ci, tipo, huella)) {
    Serial.printf("[SYNC] Aula %d: fallo el enlace LoRa\n", aula.idAula);
    confirmarSync(idSync, "ERROR", "FALLO_ENLACE_LORA");
    return false;
  }
  // El estado queda ENVIADO hasta que el aula confirme que storeModel() tuvo exito.
  return true;
}

void confirmarSync(int idSync, const String &estado, const String &mensaje) {
  if (!idSync || WiFi.status() != WL_CONNECTED) {
    Serial.printf("[SYNC] No se pudo confirmar sync %d: datos invalidos o Wi-Fi desconectado\n", idSync);
    return;
  }
  HTTPClient http; if (!http.begin(String(BASE) + "src/api/Gateway/confirmar_sync.php")) return;
  http.addHeader("X-GATEWAY-KEY", API_KEY); http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  const int codigo = http.POST("id_sync=" + String(idSync) + "&estado=" + urlEncode(estado) + "&mensaje=" + urlEncode(mensaje)); http.end();
  Serial.printf("[SYNC] Estado de sync %d enviado a la API: %s (HTTP %d)\n",
                idSync, estado.c_str(), codigo);
}

void confirmarEntregaAula(const String &payload) {
  // SYNCOK:id_sync:id_huella:id_aula:slot:ci:tipo:detalle
  const int idSync = campo(payload, 1).toInt(), idHuella = campo(payload, 2).toInt(), idAula = campo(payload, 3).toInt(), slot = campo(payload, 4).toInt();
  const String ci = campo(payload, 5), tipo = campo(payload, 6);
  if (!idSync || !idHuella || !idAula || !slot || !ci.length() || !tipo.length()) {
    Serial.println("[SYNC] Confirmacion LoRa de aula invalida");
    return;
  }
  if (WiFi.status() != WL_CONNECTED) { Serial.println("[SYNC] Confirmacion no enviada: Wi-Fi desconectado"); return; }
  Serial.printf("[SYNC] Aula %d confirmo huella %d en slot %d\n", idAula, idHuella, slot);
  HTTPClient http; if (!http.begin(String(BASE) + "src/api/Gateway/confirmar_entrega_aula.php")) return;
  http.addHeader("X-GATEWAY-KEY", API_KEY); http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  const String cuerpo = "id_sync=" + String(idSync) + "&id_huella=" + String(idHuella) + "&id_aula=" + String(idAula) + "&slot_local=" + String(slot) + "&ci=" + urlEncode(ci) + "&tipo_persona=" + urlEncode(tipo);
  const int codigo = http.POST(cuerpo); http.end();
  Serial.printf("[SYNC] Confirmacion de entrega enviada a la API (HTTP %d)\n", codigo);
}

void procesarAsistenciaEntrante(const String &payload) {
  // AST:id_aula:ci:tipo:estado
  const int idAula = campo(payload, 1).toInt(); const String ci = campo(payload, 2), tipo = campo(payload, 3), estado = campo(payload, 4);
  if (!idAula || !ci.length() || !tipo.length() || !estado.length()) {
    Serial.println("[ASISTENCIA] Trama LoRa invalida");
    return;
  }
  if (WiFi.status() != WL_CONNECTED) { Serial.println("[ASISTENCIA] Registro omitido: Wi-Fi desconectado"); return; }
  Serial.printf("[ASISTENCIA] Recibida desde aula %d: tipo=%s, estado=%s\n",
                idAula, tipo.c_str(), estado.c_str());
  HTTPClient http; if (!http.begin(String(BASE) + "src/api/Gateway/registrar_asistencia.php")) return;
  http.addHeader("X-GATEWAY-KEY", API_KEY); http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  const int codigo = http.POST("id_aula=" + String(idAula) + "&ci=" + urlEncode(ci) + "&tipo_persona=" + urlEncode(tipo) + "&estado=" + urlEncode(estado)); http.end();
  Serial.printf("[ASISTENCIA] Registro enviado a la API (HTTP %d)\n", codigo);
}

void atenderMensajesLoRa() {
  if (!loraSerial.available()) return;
  String linea = loraSerial.readStringUntil('\n'); linea.trim(); if (!linea.startsWith("+RCV=")) return;
  int c1 = linea.indexOf(','), c2 = linea.indexOf(',', c1 + 1), c3 = linea.indexOf(',', c2 + 1); if (c1 < 0 || c2 < 0 || c3 < 0) return;
  const String payload = linea.substring(c2 + 1, c3);
  const int finTipo = payload.indexOf(':');
  const String tipoMensaje = finTipo < 0 ? payload : payload.substring(0, finTipo);
  Serial.printf("[LORA] Mensaje %s recibido del dispositivo %d\n",
                tipoMensaje.c_str(), linea.substring(5, c1).toInt());
  if (payload.startsWith("AST:")) procesarAsistenciaEntrante(payload);
  else if (payload.startsWith("SYNCOK:")) confirmarEntregaAula(payload);
  else if (payload.startsWith("SYNCERR:")) confirmarSync(campo(payload, 1).toInt(), "ERROR", campo(payload, 7));
  else Serial.println("[LORA] Tipo de mensaje no reconocido");
}
