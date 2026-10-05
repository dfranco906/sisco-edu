-- Aditiva. Probar primero en BD temporal. No toca asistencias históricas.
ALTER TABLE eventos_asistencia
    ADD COLUMN IF NOT EXISTS source_event_id VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin NULL,
    ADD UNIQUE INDEX IF NOT EXISTS uq_eventos_source_event (source_event_id);

CREATE TABLE IF NOT EXISTS gateway_delivery_receipts (
    source_event_id VARCHAR(80) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    payload_hash CHAR(64) CHARACTER SET ascii NOT NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    response_json MEDIUMTEXT NULL
) ENGINE=InnoDB;
