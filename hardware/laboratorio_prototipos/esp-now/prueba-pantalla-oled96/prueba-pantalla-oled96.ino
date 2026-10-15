#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64

// Reset pin (-1 si no se usa)
#define OLED_RESET -1

Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

void setup() {

  // Iniciar comunicación I2C
  Wire.begin(21, 22);

  // Inicializar pantalla OLED
  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    while (true); // si falla, se queda aquí
  }

  display.clearDisplay();

  // Texto
  display.setTextSize(2);
  display.setTextColor(SSD1306_WHITE);
  display.setCursor(10, 25);
  display.println("Hello ESP");

  display.display();
}

void loop() {
  // No hace falta nada aquí
}
