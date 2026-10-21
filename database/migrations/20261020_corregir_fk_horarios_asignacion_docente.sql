-- ==============================================================================
-- Migración: Corregir Foreign Keys de Horarios y Grados Institucionales
-- 1. Horarios: horarios.id_asignacion apuntaba a la tabla legacy 'asignaciones'.
--    Se redirige a 'asignacion_docente(id_asignacion)' y se hace nullable para recesos.
-- 2. Grados: Se crean las claves foráneas con turnos, niveles_academicos y bachilleratos.
-- Fecha: 2026-10-20
-- ==============================================================================

-- 1. Horarios -> asignacion_docente
ALTER TABLE horarios DROP CONSTRAINT IF EXISTS horarios_id_asignacion_fkey;

ALTER TABLE horarios ALTER COLUMN id_asignacion DROP NOT NULL;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_horarios_asignacion_docente') THEN
        ALTER TABLE horarios 
            ADD CONSTRAINT fk_horarios_asignacion_docente 
            FOREIGN KEY (id_asignacion) REFERENCES asignacion_docente(id_asignacion) 
            ON DELETE CASCADE;
    END IF;
END $$;

-- 2. Grados -> turnos, niveles_academicos, bachilleratos
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_grados_turnos') THEN
        ALTER TABLE grados 
            ADD CONSTRAINT fk_grados_turnos 
            FOREIGN KEY (id_turno) REFERENCES turnos(id_turno) 
            ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_grados_niveles') THEN
        ALTER TABLE grados 
            ADD CONSTRAINT fk_grados_niveles 
            FOREIGN KEY (id_nivel) REFERENCES niveles_academicos(id_nivel) 
            ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_grados_bachilleratos') THEN
        ALTER TABLE grados 
            ADD CONSTRAINT fk_grados_bachilleratos 
            FOREIGN KEY (id_bachillerato) REFERENCES bachilleratos(id_bachillerato) 
            ON DELETE SET NULL;
    END IF;
END $$;
