-- Normaliza las asignaciones docentes y asegura sus relaciones con grado/aula.
-- Los registros históricos cuyo grado no pueda inferirse se conservan con id_grado NULL.

START TRANSACTION;

ALTER TABLE asignacion_docente
    CHANGE COLUMN `año_lectivo` anio_lectivo INT NOT NULL,
    ADD COLUMN id_grado INT NULL AFTER id_materia,
    ADD COLUMN carga_horaria INT NULL AFTER id_grado;

-- Cuando existen horarios históricos de un único grado, se conserva esa relación.
UPDATE asignacion_docente ad
INNER JOIN (
    SELECT id_asignacion, MIN(id_grado) AS id_grado
    FROM horarios
    WHERE id_grado IS NOT NULL
    GROUP BY id_asignacion
    HAVING COUNT(DISTINCT id_grado) = 1
) h ON h.id_asignacion = ad.id_asignacion
SET ad.id_grado = h.id_grado
WHERE ad.id_grado IS NULL;

-- La carga existente de la materia sirve únicamente como valor inicial histórico.
UPDATE asignacion_docente ad
INNER JOIN materias m ON m.id_materia = ad.id_materia
SET ad.carga_horaria = m.carga_horaria_semanal
WHERE ad.carga_horaria IS NULL;

ALTER TABLE asignacion_docente
    MODIFY COLUMN carga_horaria INT NOT NULL,
    ADD INDEX idx_asignacion_grado (id_grado),
    ADD INDEX idx_asignacion_busqueda (id_profesor, id_materia, id_grado, anio_lectivo),
    ADD UNIQUE KEY uq_asignacion_id_grado (id_asignacion, id_grado),
    ADD CONSTRAINT fk_asignacion_grado
        FOREIGN KEY (id_grado) REFERENCES grados (id_grado)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT chk_asignacion_carga_horaria
        CHECK (carga_horaria BETWEEN 1 AND 100),
    ADD CONSTRAINT chk_asignacion_anio_lectivo
        CHECK (anio_lectivo BETWEEN 2000 AND 2100);

ALTER TABLE grados
    ADD INDEX idx_grado_aula (id_aula),
    ADD UNIQUE KEY uq_grado_id_aula (id_grado, id_aula),
    ADD CONSTRAINT fk_grado_aula
        FOREIGN KEY (id_aula) REFERENCES aulas (id_aula)
        ON UPDATE CASCADE ON DELETE RESTRICT;

ALTER TABLE horarios
    ADD INDEX idx_horario_asignacion_grado (id_asignacion, id_grado),
    ADD INDEX idx_horario_grado_aula (id_grado, id_aula),
    ADD CONSTRAINT fk_horario_asignacion_grado
        FOREIGN KEY (id_asignacion, id_grado)
        REFERENCES asignacion_docente (id_asignacion, id_grado)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_horario_grado_aula
        FOREIGN KEY (id_grado, id_aula)
        REFERENCES grados (id_grado, id_aula)
        ON UPDATE CASCADE ON DELETE RESTRICT;

COMMIT;
