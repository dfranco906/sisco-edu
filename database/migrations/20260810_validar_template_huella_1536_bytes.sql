-- Normaliza la integridad de templates biométricos activos.
-- El transporte vigente usa 1536 bytes codificados como 3072 caracteres HEX.

START TRANSACTION;

UPDATE sync_biometrica s
INNER JOIN huellas_templates h ON h.id_huella = s.id_huella
SET s.estado = 'ERROR',
    s.mensaje = 'Template incompatible: requiere nueva captura de 1536 bytes',
    s.fecha_actualizacion = NOW()
WHERE s.estado IN ('PENDIENTE', 'ENVIADO')
  AND (
      UPPER(COALESCE(h.formato, '')) <> 'HEX'
      OR CHAR_LENGTH(h.fingerprint_data) <> 3072
      OR h.fingerprint_data NOT REGEXP '^[0-9A-Fa-f]{3072}$'
  );

UPDATE huellas_templates
SET activo = 0,
    pendiente_sync = 0
WHERE activo = 1
  AND (
      UPPER(COALESCE(formato, '')) <> 'HEX'
      OR CHAR_LENGTH(fingerprint_data) <> 3072
      OR fingerprint_data NOT REGEXP '^[0-9A-Fa-f]{3072}$'
  );

UPDATE huellas_templates
SET fingerprint_data = LOWER(fingerprint_data),
    formato = 'HEX'
WHERE activo = 1
  AND UPPER(formato) = 'HEX'
  AND CHAR_LENGTH(fingerprint_data) = 3072
  AND fingerprint_data REGEXP '^[0-9A-Fa-f]{3072}$';

COMMIT;

SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'huellas_templates'
      AND CONSTRAINT_NAME = 'chk_huella_template_activa_1536'
);

SET @constraint_sql = IF(
    @constraint_exists = 0,
    'ALTER TABLE huellas_templates ADD CONSTRAINT chk_huella_template_activa_1536 CHECK (activo = 0 OR (formato = ''HEX'' AND CHAR_LENGTH(fingerprint_data) = 3072 AND fingerprint_data REGEXP ''^[0-9A-Fa-f]{3072}$''))',
    'SELECT ''La restriccion chk_huella_template_activa_1536 ya existe'''
);

PREPARE constraint_statement FROM @constraint_sql;
EXECUTE constraint_statement;
DEALLOCATE PREPARE constraint_statement;
