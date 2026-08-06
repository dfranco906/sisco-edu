// ESP GATEWAY: RYLR998 + Wi-Fi + API Sisco-Edu
#include <Arduino.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h>

const char *ssid = "esp";
const char *password = "123456789";
const char *BASE = "http://192.168.0.165/sisco-edu/";
const char *API_KEY = "SISCO_GATEWAY_2026_SECRETO";
const int MI_LORA_ID = 100;
const int LORA_NETWORK_ID = 18;
const int CHARS_POR_CHUNK = 128;
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
  loraSerial.begin(115200, SERIAL_8N1, LORA_RX, LORA_TX); loraSerial.setTimeout(120);
  loraSerial.printf("AT+ADDRESS=%d\r\n", MI_LORA_ID); delay(100); while (loraSerial.available()) loraSerial.read();
  loraSerial.printf("AT+NETWORKID=%d\r\n", LORA_NETWORK_ID); delay(100); while (loraSerial.available()) loraSerial.read();

  WiFi.mode(WIFI_STA); WiFi.begin(ssid, password);
  const unsigned long limite = millis() + 20000;
  while (WiFi.status() != WL_CONNECTED && (int32_t)(millis() - limite) < 0) { delay(300); Serial.print('.'); }
  Serial.println(WiFi.status() == WL_CONNECTED ? "\nWi-Fi conectado" : "\nWi-Fi no disponible; se reintentara en loop");

  server.collectHeaders(headerKeys, 1);
  server.on("/sync", HTTP_POST, []() {
    if (server.header("X-GATEWAY-KEY") != API_KEY) { server.send(404, "application/json", "{\"status\":\"not_found\"}"); return; }
    server.send(202, "application/json", "{\"status\":\"accepted\"}");
    pedirSyncPendiente();
  });
  server.begin();
}

void loop() {
  server.handleClient(); atenderMensajesLoRa();
  static unsigned long ultimoReintentoWifi = 0;
  if (WiFi.status() != WL_CONNECTED && millis() - ultimoReintentoWifi > 10000) { ultimoReintentoWifi = millis(); WiFi.reconnect(); }
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
  if (huella.length() != 2816 || (huella.length() & 1)) { Serial.println("Template HEX invalido"); return false; }
  const int total = (huella.length() + CHARS_POR_CHUNK - 1) / CHARS_POR_CHUNK;
  for (int chunk = 0; chunk < total; ++chunk) {
    const String datos = huella.substring(chunk * CHARS_POR_CHUNK, min((chunk + 1) * CHARS_POR_CHUNK, (int)huella.length()));
    const String trama = chunk == 0
      ? "0:" + String(idSync) + ":" + String(idHuella) + ":" + String(idAula) + ":" + ci + ":" + tipo + ":" + String(total) + ":" + datos
      : String(chunk) + ":" + datos;
    if (trama.length() > 240) { Serial.println("Trama excede limite RYLR998"); return false; }
    bool confirmado = false;
    for (int intento = 0; intento < MAX_REINTENTOS && !confirmado; ++intento) {
      while (loraSerial.available()) loraSerial.read();
      enviarComandoLoRa(loraId, trama);
      confirmado = esperarOKLocal() && esperarAckRemoto(loraId, chunk);
      if (!confirmado) delay(100);
    }
    if (!confirmado) { Serial.printf("Sin ACK en fragmento %d\n", chunk); return false; }
  }
  return true;
}

void pedirSyncPendiente() {
  if (WiFi.status() != WL_CONNECTED) return;
  for (size_t i = 0; i < CANTIDAD_AULAS; ++i) pedirSyncAula(tablaAulas[i]);
}

