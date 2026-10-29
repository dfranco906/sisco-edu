-- ============================================================================
-- SEMILLERO: Marcos Horarios Institucionales Oficiales
-- Basado en la realidad institucional:
-- 1. EEB 1° y 2° Ciclo: Salida a las 12:00 hs.
-- 2. EEB 3° Ciclo y Nivel Medio (Ciencias Básicas): Salida a las 13:30 hs.
-- 3. BTI (Base general): Salida a las 13:30 hs.
-- 4. Excepciones BTI Escolaridad Extendida por Curso:
--    - 1° BTI: Viernes hasta las 14:10 hs.
--    - 2° BTI: Martes hasta las 16:00 hs.
--    - 3° BTI: Lunes y Jueves hasta las 15:30 hs.
-- ============================================================================

-- 1. EEB 1° Ciclo (Nivel 1)
INSERT INTO public.marcos_horarios_institucionales
    (dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion, activo)
SELECT d.dia, 1, 1, NULL, NULL, '06:45:00'::TIME, '07:00:00'::TIME, 15, '12:00:00'::TIME, 'EEB 1° Ciclo - Jornada Matutina', 1
FROM (VALUES ('Lunes'), ('Martes'), ('Miércoles'), ('Jueves'), ('Viernes')) AS d(dia)
ON CONFLICT (dia_semana, id_turno, COALESCE(id_nivel, 0), COALESCE(id_bachillerato, 0), COALESCE(id_grado, 0)) WHERE activo = 1
DO UPDATE SET hora_cierre = EXCLUDED.hora_cierre, descripcion = EXCLUDED.descripcion, activo = 1;

-- 2. EEB 2° Ciclo (Nivel 2)
INSERT INTO public.marcos_horarios_institucionales
    (dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion, activo)
SELECT d.dia, 1, 2, NULL, NULL, '06:45:00'::TIME, '07:00:00'::TIME, 15, '12:00:00'::TIME, 'EEB 2° Ciclo - Jornada Matutina', 1
FROM (VALUES ('Lunes'), ('Martes'), ('Miércoles'), ('Jueves'), ('Viernes')) AS d(dia)
ON CONFLICT (dia_semana, id_turno, COALESCE(id_nivel, 0), COALESCE(id_bachillerato, 0), COALESCE(id_grado, 0)) WHERE activo = 1
DO UPDATE SET hora_cierre = EXCLUDED.hora_cierre, descripcion = EXCLUDED.descripcion, activo = 1;

-- 3. EEB 3° Ciclo (Nivel 3)
INSERT INTO public.marcos_horarios_institucionales
    (dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion, activo)
SELECT d.dia, 1, 3, NULL, NULL, '06:45:00'::TIME, '07:00:00'::TIME, 15, '13:30:00'::TIME, 'EEB 3° Ciclo - Jornada Extendida', 1
FROM (VALUES ('Lunes'), ('Martes'), ('Miércoles'), ('Jueves'), ('Viernes')) AS d(dia)
ON CONFLICT (dia_semana, id_turno, COALESCE(id_nivel, 0), COALESCE(id_bachillerato, 0), COALESCE(id_grado, 0)) WHERE activo = 1
DO UPDATE SET hora_cierre = EXCLUDED.hora_cierre, descripcion = EXCLUDED.descripcion, activo = 1;

-- 4. Nivel Medio Ciencias Básicas (Nivel 4, Bachillerato 2)
INSERT INTO public.marcos_horarios_institucionales
    (dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion, activo)
SELECT d.dia, 1, 4, 2, NULL, '06:45:00'::TIME, '07:00:00'::TIME, 15, '13:30:00'::TIME, 'Bachillerato Ciencias Básicas (BCB)', 1
FROM (VALUES ('Lunes'), ('Martes'), ('Miércoles'), ('Jueves'), ('Viernes')) AS d(dia)
ON CONFLICT (dia_semana, id_turno, COALESCE(id_nivel, 0), COALESCE(id_bachillerato, 0), COALESCE(id_grado, 0)) WHERE activo = 1
DO UPDATE SET hora_cierre = EXCLUDED.hora_cierre, descripcion = EXCLUDED.descripcion, activo = 1;

