-- Materias funciona únicamente como catálogo nominal.
-- La carga horaria pertenece a asignacion_docente.
-- Debe ejecutarse después de 20260820_normalizar_asignaciones_grado_carga.sql.

ALTER TABLE materias
    DROP COLUMN IF EXISTS carga_horaria_semanal,
    DROP COLUMN IF EXISTS descripcion;
