/***************************************************
  Ejemplo de Enrolamiento (Registro) de Huellas
  Adaptado para ESP32 (Pines 16 y 17) y traducido al español.
 ****************************************************/

#include <Adafruit_Fingerprint.h>

// Usamos la UART2 por hardware del ESP32
HardwareSerial mySerial(2);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

uint8_t id;

void setup() {
  Serial.begin(115200);
  while (!Serial); 
  delay(2000); // Tiempo para que el sensor inicie de forma segura
  
  Serial.println("\n\n=== REGISTRO DE HUELLAS - SISCO-EDU ===");

  // --- CORRECCIÓN DE SINTAXIS ---
  // 1. Configuramos el puerto serial del ESP32 asignando los pines 16 (RX) y 17 (TX)
  mySerial.begin(57600, SERIAL_8N1, 16, 17);
  
  // 2. Inicializamos la librería pasándole solo la velocidad
  finger.begin(57600);
  // ------------------------------

  if (finger.verifyPassword()) {
    Serial.println("¡Sensor de huellas detectado con éxito!");
  } else {
    Serial.println("No se encontró el sensor de huellas digital :(");
    while (1) { delay(1); }
  }

  // Muestra las especificaciones del sensor en la pantalla
  Serial.println("Leyendo parámetros del sensor...");
  finger.getParameters();
  Serial.print("Estado de Reg: 0x"); Serial.println(finger.status_reg, HEX);
  Serial.print("ID de Sistema: 0x"); Serial.println(finger.system_id, HEX);
  Serial.print("Capacidad de memoria: "); Serial.println(finger.capacity);
  Serial.print("Nivel de seguridad: "); Serial.println(finger.security_level);
  Serial.print("Dirección del dispositivo: "); Serial.println(finger.device_addr, HEX);
  Serial.print("Largo de paquete: "); Serial.println(finger.packet_len);
  Serial.print("Velocidad de baudios: "); Serial.println(finger.baud_rate);
}

// Función auxiliar para leer el número que escribas en el monitor serial
uint8_t readnumber(void) {
  uint8_t num = 0;
  while (num == 0) {
    while (!Serial.available());
    num = Serial.parseInt();
  }
  return num;
}

void loop() {
  Serial.println("\n-------------------------------------------");
  Serial.println("¡Listo para registrar una nueva huella!");
  Serial.println("Por favor, escribe el número de ID (de 1 a 127) con el que quieres guardar esta huella y presiona Enter...");
  
  id = readnumber();
  if (id == 0) { // El ID 0 no está permitido
    return;
  }
  Serial.print("Registrando en el ID #");
  Serial.println(id);

  // Ejecuta el proceso de registro. Se repite si hay algún error intermedio.
  while (!getFingerprintEnroll());
}

