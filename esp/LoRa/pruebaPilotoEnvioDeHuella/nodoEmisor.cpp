// ============================================================================
// SISCO-EDU: PILOTO EMISOR LORA CON CONTROL DE INTEGRIDAD REMOTA (ARQ)
// ============================================================================
#include <Arduino.h>

// Definición de pines UART para el LoRa RYLR998
#define LORA_RX 16
#define LORA_TX 17
HardwareSerial loraSerial(2);

const int RECEPTOR_ID = 102;
const int TOTAL_BYTES = 1408; // 1536 si el firmware DY50 lo requiere
const int CHUNK_BYTES = 64;   // 64 bytes binarios = 128 caracteres HEX
const int TOTAL_CHUNKS = TOTAL_BYTES / CHUNK_BYTES;
const int MAX_REINTENTOS =
    5; // Máximo de retransmisiones permitidas por paquete

void enviarHuellaSimulada(int numeroHuella);
bool esperarOKLocal(unsigned long timeoutMs = 500);
bool esperarAckRemoto(int chunkIdx, unsigned long timeoutMs = 1500);

void setup() {
  Serial.begin(115200);
  loraSerial.begin(115200, SERIAL_8N1, LORA_RX, LORA_TX);

  Serial.println("\n==================================================");
  Serial.println("[PILOTO] INICIALIZANDO EMISOR LORA SEGURO (ID 101)");
  Serial.println("==================================================");

  Serial.println(
      "[TIMER] Esperando 5 segundos antes de transmitir la Huella 1...");
  delay(5000);

  // Transmisión de la Primera Huella con control ARQ
  enviarHuellaSimulada(1);

  Serial.println("\n[TIMER] Huella 1 finalizada. Esperando 5 segundos antes de "
                 "Huella 2...");
  delay(5000);

  // Transmisión de la Segunda Huella con control ARQ
  enviarHuellaSimulada(2);

  Serial.println("\n==================================================");
  Serial.println("[PILOTO] SIMULACIÓN DE EMISIÓN COMPLETADA");
  Serial.println("==================================================");
}

void loop() {
  // Escucha respuestas asíncronas fuera del proceso de transmisión principal
  if (loraSerial.available()) {
    String respuesta = loraSerial.readStringUntil('\n');
    respuesta.trim();
    if (respuesta.length() > 0) {
      Serial.printf("[LoRa Async Msg]: %s\n", respuesta.c_str());
    }
  }
}

void enviarHuellaSimulada(int numeroHuella) {
  Serial.printf(
      "\n>>> TRANSMITIENDO HUELLA %d CON VERIFICACIÓN DE ENLACE <<<\n",
      numeroHuella);

  unsigned long tiempoInicioTotal = millis();

  // Generamos el bloque base de simulación de 128 caracteres HEX
  String hexPayload = "";
  for (int i = 0; i < CHUNK_BYTES * 2; i++) {
    hexPayload += (numeroHuella == 1) ? "A" : "B";
  }

  for (int chunkIdx = 0; chunkIdx < TOTAL_CHUNKS; chunkIdx++) {
    int reintentos = 0;
    bool paqueteConfirmado = false;

    // Estructuramos el payload incluyendo el índice y un separador (Ej:
    // "0:AAAA...")
    String payloadConIndice = String(chunkIdx) + ":" + hexPayload;

    while (!paqueteConfirmado && reintentos < MAX_REINTENTOS) {
      unsigned long tiempoInicioPaquete = millis();

      // Limpieza exhaustiva del canal UART local antes de transmitir
      while (loraSerial.available()) {
        loraSerial.read();
      }

      // Envío del comando de transmisión hacia el RYLR998 local
      loraSerial.print("AT+SEND=");
      loraSerial.print(RECEPTOR_ID);
      loraSerial.print(",");
      loraSerial.print(payloadConIndice.length());
      loraSerial.print(",");
      loraSerial.print(payloadConIndice);
      loraSerial.write(0x0D); // CR
      loraSerial.write(0x0A); // LF

      // FASE 1: Comprobar que el chip de radio local aceptó el comando
      if (esperarOKLocal(500)) {
        // FASE 2: Esperar activamente la confirmación física del receptor
        // remoto
        paqueteConfirmado = esperarAckRemoto(chunkIdx, 1500);
      }

      unsigned long tiempoFinPaquete = millis();

      if (paqueteConfirmado) {
        Serial.printf("  -> Paquete [%d/%d] [CONFIRMADO REMOTO] en %lu ms\n",
                      chunkIdx + 1, TOTAL_CHUNKS,
                      (tiempoFinPaquete - tiempoInicioPaquete));
        // Breve delay técnico de estabilización antes de procesar el siguiente
        // paquete
        delay(40);
      } else {
        reintentos++;
        Serial.printf("  -> [ALERTA] Paquete [%d/%d] PERDIDO o SIN RESPUESTA. "
                      "Reintento %d/%d...\n",
                      chunkIdx + 1, TOTAL_CHUNKS, reintentos, MAX_REINTENTOS);
        // Espera táctica de desvanecimiento para permitir que el canal de radio
        // se limpie
        delay(250);
      }
    }

    // Interrupción de seguridad si el enlace se rompe permanentemente
    if (!paqueteConfirmado) {
      Serial.printf("\n[ERROR CRÍTICO] Enlace LoRa interrumpido. No se pudo "
                    "validar el paquete [%d].\n",
                    chunkIdx + 1);
      return;
    }
  }

  unsigned long tiempoFinTotal = millis();
  float tiempoSegundos = (tiempoFinTotal - tiempoInicioTotal) / 1000.0;

  Serial.println("--------------------------------------------------");
  Serial.printf(">>> HUELLA %d TRANSMITIDA EXITOSAMENTE CON 100%% DE "
                "INTEGRIDAD EN %.2f SEG <<<\n",
                numeroHuella, tiempoSegundos);
  Serial.println("--------------------------------------------------");
}

// Procesa el eco local inmediato del chip acoplado
bool esperarOKLocal(unsigned long timeoutMs) {
  unsigned long start = millis();
  while (millis() - start < timeoutMs) {
    if (loraSerial.available()) {
      String respuesta = loraSerial.readStringUntil('\n');
      respuesta.trim();
      if (respuesta.length() > 0) {
        if (respuesta.indexOf("+OK") != -1)
          return true;
        if (respuesta.indexOf("+ERR") != -1) {
          Serial.printf("   [LoRa Local Error]: %s\n", respuesta.c_str());
          return false;
        }
      }
    }
    yield();
  }
  Serial.println("   [LoRa Local Error]: TIMEOUT esperando buffer de comando");
  return false;
}

// Escucha el medio inalámbrico buscando la cadena de confirmación del receptor
bool esperarAckRemoto(int chunkIdx, unsigned long timeoutMs) {
  unsigned long start = millis();
  String tokenAckEsperado = "ACK" + String(chunkIdx);

  while (millis() - start < timeoutMs) {
    if (loraSerial.available()) {
      String respuesta = loraSerial.readStringUntil('\n');
      respuesta.trim();
      if (respuesta.length() > 0) {
        // Formato esperado por RF: +RCV=102,<longitud>,ACK<índice>,<RSSI>,<SNR>
        if (respuesta.indexOf("+RCV=") != -1) {
          if (respuesta.indexOf(tokenAckEsperado) != -1) {
            return true; // ACK recibido perfectamente
          }
        }
      }
    }
    yield();
  }
  return false; // Llegó al límite de tiempo sin recibir confirmación del
                // receptor
}