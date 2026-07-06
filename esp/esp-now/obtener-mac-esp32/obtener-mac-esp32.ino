#include <WiFi.h>

void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\n=== SISCO-EDU: RE-INTENTO OBTENER MAC ===");

  // Inicializar el Wi-Fi en modo Estación (con la librería estándar)
  WiFi.mode(WIFI_STA);
  delay(500);

  // Imprimir la dirección MAC usando la propiedad directa del objeto WiFi
  Serial.print("Dirección MAC de este ESP32: ");
  Serial.println(WiFi.macAddress());
}

void loop() {}