// Proceso principal de captura, conversión, verificación y guardado
uint8_t getFingerprintEnroll() {
  int p = -1;
  Serial.print("Esperando un dedo válido para registrar en el ID #");
  Serial.println(id);
  
  // PRIMERA CAPTURA
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    switch (p) {
      case FINGERPRINT_OK:
        Serial.println("-> Imagen tomada con éxito.");
        break;
      case FINGERPRINT_NOFINGER:
        Serial.print("."); // Imprime puntos suspensivos mientras espera el dedo
        delay(100);
        break;
      case FINGERPRINT_PACKETRECIEVEERR:
        Serial.println("Error de comunicación con el sensor.");
        break;
      case FINGERPRINT_IMAGEFAIL:
        Serial.println("Error al procesar la imagen.");
        break;
      default:
        Serial.println("Error desconocido.");
        break;
    }
  }

  // CONVERSIÓN DE LA PRIMERA IMAGEN
  p = finger.image2Tz(1);
  switch (p) {
    case FINGERPRINT_OK:
      Serial.println("-> Imagen convertida (Paso 1 completado).");
      break;
    case FINGERPRINT_IMAGEMESS:
      Serial.println("La imagen está muy borrosa o sucia.");
      return p;
    case FINGERPRINT_PACKETRECIEVEERR:
      Serial.println("Error de comunicación.");
      return p;
    case FINGERPRINT_FEATUREFAIL:
      Serial.println("No se pudieron encontrar los rasgos de la huella.");
      return p;
    case FINGERPRINT_INVALIDIMAGE:
      Serial.println("Imagen de huella inválida.");
      return p;
    default:
      Serial.println("Error desconocido.");
      return p;
  }

  // SEGUNDA CAPTURA (Verificación)
  Serial.println("!!! RETIRA EL DEDO DEL SENSOR !!!");
  delay(2000);
  p = 0;
  while (p != FINGERPRINT_NOFINGER) {
    p = finger.getImage();
  }
  
  Serial.print("ID asignado: "); Serial.println(id);
  p = -1;
  Serial.println("=== COLOCA EL MISMO DEDO OTRA VEZ ===");
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    switch (p) {
      case FINGERPRINT_OK:
        Serial.println("-> Segunda imagen tomada con éxito.");
        break;
      case FINGERPRINT_NOFINGER:
        Serial.print(".");
        delay(100);
        break;
      case FINGERPRINT_PACKETRECIEVEERR:
        Serial.println("Error de comunicación.");
        break;
      case FINGERPRINT_IMAGEFAIL:
        Serial.println("Error al procesar la segunda imagen.");
        break;
      default:
        Serial.println("Error desconocido.");
        break;
    }
  }

  // CONVERSIÓN DE LA SEGUNDA IMAGEN
  p = finger.image2Tz(2);
  switch (p) {
    case FINGERPRINT_OK:
      Serial.println("-> Segunda imagen convertida (Paso 2 completado).");
      break;
    case FINGERPRINT_IMAGEMESS:
      Serial.println("Segunda imagen muy borrosa.");
      return p;
    case FINGERPRINT_PACKETRECIEVEERR:
      Serial.println("Error de comunicación.");
      return p;
    case FINGERPRINT_FEATUREFAIL:
      Serial.println("No se encontraron rasgos en la segunda huella.");
      return p;
    case FINGERPRINT_INVALIDIMAGE:
      Serial.println("Imagen inválida.");
      return p;
    default:
      Serial.println("Error desconocido.");
      return p;
  }

  // CREACIÓN DEL MODELO MATEMÁTICO (Unir ambas capturas)
  Serial.print("Generando plantilla de huella para el ID #");
  Serial.println(id);

  p = finger.createModel();
  if (p == FINGERPRINT_OK) {
    Serial.println("¡Excelente! Las dos capturas coinciden perfectamente.");
  } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
    Serial.println("Error de comunicación.");
    return p;
  } else if (p == FINGERPRINT_ENROLLMISMATCH) {
    Serial.println("Las huellas no coinciden. Inténtalo de nuevo.");
    return p;
  } else {
    Serial.println("Error desconocido al emparejar.");
    return p;
  }

  // GUARDAR EN LA MEMORIA FLASH DEL SENSOR
  p = finger.storeModel(id);
  if (p == FINGERPRINT_OK) {
    Serial.println("¡¡¡GUARDADO CON ÉXITO EN LA MEMORIA DEL SENSOR!!! ✅");
  } else if (p == FINGERPRINT_PACKETRECIEVEERR) {
    Serial.println("Error de comunicación al guardar.");
    return p;
  } else if (p == FINGERPRINT_BADLOCATION) {
    Serial.println("No se puede guardar en esa posición de memoria.");
    return p;
  } else if (p == FINGERPRINT_FLASHERR) {
    Serial.println("Error físico al escribir en la memoria flash del sensor.");
    return p;
  } else {
    Serial.println("Error desconocido al almacenar.");
    return p;
  }

  return true;
}