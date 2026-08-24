-- Permite que una clase conjunta agrupe más de dos cursos (por ejemplo,
-- Culto para 7.º, 8.º y 9.º) y no solamente pares BTI/BCB.

ALTER TABLE horarios
    ADD COLUMN IF NOT EXISTS id_grupo_clase_conjunta INT NULL AFTER id_horario_vinculado,
    ADD INDEX IF NOT EXISTS idx_grupo_clase_conjunta (id_grupo_clase_conjunta);

-- Convierte los vínculos recíprocos existentes en grupos de dos miembros.
UPDATE horarios
SET id_grupo_clase_conjunta = LEAST(id_horario, id_horario_vinculado)
WHERE permite_superposicion = 1
  AND id_horario_vinculado IS NOT NULL
  AND id_grupo_clase_conjunta IS NULL;
