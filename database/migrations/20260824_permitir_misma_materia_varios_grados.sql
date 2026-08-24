-- Una asignación docente pertenece a un solo grado, pero el mismo profesor
-- puede dictar la misma materia en tantos grados como corresponda.
--
-- Solo se impide repetir exactamente:
-- profesor + materia + grado + año lectivo mientras la asignación está activa.
-- Las filas desactivadas quedan fuera de la unicidad para permitir recargas
-- históricas sin borrar información.

ALTER TABLE asignacion_docente
    ADD COLUMN IF NOT EXISTS clave_asignacion_activa TINYINT(1)
        AS (CASE WHEN activo = 1 THEN 1 ELSE NULL END) PERSISTENT;

ALTER TABLE asignacion_docente
    ADD UNIQUE INDEX IF NOT EXISTS uq_asignacion_activa_por_grado (
        id_profesor,
        id_materia,
        id_grado,
        anio_lectivo,
        clave_asignacion_activa
    );
