// ============================================================================
// SISCO-EDU: EMISOR LORA SEGURO ULTRA-OPTIMIZADO (NODO 101)
// ============================================================================
#include <Arduino.h>

#define LORA_RX 16
#define LORA_TX 17
HardwareSerial loraSerial(2);

const int RECEPTOR_ID = 102;
const int TOTAL_BYTES = 1536;
const int CHUNK_BYTES = 64;
const int TOTAL_CHUNKS = TOTAL_BYTES / CHUNK_BYTES;
const int MAX_REINTENTOS =
    8; // Incrementamos los reintentos permitidos ya que son más rápidos

void enviarHuellaSimulada(int numeroHuella);
bool esperarOKLocal(unsigned long timeoutMs = 200);
bool esperarAckRemoto(int chunkIdx, unsigned long timeoutMs =
                                        400); // Optimizado de 1500ms a 400ms

void setup() {
  Serial.begin(115200);
  loraSerial.begin(115200, SERIAL_8N1, LORA_RX, LORA_TX);

  Serial.println("\n==================================================");
  Serial.println("[PILOTO] INICIALIZANDO EMISOR OPTIMIZADO (ID 101)");
  Serial.println("==================================================");

  // NOTA: Para aplicar la optimización física de RF de forma automática,
  // puedes descomentar las siguientes líneas una sola vez en tus equipos:
  /*
  delay(1000);
  loraSerial.print("AT+PARAMETER=7,9,1,12\r\n"); // SF=7, BW=500kHz
  delay(500);
  */

  Serial.println(
      "[TIMER] Esperando 5 segundos antes de transmitir la Huella 1...");
  delay(5000);

  enviarHuellaSimulada(1);

  Serial.println("\n[TIMER] Huella 1 finalizada. Esperando 5 segundos antes de "
                 "Huella 2...");
  delay(5000);

  enviarHuellaSimulada(2);
}

void loop() {
  if (loraSerial.available()) {
    String respuesta = loraSerial.readStringUntil('\n');
    respuesta.trim();
    if (respuesta.length() > 0) {
      Serial.printf("[LoRa Async Msg]: %s\n", respuesta.c_str());
    }
  }
}

void enviarHuellaSimulada(int numeroHuella) {
  Serial.printf("\n>>> TRANSMITIENDO HUELLA %d OPTIMIZADA <<<\n", numeroHuella);

  unsigned long tiempoInicioTotal = millis();

  String hexPayload = "";
  for (int i = 0; i < CHUNK_BYTES * 2; i++) {
    hexPayload += (numeroHuella == 1) ? "A" : "B";
  }

  for (int chunkIdx = 0; chunkIdx < TOTAL_CHUNKS; chunkIdx++) {
    int reintentos = 0;
    bool paqueteConfirmado = false;
    String payloadConIndice = String(chunkIdx) + ":" + hexPayload;

    while (!paqueteConfirmado && reintentos < MAX_REINTENTOS) {
      unsigned long tiempoInicioPaquete = millis();

      while (loraSerial.available()) {
        loraSerial.read();
      }

      loraSerial.print("AT+SEND=");
      loraSerial.print(RECEPTOR_ID);
      loraSerial.print(",");
      loraSerial.print(payloadConIndice.length());
      loraSerial.print(",");
      loraSerial.print(payloadConIndice);
      loraSerial.write(0x0D);
      loraSerial.write(0x0A);

      // FASE 1: Comprobación local veloz
      if (esperarOKLocal(200)) {
        // FASE 2: Ventana corta de ACK Remoto (400ms máx)
        paqueteConfirmado = esperarAckRemoto(chunkIdx, 400);
      }

      unsigned long tiempoFinPaquete = millis();

      if (paqueteConfirmado) {
        Serial.printf("  -> Paquete [%d/%d] [OK] en %lu ms\n", chunkIdx + 1,
                      TOTAL_CHUNKS, (tiempoFinPaquete - tiempoInicioPaquete));
        delay(15; // Delay técnico mínimo de recuperación del buffer
      } else {
        reintentos++;
        Serial.printf(
            "  -> [ALERTA] Paquete [%d/%d] reintentando rápido (%d/%d)...\n",
            chunkIdx + 1, TOTAL_CHUNKS, reintentos, MAX_REINTENTOS);
        delay(80; // Tiempo de espera corto para disipar colisiones menores
      }
    }

    if (!paqueteConfirmado) {
      Serial.printf(
          "\n[ERROR CRÍTICO] Enlace interrumpido en el paquete [%d].\n",
          chunkIdx + 1);
      return;
    }
  }

  unsigned long tiempoFinTotal = millis();
  float tiempoSegundos = (tiempoFinTotal - tiempoInicioTotal) / 1000.0;

  Serial.println("--------------------------------------------------");
  Serial.printf(">>> HUELLA %d TRANSMITIDA EN %.2f SEGUNDOS <<<\n",
                numeroHuella, tiempoSegundos);
  Serial.println("--------------------------------------------------");
}

bool esperarOKLocal(unsigned long timeoutMs) {
  unsigned long start = millis();
  while (millis() - start < timeoutMs) {
    if (loraSerial.available()) {
      String respuesta = loraSerial.readStringUntil('\n');
      respuesta.trim();
      if (respuesta.length() > 0) {
        if (respuesta.indexOf("+OK") != -1)
          return true;
        if (respuesta.indexOf("+ERR") != -1)
          return false;
      }
    }
    yield();
  }
  return false;
}

bool esperarAckRemoto(int chunkIdx, unsigned long timeoutMs) {
  unsigned long start = millis();
  String tokenAckEsperado = "ACK" + String(chunkIdx);

  while (millis() - start < timeoutMs) {
    if (loraSerial.available()) {
      String respuesta = loraSerial.readStringUntil('\n');
      respuesta.trim();
      if (respuesta.length() > 0) {
        if (respuesta.indexOf("+RCV=") != -1 &&
            respuesta.indexOf(tokenAckEsperado) != -1) {
          return true;
        }
      }
    }
    yield();
  }
  return false;
}
