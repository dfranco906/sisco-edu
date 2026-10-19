-- database/migrations/20261019_supabase_complete_schema_alignment.sql
-- Alineación exhaustiva del esquema de Supabase PostgreSQL con el volcado completo de SISCO-EDU

-- 1. AULAS: Agregar codigo y ubicacion
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS codigo VARCHAR(50);
ALTER TABLE aulas ADD COLUMN IF NOT EXISTS ubicacion VARCHAR(150);
-- Sincronizar codigo con codigo_sala si existe
UPDATE aulas SET codigo = codigo_sala WHERE codigo IS NULL AND codigo_sala IS NOT NULL;

-- 2. PROFESORES: Agregar id_usuario, huella_id, pendiente_sync
ALTER TABLE profesores ADD COLUMN IF NOT EXISTS id_usuario INT;
ALTER TABLE profesores ADD COLUMN IF NOT EXISTS huella_id INT;
ALTER TABLE profesores ADD COLUMN IF NOT EXISTS pendiente_sync SMALLINT DEFAULT 0;

-- 3. ESTUDIANTES: Agregar room_id, huella_id, id_aula, fingerprint_data_user, pendiente_sync
ALTER TABLE estudiantes ADD COLUMN IF NOT EXISTS room_id VARCHAR(50);
ALTER TABLE estudiantes ADD COLUMN IF NOT EXISTS huella_id INT;
ALTER TABLE estudiantes ADD COLUMN IF NOT EXISTS id_aula INT;
ALTER TABLE estudiantes ADD COLUMN IF NOT EXISTS fingerprint_data_user TEXT;
ALTER TABLE estudiantes ADD COLUMN IF NOT EXISTS pendiente_sync SMALLINT DEFAULT 0;

-- 4. GRADOS: Agregar room_id, fecha_registro
ALTER TABLE grados ADD COLUMN IF NOT EXISTS room_id VARCHAR(50);
ALTER TABLE grados ADD COLUMN IF NOT EXISTS fecha_registro TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE grados ALTER COLUMN seccion DROP NOT NULL;
ALTER TABLE grados ALTER COLUMN turno DROP NOT NULL;
ALTER TABLE grados ALTER COLUMN anho_lectivo DROP NOT NULL;

-- 5. ASIGNACION_DOCENTE: Agregar carga_horaria, anio_lectivo
ALTER TABLE asignacion_docente ADD COLUMN IF NOT EXISTS carga_horaria INT DEFAULT 0;
ALTER TABLE asignacion_docente ADD COLUMN IF NOT EXISTS anio_lectivo INT DEFAULT 2026;
ALTER TABLE asignacion_docente ADD COLUMN IF NOT EXISTS clave_asignacion_activa SMALLINT;

-- 6. HORARIOS: Agregar id_grado, id_aula, grado, aula, permite_superposicion, id_horario_vinculado, id_grupo_clase_conjunta
ALTER TABLE horarios ADD COLUMN IF NOT EXISTS id_grado INT;
ALTER TABLE horarios ADD COLUMN IF NOT EXISTS id_aula INT;
ALTER TABLE horarios ADD COLUMN IF NOT EXISTS grado VARCHAR(50);
ALTER TABLE horarios ADD COLUMN IF NOT EXISTS aula VARCHAR(50);
ALTER TABLE horarios ADD COLUMN IF NOT EXISTS permite_superposicion SMALLINT DEFAULT 0;
ALTER TABLE horarios ADD COLUMN IF NOT EXISTS id_horario_vinculado INT;
ALTER TABLE horarios ADD COLUMN IF NOT EXISTS id_grupo_clase_conjunta INT;

