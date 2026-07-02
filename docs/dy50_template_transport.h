#pragma once

#include <Arduino.h>

namespace Dy50TemplateTransport {

constexpr size_t TEMPLATE_BYTES = 1536;
constexpr size_t DATA_PACKET_BYTES = 128;

constexpr uint8_t PID_COMMAND = 0x01;
constexpr uint8_t PID_DATA = 0x02;
constexpr uint8_t PID_ACK = 0x07;
constexpr uint8_t PID_END_DATA = 0x08;

constexpr uint8_t CMD_STORE = 0x06;
constexpr uint8_t CMD_UP_CHAR = 0x08;
constexpr uint8_t CMD_DOWN_CHAR = 0x09;

struct Packet {
  uint8_t pid = 0;
  uint16_t dataLength = 0;
  uint8_t data[DATA_PACKET_BYTES] = {0};
};

inline bool readByte(Stream &serial, uint8_t &value, uint32_t deadline) {
  while ((int32_t)(millis() - deadline) < 0) {
    if (serial.available() > 0) {
      value = static_cast<uint8_t>(serial.read());
      return true;
    }
    delay(1);
  }
  return false;
}

inline void drainInput(Stream &serial) {
  while (serial.available() > 0) {
    serial.read();
  }
}

inline bool readPacket(Stream &serial, Packet &packet, String &error,
                       uint32_t timeoutMs = 2500) {
  const uint32_t deadline = millis() + timeoutMs;
  uint8_t current = 0;
  uint8_t previous = 0;

  bool headerFound = false;
  while ((int32_t)(millis() - deadline) < 0) {
    if (!readByte(serial, current, deadline)) break;
    if (previous == 0xEF && current == 0x01) {
      headerFound = true;
      break;
    }
    previous = current;
  }

  if (!headerFound) {
    error = "TIMEOUT_HEADER";
    return false;
  }

  uint8_t address[4];
  for (uint8_t &b : address) {
    if (!readByte(serial, b, deadline)) {
      error = "TIMEOUT_ADDRESS";
      return false;
    }
  }

  uint8_t lengthHigh = 0;
  uint8_t lengthLow = 0;
  if (!readByte(serial, packet.pid, deadline) ||
      !readByte(serial, lengthHigh, deadline) ||
      !readByte(serial, lengthLow, deadline)) {
    error = "TIMEOUT_PACKET_HEADER";
    return false;
  }

  const uint16_t packetLength =
      (static_cast<uint16_t>(lengthHigh) << 8) | lengthLow;
  if (packetLength < 2) {
    error = "INVALID_PACKET_LENGTH";
    return false;
  }

  packet.dataLength = packetLength - 2;
  if (packet.dataLength > sizeof(packet.data)) {
    error = "PACKET_PAYLOAD_TOO_LARGE";
    return false;
  }

  uint16_t checksum = packet.pid + lengthHigh + lengthLow;
  for (uint16_t i = 0; i < packet.dataLength; i++) {
    if (!readByte(serial, packet.data[i], deadline)) {
      error = "TIMEOUT_PACKET_DATA";
      return false;
    }
    checksum += packet.data[i];
  }

  uint8_t checksumHigh = 0;
  uint8_t checksumLow = 0;
  if (!readByte(serial, checksumHigh, deadline) ||
      !readByte(serial, checksumLow, deadline)) {
    error = "TIMEOUT_CHECKSUM";
    return false;
  }

  const uint16_t receivedChecksum =
      (static_cast<uint16_t>(checksumHigh) << 8) | checksumLow;
  if (checksum != receivedChecksum) {
    error = "CHECKSUM_UART_INVALID";
    return false;
  }

  return true;
}

inline bool writePacket(Stream &serial, uint8_t pid, const uint8_t *data,
                        uint16_t dataLength) {
  const uint16_t packetLength = dataLength + 2;
  const uint8_t lengthHigh = packetLength >> 8;
  const uint8_t lengthLow = packetLength & 0xFF;

  const uint8_t header[] = {
      0xEF, 0x01, 0xFF, 0xFF, 0xFF, 0xFF, pid, lengthHigh, lengthLow};
  if (serial.write(header, sizeof(header)) != sizeof(header)) return false;

  uint16_t checksum = pid + lengthHigh + lengthLow;
  for (uint16_t i = 0; i < dataLength; i++) {
    if (serial.write(data[i]) != 1) return false;
    checksum += data[i];
  }

  const uint8_t checksumBytes[] = {
      static_cast<uint8_t>(checksum >> 8),
      static_cast<uint8_t>(checksum & 0xFF)};
  if (serial.write(checksumBytes, sizeof(checksumBytes)) !=
      sizeof(checksumBytes)) {
    return false;
  }

  serial.flush();
  return true;
}

inline bool readAck(Stream &serial, uint8_t &confirmationCode, String &error,
                    uint32_t timeoutMs = 2500) {
  Packet packet;
  if (!readPacket(serial, packet, error, timeoutMs)) return false;
  if (packet.pid != PID_ACK || packet.dataLength < 1) {
    error = "INVALID_ACK_PACKET";
    return false;
  }
  confirmationCode = packet.data[0];
  return true;
}

inline bool readTemplate(Stream &serial, uint8_t *output,
                         size_t outputCapacity, size_t &bytesRead,
                         String &error) {
  bytesRead = 0;
  bool endReceived = false;

  while (!endReceived) {
    Packet packet;
    if (!readPacket(serial, packet, error, 3000)) return false;
    if (packet.pid != PID_DATA && packet.pid != PID_END_DATA) {
      error = "UNEXPECTED_TEMPLATE_PACKET";
      return false;
    }
    if (bytesRead + packet.dataLength > outputCapacity) {
      error = "TEMPLATE_OVERFLOW";
      return false;
    }

    memcpy(output + bytesRead, packet.data, packet.dataLength);
    bytesRead += packet.dataLength;
    endReceived = packet.pid == PID_END_DATA;
  }

  if (bytesRead != TEMPLATE_BYTES) {
    error = "UNEXPECTED_TEMPLATE_SIZE_" + String(bytesRead);
    return false;
  }
  return true;
}

inline bool beginDownChar(Stream &serial, uint8_t charBuffer,
                          String &error) {
  drainInput(serial);
  const uint8_t command[] = {CMD_DOWN_CHAR, charBuffer};
  if (!writePacket(serial, PID_COMMAND, command, sizeof(command))) {
    error = "DOWNCHAR_COMMAND_WRITE_FAILED";
    return false;
  }

  uint8_t confirmationCode = 0xFF;
  if (!readAck(serial, confirmationCode, error)) return false;
  if (confirmationCode != 0x00) {
    error = "DOWNCHAR_REJECTED_0x" + String(confirmationCode, HEX);
    return false;
  }
  return true;
}

inline bool sendTemplate(Stream &serial, const uint8_t *templateData,
                         size_t templateLength, String &error) {
  if (templateLength != TEMPLATE_BYTES) {
    error = "INVALID_TEMPLATE_SIZE_" + String(templateLength);
    return false;
  }

  size_t offset = 0;
  while (offset < templateLength) {
    const uint16_t chunkLength = static_cast<uint16_t>(
        min(DATA_PACKET_BYTES, templateLength - offset));
    const bool isLast = offset + chunkLength == templateLength;
    if (!writePacket(serial, isLast ? PID_END_DATA : PID_DATA,
                     templateData + offset, chunkLength)) {
      error = "TEMPLATE_PACKET_WRITE_FAILED";
      return false;
    }
    offset += chunkLength;
    delay(4);
  }
  return true;
}

inline uint32_t crc32(const uint8_t *data, size_t length) {
  uint32_t crc = 0xFFFFFFFF;
  for (size_t i = 0; i < length; i++) {
    crc ^= data[i];
    for (uint8_t bit = 0; bit < 8; bit++) {
      crc = (crc >> 1) ^ (0xEDB88320UL & (0U - (crc & 1U)));
    }
  }
  return crc ^ 0xFFFFFFFF;
}

inline bool decodeHex(const String &hex, uint8_t *output,
                      size_t outputCapacity, size_t &bytesDecoded,
                      String &error) {
  bytesDecoded = 0;
  if ((hex.length() & 1U) != 0) {
    error = "HEX_ODD_LENGTH";
    return false;
  }
  if (hex.length() / 2 > outputCapacity) {
    error = "HEX_OUTPUT_OVERFLOW";
    return false;
  }

  auto nibble = [](char c) -> int8_t {
    if (c >= '0' && c <= '9') return c - '0';
    if (c >= 'a' && c <= 'f') return c - 'a' + 10;
    if (c >= 'A' && c <= 'F') return c - 'A' + 10;
    return -1;
  };

  for (size_t i = 0; i < hex.length(); i += 2) {
    const int8_t high = nibble(hex[i]);
    const int8_t low = nibble(hex[i + 1]);
    if (high < 0 || low < 0) {
      error = "INVALID_HEX_CHARACTER";
      return false;
    }
    output[bytesDecoded++] = static_cast<uint8_t>((high << 4) | low);
  }
  return true;
}

inline String encodeHex(const uint8_t *data, size_t length) {
  static const char HEX[] = "0123456789abcdef";
  String output;
  output.reserve(length * 2);
  for (size_t i = 0; i < length; i++) {
    output += HEX[data[i] >> 4];
    output += HEX[data[i] & 0x0F];
  }
  return output;
}

}  // namespace Dy50TemplateTransport
