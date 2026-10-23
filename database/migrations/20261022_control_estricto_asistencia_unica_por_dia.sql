-- ==============================================================================
-- Migración: Control Estricto de Unicidad de Asistencia Diaria y Activación de Nodo Aula Laboratorio
-- Base de Datos: PostgreSQL (Supabase / Local)
-- Fecha: 2026-10-22
-- ==============================================================================

-- 1. Activación de Aula 18 (AULA_A) para pruebas directas del firmware prototipo RYLR998
UPDATE aulas 
SET activo = 1 
WHERE id_aula = 18;

-- 2. Índice Único Estricto para Asistencia Diaria de Estudiantes
-- Impide a nivel de motor de base de datos la inserción de registros duplicados en el mismo día
CREATE UNIQUE INDEX IF NOT EXISTS uq_asist_est_fecha_activa 
    ON asistencias_estudiantes (id_estudiante, fecha) 
    WHERE activo = 1;

-- 3. Índice Único Estricto para Asistencia Diaria de Profesores
-- Garantiza una sola marcación activa por docente en la misma jornada lectiva
CREATE UNIQUE INDEX IF NOT EXISTS uq_asist_prof_fecha_activa 
    ON asistencias_profesores (id_profesor, fecha) 
    WHERE activo = 1;

-- 4. Índice para agilizar la detección de marcaciones del día y prevención de doble escritura
CREATE INDEX IF NOT EXISTS idx_asist_est_check_duplicado
    ON asistencias_estudiantes (id_estudiante, fecha, activo);

CREATE INDEX IF NOT EXISTS idx_asist_prof_check_duplicado
    ON asistencias_profesores (id_profesor, fecha, activo);
