-- Planificacion pedagogica e informes diarios.
-- Migracion aditiva para MariaDB 10.4: no elimina ni reescribe datos existentes.

START TRANSACTION;

ALTER TABLE profesores
    ADD COLUMN IF NOT EXISTS id_usuario INT NULL AFTER id_profesor;

CREATE UNIQUE INDEX IF NOT EXISTS uq_profesor_usuario ON profesores (id_usuario);

SET @sql_fk_profesor_usuario = IF(
    EXISTS(SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
           WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='profesores'
             AND CONSTRAINT_NAME='fk_profesor_usuario'),
    'SELECT 1',
    'ALTER TABLE profesores ADD CONSTRAINT fk_profesor_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE SET NULL'
);
PREPARE stmt_fk_profesor_usuario FROM @sql_fk_profesor_usuario;
EXECUTE stmt_fk_profesor_usuario;
DEALLOCATE PREPARE stmt_fk_profesor_usuario;

CREATE TABLE IF NOT EXISTS planes_anuales (
    id_plan INT AUTO_INCREMENT PRIMARY KEY,
    id_asignacion INT NOT NULL,
    anio INT NOT NULL,
    competencia_general TEXT NULL,
    competencia_especifica TEXT NULL,
    estado ENUM('BORRADOR','PUBLICADO','ARCHIVADO') NOT NULL DEFAULT 'BORRADOR',
    observaciones TEXT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_plan_asignacion_anio UNIQUE (id_asignacion, anio),
    CONSTRAINT fk_plan_asignacion FOREIGN KEY (id_asignacion)
        REFERENCES asignacion_docente (id_asignacion) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_plan_creador FOREIGN KEY (created_by)
        REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_plan_anio CHECK (anio BETWEEN 2000 AND 2100),
    INDEX idx_plan_estado_anio (estado, anio),
    INDEX idx_plan_asignacion_estado (id_asignacion, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS plan_unidades (
    id_unidad INT AUTO_INCREMENT PRIMARY KEY,
    id_plan INT NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    orden INT NOT NULL DEFAULT 1,
    horas_catedra DECIMAL(6,2) NULL,
    proceso_inicio DATE NULL,
    proceso_fin DATE NULL,
    area_transversal TEXT NULL,
    metodologia TEXT NULL,
    medios_verificacion TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_unidad_plan FOREIGN KEY (id_plan)
        REFERENCES planes_anuales (id_plan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_unidad_periodo CHECK (proceso_fin IS NULL OR proceso_inicio IS NULL OR proceso_fin >= proceso_inicio),
    INDEX idx_unidad_plan_orden (id_plan, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS plan_capacidades (
    id_capacidad INT AUTO_INCREMENT PRIMARY KEY,
    id_unidad INT NOT NULL,
    descripcion TEXT NOT NULL,
    proceso_desarrollo TEXT NULL,
    orden INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_capacidad_unidad FOREIGN KEY (id_unidad)
        REFERENCES plan_unidades (id_unidad) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_capacidad_unidad_orden (id_unidad, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS plan_temas (
    id_tema INT AUTO_INCREMENT PRIMARY KEY,
    id_capacidad INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    contenido TEXT NULL,
    horas_catedra DECIMAL(6,2) NULL,
    orden INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tema_capacidad FOREIGN KEY (id_capacidad)
        REFERENCES plan_capacidades (id_capacidad) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_tema_capacidad_orden (id_capacidad, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS plan_indicadores (
    id_indicador INT AUTO_INCREMENT PRIMARY KEY,
    id_tema INT NOT NULL,
    descripcion TEXT NOT NULL,
    orden INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_indicador_tema FOREIGN KEY (id_tema)
        REFERENCES plan_temas (id_tema) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_indicador_tema_orden (id_tema, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS procedimientos_evaluativos (
    id_procedimiento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_procedimiento_nombre UNIQUE (nombre),
    INDEX idx_procedimiento_activo (activo, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS instrumentos_evaluativos (
    id_instrumento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_instrumento_nombre UNIQUE (nombre),
    INDEX idx_instrumento_activo (activo, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS plan_tema_procedimientos (
    id_tema INT NOT NULL,
    id_procedimiento INT NOT NULL,
    PRIMARY KEY (id_tema, id_procedimiento),
    CONSTRAINT fk_tema_procedimiento_tema FOREIGN KEY (id_tema)
        REFERENCES plan_temas (id_tema) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_tema_procedimiento_catalogo FOREIGN KEY (id_procedimiento)
        REFERENCES procedimientos_evaluativos (id_procedimiento) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS plan_tema_instrumentos (
    id_tema INT NOT NULL,
    id_instrumento INT NOT NULL,
    PRIMARY KEY (id_tema, id_instrumento),
    CONSTRAINT fk_tema_instrumento_tema FOREIGN KEY (id_tema)
        REFERENCES plan_temas (id_tema) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_tema_instrumento_catalogo FOREIGN KEY (id_instrumento)
        REFERENCES instrumentos_evaluativos (id_instrumento) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS plan_tema_programacion (
    id_programacion INT AUTO_INCREMENT PRIMARY KEY,
    id_tema INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    horas_catedra_planificadas DECIMAL(6,2) NULL,
    orden INT NOT NULL DEFAULT 1,
    observaciones TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_programacion_tema_periodo UNIQUE (id_tema, fecha_inicio, fecha_fin),
    CONSTRAINT fk_programacion_tema FOREIGN KEY (id_tema)
        REFERENCES plan_temas (id_tema) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_programacion_periodo CHECK (fecha_fin >= fecha_inicio),
    INDEX idx_programacion_fecha (fecha_inicio, fecha_fin),
    INDEX idx_programacion_tema_orden (id_tema, orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS clases_diarias (
    id_clase INT AUTO_INCREMENT PRIMARY KEY,
    id_asignacion INT NOT NULL,
    id_horario INT NULL,
    id_plan INT NULL,
    id_unidad INT NULL,
    id_capacidad INT NULL,
    id_tema INT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_clase_asignacion_fecha_hora UNIQUE (id_asignacion, fecha, hora_inicio, hora_fin),
    CONSTRAINT fk_clase_asignacion FOREIGN KEY (id_asignacion)
        REFERENCES asignacion_docente (id_asignacion) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clase_horario FOREIGN KEY (id_horario)
        REFERENCES horarios (id_horario) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_clase_plan FOREIGN KEY (id_plan)
        REFERENCES planes_anuales (id_plan) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clase_unidad FOREIGN KEY (id_unidad)
        REFERENCES plan_unidades (id_unidad) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clase_capacidad FOREIGN KEY (id_capacidad)
        REFERENCES plan_capacidades (id_capacidad) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_clase_tema FOREIGN KEY (id_tema)
        REFERENCES plan_temas (id_tema) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_clase_horas CHECK (hora_fin > hora_inicio),
    INDEX idx_clase_fecha (fecha),
    INDEX idx_clase_asignacion_fecha (id_asignacion, fecha),
    INDEX idx_clase_tema_fecha (id_tema, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS clase_diaria_indicadores (
    id_clase INT NOT NULL,
    id_indicador INT NOT NULL,
    cumplido TINYINT(1) NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    PRIMARY KEY (id_clase, id_indicador),
    CONSTRAINT fk_clase_indicador_clase FOREIGN KEY (id_clase)
        REFERENCES clases_diarias (id_clase) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_clase_indicador_indicador FOREIGN KEY (id_indicador)
        REFERENCES plan_indicadores (id_indicador) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS registros_anecdoticos (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_clase INT NOT NULL,
    id_estudiante INT NOT NULL,
    id_asistencia_estudiante INT NULL,
    observacion TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT uq_registro_clase_estudiante UNIQUE (id_clase, id_estudiante),
    CONSTRAINT fk_registro_clase FOREIGN KEY (id_clase)
        REFERENCES clases_diarias (id_clase) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_registro_estudiante FOREIGN KEY (id_estudiante)
        REFERENCES estudiantes (id_estudiante) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_registro_asistencia FOREIGN KEY (id_asistencia_estudiante)
        REFERENCES asistencias_estudiantes (id_asistencia_estudiante) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_registro_creador FOREIGN KEY (created_by)
        REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_registro_estudiante (id_estudiante),
    INDEX idx_registro_clase (id_clase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS configuracion_informes (
    id_configuracion INT AUTO_INCREMENT PRIMARY KEY,
    membrete_path VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    updated_by INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_config_informe_usuario FOREIGN KEY (updated_by)
        REFERENCES usuarios (id_usuario) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_config_informe_activo (activo, updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO procedimientos_evaluativos (nombre) VALUES
    ('Observacion'), ('Analisis de tareas'), ('Prueba escrita'), ('Analisis de casos'),
    ('Ejercicios practicos'), ('Resolucion de problemas'), ('Proyecto integrador');

INSERT IGNORE INTO instrumentos_evaluativos (nombre) VALUES
    ('Lista de cotejo'), ('RSA'), ('Rubrica'), ('Prueba practica'),
    ('Cuestionario'), ('Portafolio'), ('Guia de ejercicios');

COMMIT;
