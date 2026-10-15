/***************************************************
  SISCO-EDU: Sistema de Control Biométrico Interactivos
  Modo escucha dinámico con interrupción por comandos de consola.
  Placa: ESP32D | Sensor: DY50/AS608 (Pines 16 RX, 17 TX)
 ****************************************************/

#include <Adafruit_Fingerprint.h>

// Usamos la UART2 por hardware del ESP32
HardwareSerial mySerial(2);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&mySerial);

void setup() {
  Serial.begin(115200);
  while (!Serial); 
  delay(2000); // Tiempo de cortesía para el sensor
  
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

  // Imprimir el menú por primera vez
  mostrarMenu();
}

void loop() {
  // 1. Verificar si el usuario envió un comando desde la computadora
  if (Serial.available() > 0) {
    char comando = Serial.read();
    
    // Limpiar caracteres basura como saltos de línea (\n o \r)
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
        vaciarSensor();
        mostrarMenu();
        break;
      case '5':
        mostrarMenu();
        break;
      default:
        Serial.println("\n⚠️ Comando no válido. Presiona [5] para ver las opciones.");
        break;
    }
  }

  // 2. Modo Escucha por Defecto (Verificación continua no bloqueante)
  verificarHuellaModoEscucha();
  delay(50); // Pequeña pausa para estabilidad
}

// =========================================================================
// FUNCIONES INTERACTIVAS DEL SISTEMA
// =========================================================================

// Muestra el menú de opciones disponibles
void mostrarMenu() {
  Serial.println("\n=========================================");
  Serial.println("      MENÚ DE COMANDOS DISPONIBLES       ");
  Serial.println("=========================================");
  Serial.println("[1] Registrar nueva huella (Enroll)");
  Serial.println("[2] Eliminar una huella específica");
  Serial.println("[3] Ver cantidad de huellas guardadas");
  Serial.println("[4] VACIAR por completo el sensor (🚨 Peligro)");
  Serial.println("[5] Volver a mostrar este menú");
  Serial.println("=========================================");
  Serial.println("📡 [MODO ESCUCHA ACTIVO] Coloca tu dedo para marcar asistencia...\n");
}

// Función auxiliar robusta para leer un número entero desde el Monitor Serial
uint8_t leerNumeroConsola(void) {
  uint8_t num = 0;
  while (num == 0) {
    while (!Serial.available());
    num = Serial.parseInt();
    
    // Limpiar buffers restantes
    while(Serial.available() > 0) { Serial.read(); }
  }
  return num;
}

// --- COMANDO 1: REGISTRAR HUELLA ---
void registrarHuella() {
  Serial.println("\n--- [MODO REGISTRO INICIADO] ---");
  Serial.println("Escribe el número de ID (de 1 a 127) para este alumno y presiona Enter:");
  uint8_t id = leerNumeroConsola();
  
  Serial.print("Registrando en el ID #"); Serial.println(id);
  
  // Ejecuta la lógica secuencial de guardado
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
  } else if (p == FINGERPRINT_BADLOCATION) {
    Serial.println("No existe ninguna huella grabada en esa posición.");
  } else {
    Serial.print("Error al intentar borrar la huella. Código: 0x"); Serial.println(p, HEX);
  }
}

// --- COMANDO 3: VER TEMPLATES ALMACENADOS ---
void verCantidadTemplates() {
  Serial.println("\n--- [CONSULTANDO MEMORIA] ---");
  finger.getTemplateCount();
  Serial.print("-> El sensor contiene actualmente: ");
  Serial.print(finger.templateCount);
  Serial.println(" huellas guardadas en su flash local.");
}

// --- COMANDO 4: VACIAR SENSOR ---
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
    Serial.println("❌ Confirmación incorrecta. Operación cancelada de forma segura.");
  }
}

// --- MODO POR DEFECTO: VERIFICACIÓN ---
void verificarHuellaModoEscucha() {
  uint8_t p = finger.getImage();
  if (p != FINGERPRINT_OK) return; // Si no hay dedo, sale de la función en milisegundos

  p = finger.image2Tz();
  if (p != FINGERPRINT_OK) {
    Serial.println("❌ Error: No se capturaron bien los rasgos. Intenta de nuevo.");
    delay(1000);
    return;
  }

  p = finger.fingerFastSearch();
  if (p == FINGERPRINT_OK) {
    Serial.println("\n=========================================");
    Serial.println("   ¡¡¡HUELLA ASOCIADA IDENTIFICADA!!! ✅ ");
    Serial.print("   -> Alumno detectado (ID #): "); Serial.println(finger.fingerID);
    Serial.print("   -> Margen de confianza: "); Serial.println(finger.confidence);
    Serial.println("=========================================");
    delay(2000); // Evita lecturas repetidas instantáneas del mismo dedo
  } else if (p == FINGERPRINT_NOTFOUND) {
    Serial.println("\n=========================================");
    Serial.println("   ❌ ACCESO DENEGADO: Usuario no registrado ");
    Serial.println("=========================================");
    delay(1500);
  }
}

// Lógica de soporte del comando 1 (Enrolamiento)
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