-- ============================================================================
-- MIGRACIÓN: Marcos Horarios Institucionales por Turno, Nivel y Bachillerato
-- Fecha: 2026-10-29
-- Descripción:
--   Crea la tabla marcos_horarios_institucionales para parametrizar las ventanas
--   oficiales de la jornada escolar (apertura, inicio de clases, tolerancia para
--   tardanzas y hora de cierre). Permite configuraciones generales por turno y
--   excepciones específicas por bachillerato (ej. BTI con salida a las 13:30 hs).
-- ============================================================================

CREATE TABLE IF NOT EXISTS public.marcos_horarios_institucionales (
    id_marco SERIAL PRIMARY KEY,
    dia_semana VARCHAR(20) NOT NULL,
    id_turno INTEGER NOT NULL REFERENCES public.turnos(id_turno) ON DELETE CASCADE,
    id_nivel INTEGER REFERENCES public.niveles_academicos(id_nivel) ON DELETE SET NULL,
    id_bachillerato INTEGER REFERENCES public.bachilleratos(id_bachillerato) ON DELETE CASCADE,
    hora_apertura TIME NOT NULL,
    hora_inicio TIME NOT NULL,
    tolerancia_minutos INTEGER NOT NULL DEFAULT 15,
    hora_cierre TIME NOT NULL,
    descripcion VARCHAR(255),
    activo SMALLINT NOT NULL DEFAULT 1,
    fecha_registro TIMESTAMPTZ DEFAULT NOW(),
    fecha_actualizacion TIMESTAMPTZ DEFAULT NOW(),
    CONSTRAINT chk_marcos_horarios_apertura_inicio CHECK (hora_apertura <= hora_inicio),
    CONSTRAINT chk_marcos_horarios_inicio_cierre CHECK (hora_inicio < hora_cierre)
);

-- Índice de unicidad para evitar marcos redundantes en un mismo día/turno/nivel/bachillerato
CREATE UNIQUE INDEX IF NOT EXISTS uq_marcos_horarios_combo
ON public.marcos_horarios_institucionales (
    dia_semana, 
    id_turno, 
    COALESCE(id_nivel, 0), 
    COALESCE(id_bachillerato, 0)
)
WHERE activo = 1;

-- Índices de aceleración para consultas en tiempo real
CREATE INDEX IF NOT EXISTS idx_marcos_horarios_dia_turno 
ON public.marcos_horarios_institucionales (dia_semana, id_turno)
WHERE activo = 1;

CREATE INDEX IF NOT EXISTS idx_marcos_horarios_bachillerato 
ON public.marcos_horarios_institucionales (id_bachillerato)
WHERE activo = 1 AND id_bachillerato IS NOT NULL;

-- Semillero inicial de marcos horarios estándar (Lunes a Viernes)
-- 1. Turno Mañana General (07:00 a 12:00)
INSERT INTO public.marcos_horarios_institucionales 
    (dia_semana, id_turno, id_nivel, id_bachillerato, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion)
SELECT 
    d.dia, 1, NULL, NULL, '06:45:00'::TIME, '07:00:00'::TIME, 15, '12:00:00'::TIME, 'Jornada Matutina Estándar'
FROM (VALUES ('Lunes'), ('Martes'), ('Miercoles'), ('Jueves'), ('Viernes')) AS d(dia)
ON CONFLICT DO NOTHING;

-- 2. Turno Mañana Especial para BTI (Bachillerato Técnico en Informática: 07:00 a 13:30 hs)
INSERT INTO public.marcos_horarios_institucionales 
    (dia_semana, id_turno, id_nivel, id_bachillerato, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion)
SELECT 
    d.dia, 1, 4, 1, '06:45:00'::TIME, '07:00:00'::TIME, 15, '13:30:00'::TIME, 'Jornada Extendida BTI (Informática)'
FROM (VALUES ('Lunes'), ('Martes'), ('Miercoles'), ('Jueves'), ('Viernes')) AS d(dia)
ON CONFLICT DO NOTHING;

-- 3. Turno Tarde General (13:00 a 18:00)
INSERT INTO public.marcos_horarios_institucionales 
    (dia_semana, id_turno, id_nivel, id_bachillerato, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion)
SELECT 
    d.dia, 2, NULL, NULL, '12:45:00'::TIME, '13:00:00'::TIME, 15, '18:00:00'::TIME, 'Jornada Vespertina Estándar'
FROM (VALUES ('Lunes'), ('Martes'), ('Miercoles'), ('Jueves'), ('Viernes')) AS d(dia)
ON CONFLICT DO NOTHING;

-- 4. Turno Noche General (18:30 a 22:00)
INSERT INTO public.marcos_horarios_institucionales 
    (dia_semana, id_turno, id_nivel, id_bachillerato, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion)
SELECT 
    d.dia, 3, NULL, NULL, '18:00:00'::TIME, '18:30:00'::TIME, 15, '22:00:00'::TIME, 'Jornada Nocturna Estándar'
FROM (VALUES ('Lunes'), ('Martes'), ('Miercoles'), ('Jueves'), ('Viernes')) AS d(dia)
ON CONFLICT DO NOTHING;
