-- Configuración administrable de nodos ESP32 y rutas LoRa.
-- Compatible con la tabla existente; no elimina ni migra datos históricos.
ALTER TABLE nodos_esp32
    ADD COLUMN IF NOT EXISTS lora_id INT NULL AFTER node_id;

ALTER TABLE nodos_esp32
    ADD INDEX IF NOT EXISTS idx_nodos_esp32_room_activo (room_id, activo),
    ADD INDEX IF NOT EXISTS idx_nodos_esp32_lora_activo (lora_id, activo);
