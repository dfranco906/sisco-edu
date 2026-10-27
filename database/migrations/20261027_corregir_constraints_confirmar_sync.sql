-- ============================================================================
-- SISCO-EDU: CORRECCIÓN DE RESTRICCIONES Y CAMPOS PARA CONFIRMACIÓN DE ENTREGA
-- ============================================================================

-- 1. Restricción UNIQUE en aula_huellas_sync(id_sync) para PostgreSQL
DO $$ 
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'uq_aula_huellas_sync_id_sync') THEN
        ALTER TABLE aula_huellas_sync ADD CONSTRAINT uq_aula_huellas_sync_id_sync UNIQUE (id_sync);
    END IF;
END $$;

-- 2. Asegurar campos en biometric_mapping
ALTER TABLE biometric_mapping ADD COLUMN IF NOT EXISTS id_aula INT;
ALTER TABLE biometric_mapping ALTER COLUMN room_id DROP NOT NULL;
