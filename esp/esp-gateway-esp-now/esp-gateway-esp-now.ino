// REEMPLAZAR CON LA MAC DEL ESP32 RECEPTOR (AULA) B0:CB:D8:8E:72:74
// uint8_t broadcastAddress[] = {0xB0, 0xCB, 0xD8, 0x8E, 0x72, 0x74};
// Código para el ESP Emisor usando ESP-Now 

#include <esp_now.h>
#include <WiFi.h>

// REEMPLAZAR CON LOS BYTES DE LA MAC DEL ESP32 RECEPTOR (AULA)
uint8_t broadcastAddress[] = {  };  

typedef struct struct_message {
  char comando[32];
} struct_message;

struct_message miMensaje;

void OnDataSent(const wifi_tx_info_t *tx_info, esp_now_send_status_t status) {
  Serial.print("\r\nEstado del ultimo envio: ");
  Serial.println(status == ESP_NOW_SEND_SUCCESS ? "¡Entregado OK!" : "Fallo en la entrega");
}

void setup() {
  Serial.begin(115200);
 
  WiFi.mode(WIFI_STA);

  if (esp_now_init() != ESP_OK) {
    Serial.println("Error inicializando ESP-NOW");
    return;
  }

  esp_now_register_send_cb(OnDataSent);
  
  // 1. Primero declaramos la estructura
  esp_now_peer_info_t peerInfo;
  
  // 2. Ahora sí la limpiamos de forma segura en memoria
  memset(&peerInfo, 0, sizeof(peerInfo));
  
  // 3. Cargamos los datos de configuración
  memcpy(peerInfo.peer_addr, broadcastAddress, 6);
  peerInfo.channel = 0;  
  peerInfo.encrypt = false;
  peerInfo.ifidx = WIFI_IF_STA; // Interfaz obligatoria para Core 3.x
  
  if (esp_now_add_peer(&peerInfo) != ESP_OK){
    Serial.println("Fallo al añadir el receptor");
    return;
  }
}
 
void loop() {
  strcpy(miMensaje.comando, "LED_ON");
  
  esp_err_t result = esp_now_send(broadcastAddress, (uint8_t *) &miMensaje, sizeof(miMensaje));
   
  if (result == ESP_OK) {
    Serial.println("Enviando paquete por ESP-NOW...");
  } else {
    Serial.println("Error al enviar");
  }
  delay(4000);
}