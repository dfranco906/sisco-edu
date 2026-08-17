#include <Arduino.h>

// Definición de pines para UART de LoRa RYLR998
#define LORA_RX 26
#define LORA_TX 27

const int MI_LORA_ID = 101;       // Dirección LoRa del Receptor (Aula)
const int EMISOR_LORA_ID = 100;   // Dirección LoRa del Gateway
const int LORA_NETWORK_ID = 18;   // ID de Red LoRa

HardwareSerial loraSerial(1);

void enviarComandoLoRa(int destino, const String &payload) {
  loraSerial.printf("AT+SEND=%d,%u,%s\r\n", destino, payload.length(), payload.c_str());
}

void atenderTramaLoRa(const String &linea) {
  // Formato completo RYLR998: +RCV=<ID_ORIGEN>,<LONGITUD>,<PAYLOAD>,<RSSI>,<SNR>
  int c1 = linea.indexOf(',');
  int c2 = linea.indexOf(',', c1 + 1);
  int c3 = linea.indexOf(',', c2 + 1);
  int c4 = linea.indexOf(',', c3 + 1);

  if (c1 < 0 || c2 < 0 || c3 < 0) return;

  int emisor = linea.substring(5, c1).toInt();
  String payload = linea.substring(c2 + 1, c3);
  String rssi = (c4 > 0) ? linea.substring(c3 + 1, c4) : "N/A";
  String snr = (c4 > 0) ? linea.substring(c4 + 1) : "N/A";

  if (emisor != EMISOR_LORA_ID) return;

  // Si es un paquete de prueba: TEST_PING_<ID>
  if (payload.startsWith("TEST_PING_")) {
    int paqueteID = payload.substring(10).toInt();

    Serial.printf("[RECIBIDO] Paquete #%02d | RSSI: %s dBm | SNR: %s dB\n", paqueteID, rssi.c_str(), snr.c_str());

    // Responder con ACK al emisor
    String ack = "ACK_TEST_" + String(paqueteID);
    enviarComandoLoRa(EMISOR_LORA_ID, ack);
  }
}

void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n==========================================");
  Serial.println("   PRUEBA DE ENLACE LORA - RECEPTOR");
  Serial.println("==========================================");

  loraSerial.begin(115200, SERIAL_8N1, LORA_RX, LORA_TX);
  loraSerial.setTimeout(120);

  // Configuración inicial del módulo RYLR998
  loraSerial.printf("AT+ADDRESS=%d\r\n", MI_LORA_ID);
  delay(100);
  while (loraSerial.available()) loraSerial.read();

  loraSerial.printf("AT+NETWORKID=%d\r\n", LORA_NETWORK_ID);
  delay(100);
  while (loraSerial.available()) loraSerial.read();

  Serial.println("[OK] Receptor escuchando ráfagas de prueba...\n");
}

void loop() {
  if (loraSerial.available()) {
    String linea = loraSerial.readStringUntil('\n');
    linea.trim();

    if (linea.startsWith("+RCV=")) {
      atenderTramaLoRa(linea);
    }
  }
}