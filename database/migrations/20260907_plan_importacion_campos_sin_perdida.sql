-- Campos aditivos para conservar planes anuales importados sin crear un modelo paralelo.
-- MariaDB 10.4. Todos los campos nuevos son compatibles con planes manuales existentes.
-- IMPORTANTE: validar primero en una copia/BD temporal antes de aplicar en produccion.

ALTER TABLE planes_anuales
    ADD COLUMN IF NOT EXISTS institucion_fuente VARCHAR(255) NULL AFTER competencia_especifica,
    ADD COLUMN IF NOT EXISTS materia_fuente VARCHAR(255) NULL AFTER institucion_fuente,
    ADD COLUMN IF NOT EXISTS profesor_fuente VARCHAR(255) NULL AFTER materia_fuente,
    ADD COLUMN IF NOT EXISTS curso_fuente VARCHAR(100) NULL AFTER profesor_fuente,
    ADD COLUMN IF NOT EXISTS turno_fuente VARCHAR(100) NULL AFTER curso_fuente,
    ADD COLUMN IF NOT EXISTS anio_fuente INT NULL AFTER turno_fuente,
    ADD COLUMN IF NOT EXISTS dias_clase_fuente VARCHAR(255) NULL AFTER anio_fuente,
    ADD COLUMN IF NOT EXISTS importacion_origen_json LONGTEXT NULL AFTER dias_clase_fuente;

ALTER TABLE plan_unidades
    ADD COLUMN IF NOT EXISTS tiempo_texto TEXT NULL AFTER horas_catedra,
    ADD COLUMN IF NOT EXISTS proceso_texto TEXT NULL AFTER tiempo_texto;

ALTER TABLE plan_temas
    ADD COLUMN IF NOT EXISTS tiempo_texto TEXT NULL AFTER horas_catedra,
    ADD COLUMN IF NOT EXISTS fecha_texto TEXT NULL AFTER tiempo_texto;

-- El texto original y su orden pertenecen a la relacion tema-catalogo. Esto evita
-- perder mayusculas, acentos o puntuacion cuando el catalogo global se reconcilia.
ALTER TABLE plan_tema_procedimientos
    ADD COLUMN IF NOT EXISTS texto_fuente VARCHAR(150) NULL AFTER id_procedimiento,
    ADD COLUMN IF NOT EXISTS orden INT NOT NULL DEFAULT 1 AFTER texto_fuente;

ALTER TABLE plan_tema_instrumentos
    ADD COLUMN IF NOT EXISTS texto_fuente VARCHAR(150) NULL AFTER id_instrumento,
    ADD COLUMN IF NOT EXISTS orden INT NOT NULL DEFAULT 1 AFTER texto_fuente;

-- No se agrega plan_indicadores.check: Check representa avance operativo y el
-- cumplimiento real ya vive por clase en clase_diaria_indicadores.cumplido.

-- ROLLBACK MANUAL (solo antes de que estos campos sean usados):
-- ALTER TABLE plan_tema_instrumentos DROP COLUMN IF EXISTS orden, DROP COLUMN IF EXISTS texto_fuente;
-- ALTER TABLE plan_tema_procedimientos DROP COLUMN IF EXISTS orden, DROP COLUMN IF EXISTS texto_fuente;
-- ALTER TABLE plan_temas DROP COLUMN IF EXISTS fecha_texto, DROP COLUMN IF EXISTS tiempo_texto;
-- ALTER TABLE plan_unidades DROP COLUMN IF EXISTS proceso_texto, DROP COLUMN IF EXISTS tiempo_texto;
-- ALTER TABLE planes_anuales DROP COLUMN IF EXISTS importacion_origen_json,
--   DROP COLUMN IF EXISTS dias_clase_fuente, DROP COLUMN IF EXISTS turno_fuente,
--   DROP COLUMN IF EXISTS anio_fuente,
--   DROP COLUMN IF EXISTS curso_fuente, DROP COLUMN IF EXISTS profesor_fuente,
--   DROP COLUMN IF EXISTS materia_fuente, DROP COLUMN IF EXISTS institucion_fuente;
