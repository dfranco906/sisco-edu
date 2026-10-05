#pragma once
#include <stdint.h>
#include <stddef.h>
#include <string.h>

namespace SiscoReliability {
inline uint32_t backoff(uint8_t attempts) {
  return attempts >= 5 ? 60000UL : (2000UL << attempts);
}
inline bool due(uint32_t now, uint32_t next) { return (int32_t)(now - next) >= 0; }
inline bool radioValid(int self, int gateway, int network) {
  return self > 0 && self <= 65535 && gateway > 0 && gateway <= 65535 && self != gateway && ((network >= 3 && network <= 15) || network == 18);
}
inline bool identifierValid(const char *value, size_t max) {
  if (!value || !*value || strlen(value) > max) return false;
  for (const char *p = value; *p; ++p)
    if (!((*p >= 'a' && *p <= 'z') || (*p >= 'A' && *p <= 'Z') || (*p >= '0' && *p <= '9') || *p == '_' || *p == '-')) return false;
  return true;
}
}