-- 7. HUELLAS_TEMPLATES: Ajustar columnas y restricciones para admitir alumnos y docentes
ALTER TABLE huellas_templates ADD COLUMN IF NOT EXISTS user_id_global VARCHAR(50);
ALTER TABLE huellas_templates ADD COLUMN IF NOT EXISTS id_profesor INT;
ALTER TABLE huellas_templates ADD COLUMN IF NOT EXISTS slot_index INT;
ALTER TABLE huellas_templates ADD COLUMN IF NOT EXISTS fecha_creacion TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE huellas_templates ALTER COLUMN id_estudiante DROP NOT NULL;
ALTER TABLE huellas_templates ALTER COLUMN crc32_hash DROP NOT NULL;
ALTER TABLE huellas_templates ALTER COLUMN fecha_registro DROP NOT NULL;

-- 8. NODOS_ESP32: Agregar lora_id, room_id
ALTER TABLE nodos_esp32 ADD COLUMN IF NOT EXISTS lora_id INT;
ALTER TABLE nodos_esp32 ADD COLUMN IF NOT EXISTS room_id VARCHAR(50);
ALTER TABLE nodos_esp32 ALTER COLUMN id_aula DROP NOT NULL;

-- 9. SYNC_BIOMETRICA: Agregar room_id
ALTER TABLE sync_biometrica ADD COLUMN IF NOT EXISTS room_id VARCHAR(50);

-- 10. AULA_HUELLAS_SYNC: Agregar id_aula_sync, fecha_registro, restricción UNIQUE en id_sync
ALTER TABLE aula_huellas_sync ADD COLUMN IF NOT EXISTS id_aula_sync INT;
ALTER TABLE aula_huellas_sync ADD COLUMN IF NOT EXISTS fecha_registro TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP;
DO $$ 
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'uq_aula_huellas_sync_id_sync') THEN
        ALTER TABLE aula_huellas_sync ADD CONSTRAINT uq_aula_huellas_sync_id_sync UNIQUE (id_sync);
    END IF;
END $$;

-- 11. EVENTOS_ASISTENCIA: Agregar activo, fecha_recibido
ALTER TABLE eventos_asistencia ADD COLUMN IF NOT EXISTS activo SMALLINT DEFAULT 1;
ALTER TABLE eventos_asistencia ADD COLUMN IF NOT EXISTS fecha_recibido TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP;

-- 12. TABLA ASISTENCIAS GENERAL (Modelo Asistencia.php)
CREATE TABLE IF NOT EXISTS asistencias (
    id_asistencia SERIAL PRIMARY KEY,
    user_id_global VARCHAR(50),
    id_aula INT,
    huella_id INT,
    tipo_usuario VARCHAR(20) NOT NULL,
    fecha_hora TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(30) NOT NULL DEFAULT 'PRESENTE',
    activo SMALLINT DEFAULT 1
);
ALTER TABLE asistencias ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total asistencias" ON asistencias FOR ALL USING (true) WITH CHECK (true);

-- 13. ASISTENCIAS_ESTUDIANTES: Ajustar a estructura del modelo y dump
ALTER TABLE asistencias_estudiantes ADD COLUMN IF NOT EXISTS id_asistencia_estudiante SERIAL;
ALTER TABLE asistencias_estudiantes ADD COLUMN IF NOT EXISTS huella_id INT;
ALTER TABLE asistencias_estudiantes ADD COLUMN IF NOT EXISTS hora TIME;
ALTER TABLE asistencias_estudiantes ADD COLUMN IF NOT EXISTS activo SMALLINT DEFAULT 1;
ALTER TABLE asistencias_estudiantes ADD COLUMN IF NOT EXISTS fecha_registro TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE asistencias_estudiantes ALTER COLUMN id_horario DROP NOT NULL;
ALTER TABLE asistencias_estudiantes ALTER COLUMN hora_marcacion DROP NOT NULL;

