/***************************************************
  SISCO-EDU: Sistema de Control Biométrico Interactivo
  Nodo de Registro con Extracción de Templates en formato HEX.
  Placa: ESP32 | Sensor: DY50/AS608 (Pines 16 RX, 17 TX)
 ****************************************************/

#include <Adafruit_Fingerprint.h>

// Usamos la UART2 por hardware del ESP32
HardwareSerial mySerial(2);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

void setup() {
  Serial.begin(115200);
  while (!Serial); 
  delay(2000); 
  
  Serial.println("\n\n=========================================");
  Serial.println("      SISTEMA BIOMÉTRICO SISCO-EDU       ");
  Serial.println("=========================================");

  // Configuración física de comunicación (Pines 16 y 17)
  mySerial.begin(57600, SERIAL_8N1, 16, 17);
  finger.begin(57600);

  if (finger.verifyPassword()) {
    Serial.println("¡Sensor de huellas detectado con éxito! ✅");
  } else {
    Serial.println("No se encontró el sensor de huellas digital ❌");
    while (1) { delay(1); }
  }

  mostrarMenu();
}

void loop() {
  // 1. Verificar si el usuario envió un comando desde la consola
  if (Serial.available() > 0) {
    char comando = Serial.read();
    
    // Limpiar caracteres de control residuales (\n o \r)
    while (Serial.available() > 0 && (Serial.peek() == '\n' || Serial.peek() == '\r')) {
      Serial.read();
    }
    
    // Evaluar la opción ingresada
    switch (comando) {
      case '1':
        registrarHuella();
        mostrarMenu();
        break;
      case '2':
        eliminarHuella();
        mostrarMenu();
        break;
      case '3':
        verCantidadTemplates();
        mostrarMenu();
        break;
      case '4':
        extraerTemplateHEX();
        mostrarMenu();
        break;
      case '5':
        vaciarSensor();
        mostrarMenu();
        break;
      case '6':
        mostrarMenu();
        break;
      default:
        Serial.println("\n⚠️ Comando no válido. Presiona [6] para ver las opciones.");
        break;
    }
  }

  // 2. Modo Escucha Activo por Defecto (Verificación continua no bloqueante)
  verificarHuellaModoEscucha();
  delay(50); 
}

// =========================================================================
// INTERFAZ DE COMANDOS EN CONSOLA
// =========================================================================

void mostrarMenu() {
  Serial.println("\n=========================================");
  Serial.println("      MENÚ DE COMANDOS DISPONIBLES       ");
  Serial.println("=========================================");
  Serial.println("[1] Registrar nueva huella (Enroll)");
  Serial.println("[2] Eliminar una huella específica");
  Serial.println("[3] Ver cantidad de huellas guardadas");
  Serial.println("[4] EXTRAER TEMPLATE de una huella (Exportar HEX) 💾");
  Serial.println("[5] VACIAR por completo el sensor (🚨 Peligro)");
  Serial.println("[6] Volver a mostrar este menú");
  Serial.println("=========================================");
  Serial.println("📡 [MODO ESCUCHA ACTIVO] Coloca tu dedo para marcar asistencia...\n");
}

uint8_t leerNumeroConsola(void) {
  uint8_t num = 0;
  while (num == 0) {
    while (!Serial.available());
    num = Serial.parseInt();
    while(Serial.available() > 0) { Serial.read(); } // Limpiar buffer
  }
  return num;
}

// --- COMANDO 1: REGISTRAR HUELLA ---
void registrarHuella() {
  Serial.println("\n--- [MODO REGISTRO INICIADO] ---");
  Serial.println("Escribe el número de ID (de 1 a 127) para este alumno y presiona Enter:");
  uint8_t id = leerNumeroConsola();
  Serial.print("Registrando en el ID #"); Serial.println(id);
  while (!ejecutarProcesoEnrolamiento(id));
}

