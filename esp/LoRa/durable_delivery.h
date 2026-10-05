#pragma once
#include <Arduino.h>
#include <Preferences.h>
#include "reliability_core.h"

// Cada clave es una escritura NVS individual. Nunca sobrescribir una marca pendiente.
template <size_t Capacity> class DurableDelivery {
  Preferences storage;
  String entries[Capacity];
  uint32_t next[Capacity] = {};
  uint8_t attempts[Capacity] = {};
  size_t cursor = 0;
  String key(size_t i) const { return "e" + String(i); }
public:
  bool begin(const char *name) {
    if (!storage.begin(name, false)) return false;
    for (size_t i = 0; i < Capacity; ++i) entries[i] = storage.getString(key(i).c_str(), "");
    return true;
  }
  bool enqueue(const String &payload) {
    if (!payload.length() || payload.length() > 240) return false;
    for (size_t i = 0; i < Capacity; ++i) if (entries[i] == payload) return true;
    for (size_t i = 0; i < Capacity; ++i) if (!entries[i].length()) {
      if (storage.putString(key(i).c_str(), payload) != payload.length()) return false;
      entries[i] = payload; next[i] = millis(); attempts[i] = 0; return true;
    }
    return false;
  }
  int ready(uint32_t now) {
    for (size_t n = 0; n < Capacity; ++n) {
      size_t i = cursor++ % Capacity;
      if (entries[i].length() && SiscoReliability::due(now, next[i])) return (int)i;
    }
    return -1;
  }
  const String &at(size_t i) const { return entries[i]; }
  void retry(size_t i, uint32_t now) {
    next[i] = now + SiscoReliability::backoff(attempts[i]) + (uint32_t)(esp_random() % 501);
    if (attempts[i] < 8) ++attempts[i];
  }
  bool remove(size_t i) {
    if (!storage.remove(key(i).c_str())) return false;
    entries[i] = ""; return true;
  }
  size_t count() const { size_t result = 0; for (size_t i = 0; i < Capacity; ++i) if (entries[i].length()) ++result; return result; }
  static constexpr size_t capacity() { return Capacity; }
};

// Contador reservado en NVS antes de usarlo: reiniciar sólo produce huecos.
// Epoch aleatorio conservado incluso al reprovisionar; no borrar este namespace.
inline String newSourceEventId() {
  Preferences p;
  if (!p.begin("eventseq", false)) return "";
  String epoch = p.getString("epoch", "");
  if (!epoch.length()) {
    char value[17]; snprintf(value, sizeof(value), "%08lx%08lx", (unsigned long)esp_random(), (unsigned long)esp_random());
    epoch = value;
    if (p.putString("epoch", epoch) != epoch.length()) { p.end(); return ""; }
  }
  uint64_t seq = p.getULong64("seq", 0);
  if (seq == UINT64_MAX || p.putULong64("seq", seq + 1) != sizeof(uint64_t)) { p.end(); return ""; }
  p.end();
  char value[80]; snprintf(value, sizeof(value), "%012llx-%s-%016llx", (unsigned long long)ESP.getEfuseMac(), epoch.c_str(), (unsigned long long)(seq + 1));
  return String(value);
}
