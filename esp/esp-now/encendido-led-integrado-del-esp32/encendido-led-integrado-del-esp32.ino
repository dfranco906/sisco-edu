// LED integrado del ESP32
const int LED_PIN = 2;

void setup() {
  // Configura el pin como salida
  pinMode(LED_PIN, OUTPUT);
}

void loop() {
  // Encender LED
  digitalWrite(LED_PIN, HIGH);
  delay(1000);

  // Apagar LED
  digitalWrite(LED_PIN, LOW);
  delay(1000);
}
