-- ============================================================================
-- SEMILLERO: Recesos Institucionales Oficiales y Pausas Específicas por Curso
-- ============================================================================

-- 1. Receso Principal de la Mañana (3° Ciclo y Nivel Medio): 10:10 a 10:50 hs.
INSERT INTO public.recesos_institucionales 
    (nombre, dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_inicio, hora_fin, descripcion, activo)
SELECT 
    'Receso Principal Matutino', d.dia, 1, 4, NULL, NULL, '10:10:00'::TIME, '10:50:00'::TIME, 'Receso oficial Nivel Medio', 1
FROM (VALUES ('Lunes'), ('Martes'), ('Miércoles'), ('Jueves'), ('Viernes')) AS d(dia);

INSERT INTO public.recesos_institucionales 
    (nombre, dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_inicio, hora_fin, descripcion, activo)
SELECT 
    'Receso Principal Matutino', d.dia, 1, 3, NULL, NULL, '10:10:00'::TIME, '10:50:00'::TIME, 'Receso oficial 3° Ciclo', 1
FROM (VALUES ('Lunes'), ('Martes'), ('Miércoles'), ('Jueves'), ('Viernes')) AS d(dia);

-- 2. 3° BTI (id_grado 18 y 1):
--    - Lunes, Martes y Jueves: Almuerzo / Pausa Pedagógica (12:10 a 12:50 hs)
--    - Viernes: Pausa Pedagógica 9na Hora (12:50 a 13:30 hs)
INSERT INTO public.recesos_institucionales 
    (nombre, dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_inicio, hora_fin, descripcion, activo)
VALUES
    ('Almuerzo / Pausa Pedagógica BTI', 'Lunes', 1, 4, 1, 18, '12:10:00'::TIME, '12:50:00'::TIME, 'Pausa almuerzo previa a turno extendido', 1),
    ('Almuerzo / Pausa Pedagógica BTI', 'Martes', 1, 4, 1, 18, '12:10:00'::TIME, '12:50:00'::TIME, 'Pausa almuerzo previa a turno extendido', 1),
    ('Almuerzo / Pausa Pedagógica BTI', 'Jueves', 1, 4, 1, 18, '12:10:00'::TIME, '12:50:00'::TIME, 'Pausa almuerzo previa a turno extendido', 1),
    ('Pausa Pedagógica 9ª HC', 'Viernes', 1, 4, 1, 18, '12:50:00'::TIME, '13:30:00'::TIME, 'Pausa intermedia escolaridad extendida', 1),

    ('Almuerzo / Pausa Pedagógica BTI', 'Lunes', 1, 4, 1, 1, '12:10:00'::TIME, '12:50:00'::TIME, 'Pausa almuerzo previa a turno extendido', 1),
    ('Almuerzo / Pausa Pedagógica BTI', 'Martes', 1, 4, 1, 1, '12:10:00'::TIME, '12:50:00'::TIME, 'Pausa almuerzo previa a turno extendido', 1),
    ('Almuerzo / Pausa Pedagógica BTI', 'Jueves', 1, 4, 1, 1, '12:10:00'::TIME, '12:50:00'::TIME, 'Pausa almuerzo previa a turno extendido', 1),
    ('Pausa Pedagógica 9ª HC', 'Viernes', 1, 4, 1, 1, '12:50:00'::TIME, '13:30:00'::TIME, 'Pausa intermedia escolaridad extendida', 1);

-- 3. 2° BTI (id_grado 17 y 2):
--    - Lunes y Miércoles: Almuerzo / Pausa Extendida (13:30 a 14:00 hs)
--    - Viernes: Pausa Pedagógica 7ma Hora (12:10 a 12:50 hs)
INSERT INTO public.recesos_institucionales 
    (nombre, dia_semana, id_turno, id_nivel, id_bachillerato, id_grado, hora_inicio, hora_fin, descripcion, activo)
VALUES
    ('Almuerzo / Pausa Extendida BTI', 'Lunes', 1, 4, 1, 17, '13:30:00'::TIME, '14:00:00'::TIME, 'Pausa almuerzo previa a turno tarde', 1),
    ('Almuerzo / Pausa Extendida BTI', 'Miércoles', 1, 4, 1, 17, '13:30:00'::TIME, '14:00:00'::TIME, 'Pausa almuerzo previa a turno tarde', 1),
    ('Pausa Pedagógica 7ª HC', 'Viernes', 1, 4, 1, 17, '12:10:00'::TIME, '12:50:00'::TIME, 'Pausa intermedia escolaridad extendida', 1),

    ('Almuerzo / Pausa Extendida BTI', 'Lunes', 1, 4, 1, 2, '13:30:00'::TIME, '14:00:00'::TIME, 'Pausa almuerzo previa a turno tarde', 1),
    ('Almuerzo / Pausa Extendida BTI', 'Miércoles', 1, 4, 1, 2, '13:30:00'::TIME, '14:00:00'::TIME, 'Pausa almuerzo previa a turno tarde', 1),
    ('Pausa Pedagógica 7ª HC', 'Viernes', 1, 4, 1, 2, '12:10:00'::TIME, '12:50:00'::TIME, 'Pausa intermedia escolaridad extendida', 1);
