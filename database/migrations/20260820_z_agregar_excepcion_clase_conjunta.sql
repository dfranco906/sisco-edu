-- Excepción explícita para clases conjuntas del mismo profesor.
-- No modifica las relaciones: solo registra la autorización de superposición.

ALTER TABLE horarios
    ADD COLUMN permite_superposicion TINYINT(1) NOT NULL DEFAULT 0 AFTER activo,
    ADD CONSTRAINT chk_horario_permite_superposicion
        CHECK (permite_superposicion IN (0, 1));
