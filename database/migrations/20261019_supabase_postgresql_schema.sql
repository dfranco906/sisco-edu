-- ============================================================================
-- SISCO-EDU: Esquema Completo para PostgreSQL / Supabase
-- Fecha: 2026-10-19
-- ============================================================================
-- Este script crea la estructura relacional de SISCO-EDU en Supabase PostgreSQL,
-- aplicando las directrices de seguridad (RLS), tipos nativos y el contrato
-- biométrico estricto de 1536 bytes (3072 HEX).

-- 1. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(80) NOT NULL,
    celular VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(30) NOT NULL CHECK (rol IN ('SuperAdmin', 'Coordinador', 'Profesor', 'Administracion')),
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activo SMALLINT DEFAULT 1
);

-- 2. Tabla de Aulas
CREATE TABLE IF NOT EXISTS aulas (
    id_aula SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    codigo_sala VARCHAR(20) NOT NULL UNIQUE,
    capacidad INT DEFAULT 30,
    activo SMALLINT DEFAULT 1,
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabla de Grados
CREATE TABLE IF NOT EXISTS grados (
    id_grado SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    seccion VARCHAR(10) NOT NULL,
    turno VARCHAR(20) NOT NULL CHECK (turno IN ('Mañana', 'Tarde', 'Noche')),
    anho_lectivo INT NOT NULL,
    id_aula INT REFERENCES aulas(id_aula) ON DELETE SET NULL,
    activo SMALLINT DEFAULT 1
);

-- 4. Tabla de Materias
CREATE TABLE IF NOT EXISTS materias (
    id_materia SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    carga_horaria_semanal INT DEFAULT 0,
    activo SMALLINT DEFAULT 1
);

-- 5. Tabla de Profesores
CREATE TABLE IF NOT EXISTS profesores (
    id_profesor SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    cedula_identidad VARCHAR(20) NOT NULL UNIQUE,
    user_id_global VARCHAR(50),
    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activo SMALLINT DEFAULT 1
);

-- 6. Tabla de Estudiantes
CREATE TABLE IF NOT EXISTS estudiantes (
    id_estudiante SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    cedula_identidad VARCHAR(20) NOT NULL UNIQUE,
    id_grado INT REFERENCES grados(id_grado) ON DELETE SET NULL,
    user_id_global VARCHAR(50),
    huella_registrada SMALLINT DEFAULT 0,
    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    activo SMALLINT DEFAULT 1
);

-- 7. Tabla de Asignaciones
CREATE TABLE IF NOT EXISTS asignaciones (
    id_asignacion SERIAL PRIMARY KEY,
    id_profesor INT NOT NULL REFERENCES profesores(id_profesor) ON DELETE CASCADE,
    id_materia INT NOT NULL REFERENCES materias(id_materia) ON DELETE CASCADE,
    id_grado INT NOT NULL REFERENCES grados(id_grado) ON DELETE CASCADE,
    anho_lectivo INT NOT NULL,
    activo SMALLINT DEFAULT 1
);

-- 8. Tabla de Horarios
CREATE TABLE IF NOT EXISTS horarios (
    id_horario SERIAL PRIMARY KEY,
    id_asignacion INT NOT NULL REFERENCES asignaciones(id_asignacion) ON DELETE CASCADE,
    dia_semana VARCHAR(20) NOT NULL CHECK (dia_semana IN ('Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado')),
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    activo SMALLINT DEFAULT 1
);

-- 9. Tabla de Huellas Templates (Contrato Biométrico: 1536 bytes / 3072 HEX)
CREATE TABLE IF NOT EXISTS huellas_templates (
    id_huella SERIAL PRIMARY KEY,
    id_estudiante INT NOT NULL REFERENCES estudiantes(id_estudiante) ON DELETE CASCADE,
    fingerprint_data TEXT NOT NULL,
    crc32_hash VARCHAR(8) NOT NULL,
    formato VARCHAR(10) NOT NULL DEFAULT 'HEX',
    sensor_slot INT DEFAULT NULL,
    activo SMALLINT DEFAULT 1,
    pendiente_sync SMALLINT DEFAULT 1,
    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_huella_template_activa_1536 CHECK (
        activo = 0 OR (
            formato = 'HEX' 
            AND length(fingerprint_data) = 3072 
            AND fingerprint_data ~ '^[0-9a-fA-F]{3072}$'
        )
    )
);

-- 10. Asistencias de Estudiantes
CREATE TABLE IF NOT EXISTS asistencias_estudiantes (
    id_asistencia SERIAL PRIMARY KEY,
    id_estudiante INT NOT NULL REFERENCES estudiantes(id_estudiante) ON DELETE CASCADE,
    id_horario INT REFERENCES horarios(id_horario) ON DELETE SET NULL,
    fecha DATE NOT NULL,
    hora_marcacion TIME NOT NULL,
    estado VARCHAR(20) NOT NULL CHECK (estado IN ('Presente', 'Llegada Tardia', 'Ausente', 'Justificado')),
    tipo_registro VARCHAR(20) NOT NULL DEFAULT 'BIOMETRICO',
    nodo_origen VARCHAR(50),
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 11. Asistencias de Profesores
CREATE TABLE IF NOT EXISTS asistencias_profesores (
    id_asistencia_prof SERIAL PRIMARY KEY,
    id_profesor INT NOT NULL REFERENCES profesores(id_profesor) ON DELETE CASCADE,
    id_horario INT REFERENCES horarios(id_horario) ON DELETE SET NULL,
    fecha DATE NOT NULL,
    hora_marcacion TIME NOT NULL,
    estado VARCHAR(20) NOT NULL CHECK (estado IN ('Presente', 'Llegada Tardia', 'Ausente', 'Justificado')),
    tipo_registro VARCHAR(20) NOT NULL DEFAULT 'BIOMETRICO',
    nodo_origen VARCHAR(50),
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 12. Nodos ESP32
CREATE TABLE IF NOT EXISTS nodos_esp32 (
    id_nodo SERIAL PRIMARY KEY,
    node_id VARCHAR(100) NOT NULL UNIQUE,
    id_aula INT REFERENCES aulas(id_aula) ON DELETE SET NULL,
    tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('CAPTURA', 'GATEWAY', 'AULA')),
    estado VARCHAR(20) DEFAULT 'offline' CHECK (estado IN ('online', 'offline')),
    ultimo_heartbeat TIMESTAMPTZ NULL,
    activo SMALLINT DEFAULT 1,
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 13. Sincronización Biométrica LoRa
CREATE TABLE IF NOT EXISTS sync_biometrica (
    id_sync SERIAL PRIMARY KEY,
    id_huella INT NOT NULL REFERENCES huellas_templates(id_huella) ON DELETE CASCADE,
    id_aula INT REFERENCES aulas(id_aula) ON DELETE SET NULL,
    estado VARCHAR(20) DEFAULT 'PENDIENTE' CHECK (estado IN ('PENDIENTE', 'ENVIADO', 'CONFIRMADO', 'ERROR')),
    intentos INT DEFAULT 0,
    mensaje VARCHAR(255),
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMPTZ NULL
);

-- 14. Solicitudes de Huella
CREATE TABLE IF NOT EXISTS solicitudes_huella (
    id_solicitud SERIAL PRIMARY KEY,
    id_estudiante INT NOT NULL REFERENCES estudiantes(id_estudiante) ON DELETE CASCADE,
    estado VARCHAR(20) DEFAULT 'PENDIENTE' CHECK (estado IN ('PENDIENTE', 'CAPTURANDO', 'COMPLETADO', 'ERROR')),
    huella_id INT NULL,
    mensaje VARCHAR(255),
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 15. Configuraciones del Sistema (Dinámica de Red e IPs)
CREATE TABLE IF NOT EXISTS configuraciones_sistema (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT NOT NULL,
    categoria VARCHAR(50) NOT NULL DEFAULT 'red',
    tipo VARCHAR(20) NOT NULL DEFAULT 'string',
    descripcion VARCHAR(255) NULL,
    fecha_actualizacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Inserción de Configuraciones por Defecto
INSERT INTO configuraciones_sistema (clave, valor, categoria, tipo, descripcion) VALUES
('ip_servidor', '192.168.100.109', 'red', 'ip', 'Dirección IP o hostname del servidor central'),
('url_servidor', '', 'red', 'url', 'URL base del servidor backend (vacía para autodetectar)'),
('ip_registrador', '192.168.100.125', 'dispositivos', 'ip', 'Dirección IP del ESP32 registrador biométrico (sensor DY50)'),
('ip_gateway', '192.168.100.17', 'dispositivos', 'ip', 'Dirección IP del ESP32 Gateway LoRa'),
('gateway_sync_url', 'http://192.168.100.17/sync', 'red', 'url', 'URL completa para notificar sincronizaciones al Gateway LoRa'),
('gateway_api_key', 'SISCO_GATEWAY_2026_SECRETO', 'seguridad', 'password', 'Clave secreta enviada en la cabecera X-GATEWAY-KEY'),
('lora_red_id', '18', 'lora', 'number', 'ID de red LoRa configurado en los módulos RYLR998'),
('lora_id_gateway', '100', 'lora', 'number', 'Dirección LoRa del Gateway receptor')
ON CONFLICT (clave) DO NOTHING;

-- Usuario Administrador Inicial (password: password - hash bcrypt)
INSERT INTO usuarios (id_usuario, nombre, apellido, usuario, email, celular, password, rol, activo)
VALUES (
    1,
    'Admin',
    'Principal',
    'admin1',
    'admin@test.com',
    '0981234567',
    '$2y$10$cPm0PqykacBQYPXSO50XUeQH3CiPBt1J1fKlQwOy6l7mSUHFVtOPm',
    'SuperAdmin',
    1
) ON CONFLICT (id_usuario) DO NOTHING;

-- ============================================================================
-- Políticas de Seguridad y Row Level Security (RLS)
-- ============================================================================
ALTER TABLE usuarios ENABLE ROW LEVEL SECURITY;
ALTER TABLE aulas ENABLE ROW LEVEL SECURITY;
ALTER TABLE grados ENABLE ROW LEVEL SECURITY;
ALTER TABLE materias ENABLE ROW LEVEL SECURITY;
ALTER TABLE profesores ENABLE ROW LEVEL SECURITY;
ALTER TABLE estudiantes ENABLE ROW LEVEL SECURITY;
ALTER TABLE asignaciones ENABLE ROW LEVEL SECURITY;
ALTER TABLE horarios ENABLE ROW LEVEL SECURITY;
ALTER TABLE huellas_templates ENABLE ROW LEVEL SECURITY;
ALTER TABLE asistencias_estudiantes ENABLE ROW LEVEL SECURITY;
ALTER TABLE asistencias_profesores ENABLE ROW LEVEL SECURITY;
ALTER TABLE nodos_esp32 ENABLE ROW LEVEL SECURITY;
ALTER TABLE sync_biometrica ENABLE ROW LEVEL SECURITY;
ALTER TABLE solicitudes_huella ENABLE ROW LEVEL SECURITY;
ALTER TABLE configuraciones_sistema ENABLE ROW LEVEL SECURITY;

-- Permitir acceso completo a roles del servicio backend / autenticados
CREATE POLICY "Acceso total postgres y service_role a usuarios" ON usuarios FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a aulas" ON aulas FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a grados" ON grados FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a materias" ON materias FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a profesores" ON profesores FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a estudiantes" ON estudiantes FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a asignaciones" ON asignaciones FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a horarios" ON horarios FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a huellas_templates" ON huellas_templates FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a asistencias_estudiantes" ON asistencias_estudiantes FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a asistencias_profesores" ON asistencias_profesores FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a nodos_esp32" ON nodos_esp32 FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a sync_biometrica" ON sync_biometrica FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a solicitudes_huella" ON solicitudes_huella FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
CREATE POLICY "Acceso total postgres y service_role a configuraciones_sistema" ON configuraciones_sistema FOR ALL TO postgres, service_role USING (true) WITH CHECK (true);
