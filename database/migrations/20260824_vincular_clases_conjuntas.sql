-- Una clase conjunta debe conservar una relación explícita con el horario
-- correspondiente. La referencia es recíproca para poder sincronizar ambas
-- tarjetas cuando se modifica cualquiera de los dos cursos.

ALTER TABLE horarios
    ADD COLUMN IF NOT EXISTS id_horario_vinculado INT NULL AFTER permite_superposicion,
    ADD INDEX IF NOT EXISTS idx_horario_vinculado (id_horario_vinculado);

SET @fk_horario_vinculado_existe = (
    SELECT COUNT(*)
    FROM information_schema.REFERENTIAL_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'horarios'
      AND CONSTRAINT_NAME = 'fk_horario_vinculado'
);

SET @sql_fk_horario_vinculado = IF(
    @fk_horario_vinculado_existe = 0,
    'ALTER TABLE horarios ADD CONSTRAINT fk_horario_vinculado FOREIGN KEY (id_horario_vinculado) REFERENCES horarios (id_horario) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
);

PREPARE stmt_fk_horario_vinculado FROM @sql_fk_horario_vinculado;
EXECUTE stmt_fk_horario_vinculado;
DEALLOCATE PREPARE stmt_fk_horario_vinculado;

-- Recupera los pares conjuntos existentes que coinciden exactamente en
-- profesor, materia, año, día y franja, como 3.º BCB / 3.º BTI.
UPDATE horarios h
INNER JOIN (
    SELECT
        h1.id_horario,
        MIN(h2.id_horario) AS id_horario_vinculado
    FROM horarios h1
    INNER JOIN asignacion_docente a1 ON a1.id_asignacion = h1.id_asignacion
    INNER JOIN horarios h2
        ON h2.id_horario <> h1.id_horario
       AND h2.activo = 1
       AND h2.permite_superposicion = 1
       AND h2.id_grado <> h1.id_grado
       AND h2.dia_semana = h1.dia_semana
       AND h2.hora_inicio = h1.hora_inicio
       AND h2.hora_fin = h1.hora_fin
    INNER JOIN asignacion_docente a2
        ON a2.id_asignacion = h2.id_asignacion
       AND a2.id_profesor = a1.id_profesor
       AND a2.id_materia = a1.id_materia
       AND a2.anio_lectivo = a1.anio_lectivo
    WHERE h1.activo = 1
      AND h1.permite_superposicion = 1
    GROUP BY h1.id_horario
) pares ON pares.id_horario = h.id_horario
SET h.id_horario_vinculado = pares.id_horario_vinculado
WHERE h.id_horario_vinculado IS NULL;
