#include <Arduino.h>

// Definición de pines para UART de LoRa RYLR998
#define LORA_RX 16
#define LORA_TX 17

const int MI_LORA_ID = 100;       // Dirección LoRa del Emisor
const int DESTINO_LORA_ID = 101;  // Dirección LoRa del Receptor (Aula)
const int LORA_NETWORK_ID = 18;   // ID de Red LoRa

const int TOTAL_PAQUETES = 10;     // Cantidad de paquetes en la ráfaga
const uint32_t TIMEOUT_ACK = 1500; // Tiempo máximo de espera por paquete (ms)

HardwareSerial loraSerial(2);

void enviarComandoLoRa(int destino, const String &payload) {
  loraSerial.printf("AT+SEND=%d,%u,%s\r\n", destino, payload.length(), payload.c_str());
}

bool esperarAckRemoto(int paqueteID) {
  const String esperado = "ACK_TEST_" + String(paqueteID);
  const uint32_t inicio = millis();

  while (millis() - inicio < TIMEOUT_ACK) {
    if (loraSerial.available()) {
      String linea = loraSerial.readStringUntil('\n');
      linea.trim();
      
      // Formato esperado RYLR998: +RCV=<ID_ORIGEN>,<LONGITUD>,<PAYLOAD>,<RSSI>,<SNR>
      if (linea.startsWith("+RCV=")) {
        int c1 = linea.indexOf(',');
        int c2 = linea.indexOf(',', c1 + 1);
        int c3 = linea.indexOf(',', c2 + 1);
        
        if (c1 > 0 && c2 > 0 && c3 > 0) {
          int emisor = linea.substring(5, c1).toInt();
          String payload = linea.substring(c2 + 1, c3);

          if (emisor == DESTINO_LORA_ID && payload == esperado) {
            return true;
          }
        }
      }
    }
    delay(1);
  }
  return false;
}

void setup() {
  Serial.begin(115200);
  delay(1000);

  Serial.println("\n==========================================");
  Serial.println("   PRUEBA DE ENLACE LORA - TRANSMISOR");
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

  Serial.println("[OK] Módulo LoRa emisor iniciado.");
  Serial.println("Presiona 'ENTER' en el Monitor Serial para iniciar una ráfaga...");
}

void loop() {
  if (Serial.available()) {
    while (Serial.available()) Serial.read(); // Limpiar buffer de entrada

    Serial.println("\n--- INICIANDO RÁFAGA DE PRUEBA ---");
    int confirmados = 0;

    for (int i = 1; i <= TOTAL_PAQUETES; i++) {
      String mensaje = "TEST_PING_" + String(i);
      
      // Limpiar buffer UART previo
      while (loraSerial.available()) loraSerial.read();

      uint32_t tInicio = millis();
      enviarComandoLoRa(DESTINO_LORA_ID, mensaje);
      
      Serial.printf("[PAQUETE %02d/%02d] Enviado -> ", i, TOTAL_PAQUETES);

      if (esperarAckRemoto(i)) {
        uint32_t rtt = millis() - tInicio;
        Serial.printf("ACK Recibido (%ms)\n", rtt);
        confirmados++;
      } else {
        Serial.println("FALLO / SIN ACK (Timeout)");
      }

      delay(200); // Pausa breve entre paquetes
    }

    Serial.println("------------------------------------------");
    Serial.printf("RESULTADOS: %d/%d paquetes confirmados (%.1f%% de éxito)\n",
                  confirmados, TOTAL_PAQUETES, (confirmados * 100.0) / TOTAL_PAQUETES);
    Serial.println("------------------------------------------\n");
    Serial.println("Presiona 'ENTER' para repetir la prueba...");
  }
}