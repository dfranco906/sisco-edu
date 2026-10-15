#include <HardwareSerial.h>

// Configuración de pines (ajusta si tus pines son diferentes)
// Usaremos HardwareSerial 2 para el LoRa
HardwareSerial loraSerial(2);

void setup() {
  // Serial para el PC
  Serial.begin(115200);

  // Serial para el LoRa (115200 es la velocidad estándar del RYLR998)
  loraSerial.begin(115200, SERIAL_8N1, 16,
                   17); // Ajusta 16 y 17 a tus pines RX/TX

  Serial.println("--- MODO PUENTE LORA INICIADO ---");
  Serial.println("Escribe comandos AT en el monitor serial (ej: AT)");
  Serial.println(
      "Asegurate de seleccionar 'Ambos NL & CR' en el monitor serial");
}

void loop() {
  // 1. Del PC al LoRa
  if (Serial.available()) {
    String comando = Serial.readStringUntil('\n');
    loraSerial.println(comando);
    Serial.print(">> Enviando: ");
    Serial.println(comando);
  }

  // 2. Del LoRa al PC
  if (loraSerial.available()) {
    while (loraSerial.available()) {
      Serial.write(loraSerial.read());
    }
  }
}