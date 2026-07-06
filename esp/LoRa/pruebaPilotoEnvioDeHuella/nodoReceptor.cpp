// ============================================================================
// SISCO-EDU: PILOTO RECEPTOR LORA CON CONTROL DE INTEGRIDAD REMOTA (NODO 102)
// ============================================================================
#include <Arduino.h>

#define LORA_RX 16
#define LORA_TX 17
HardwareSerial loraSerial(2);

const int EMISOR_ID = 101; // ID del nodo emisor
const int TOTAL_CHUNKS_ESPERADOS = 22;

unsigned long tiempoPrimerChunk = 0;
int chunksGuardadosValidos = 0;
int ultimoIndiceProcesado =
    -1; // Almacena el último índice de paquete guardado exitosamente

void setup() {
  Serial.begin(115200);
  loraSerial.begin(115200, SERIAL_8N1, LORA_RX, LORA_TX);

  Serial.println("\n==================================================");
  Serial.println("[PILOTO] RECEPTOR LORA SEGURO INICIALIZADO (ID 102)");
  Serial.println("Escuchando ráfagas entrantes con control ARQ...");
  Serial.println("==================================================");
}

void loop() {
  if (loraSerial.available()) {
    String linea = loraSerial.readStringUntil('\n');
    linea.trim();

    // Filtramos únicamente las tramas de datos recibidas por radio
    if (linea.startsWith("+RCV=")) {
      unsigned long tiempoActual = millis();

      // Formato esperado de línea: +RCV=<ID>,<LONGITUD>,<DATOS>,<RSSI>,<SNR>
      // Ejemplo: +RCV=101,132,0:AAAAAAAAAAAAAAAA...,-77,13

      // Encontrar las posiciones de las comas para aislar los datos
      int primeraComa = linea.indexOf(',');
      int segundaComa = linea.indexOf(',', primeraComa + 1);
      int terceraComa = linea.indexOf(',', segundaComa + 1);

      if (segundaComa != -1 && terceraComa != -1) {
        // Extraemos la porción del Payload (índice:datos)
        String datosPayload = linea.substring(segundaComa + 1, terceraComa);

        // Buscamos nuestro separador estructural de índice ":"
        int posicionDosPuntos = datosPayload.indexOf(':');

        if (posicionDosPuntos != -1) {
          // Extraemos el índice del paquete enviado y los datos HEX puros
          int indexPaquete =
              datosPayload.substring(0, posicionDosPuntos).toInt();
          String hexChunk = datosPayload.substring(posicionDosPuntos + 1);

          // 1. RESPONDER EL ACK INMEDIATAMENTE al Emisor (ID 101) para
          // liberarlo
          String stringAck = "ACK" + String(indexPaquete);

          loraSerial.print("AT+SEND=");
          loraSerial.print(EMISOR_ID);
          loraSerial.print(",");
          loraSerial.print(stringAck.length());
          loraSerial.print(",");
          loraSerial.print(stringAck);
          loraSerial.write(0x0D); // CR
          loraSerial.write(0x0A); // LF

          // 2. CONTROL DE INTEGRIDAD Y FILTRADO DE DUPLICADOS
          if (indexPaquete == 0 && chunksGuardadosValidos == 0) {
            // Primer paquete real de una nueva ráfaga/huella
            tiempoPrimerChunk = tiempoActual;
            ultimoIndiceProcesado = -1;
            Serial.println("\n[RECEPTOR] >>> Comenzando recepción de nueva "
                           "huella biométrica... <<<");
          }

          unsigned long tiempoTranscurrido = tiempoActual - tiempoPrimerChunk;

          if (indexPaquete > ultimoIndiceProcesado) {
            // Es un paquete nuevo en la secuencia correcta
            chunksGuardadosValidos++;
            ultimoIndiceProcesado = indexPaquete; // Actualizamos el registro

            // Telemetría de guardado exitoso
            Serial.printf("  Chunk #%02d [GUARDADO] | Index: %02d | Tiempo "
                          "acum: %5lu ms | RSSI/SNR: %s\n",
                          chunksGuardadosValidos, indexPaquete,
                          tiempoTranscurrido,
                          linea.substring(terceraComa + 1).c_str());

            // >>> AQUÍ SE PROCESA O ALMACENA EL CONTENIDO REAL (hexChunk) <<<
            // (Ej: escribirlo en el buffer de la memoria de la huella)

            // Verificamos si completamos exitosamente todos los paquetes únicos
            // de la huella
            if (chunksGuardadosValidos == TOTAL_CHUNKS_ESPERADOS) {
              float tiempoSegundos = tiempoTranscurrido / 1000.0;
              Serial.println(
                  "--------------------------------------------------");
              Serial.printf(
                  "[ÉXITO] HUELLA RECONSTRUIDA AL 100%% SIN PÉRDIDAS\n");
              Serial.printf("Paquetes únicos procesados: %d\n",
                            chunksGuardadosValidos);
              Serial.printf("Tiempo total de ráfaga: %lu ms (%.2f segundos)\n",
                            tiempoTranscurrido, tiempoSegundos);
              Serial.println(
                  "--------------------------------------------------");

              // Reseteamos variables de control para la próxima transferencia
              chunksGuardadosValidos = 0;
              ultimoIndiceProcesado = -1;
            }
          } else {
            // El paquete ya lo teníamos guardado (Duplicado por pérdida del ACK
            // previo en el aire)
            Serial.printf("  Chunk duplicado ignorado (Index: %02d) -> [ACK "
                          "RE-ENVIADO]\n",
                          indexPaquete);
          }
        }
      }
    } else {
      // Muestra respuestas de control de los comandos AT locales del receptor
      // (+OK, etc.)
      if (linea.length() > 0) {
        Serial.printf("[System Log]: %s\n", linea.c_str());
      }
    }
  }
}