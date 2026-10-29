-- ====================================================================
-- MIGRACIÓN: Restricción Única e Índices para Alcance de Usuarios
-- Archivo: database/migrations/20261029_crear_uq_coordinador_grados_y_alcance.sql
-- Descripción: Asegura unicidad y aceleración para asignación de turnos,
--              niveles, bachilleratos y grados a Administradores y Coordinadores.
-- ====================================================================

DO $$
BEGIN
    -- Limpiar posibles duplicados inactivos antes de crear el índice único
    DELETE FROM public.coordinador_grados a
    USING public.coordinador_grados b
    WHERE a.id_coordinador_grado < b.id_coordinador_grado
      AND a.id_usuario = b.id_usuario
      AND a.id_grado = b.id_grado;
END $$;

-- Índice único en (id_usuario, id_grado)
CREATE UNIQUE INDEX IF NOT EXISTS uq_coordinador_grados_usuario_grado 
ON public.coordinador_grados (id_usuario, id_grado);

-- Índices de aceleración
CREATE INDEX IF NOT EXISTS idx_coordinador_grados_usuario 
ON public.coordinador_grados (id_usuario) WHERE activo = 1;

CREATE INDEX IF NOT EXISTS idx_coordinador_grados_grado 
ON public.coordinador_grados (id_grado) WHERE activo = 1;
