-- Migración para soporte dinámico de Nodos LoRa en base de datos
-- Compatible con PostgreSQL y MySQL

-- 1. Agregar columna lora_red_id si no existe
ALTER TABLE nodos_esp32 ADD COLUMN IF NOT EXISTS lora_red_id INTEGER NOT NULL DEFAULT 18;

-- 2. Asegurar índices para búsquedas rápidas en la red LoRa
CREATE INDEX IF NOT EXISTS idx_nodos_esp32_lora ON nodos_esp32(lora_red_id, lora_id, activo);
CREATE INDEX IF NOT EXISTS idx_nodos_esp32_aula ON nodos_esp32(id_aula, activo);