bool pedirSyncAula(const DispositivoAula &aula) {
  HTTPClient http;
  const String url = String(BASE) + "src/api/Gateway/obtener_sync_aula.php?id_aula=" + String(aula.idAula);
  if (!http.begin(url)) return false;
  http.addHeader("X-GATEWAY-KEY", API_KEY);
  const int codigo = http.GET(); const String respuesta = http.getString(); http.end();
  if (codigo != 200) return false;
  DynamicJsonDocument doc(8192);
  if (deserializeJson(doc, respuesta) || String(doc["status"] | "") != "success") return false;
  const int idSync = doc["data"]["id_sync"] | 0, idHuella = doc["data"]["id_huella"] | 0, idAula = doc["data"]["id_aula"] | 0;
  const String ci = doc["data"]["ci"] | "", tipo = doc["data"]["tipo_persona"] | "", huella = doc["data"]["huella_base64"] | "";
  if (!idSync || !idHuella || idAula != aula.idAula || !ci.length() || !tipo.length()) { confirmarSync(idSync, "ERROR", "DATOS_SYNC_INVALIDOS"); return false; }
  if (!enviarHuellaPorLoRa(aula.loraId, idSync, idHuella, idAula, ci, tipo, huella)) { confirmarSync(idSync, "ERROR", "FALLO_ENLACE_LORA"); return false; }
  // El estado queda ENVIADO hasta que el aula confirme que storeModel() tuvo exito.
  return true;
}

void confirmarSync(int idSync, const String &estado, const String &mensaje) {
  if (!idSync || WiFi.status() != WL_CONNECTED) return;
  HTTPClient http; if (!http.begin(String(BASE) + "src/api/Gateway/confirmar_sync.php")) return;
  http.addHeader("X-GATEWAY-KEY", API_KEY); http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.POST("id_sync=" + String(idSync) + "&estado=" + urlEncode(estado) + "&mensaje=" + urlEncode(mensaje)); http.end();
}

void confirmarEntregaAula(const String &payload) {
  // SYNCOK:id_sync:id_huella:id_aula:slot:ci:tipo:detalle
  const int idSync = campo(payload, 1).toInt(), idHuella = campo(payload, 2).toInt(), idAula = campo(payload, 3).toInt(), slot = campo(payload, 4).toInt();
  const String ci = campo(payload, 5), tipo = campo(payload, 6);
  if (!idSync || !idHuella || !idAula || !slot || !ci.length() || !tipo.length()) return;
  if (WiFi.status() != WL_CONNECTED) return;
  HTTPClient http; if (!http.begin(String(BASE) + "src/api/Gateway/confirmar_entrega_aula.php")) return;
  http.addHeader("X-GATEWAY-KEY", API_KEY); http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  const String cuerpo = "id_sync=" + String(idSync) + "&id_huella=" + String(idHuella) + "&id_aula=" + String(idAula) + "&slot_local=" + String(slot) + "&ci=" + urlEncode(ci) + "&tipo_persona=" + urlEncode(tipo);
  http.POST(cuerpo); http.end();
}

void procesarAsistenciaEntrante(const String &payload) {
  // AST:id_aula:ci:tipo:estado
  const int idAula = campo(payload, 1).toInt(); const String ci = campo(payload, 2), tipo = campo(payload, 3), estado = campo(payload, 4);
  if (!idAula || !ci.length() || !tipo.length() || !estado.length() || WiFi.status() != WL_CONNECTED) return;
  HTTPClient http; if (!http.begin(String(BASE) + "src/api/Gateway/registrar_asistencia.php")) return;
  http.addHeader("X-GATEWAY-KEY", API_KEY); http.addHeader("Content-Type", "application/x-www-form-urlencoded");
  http.POST("id_aula=" + String(idAula) + "&ci=" + urlEncode(ci) + "&tipo_persona=" + urlEncode(tipo) + "&estado=" + urlEncode(estado)); http.end();
}

void atenderMensajesLoRa() {
  if (!loraSerial.available()) return;
  String linea = loraSerial.readStringUntil('\n'); linea.trim(); if (!linea.startsWith("+RCV=")) return;
  int c1 = linea.indexOf(','), c2 = linea.indexOf(',', c1 + 1), c3 = linea.indexOf(',', c2 + 1); if (c1 < 0 || c2 < 0 || c3 < 0) return;
  const String payload = linea.substring(c2 + 1, c3);
  if (payload.startsWith("AST:")) procesarAsistenciaEntrante(payload);
  else if (payload.startsWith("SYNCOK:")) confirmarEntregaAula(payload);
  else if (payload.startsWith("SYNCERR:")) confirmarSync(campo(payload, 1).toInt(), "ERROR", campo(payload, 7));
}