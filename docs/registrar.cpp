/***************************************************
  SISCO-EDU: Nodo de Registro Biométrico Web + OLED
  Recibe comandos vía HTTP (Wi-Fi) desde el Frontend.
  El endpoint /obtener_template extrae la huella y la retorna
  en un formato JSON con el String Hexadecimal listo para el Frontend.
  Placa: ESP32 | Sensor: AS608 (16 RX, 17 TX) | OLED: I2C (21 SDA, 22 SCL)
 ****************************************************/

#include <WiFi.h>
#include <WebServer.h>
#include <Adafruit_Fingerprint.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>

#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET    -1
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);

const char* ssid = "esp";
const char* password = "123456789";

WebServer server(80);
HardwareSerial mySerial(2);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

bool ejecutarEnrolamiento = false;

void actualizarPantalla(String linea1, String linea2 = "") {
  display.clearDisplay();
  display.setTextColor(SSD1306_WHITE);
 
  display.setTextSize(2);
  display.setCursor(0, 8);
  display.println(linea1);
 
  display.setTextSize(1);
  display.setCursor(0, 48);
  display.println(linea2);
 
  display.display();
}

void setup() {
  Serial.begin(115200);
 
  if(!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println(F("Fallo al inicializar SSD1306"));
    for(;;);
  }
  display.clearDisplay();
  actualizarPantalla("INICIANDO", "Cargando componentes...");

  mySerial.begin(57600, SERIAL_8N1, 16, 17);
  finger.begin(57600);
  delay(1000);
 
  if (!finger.verifyPassword()) {
    actualizarPantalla("ERROR HW", "Sensor de huellas no hallado");
    while (1) { delay(1); }
  }

  WiFi.begin(ssid, password);
  int reintentos = 0;
  while (WiFi.status() != WL_CONNECTED && reintentos < 30) {
    delay(500);
    actualizarPantalla("BUSCANDO", "Conectando al Wi-Fi: " + String(reintentos) + "s");
    reintentos++;
  }

  if(WiFi.status() == WL_CONNECTED) {
    Serial.println("\nConectado!");
    actualizarPantalla("ONLINE", "IP: " + WiFi.localIP().toString());
  } else {
    actualizarPantalla("ERR WIFI", "Fallo red. Reinicie equipo.");
    while(1);
  }

  // Endpoint de Registro (Ranura fija 1)
  server.on("/registrar", HTTP_GET, []() {
    server.sendHeader("Access-Control-Allow-Origin", "*");
    ejecutarEnrolamiento = true;
    server.send(200, "application/json", "{\"status\":\"success\",\"message\":\"Captura iniciada en Ranura 1.\"}");
  });

  // NUEVO: Endpoint de Extracción de Datos con Retorno JSON Hexadecimal
  server.on("/obtener_template", HTTP_GET, []() {
    server.sendHeader("Access-Control-Allow-Origin", "*");
   
    uint8_t p = finger.loadModel(1);
    if (p != FINGERPRINT_OK) {
      server.send(404, "application/json", "{\"status\":\"error\",\"message\":\"No hay huella capturada aun en la ranura 1.\"}");
      return;
    }
   
    p = finger.getModel();
    if (p != FINGERPRINT_OK) {
      server.send(500, "application/json", "{\"status\":\"error\",\"message\":\"Error al empaquetar la huella desde el sensor.\"}");
      return;
    }

    // Reservamos espacio en memoria para el string hexadecimal (1536 bytes * 2 caracteres por byte = 3072 caracteres)
    String templateHex = "";
    templateHex.reserve(3072);

    uint32_t timeout = millis();
    int bytesLeidos = 0;

    // Leemos los 1536 bytes provenientes del buffer serial del AS608
    while ((millis() - timeout < 2000) && (bytesLeidos < 1536)) {
      if (mySerial.available()) {
        uint8_t b = mySerial.read();
        if (b < 16) templateHex += "0"; // Añadir cero a la izquierda para valores menores a 0x10
        templateHex += String(b, HEX);
        bytesLeidos++;
      }
    }

    // Validamos que se hayan extraído los datos completos antes de responder
    if (bytesLeidos == 1536) {
      actualizarPantalla("EXPORTADO", "Template enviado a la web");
     
      // Construimos el JSON de respuesta conteniendo el string hexadecimal completo
      String jsonRespuesta = "{\"status\":\"success\",\"bytes\":" + String(bytesLeidos) + ",\"template\":\"" + templateHex + "\"}";
      server.send(200, "application/json", jsonRespuesta);
     
      delay(1500);
      actualizarPantalla("ONLINE", "IP: " + WiFi.localIP().toString());
    } else {
      actualizarPantalla("ERR TRANS", "Paquete incompleto en lectura");
      server.send(500, "application/json", "{\"status\":\"error\",\"message\":\"Transmision incompleta. Se leyeron solo " + String(bytesLeidos) + " bytes.\"}");
    }
  });

  // Endpoint para Limpiar la Ranura Temporal
  server.on("/limpiar", HTTP_GET, []() {
    server.sendHeader("Access-Control-Allow-Origin", "*");
    uint8_t p = finger.emptyDatabase();
    if (p == FINGERPRINT_OK) {
      server.send(200, "application/json", "{\"status\":\"success\",\"message\":\"Memoria limpia.\"}");
      actualizarPantalla("ONLINE", "IP: " + WiFi.localIP().toString());
    } else {
      server.send(500, "application/json", "{\"status\":\"error\",\"message\":\"Error de hardware.\"}");
    }
  });

  server.begin();
}

void loop() {
  server.handleClient();

  if (ejecutarEnrolamiento) {
    ejecutarEnrolamiento = false;
    PROCESO_ENROLAMIENTO_WEB();
    actualizarPantalla("ONLINE", "IP: " + WiFi.localIP().toString());
  }
  delay(10);
}

void PROCESO_ENROLAMIENTO_WEB() {
  int p = -1;
 
  // --- MUESTRA 1 ---
  actualizarPantalla("PONGA EL\nDEDO", "[MUESTRA 1 / 2]");
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    if (p == FINGERPRINT_PACKETRECIEVEERR) return;
  }
 
  p = finger.image2Tz(1);
  if (p != FINGERPRINT_OK) {
    actualizarPantalla("ERR CAPTURA", "Muestra borrosa. Reintente.");
    delay(2000);
    return;
  }
 
  // --- RETIRAR ---
  actualizarPantalla("RETIRE EL\nDEDO", "Procesando...");
  delay(2000);
  p = 0;
  while (p != FINGERPRINT_NOFINGER) { p = finger.getImage(); }
 
  // --- MUESTRA 2 ---
  p = -1;
  actualizarPantalla("COLOQUE\nDE NUEVO", "[MUESTRA 2 / 2]");
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    if (p == FINGERPRINT_PACKETRECIEVEERR) return;
  }
 
  p = finger.image2Tz(2);
  if (p != FINGERPRINT_OK) {
    actualizarPantalla("ERR CAPTURA", "No coinciden. Reintente.");
    delay(2000);
    return;
  }
 
  // --- GUARDADO ---
  p = finger.createModel();
  if (p != FINGERPRINT_OK) {
    actualizarPantalla("ERR MATEMAT", "Error de estructura dactilar.");
    delay(2000);
    return;
  }
 
  p = finger.storeModel(1);
  if (p == FINGERPRINT_OK) {
    actualizarPantalla("¡EXITOSO!", "Huella lista para exportar");
    delay(2500);
  } else {
    actualizarPantalla("ERR FLASH", "No se pudo guardar localmente.");
    delay(2000);
  }
}