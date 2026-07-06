// ============================================================================
// SISCO-EDU: PILOTO EMISOR LORA BIOMÉTRICO (NODO 101)
// ============================================================================
#include <Arduino.h>

// Definición de pines UART para el LoRa RYLR998 (Modificar según tu
// shield/placa)
#define LORA_RX 16
#define LORA_TX 17
HardwareSerial loraSerial(2);

const int RECEPTOR_ID = 102;
const int TOTAL_BYTES = 1408; // Cambiar a 1536 si tu firmware DY50 lo requiere
const int CHUNK_BYTES =
    64; // 64 bytes binarios = 128 caracteres HEX (Evita +ERR=13)
const int TOTAL_CHUNKS = TOTAL_BYTES / CHUNK_BYTES;

void enviarHuellaSimulada(int numeroHuella);

void setup() {
  Serial.begin(115200);
  loraSerial.begin(115200, SERIAL_8N1, LORA_RX, LORA_TX);

  Serial.println("\n==================================================");
  Serial.println("[PILOTO] INICIALIZANDO EMISOR LORA (ID 101)");
  Serial.println("==================================================");

  // Espera de seguridad inicial solicitada de 5 segundos
  Serial.println(
      "[TIMER] Esperando 5 segundos antes de transmitir la Huella 1...");
  delay(5000);

  // Transmisión de la Primera Huella
  enviarHuellaSimulada(1);

  // Espera intermedia solicitada de 5 segundos
  Serial.println("\n[TIMER] Huella 1 finalizada. Esperando 5 segundos antes de "
                 "Huella 2...");
  delay(5000);

  // Transmisión de la Segunda Huella
  enviarHuellaSimulada(2);

  Serial.println("\n==================================================");
  Serial.println("[PILOTO] SIMULACIÓN DE EMISIÓN COMPLETADA");
  Serial.println("==================================================");
}

void loop() {
  // Escucha respuestas asíncronas del módulo local por si acaso (+OK o errores)
  if (loraSerial.available()) {
    String respuesta = loraSerial.readStringUntil('\n');
    respuesta.trim();
    if (respuesta.length() > 0) {
      Serial.printf("[LoRa Local Msg]: %s\n", respuesta.c_str());
    }
  }
}

void enviarHuellaSimulada(int numeroHuella) {
  Serial.printf("\n>>> TRANSMITIENDO HUELLA %d (%d bytes | %d paquetes) <<<\n",
                numeroHuella, TOTAL_BYTES, TOTAL_CHUNKS);

  unsigned long tiempoInicioTotal = millis();

  // Creamos un paquete estático de simulación de 128 caracteres HEX
  String hexPayload = "";
  for (int i = 0; i < CHUNK_BYTES * 2; i++) {
    hexPayload += (numeroHuella == 1) ? "A" : "B";
  }

  for (int chunkIdx = 0; chunkIdx < TOTAL_CHUNKS; chunkIdx++) {
    unsigned long tiempoInicioPaquete = millis();

    // Construcción estricta del comando AT sin añadir caracteres invisibles
    // basura
    loraSerial.print("AT+SEND=");
    loraSerial.print(RECEPTOR_ID);
    loraSerial.print(",");
    loraSerial.print(hexPayload.length());
    loraSerial.print(",");
    loraSerial.print(hexPayload);
    loraSerial.write(0x0D); // CR
    loraSerial.write(0x0A); // LF

    // CRÍTICO: Ventana de tiempo para permitir la modulación física de la
    // radio. Con los parámetros por defecto de fábrica (SF=9, BW=125kHz),
    // enviar 128 bytes de texto toma cerca de 220-280ms en el aire. Un delay
    // menor puede saturar el buffer (+ERR=10).
    delay(350);

    unsigned long tiempoFinPaquete = millis();
    Serial.printf("  -> Paquete [%d/%d] procesado en: %lu ms\n", chunkIdx + 1,
                  TOTAL_CHUNKS, (tiempoFinPaquete - tiempoInicioPaquete));
  }

  unsigned long tiempoFinTotal = millis();
  float tiempoSegundos = (tiempoFinTotal - tiempoInicioTotal) / 1000.0;

  Serial.println("--------------------------------------------------");
  Serial.printf(
      ">>> HUELLA %d ENVIADA EXITOSAMENTE en %lu ms (%.2f segundos) <<<\n",
      numeroHuella, (tiempoFinTotal - tiempoInicioTotal), tiempoSegundos);
  Serial.println("--------------------------------------------------");
}