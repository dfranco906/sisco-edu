// CODIGO ESP AULA-A (con sensor de huella DY50 y pantalla oled 0.96''

#include <esp_now.h>
#include <WiFi.h>
#include <esp_wifi.h>
#include <Adafruit_Fingerprint.h>
#include <Adafruit_SSD1306.h>
#include <Adafruit_GFX.h>

// ============================================================================
// CONFIGURACIÓN DE HARDWARE
// ============================================================================
String ROOM_ID = "AULA_A";
uint8_t gatewayMac[] = {0xD4, 0xE9, 0xF4, 0xE6, 0xD6, 0xF4}; // MAC real del Gateway

// Pines Sensor DY50 (UART2)
HardwareSerial dy50Serial(2);
Adafruit_Fingerprint finger = Adafruit_Fingerprint(&dy50Serial);

// Pines OLED 0.96
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
Adafruit_SSD1306 oled(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, -1);

// Botón de Salida
#define PIN_BOTON_SALIDA 4

// Máquina de Estados
enum EstadoSistema { NORMAL, ESPERANDO_PROFE, SALIDA_ACTIVA };
EstadoSistema estadoActual = NORMAL;
uint32_t tiempoLimiteEstado = 0;

// ============================================================================
// ESTRUCTURAS DE DATOS
// ============================================================================
typedef struct {
  int id_sync;
  int id_huella;
  int total_chunks;
  int chunk_index;
  char ci[15];
  char room_id[16];
  char tipo_persona[15];
  char data[161];
} PaqueteHuella;

typedef struct {
  char room_id[16];
  char ci[15];
  char tipo_persona[15];
  char estado[15];
  char fecha_hora[20];
} PaqueteAsistencia;

typedef struct { char msg[4]; } PaquetePing;

struct RegistroUsuario {
  char ci[15];
  char tipo_persona[15]; // "ALUMNO", "PROFESOR", "COORDINADOR"
  bool registrado = false;
};
RegistroUsuario dbLocal[201];

uint32_t ultimoRegistroSlot[201] = {0};
const uint32_t TIEMPO_COOLDOWN = 300000; // 5 min

String huellaReconstruida = "";
int chunkEsperado = 0;
volatile bool pingAckRecibido = false;
volatile bool txStatusStatus = false;

// ============================================================================
// FUNCIONES DE PANTALLA OLED
// ============================================================================
void msgOled(String t1, String t2 = "") {
  oled.clearDisplay();
  oled.setCursor(0, 10);
  oled.setTextSize(2); // Texto mediano/grande
  oled.println(t1);
  oled.setTextSize(1);
  oled.setCursor(0, 45);
  oled.println(t2);
  oled.display();
}

// ============================================================================
// CALLBACKS ESP-NOW
// ============================================================================
void onSend(const wifi_tx_info_t *tx_info, esp_now_send_status_t status) {
  txStatusStatus = true;
  pingAckRecibido = (status == ESP_NOW_SEND_SUCCESS);
}

void onReceive(const esp_now_recv_info_t *recv_info, const uint8_t *incomingData, int len) {
  if (len != sizeof(PaqueteHuella)) return;

  PaqueteHuella paquete;
  memcpy(&paquete, incomingData, sizeof(paquete));
  if (String(paquete.room_id) != ROOM_ID) return;

  if (paquete.chunk_index == 0) {
    huellaReconstruida = "";
    chunkEsperado = 0;
    msgOled("SYNC...", "CI: " + String(paquete.ci));
  }

  if (paquete.chunk_index == chunkEsperado) {
    huellaReconstruida += String(paquete.data);
    chunkEsperado++;
  } else { return; }

  if (paquete.chunk_index == paquete.total_chunks - 1) {
    int slotAsignado = paquete.id_huella;
   
    // Aquí iría el guardado físico real: finger.storeModel(slotAsignado)
    // Usamos el flujo exitoso para la integración lógica
    strcpy(dbLocal[slotAsignado].ci, paquete.ci);
    strcpy(dbLocal[slotAsignado].tipo_persona, paquete.tipo_persona);
    dbLocal[slotAsignado].registrado = true;

    msgOled("SYNC OK", "Slot: " + String(slotAsignado));
   
    huellaReconstruida = String();// Reinicializa el objeto desde cero vaciando su buffer
    delay(1000);
    msgOled("AULA: " + ROOM_ID, "Listo...");
  }
}

// ============================================================================
// AUTO-CANAL (Plug & Play)
// ============================================================================
void escanearYFijarCanalGateway() {
  msgOled("BUSCANDO", "Canal Gateway...");
  PaquetePing ping = {"PNG"};
  bool encontrado = false;

  for (uint8_t canal = 1; canal <= 11; canal++) {
    esp_wifi_set_channel(canal, WIFI_SECOND_CHAN_NONE);
    delay(40);
    txStatusStatus = false;
    pingAckRecibido = false;
    esp_now_send(gatewayMac, (uint8_t*)&ping, sizeof(ping));
   
    uint32_t t0 = millis();
    while (!txStatusStatus && (millis() - t0 < 100)) { delay(1); }

    if (pingAckRecibido) {
      msgOled("CANAL: " + String(canal), "Enlace OK");
      encontrado = true;
      delay(1000);
      break;
    }
  }
  if (!encontrado) {
    msgOled("ERROR", "No hay Gateway");
    esp_wifi_set_channel(1, WIFI_SECOND_CHAN_NONE);
  }
}

