-- Migración: Asegurar restricción única en tabla matriculas para inserción ON CONFLICT
-- Fecha: 2026-10-29

CREATE UNIQUE INDEX IF NOT EXISTS uq_estudiante_materia_anho 
    ON public.matriculas (id_estudiante, id_materia, anho_lectivo);

-- Índices de aceleración para consultas masivas de nómina y materias
CREATE INDEX IF NOT EXISTS idx_matriculas_estudiante 
    ON public.matriculas (id_estudiante);

CREATE INDEX IF NOT EXISTS idx_matriculas_grado 
    ON public.matriculas (id_grado);

CREATE INDEX IF NOT EXISTS idx_matriculas_materia 
    ON public.matriculas (id_materia);

CREATE INDEX IF NOT EXISTS idx_matriculas_est_grado_materia 
    ON public.matriculas (id_estudiante, id_grado, id_materia, anho_lectivo);
