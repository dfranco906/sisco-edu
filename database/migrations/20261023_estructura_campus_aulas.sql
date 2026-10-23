-- ============================================================================
-- SISCO-EDU: GESTIÓN DE ESTRUCTURA INSTITUCIONAL (BLOQUES / EDIFICIOS Y PISOS)
-- ============================================================================

-- 1. Agregar columna bloque a la tabla aulas
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS bloque VARCHAR(50) DEFAULT 'Bloque A';
CREATE INDEX IF NOT EXISTS idx_aulas_bloque_piso ON aulas(bloque, piso);

-- 2. Asegurar llaves de configuración de bloques y pisos en configuraciones_sistema
INSERT INTO configuraciones_sistema (clave, valor, categoria, tipo, descripcion)
VALUES 
  ('campus_bloques', '[{"id":"A","nombre":"Bloque A"},{"id":"B","nombre":"Bloque B"}]', 'campus', 'json', 'Lista de bloques o edificios de la institución'),
  ('campus_pisos', '[{"id":"PB","nombre":"Planta Baja","numero":0},{"id":"P1","nombre":"1° Piso","numero":1},{"id":"P2","nombre":"2° Piso","numero":2},{"id":"P3","nombre":"3° Piso","numero":3}]', 'campus', 'json', 'Lista de pisos o niveles de la institución')
ON CONFLICT (clave) DO NOTHING;

-- 3. Sincronizar codigo donde esté nulo pero exista codigo_sala
UPDATE aulas SET codigo = codigo_sala WHERE (codigo IS NULL OR codigo = '') AND codigo_sala IS NOT NULL;