// --- COMANDO 2: ELIMINAR HUELLA ---
void eliminarHuella() {
  Serial.println("\n--- [MODO ELIMINACIÓN INICIADO] ---");
  Serial.println("Escribe el ID de la huella que deseas borrar de la memoria:");
  uint8_t id = leerNumeroConsola();
  
  uint8_t p = finger.deleteModel(id);
  if (p == FINGERPRINT_OK) {
    Serial.print("¡Éxito! La huella asignada al ID #"); Serial.print(id); Serial.println(" fue borrada. 🗑️");
  } else {
    Serial.print("No se pudo borrar o no existe. Código: 0x"); Serial.println(p, HEX);
  }
}

// --- COMANDO 3: VER CANITDAD DE TEMPLATES ---
void verCantidadTemplates() {
  Serial.println("\n--- [CONSULTANDO MEMORIA] ---");
  finger.getTemplateCount();
  Serial.print("-> El sensor contiene actualmente: ");
  Serial.print(finger.templateCount);
  Serial.println(" huellas guardadas.");
}

// --- COMANDO 4: EXTRAER TEMPLATE DE UNA HUELLA (NUEVA FUNCIÓN) ---
void extraerTemplateHEX() {
  Serial.println("\n--- [MODO EXTRACCIÓN DE TEMPLATE INICIADA] ---");
  Serial.println("Escribe el ID de la huella que deseas exportar (1 a 127):");
  uint8_t id = leerNumeroConsola();
  
  // Paso A: Mover la huella guardada en la Flash hacia el búfer temporal del sensor (CharBuffer1)
  uint8_t p = finger.loadModel(id);
  if (p != FINGERPRINT_OK) {
    Serial.print("❌ Error: No se pudo cargar el ID de la flash. ¿Seguro que existe esa huella? Código: 0x");
    Serial.println(p, HEX);
    return;
  }
  
  // Paso B: Solicitar al sensor que empiece la descarga de datos hacia el ESP32
  p = finger.getModel();
  if (p != FINGERPRINT_OK) {
    Serial.print("❌ El sensor rechazó la solicitud de transmisión. Código: 0x");
    Serial.println(p, HEX);
    return;
  }
  
  // Paso C: Parsear el protocolo de comunicación UART del sensor
  // El sensor envía la plantilla fragmentada en paquetes de datos envueltos en tramas de comunicación.
  // Vamos a filtrar los encabezados para imprimir únicamente la data útil (los bytes de la huella).
  
  Serial.println("\n=================================================================");
  Serial.print(" CÓDIGO HEXADECIMAL DE LA HUELLA - ID #"); Serial.println(id);
  Serial.println("=================================================================");
  
  uint32_t timeout = millis();
  bool finTransferencia = false;
  int totalBytesUtiles = 0;
  
  // Mantener el bucle abierto hasta completar el envío o por timeout físico (2 segundos)
  while (!finTransferencia && (millis() - timeout < 2000)) {
    if (mySerial.available() >= 9) { // Esperar al menos el tamaño mínimo de un encabezado de paquete
      // Buscar el byte identificador de inicio de trama del sensor (0xEF 0x01)
      if (mySerial.read() == 0xEF) {
        if (mySerial.read() == 0x01) {
          // Descartar la dirección física por defecto (4 bytes: 0xFFFFFFFF)
          mySerial.read(); mySerial.read(); mySerial.read(); mySerial.read();
          
          // Leer el identificador de tipo de paquete (PID)
          // 0x02 = Paquete con fragmento de datos intermedio. 
          // 0x08 = Último paquete de datos de la secuencia.
          uint8_t pid = mySerial.read();
          
          // Leer la longitud del paquete enviado (2 bytes)
          uint16_t len = mySerial.read();
          len <<= 8;
          len |= mySerial.read();
          
          // El campo 'len' incluye los bytes de datos más 2 bytes del checksum final.
          // Por ende, la cantidad de bytes de huella puros en este paquete es: len - 2
          uint16_t longitudDataPura = len - 2;
          
          // Esperar a que entren por el puerto serial el resto de los bytes de este bloque específico
          uint32_t esperaBuffer = millis();
          while ((mySerial.available() < len) && (millis() - esperaBuffer < 500)) {
            delay(1);
          }
          
          // Procesar e imprimir los bytes pases del template
          for (uint16_t i = 0; i < longitudDataPura; i++) {
            uint8_t byteHuella = mySerial.read();
            totalBytesUtiles++;
            
            // Forzar formateo visual a dos dígitos hexadecimales (ejemplo: '0A' en lugar de 'A')
            if (byteHuella < 16) Serial.print("0");
            Serial.print(byteHuella, HEX);
          }
          
          // Descartar los dos bytes del checksum del final del paquete de datos
          mySerial.read(); mySerial.read();
          
          // Validar si el paquete actual marcó el fin del flujo de datos
          if (pid == 0x08) {
            finTransferencia = true;
          }
          
          timeout = millis(); // Refrescar el temporizador tras procesar una trama válida
        }
      }
    }
  }
  
  Serial.println("\n=================================================================");
  if (finTransferencia) {
    Serial.print("✅ Extracción finalizada con éxito. Total data pura: ");
    Serial.print(totalBytesUtiles); Serial.println(" bytes.");
    Serial.println("ℹ️ Puedes copiar la cadena alfanumérica superior directamente.");
  } else {
    Serial.println("❌ Error crítico: Tiempo de espera agotado. Pérdida de sincronía serial.");
  }
}