-- 14. ASISTENCIAS_PROFESORES: Ajustar a estructura del modelo y dump
ALTER TABLE asistencias_profesores ADD COLUMN IF NOT EXISTS id_asistencia_profesor SERIAL;
ALTER TABLE asistencias_profesores ADD COLUMN IF NOT EXISTS huella_id INT;
ALTER TABLE asistencias_profesores ADD COLUMN IF NOT EXISTS hora TIME;
ALTER TABLE asistencias_profesores ADD COLUMN IF NOT EXISTS activo SMALLINT DEFAULT 1;
ALTER TABLE asistencias_profesores ADD COLUMN IF NOT EXISTS fecha_registro TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE asistencias_profesores ALTER COLUMN id_horario DROP NOT NULL;
ALTER TABLE asistencias_profesores ALTER COLUMN hora_marcacion DROP NOT NULL;

-- 15. TABLA BIOMETRIC_MAPPING
CREATE TABLE IF NOT EXISTS biometric_mapping (
    id_mapping SERIAL PRIMARY KEY,
    user_id_global VARCHAR(50) NOT NULL,
    room_id VARCHAR(50) NOT NULL,
    sensor_slot INT NOT NULL,
    activo SMALLINT DEFAULT 1,
    fecha_creacion TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE biometric_mapping ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total biometric_mapping" ON biometric_mapping FOR ALL USING (true) WITH CHECK (true);

-- 16. TABLAS DE INSTRUMENTOS Y PROCEDIMIENTOS EVALUATIVOS
CREATE TABLE IF NOT EXISTS instrumentos_evaluativos (
    id_instrumento SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    activo SMALLINT DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE instrumentos_evaluativos ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total instrumentos_evaluativos" ON instrumentos_evaluativos FOR ALL USING (true) WITH CHECK (true);

CREATE TABLE IF NOT EXISTS procedimientos_evaluativos (
    id_procedimiento SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    activo SMALLINT DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE procedimientos_evaluativos ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total procedimientos_evaluativos" ON procedimientos_evaluativos FOR ALL USING (true) WITH CHECK (true);

-- 17. TABLAS DE PLANIFICACIÓN PEDAGÓGICA (PLANES ANUALES, UNIDADES, TEMAS, CAPACIDADES, INDICADORES)
CREATE TABLE IF NOT EXISTS planes_anuales (
    id_plan SERIAL PRIMARY KEY,
    id_asignacion INT,
    anio INT,
    competencia_general TEXT,
    competencia_especifica TEXT,
    institucion_fuente VARCHAR(255),
    materia_fuente VARCHAR(255),
    profesor_fuente VARCHAR(255),
    curso_fuente VARCHAR(100),
    turno_fuente VARCHAR(50),
    anio_fuente VARCHAR(50),
    dias_clase_fuente VARCHAR(100),
    importacion_origen_json TEXT,
    estado VARCHAR(50) DEFAULT 'BORRADOR',
    observaciones TEXT,
    created_by INT,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE planes_anuales ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total planes_anuales" ON planes_anuales FOR ALL USING (true) WITH CHECK (true);

CREATE TABLE IF NOT EXISTS plan_unidades (
    id_unidad SERIAL PRIMARY KEY,
    id_plan INT,
    codigo VARCHAR(50),
    nombre VARCHAR(255),
    descripcion TEXT,
    orden INT DEFAULT 1,
    horas_catedra INT DEFAULT 0,
    tiempo_texto VARCHAR(100),
    proceso_texto TEXT,
    proceso_inicio DATE,
    proceso_fin DATE,
    area_transversal TEXT,
    metodologia TEXT,
    medios_verificacion TEXT,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE plan_unidades ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total plan_unidades" ON plan_unidades FOR ALL USING (true) WITH CHECK (true);

CREATE TABLE IF NOT EXISTS plan_capacidades (
    id_capacidad SERIAL PRIMARY KEY,
    id_unidad INT,
    descripcion TEXT NOT NULL,
    proceso_desarrollo TEXT,
    orden INT DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE plan_capacidades ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total plan_capacidades" ON plan_capacidades FOR ALL USING (true) WITH CHECK (true);

CREATE TABLE IF NOT EXISTS plan_temas (
    id_tema SERIAL PRIMARY KEY,
    id_capacidad INT,
    codigo VARCHAR(50),
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT,
    horas_catedra INT DEFAULT 0,
    tiempo_texto VARCHAR(100),
    fecha_texto VARCHAR(100),
    orden INT DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE plan_temas ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total plan_temas" ON plan_temas FOR ALL USING (true) WITH CHECK (true);

CREATE TABLE IF NOT EXISTS plan_indicadores (
    id_indicador SERIAL PRIMARY KEY,
    id_tema INT,
    codigo VARCHAR(50),
    descripcion TEXT NOT NULL,
    orden INT DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE plan_indicadores ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total plan_indicadores" ON plan_indicadores FOR ALL USING (true) WITH CHECK (true);

CREATE TABLE IF NOT EXISTS plan_tema_programacion (
    id_programacion SERIAL PRIMARY KEY,
    id_tema INT,
    fecha_inicio DATE,
    fecha_fin DATE,
    horas_catedra_planificadas INT DEFAULT 0,
    orden INT DEFAULT 1,
    observaciones TEXT,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE plan_tema_programacion ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total plan_tema_programacion" ON plan_tema_programacion FOR ALL USING (true) WITH CHECK (true);

CREATE TABLE IF NOT EXISTS plan_tema_instrumentos (
    id_tema INT NOT NULL,
    id_instrumento INT NOT NULL,
    texto_fuente TEXT,
    orden INT DEFAULT 1,
    PRIMARY KEY (id_tema, id_instrumento)
);
ALTER TABLE plan_tema_instrumentos ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total plan_tema_instrumentos" ON plan_tema_instrumentos FOR ALL USING (true) WITH CHECK (true);

CREATE TABLE IF NOT EXISTS plan_tema_procedimientos (
    id_tema INT NOT NULL,
    id_procedimiento INT NOT NULL,
    texto_fuente TEXT,
    orden INT DEFAULT 1,
    PRIMARY KEY (id_tema, id_procedimiento)
);
ALTER TABLE plan_tema_procedimientos ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total plan_tema_procedimientos" ON plan_tema_procedimientos FOR ALL USING (true) WITH CHECK (true);

-- 18. CLASES DIARIAS Y OBSERVACIONES ANECDÓTICAS
CREATE TABLE IF NOT EXISTS clases_diarias (
    id_clase SERIAL PRIMARY KEY,
    id_asignacion INT,
    id_horario INT,
    id_plan INT,
    id_unidad INT,
    id_capacidad INT,
    id_tema INT,
    fecha DATE,
    hora_inicio TIME,
    hora_fin TIME,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE clases_diarias ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total clases_diarias" ON clases_diarias FOR ALL USING (true) WITH CHECK (true);

CREATE TABLE IF NOT EXISTS clase_diaria_indicadores (
    id_clase INT NOT NULL,
    id_indicador INT NOT NULL,
    cumplido SMALLINT DEFAULT 0,
    observaciones TEXT,
    PRIMARY KEY (id_clase, id_indicador)
);
ALTER TABLE clase_diaria_indicadores ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total clase_diaria_indicadores" ON clase_diaria_indicadores FOR ALL USING (true) WITH CHECK (true);

CREATE TABLE IF NOT EXISTS configuracion_informes (
    id_configuracion SERIAL PRIMARY KEY,
    membrete_path VARCHAR(255),
    activo SMALLINT DEFAULT 1,
    updated_by INT,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE configuracion_informes ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total configuracion_informes" ON configuracion_informes FOR ALL USING (true) WITH CHECK (true);

CREATE TABLE IF NOT EXISTS registros_anecdoticos (
    id_registro SERIAL PRIMARY KEY,
    id_asistencia_estudiante INT,
    observacion TEXT,
    activo SMALLINT DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE registros_anecdoticos ENABLE ROW LEVEL SECURITY;
CREATE POLICY "Acceso total registros_anecdoticos" ON registros_anecdoticos FOR ALL USING (true) WITH CHECK (true);
