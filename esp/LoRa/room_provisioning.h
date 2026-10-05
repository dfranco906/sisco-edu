#pragma once
#include <Arduino.h>
#include <ArduinoJson.h>
#include <Preferences.h>
#include "reliability_core.h"

struct RoomConfig {
  uint32_t version = 1;
  int id_aula = 0, lora_id = 0, gateway_lora_id = 0, network_id = 0;
  char codigo[51] = {};
};
inline bool roomConfigValid(const RoomConfig &c) {
  return c.version == 1 && c.id_aula > 0 && c.codigo[50] == 0 && SiscoReliability::identifierValid(c.codigo, 50)
    && SiscoReliability::radioValid(c.lora_id, c.gateway_lora_id, c.network_id);
}
inline bool loadRoomConfig(RoomConfig &c) {
  Preferences p; if (!p.begin("roomconfig", true)) return false;
  bool ok = p.getBytesLength("config") == sizeof(c) && p.getBytes("config", &c, sizeof(c)) == sizeof(c);
  p.end(); return ok && roomConfigValid(c);
}
inline bool provisionRoom(const String &json, RoomConfig &c) {
  RoomConfig previous;
  if (loadRoomConfig(previous)) return false; // una sola vez, acceso serie físico
  DynamicJsonDocument doc(1024);
  if (deserializeJson(doc, json)) return false;
  RoomConfig value;
  value.id_aula = doc["id_aula"] | 0; value.lora_id = doc["lora_id"] | 0;
  value.gateway_lora_id = doc["gateway_lora_id"] | 0; value.network_id = doc["network_id"] | 0;
  const char *code = doc["codigo"] | "";
  if (strlen(code) > 50) return false;
  strlcpy(value.codigo, code, sizeof(value.codigo));
  if (!roomConfigValid(value)) return false;
  Preferences p; if (!p.begin("roomconfig", false)) return false;
  bool ok = p.putBytes("config", &value, sizeof(value)) == sizeof(value); p.end();
  if (ok) c = value; return ok;
}
