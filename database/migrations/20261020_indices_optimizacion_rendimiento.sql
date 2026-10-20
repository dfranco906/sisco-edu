-- ==============================================================================
-- Migración: Optimización de Índices y Rendimiento Institucional (Etapa 5)
-- Base de Datos: PostgreSQL (Supabase / Local)
-- Fecha: 2026-10-20
-- ==============================================================================

-- 1. Horarios: Aceleración de cuadrícula semanal y detección de clases en curso
CREATE INDEX IF NOT EXISTS idx_horarios_asignacion_dia 
    ON horarios(id_asignacion, dia_semana);

CREATE INDEX IF NOT EXISTS idx_horarios_grado_dia 
    ON horarios(id_grado, dia_semana);

CREATE INDEX IF NOT EXISTS idx_horarios_dia_horas 
    ON horarios(dia_semana, hora_inicio, hora_fin);

CREATE INDEX IF NOT EXISTS idx_horarios_aula 
    ON horarios(id_aula);

-- 2. Asistencias de Estudiantes: Agregaciones de métricas y expedientes
CREATE INDEX IF NOT EXISTS idx_asist_est_fecha_estudiante 
    ON asistencias_estudiantes(fecha, id_estudiante);

CREATE INDEX IF NOT EXISTS idx_asist_est_estudiante_fecha 
    ON asistencias_estudiantes(id_estudiante, fecha);

CREATE INDEX IF NOT EXISTS idx_asist_est_horario_fecha 
    ON asistencias_estudiantes(id_horario, fecha);

-- 3. Asistencias de Profesores: Búsqueda rápida por jornada y docente
CREATE INDEX IF NOT EXISTS idx_asist_prof_fecha_profesor 
    ON asistencias_profesores(fecha, id_profesor);

CREATE INDEX IF NOT EXISTS idx_asist_prof_profesor_fecha 
    ON asistencias_profesores(id_profesor, fecha);

-- 4. Asignaciones Docentes: Búsquedas de cátedras por profesor o grado
CREATE INDEX IF NOT EXISTS idx_asig_doc_profesor_activo 
    ON asignacion_docente(id_profesor, activo);

CREATE INDEX IF NOT EXISTS idx_asig_doc_grado_materia_activo 
    ON asignacion_docente(id_grado, id_materia, activo);

-- 5. Matrículas: Control de unicidad compuesta y carga curricular masiva
CREATE INDEX IF NOT EXISTS idx_matriculas_est_grado_materia 
    ON matriculas(id_estudiante, id_grado, id_materia, anho_lectivo);

-- 6. Grados: Filtros en cascada (Turno -> Nivel -> Bachillerato)
CREATE INDEX IF NOT EXISTS idx_grados_filtros_jerarquia 
    ON grados(id_turno, id_nivel, id_bachillerato, activo);

CREATE INDEX IF NOT EXISTS idx_grados_aula 
    ON grados(id_aula);

-- 7. Usuarios: Autenticación e integración de nómina por rol
CREATE INDEX IF NOT EXISTS idx_usuarios_rol_activo 
    ON usuarios(rol, activo);

-- 8. Plan Curricular: Ordenamiento de temas por capacidad
CREATE INDEX IF NOT EXISTS idx_plan_temas_capacidad_orden 
    ON plan_temas(id_capacidad, orden);

-- 9. Biometría y Plantillas: Búsqueda ágil por usuario y actor
CREATE INDEX IF NOT EXISTS idx_huellas_user_global_activo 
    ON huellas_templates(user_id_global, activo);

CREATE INDEX IF NOT EXISTS idx_huellas_estudiante_activo 
    ON huellas_templates(id_estudiante, activo);

CREATE INDEX IF NOT EXISTS idx_huellas_profesor_activo 
    ON huellas_templates(id_profesor, activo);

-- 10. Eventos de Asistencia: Log cronológico y por usuario
CREATE INDEX IF NOT EXISTS idx_eventos_asistencia_fecha 
    ON eventos_asistencia(timestamp_evento DESC);

CREATE INDEX IF NOT EXISTS idx_eventos_asistencia_user_fecha 
    ON eventos_asistencia(user_id_global, timestamp_evento DESC);
