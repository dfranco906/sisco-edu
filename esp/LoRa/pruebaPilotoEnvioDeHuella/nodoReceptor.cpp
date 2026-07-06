// ============================================================================
// SISCO-EDU: PILOTO RECEPTOR LORA BIOMÉTRICO (NODO 102)
// ============================================================================
#include <Arduino.h>

#define LORA_RX 16
#define LORA_TX 17
HardwareSerial loraSerial(2);

// Debe coincidir estrictamente con la cantidad de paquetes del Emisor
const int TOTAL_CHUNKS_ESPERADOS = 22;

unsigned long tiempoPrimerChunk = 0;
int contadorPaquetes = 0;

void setup() {
  Serial.begin(115200);
  loraSerial.begin(115200, SERIAL_8N1, LORA_RX, LORA_TX);

  Serial.println("\n==================================================");
  Serial.println("[PILOTO] RECEPTOR LORA INICIALIZADO (ID 102)");
  Serial.println("Escuchando ráfagas entrantes...");
  Serial.println("==================================================");
}

void loop() {
  if (loraSerial.available()) {
    String linea = loraSerial.readStringUntil('\n');
    linea.trim();

    // Filtramos únicamente las tramas de datos recibidas por radio
    if (linea.startsWith("+RCV=")) {
      contadorPaquetes++;
      unsigned long tiempoActual = millis();

      // Marcamos el instante exacto en el que inició la transferencia de esta
      // huella
      if (contadorPaquetes == 1) {
        tiempoPrimerChunk = tiempoActual;
        Serial.println("\n[RECEPTOR] >>> Comenzando recepción de nueva huella "
                       "biométrica... <<<");
      }

      unsigned long tiempoTranscurrido = tiempoActual - tiempoPrimerChunk;

      // Imprimimos la telemetría del paquete actual
      Serial.printf("  Chunk #%02d recibido | Tiempo acumulado: %5lu ms | "
                    "Contenido: %s\n",
                    contadorPaquetes, tiempoTranscurrido, linea.c_str());

      // Verificamos si completamos la estructura de la huella
      if (contadorPaquetes == TOTAL_CHUNKS_ESPERADOS) {
        float tiempoSegundos = tiempoTranscurrido / 1000.0;
        Serial.println("--------------------------------------------------");
        Serial.printf("[ÉXITO] HUELLA RECONSTRUIDA EN RECEPTOR\n");
        Serial.printf("Tiempo total de ráfaga: %lu ms (%.2f segundos)\n",
                      tiempoTranscurrido, tiempoSegundos);
        Serial.println("--------------------------------------------------");

        contadorPaquetes = 0; // Reseteamos el contador para la siguiente huella
      }
    } else {
      // Muestra respuestas de control adicionales (+OK, etc.)
      if (linea.length() > 0) {
        Serial.printf("[System Log]: %s\n", linea.c_str());
      }
    }
  }
}