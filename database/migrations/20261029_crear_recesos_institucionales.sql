-- ============================================================================
-- MIGRACIÓN: Tabla de Recesos y Pausas Institucionales Deterministas
-- Permite parametrizar recesos generales y pausas pedagógicas / almuerzos
-- específicos por curso (ej: 3° BTI Lunes, Martes y Jueves 12:10 a 12:50;
-- 2° BTI Lunes y Miércoles 13:30 a 14:00; Receso común 10:10 a 10:50).
-- ============================================================================

CREATE TABLE IF NOT EXISTS public.recesos_institucionales (
    id_receso SERIAL PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    dia_semana VARCHAR(20) NOT NULL,
    id_turno INTEGER NOT NULL REFERENCES public.turnos(id_turno) ON DELETE CASCADE,
    id_nivel INTEGER REFERENCES public.niveles_academicos(id_nivel) ON DELETE SET NULL,
    id_bachillerato INTEGER REFERENCES public.bachilleratos(id_bachillerato) ON DELETE CASCADE,
    id_grado INTEGER REFERENCES public.grados(id_grado) ON DELETE CASCADE,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    descripcion VARCHAR(255),
    activo SMALLINT NOT NULL DEFAULT 1,
    fecha_registro TIMESTAMPTZ DEFAULT NOW(),
    fecha_actualizacion TIMESTAMPTZ DEFAULT NOW(),
    CONSTRAINT chk_receso_horario CHECK (hora_inicio < hora_fin)
);

-- Índices de consulta rápida
CREATE INDEX IF NOT EXISTS idx_recesos_dia_turno 
ON public.recesos_institucionales (dia_semana, id_turno) 
WHERE activo = 1;

CREATE INDEX IF NOT EXISTS idx_recesos_grado 
ON public.recesos_institucionales (id_grado) 
WHERE activo = 1 AND id_grado IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_recesos_bachi 
ON public.recesos_institucionales (id_bachillerato) 
WHERE activo = 1 AND id_bachillerato IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_recesos_nivel 
ON public.recesos_institucionales (id_nivel) 
WHERE activo = 1 AND id_nivel IS NOT NULL;
