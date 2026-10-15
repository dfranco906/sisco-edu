#include <Adafruit_Fingerprint.h>

HardwareSerial mySerial(2); 
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

void setup() {
  Serial.begin(115200);
  
  // 1. Darle tiempo al sensor para que termine de encender (crucial)
  Serial.println("Esperando 2 segundos para que el sensor inicie...");
  delay(2000); 
  
  // 2. Iniciamos el puerto serial hacia el sensor. 
  // Recuerda: el TX del sensor debe ir al 16, y el RX del sensor al 17.
  mySerial.begin(57600, SERIAL_8N1, 16, 17); 
  
  Serial.println("Buscando sensor de huellas...");
  
  if (finger.verifyPassword()) {
    Serial.println("¡Sensor AS608/DY50 detectado con éxito!");
  } else {
    Serial.println("No se encontró el sensor de huellas :(");
    Serial.println("Revisa: ¿Están cruzados TX y RX? ¿Quitaste las resistencias?");
  }
}

void loop() {
  // Vacío por ahora hasta superar la prueba de conexión
}