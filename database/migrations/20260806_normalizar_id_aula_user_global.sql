-- Normalizacion compatible con los commits 0f5c443 y 3835cee.
-- Conserva las columnas heredadas para no perder historico; el codigo usa las nuevas.

ALTER TABLE estudiantes MODIFY user_id_global VARCHAR(50) NULL;
UPDATE estudiantes SET user_id_global = CONCAT('EST_', id_estudiante) WHERE user_id_global IS NULL OR TRIM(user_id_global) = '';
UPDATE profesores SET user_id_global = CONCAT('PROF_', id_profesor) WHERE user_id_global IS NULL OR TRIM(user_id_global) = '';

ALTER TABLE huellas_templates ADD COLUMN slot_index INT NULL AFTER formato;

ALTER TABLE sync_biometrica ADD COLUMN id_aula INT NULL AFTER id_huella;
UPDATE sync_biometrica s LEFT JOIN aulas a ON a.codigo = s.room_id SET s.id_aula = a.id_aula WHERE s.id_aula IS NULL;
ALTER TABLE sync_biometrica MODIFY room_id VARCHAR(50) NULL;
ALTER TABLE sync_biometrica ADD INDEX idx_sync_aula_estado (id_aula, estado);

ALTER TABLE aula_huellas_sync ADD COLUMN id_aula INT NULL AFTER id_huella;
UPDATE aula_huellas_sync ah LEFT JOIN aulas a ON a.codigo = ah.room_id SET ah.id_aula = a.id_aula WHERE ah.id_aula IS NULL;
ALTER TABLE aula_huellas_sync MODIFY room_id VARCHAR(50) NULL;
ALTER TABLE aula_huellas_sync ADD UNIQUE KEY uq_aula_huellas_sync_id_sync (id_sync);

ALTER TABLE eventos_asistencia ADD COLUMN id_aula INT NULL AFTER user_id_global;
UPDATE eventos_asistencia ea LEFT JOIN aulas a ON a.codigo = ea.room_id SET ea.id_aula = a.id_aula WHERE ea.id_aula IS NULL;
ALTER TABLE eventos_asistencia ADD COLUMN estado VARCHAR(30) NOT NULL DEFAULT 'PRESENTE' AFTER id_aula;
ALTER TABLE eventos_asistencia ADD COLUMN timestamp_evento_dt DATETIME NULL AFTER estado;
UPDATE eventos_asistencia SET timestamp_evento_dt = FROM_UNIXTIME(timestamp_evento) WHERE timestamp_evento_dt IS NULL;
ALTER TABLE eventos_asistencia DROP COLUMN timestamp_evento;
ALTER TABLE eventos_asistencia CHANGE COLUMN timestamp_evento_dt timestamp_evento DATETIME NOT NULL;
ALTER TABLE eventos_asistencia MODIFY room_id VARCHAR(50) NULL;
ALTER TABLE eventos_asistencia ADD INDEX idx_evento_aula_fecha (id_aula, timestamp_evento);

ALTER TABLE asistencias ADD COLUMN user_id_global VARCHAR(50) NULL AFTER id_asistencia;
ALTER TABLE asistencias ADD COLUMN id_aula INT NULL AFTER user_id_global;
ALTER TABLE asistencias MODIFY huella_id INT NULL;
ALTER TABLE asistencias MODIFY tipo_usuario VARCHAR(20) NOT NULL;
ALTER TABLE asistencias MODIFY estado VARCHAR(30) NOT NULL DEFAULT 'PRESENTE';
UPDATE asistencias a LEFT JOIN estudiantes e ON e.huella_id = a.huella_id LEFT JOIN profesores p ON p.huella_id = a.huella_id
SET a.user_id_global = COALESCE(e.user_id_global, p.user_id_global) WHERE a.user_id_global IS NULL;

ALTER TABLE horarios ADD COLUMN id_grado INT NULL AFTER id_asignacion;
ALTER TABLE horarios ADD COLUMN id_aula INT NULL AFTER id_grado;
UPDATE horarios h LEFT JOIN grados g ON g.nombre = h.grado SET h.id_grado = g.id_grado, h.id_aula = g.id_aula WHERE h.id_grado IS NULL;
ALTER TABLE horarios MODIFY grado VARCHAR(50) NULL;
ALTER TABLE horarios ADD INDEX idx_horario_grado (id_grado), ADD INDEX idx_horario_aula (id_aula);