-- 5. BTI General (Nivel 4, Bachillerato 1)
INSERT INTO public.marcos_horarios_institucionales
    (dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion, activo)
SELECT d.dia, 1, 4, 1, NULL, '06:45:00'::TIME, '07:00:00'::TIME, 15, '13:30:00'::TIME, 'BTI - Jornada Estándar', 1
FROM (VALUES ('Lunes'), ('Martes'), ('Miércoles'), ('Jueves'), ('Viernes')) AS d(dia)
ON CONFLICT (dia_semana, id_turno, COALESCE(id_nivel, 0), COALESCE(id_bachillerato, 0), COALESCE(id_grado, 0)) WHERE activo = 1
DO UPDATE SET hora_cierre = EXCLUDED.hora_cierre, descripcion = EXCLUDED.descripcion, activo = 1;

-- 6. Excepciones BTI: 1° BTI (id_grado 16) - Viernes hasta las 14:10
INSERT INTO public.marcos_horarios_institucionales
    (dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion, activo)
VALUES
    ('Viernes', 1, 4, 1, 16, '06:45:00'::TIME, '07:00:00'::TIME, 15, '14:10:00'::TIME, '1° BTI - Viernes Escolaridad Extendida', 1)
ON CONFLICT (dia_semana, id_turno, COALESCE(id_nivel, 0), COALESCE(id_bachillerato, 0), COALESCE(id_grado, 0)) WHERE activo = 1
DO UPDATE SET hora_cierre = EXCLUDED.hora_cierre, descripcion = EXCLUDED.descripcion, activo = 1;

-- 7. Excepciones BTI: 2° BTI (id_grado 17) - Martes hasta las 16:00
INSERT INTO public.marcos_horarios_institucionales
    (dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion, activo)
VALUES
    ('Martes', 1, 4, 1, 17, '06:45:00'::TIME, '07:00:00'::TIME, 15, '16:00:00'::TIME, '2° BTI - Martes Escolaridad Extendida', 1)
ON CONFLICT (dia_semana, id_turno, COALESCE(id_nivel, 0), COALESCE(id_bachillerato, 0), COALESCE(id_grado, 0)) WHERE activo = 1
DO UPDATE SET hora_cierre = EXCLUDED.hora_cierre, descripcion = EXCLUDED.descripcion, activo = 1;

-- 8. Excepciones BTI: 3° BTI (id_grado 18 y id_grado 1) - Lunes y Jueves hasta las 15:30
INSERT INTO public.marcos_horarios_institucionales
    (dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_apertura, hora_inicio, tolerancia_minutos, hora_cierre, descripcion, activo)
VALUES
    ('Lunes', 1, 4, 1, 18, '06:45:00'::TIME, '07:00:00'::TIME, 15, '15:30:00'::TIME, '3° BTI - Lunes Escolaridad Extendida', 1),
    ('Jueves', 1, 4, 1, 18, '06:45:00'::TIME, '07:00:00'::TIME, 15, '15:30:00'::TIME, '3° BTI - Jueves Escolaridad Extendida', 1),
    ('Lunes', 1, 4, 1, 1, '06:45:00'::TIME, '07:00:00'::TIME, 15, '15:30:00'::TIME, '3ro BTI - Lunes Escolaridad Extendida', 1),
    ('Jueves', 1, 4, 1, 1, '06:45:00'::TIME, '07:00:00'::TIME, 15, '15:30:00'::TIME, '3ro BTI - Jueves Escolaridad Extendida', 1)
ON CONFLICT (dia_semana, id_turno, COALESCE(id_nivel, 0), COALESCE(id_bachillerato, 0), COALESCE(id_grado, 0)) WHERE activo = 1
DO UPDATE SET hora_cierre = EXCLUDED.hora_cierre, descripcion = EXCLUDED.descripcion, activo = 1;
