-- ============================================================================
-- MIGRACIÓN: Agregar id_grado a marcos_horarios_institucionales
-- Permite excepciones por curso específico (ej. BTI con escolaridad extendida
-- variable según año lectivo: 1° BTI Viernes 14:10, 2° BTI Martes 16:00, 3° BTI Lunes/Jueves 15:30)
-- ============================================================================

ALTER TABLE public.marcos_horarios_institucionales 
ADD COLUMN IF NOT EXISTS id_grado INTEGER REFERENCES public.grados(id_grado) ON DELETE CASCADE;

-- Recrear el índice de unicidad incorporando id_grado
DROP INDEX IF EXISTS public.uq_marcos_horarios_combo;

CREATE UNIQUE INDEX IF NOT EXISTS uq_marcos_horarios_combo
ON public.marcos_horarios_institucionales (
    dia_semana, 
    id_turno, 
    COALESCE(id_nivel, 0), 
    COALESCE(id_bachillerato, 0),
    COALESCE(id_grado, 0)
)
WHERE activo = 1;

-- Índice para acelerar la búsqueda por grado
CREATE INDEX IF NOT EXISTS idx_marcos_horarios_grado 
ON public.marcos_horarios_institucionales (id_grado)
WHERE activo = 1 AND id_grado IS NOT NULL;