// --- COMANDO 5: VACIAR SENSOR ---
void vaciarSensor() {
  Serial.println("\n🚨 🚨 🚨 ¡ALERTA DE SEGURIDAD! 🚨 🚨 🚨");
  Serial.println("Estás a punto de borrar TODAS las huellas del sensor.");
  Serial.println("Para confirmar, escribe el número secreto [99] y presiona Enter:");
  uint8_t confirmacion = leerNumeroConsola();
  
  if (confirmacion == 99) {
    uint8_t p = finger.emptyDatabase();
    if (p == FINGERPRINT_OK) {
      Serial.println("¡Operación exitosa! La base de datos del sensor está totalmente VACÍA. 🚨");
    } else {
      Serial.println("Hubo un error físico al intentar formatear la memoria.");
    }
  } else {
    Serial.println("❌ Confirmación incorrecta. Operación cancelada.");
  }
}

// --- MODO POR DEFECTO: VERIFICACIÓN ---
void verificarHuellaModoEscucha() {
  uint8_t p = finger.getImage();
  if (p != FINGERPRINT_OK) return; 

  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) return;

  p = finger.fingerFastSearch();
  if (p == FINGERPRINT_OK) {
    Serial.println("\n=========================================");
    Serial.println("   ¡¡¡HUELLA ASOCIADA IDENTIFICADA!!! ✅ ");
    Serial.print("   -> Alumno detectado (ID #): "); Serial.println(finger.fingerID);
    Serial.print("   -> Margen de confianza: "); Serial.println(finger.confidence);
    Serial.println("=========================================");
    delay(2000); 
  }
}

// Soporte Enrolamiento Secuencial
bool ejecutarProcesoEnrolamiento(uint8_t id) {
  int p = -1;
  Serial.println("Coloca el dedo en el sensor...");
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    if (p == FINGERPRINT_NOFINGER) { Serial.print("."); delay(150); }
  }
  
  p = finger.image2Tz(1);
  if (p != FINGERPRINT_OK) { Serial.println("\nError de imagen."); return false; }
  
  Serial.println("\n-> Primera muestra capturada. ¡Retira el dedo!");
  delay(2000);
  p = 0;
  while (p != FINGERPRINT_NOFINGER) { p = finger.getImage(); }
  
  p = -1;
  Serial.println("Coloca el MISMO dedo otra vez...");
  while (p != FINGERPRINT_OK) {
    p = finger.getImage();
    if (p == FINGERPRINT_NOFINGER) { Serial.print("."); delay(150); }
  }
  
  p = finger.image2Tz(2);
  if (p != FINGERPRINT_OK) { Serial.println("\nError en la segunda muestra."); return false; }
  
  p = finger.createModel();
  if (p != FINGERPRINT_OK) { Serial.println("\nLas muestras no coinciden."); return false; }
  
  p = finger.storeModel(id);
  if (p == FINGERPRINT_OK) {
    Serial.println("\n¡¡¡GUARDADO CON ÉXITO EN EL SENSOR!!! ✅");
    return true;
  } else {
    Serial.println("\nError al escribir en la memoria.");
    return false;
  }
}