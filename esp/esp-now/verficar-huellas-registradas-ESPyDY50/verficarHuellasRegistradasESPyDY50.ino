/***************************************************
  Ejemplo de Verificación (Búsqueda) de Huellas
  Adaptado para ESP32 (Pines 16 y 17) y traducido al español.
 ****************************************************/

#include <Adafruit_Fingerprint.h>

// Usamos la UART2 por hardware del ESP32
HardwareSerial mySerial(2);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

void setup() {
  Serial.begin(115200);
  while (!Serial); 
  delay(2000); // Tiempo de cortesía para el arranque del sensor
  
  Serial.println("\n\n=== MÓDULO DE VERIFICACIÓN - SISCO-EDU ===");

  // Configuramos el puerto serial físico del ESP32 (Pines 16 RX y 17 TX)
  mySerial.begin(57600, SERIAL_8N1, 16, 17);
  
  // Inicializamos la librería
  finger.begin(57600);

  if (finger.verifyPassword()) {
    Serial.println("¡Sensor de huellas detectado con éxito!");
  } else {
    Serial.println("No se encontró el sensor de huellas digital :(");
    while (1) { delay(1); }
  }

  // Comprobamos cuántas huellas tiene guardadas el sensor en su memoria real
  finger.getTemplateCount();
  Serial.print("El sensor contiene ");
  Serial.print(finger.templateCount);
  Serial.println(" plantillas de huellas guardadas actualmente.");
  
  Serial.println("\n[SISTEMA LISTO] Coloca cualquier dedo en el lector para identificarlo...");
}

void loop() {
  // Llamamos a la función encargada de buscar la huella
  getFingerprintID();
  delay(50); // Pequeña pausa para no saturar el procesador del ESP32
}

// Función que procesa la lectura y busca coincidencias en la base de datos local
int getFingerprintID() {
  uint8_t p = finger.getImage();
  
  switch (p) {
    case FINGERPRINT_OK:
      Serial.println("\n-> Dedo detectado! Tomando imagen...");
      break;
    case FINGERPRINT_NOFINGER:
      // No hay ningún dedo apoyado. Retornamos en silencio para no llenar el monitor.
      return p; 
    case FINGERPRINT_PACKETRECIEVEERR:
      Serial.println("Error de comunicación con el sensor.");
      return p;
    case FINGERPRINT_IMAGEFAIL:
      Serial.println("Error al procesar la imagen del dedo.");
      return p;
    default:
      Serial.println("Error desconocido al capturar imagen.");
      return p;
  }

  // Paso 2: Convertir la imagen capturada a un formato de rasgos matemáticos
  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) {
    switch (p) {
      case FINGERPRINT_IMAGEMESS:
        Serial.println("La imagen está demasiado borrosa.");
        break;
      case FINGERPRINT_FEATUREFAIL:
        Serial.println("No se pudieron extraer los rasgos de la huella.");
        break;
      case FINGERPRINT_INVALIDIMAGE:
        Serial.println("Imagen de huella inválida.");
        break;
      default:
        Serial.println("Error desconocido en la conversión de rasgos.");
        break;
    }
    return p;
  }

  // Paso 3: Buscar en la memoria flash interna del sensor una coincidencia
  p = finger.fingerFastSearch();
  if (p == FINGERPRINT_OK) {
    Serial.println("¡¡¡HUELLA IDENTIFICADA CON ÉXITO!!! ✅");
    Serial.print("-> ID encontrado en el Sensor: #");
    Serial.println(finger.fingerID);
    Serial.print("-> Nivel de Confianza (Score): ");
    Serial.println(finger.confidence);
    Serial.println("-----------------------------------------------------");
    
    delay(2000); // Pausa de 2 segundos para que puedas leer el resultado antes de la siguiente marca
  } else if (p == FINGERPRINT_NOTFOUND) {
    Serial.println("❌ ACCESO DENEGADO: La huella no coincide con ningún registro.");
    Serial.println("-----------------------------------------------------");
    delay(1500);
  } else {
    Serial.println("Error interno al realizar la búsqueda en la base de datos.");
  }

  return p;
}