// ============================================================================
// PROCESAMIENTO BIOMÉTRICO
// ============================================================================
void verificarLecturaHuella() {
  int status = finger.getImage();
  if (status != FINGERPRINT_OK) return;

  status = finger.image2Tz();
  if (status != FINGERPRINT_OK) return;

  status = finger.fingerFastSearch();
  if (status != FINGERPRINT_OK) {
    msgOled("ERROR", "No reconocido");
    delay(1500);
    msgOled("AULA: " + ROOM_ID, "Listo...");
    return;
  }

  int slotMatch = finger.fingerID;
  if (!dbLocal[slotMatch].registrado) {
    msgOled("ERROR", "Sin datos RAM");
    delay(1500);
    return;
  }

  String rol = String(dbLocal[slotMatch].tipo_persona);

  // LOGICA DE ESTADOS
  if (estadoActual == ESPERANDO_PROFE) {
    if (rol == "PROFESOR" || rol == "COORDINADOR") {
      estadoActual = SALIDA_ACTIVA;
      tiempoLimiteEstado = millis() + 20000;
      msgOled("AUTORIZADO", "Ponga dedo Alumno");
    } else {
      msgOled("ERROR", "No es Profe");
      delay(1500);
      estadoActual = NORMAL;
      msgOled("AULA: " + ROOM_ID);
    }
    return;
  }

  if (estadoActual == NORMAL && (millis() - ultimoRegistroSlot[slotMatch] < TIEMPO_COOLDOWN)) {
    msgOled("YA MARCADO", dbLocal[slotMatch].ci);
    delay(1500);
    msgOled("AULA: " + ROOM_ID);
    return;
  }

  String estadoEnvio = (estadoActual == SALIDA_ACTIVA) ? "RET_ANTICIPADO" : "PRESENTE";

  PaqueteAsistencia asistencia = {};
  ROOM_ID.toCharArray(asistencia.room_id, sizeof(asistencia.room_id));
  strcpy(asistencia.ci, dbLocal[slotMatch].ci);
  strcpy(asistencia.tipo_persona, dbLocal[slotMatch].tipo_persona);
  estadoEnvio.toCharArray(asistencia.estado, sizeof(asistencia.estado));
  strcpy(asistencia.fecha_hora, "AUTO");

  esp_err_t res = esp_now_send(gatewayMac, (uint8_t*)&asistencia, sizeof(asistencia));

  if (res == ESP_OK) {
    msgOled("ENVIADO", String(asistencia.ci));
    ultimoRegistroSlot[slotMatch] = millis();
    if (estadoActual == SALIDA_ACTIVA) estadoActual = NORMAL;
  } else {
    msgOled("ERROR TX", "Sin respuesta GW");
  }

  delay(2000);
  msgOled("AULA: " + ROOM_ID, "Listo...");
}

// ============================================================================
// SETUP
// ============================================================================
void setup() {
  Serial.begin(115200);
 
  // Inicializar OLED
  if(!oled.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    for(;;);
  }
  oled.setTextColor(SSD1306_WHITE);
  msgOled("INICIANDO", "Sisco-Edu v1.0");

  pinMode(PIN_BOTON_SALIDA, INPUT_PULLUP);

  WiFi.mode(WIFI_STA);
  esp_wifi_start();

  delay(1500); // Pausa de cortesía para que el monitor serial de tu PC se estabilice
  Serial.print("DIRECCION MAC FISICA DE ESTA AULA: ");
  Serial.println(WiFi.macAddress());

  if (esp_now_init() != ESP_OK) return;

  esp_now_register_send_cb(onSend);
  esp_now_register_recv_cb(onReceive);

  esp_now_peer_info_t peerInfo = {};
  memset(&peerInfo, 0, sizeof(peerInfo));
  memcpy(peerInfo.peer_addr, gatewayMac, 6);
  peerInfo.ifidx = WIFI_IF_STA;
  esp_now_add_peer(&peerInfo);

  escanearYFijarCanalGateway();

  dy50Serial.begin(57600, SERIAL_8N1, 16, 17); // Forzamos al hardware a usar los pines 16 (tx) y 17 (rx)
  finger.begin(57600); // Vinculamos la librería al puerto ya configurado
  if (finger.verifyPassword()) {
    msgOled("DY50: OK", "Sistema Listo");
  } else {
    msgOled("ERROR", "DY50 no hallado");
  }
  delay(1500);
  msgOled("AULA: " + ROOM_ID, "Esperando huellas");
}

// ============================================================================
// LOOP PRINCIPAL
// ============================================================================
void loop() {
  // 1. Botón presionado para Salida Anticipada
  if (digitalRead(PIN_BOTON_SALIDA) == LOW && estadoActual == NORMAL) {
    estadoActual = ESPERANDO_PROFE;
    tiempoLimiteEstado = millis() + 15000;
    msgOled("SALIDA", "Dedo de Profe...");
    delay(500);
  }

  // 2. Timeout de estados
  if (estadoActual != NORMAL && millis() > tiempoLimiteEstado) {
    estadoActual = NORMAL;
    msgOled("CANCELADO", "Tiempo expirado");
    delay(1500);
    msgOled("AULA: " + ROOM_ID, "Listo...");
  }

  // 3. Sensor Biométrico
  verificarLecturaHuella();
 
  delay(30);
}