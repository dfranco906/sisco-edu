-- Códigos jerárquicos opcionales para planes manuales e importados.
-- Migración aditiva y re-ejecutable para MariaDB 10.4.

ALTER TABLE plan_unidades
    ADD COLUMN IF NOT EXISTS codigo VARCHAR(32) NULL AFTER id_plan;

ALTER TABLE plan_temas
    ADD COLUMN IF NOT EXISTS codigo VARCHAR(32) NULL AFTER id_capacidad;

ALTER TABLE plan_indicadores
    ADD COLUMN IF NOT EXISTS codigo VARCHAR(32) NULL AFTER id_tema;

-- ROLLBACK MANUAL (ejecutar únicamente si la aplicación todavía no usa códigos):
-- ALTER TABLE plan_indicadores DROP COLUMN IF EXISTS codigo;
-- ALTER TABLE plan_temas DROP COLUMN IF EXISTS codigo;
-- ALTER TABLE plan_unidades DROP COLUMN IF EXISTS codigo;
