-- ============================================================================
-- SISCO-EDU: Esquema Completo PostgreSQL / Supabase Baseline
-- Generado automaticamente: 2026-10-26 12:37:48
-- ============================================================================

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- --------------------------------------------------------
-- Tabla: public.usuarios
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.usuarios (
    "id_usuario" SERIAL,
    "nombre" VARCHAR(100) NOT NULL,
    "apellido" VARCHAR(100) NOT NULL,
    "usuario" VARCHAR(50) NOT NULL,
    "email" VARCHAR(80) NOT NULL,
    "celular" VARCHAR(20) NOT NULL,
    "password" VARCHAR(255) NOT NULL,
    "rol" VARCHAR(30) NOT NULL,
    "fecha_creacion" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "activo" SMALLINT DEFAULT 1,
    PRIMARY KEY ("id_usuario")
);

ALTER TABLE public.usuarios ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'usuarios' AND policyname = 'acceso_total_usuarios') THEN
        CREATE POLICY "acceso_total_usuarios" ON public.usuarios FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.turnos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.turnos (
    "id_turno" SERIAL,
    "nombre" VARCHAR(50) NOT NULL,
    "hora_inicio" TIME,
    "hora_fin" TIME,
    "descripcion" TEXT,
    "activo" SMALLINT DEFAULT 1 NOT NULL,
    "fecha_registro" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY ("id_turno")
);

ALTER TABLE public.turnos ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'turnos' AND policyname = 'acceso_total_turnos') THEN
        CREATE POLICY "acceso_total_turnos" ON public.turnos FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.niveles_academicos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.niveles_academicos (
    "id_nivel" SERIAL,
    "nombre" VARCHAR(100) NOT NULL,
    "codigo" VARCHAR(30),
    "descripcion" TEXT,
    "activo" SMALLINT DEFAULT 1 NOT NULL,
    "fecha_registro" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY ("id_nivel")
);

ALTER TABLE public.niveles_academicos ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'niveles_academicos' AND policyname = 'acceso_total_niveles_academicos') THEN
        CREATE POLICY "acceso_total_niveles_academicos" ON public.niveles_academicos FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.bachilleratos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.bachilleratos (
    "id_bachillerato" SERIAL,
    "nombre" VARCHAR(150) NOT NULL,
    "codigo" VARCHAR(20) NOT NULL,
    "tipo" VARCHAR(50) DEFAULT 'Técnico'::character varying NOT NULL,
    "descripcion" TEXT,
    "activo" SMALLINT DEFAULT 1 NOT NULL,
    "fecha_registro" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY ("id_bachillerato")
);

ALTER TABLE public.bachilleratos ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'bachilleratos' AND policyname = 'acceso_total_bachilleratos') THEN
        CREATE POLICY "acceso_total_bachilleratos" ON public.bachilleratos FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.aulas
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.aulas (
    "id_aula" SERIAL,
    "nombre" VARCHAR(50) NOT NULL,
    "codigo_sala" VARCHAR(20),
    "capacidad" INTEGER DEFAULT 30,
    "activo" SMALLINT DEFAULT 1,
    "fecha_creacion" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "codigo" VARCHAR(50),
    "ubicacion" VARCHAR(150),
    "piso" VARCHAR(50),
    "bloque" VARCHAR(50) DEFAULT 'Bloque A'::character varying,
    PRIMARY KEY ("id_aula")
);

ALTER TABLE public.aulas ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'aulas' AND policyname = 'acceso_total_aulas') THEN
        CREATE POLICY "acceso_total_aulas" ON public.aulas FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.grados
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.grados (
    "id_grado" SERIAL,
    "nombre" VARCHAR(50) NOT NULL,
    "seccion" VARCHAR(10),
    "turno" VARCHAR(20),
    "anho_lectivo" INTEGER,
    "id_aula" INTEGER,
    "activo" SMALLINT DEFAULT 1,
    "room_id" VARCHAR(50),
    "fecha_registro" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "id_turno" INTEGER,
    "id_nivel" INTEGER,
    "id_bachillerato" INTEGER,
    PRIMARY KEY ("id_grado")
);

ALTER TABLE public.grados ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'grados' AND policyname = 'acceso_total_grados') THEN
        CREATE POLICY "acceso_total_grados" ON public.grados FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.materias
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.materias (
    "id_materia" SERIAL,
    "nombre" VARCHAR(100) NOT NULL,
    "descripcion" TEXT,
    "carga_horaria_semanal" INTEGER DEFAULT 0,
    "activo" SMALLINT DEFAULT 1,
    PRIMARY KEY ("id_materia")
);

ALTER TABLE public.materias ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'materias' AND policyname = 'acceso_total_materias') THEN
        CREATE POLICY "acceso_total_materias" ON public.materias FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.profesores
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.profesores (
    "id_profesor" SERIAL,
    "nombre" VARCHAR(100) NOT NULL,
    "apellido" VARCHAR(100) NOT NULL,
    "cedula_identidad" VARCHAR(20) NOT NULL,
    "user_id_global" VARCHAR(50),
    "fecha_registro" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "activo" SMALLINT DEFAULT 1,
    "id_usuario" INTEGER,
    "huella_id" INTEGER,
    "pendiente_sync" SMALLINT DEFAULT 0,
    PRIMARY KEY ("id_profesor")
);

ALTER TABLE public.profesores ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'profesores' AND policyname = 'acceso_total_profesores') THEN
        CREATE POLICY "acceso_total_profesores" ON public.profesores FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.estudiantes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.estudiantes (
    "id_estudiante" SERIAL,
    "nombre" VARCHAR(100) NOT NULL,
    "apellido" VARCHAR(100) NOT NULL,
    "cedula_identidad" VARCHAR(20) NOT NULL,
    "id_grado" INTEGER,
    "user_id_global" VARCHAR(50),
    "huella_registrada" SMALLINT DEFAULT 0,
    "fecha_registro" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "activo" SMALLINT DEFAULT 1,
    "room_id" VARCHAR(50),
    "huella_id" INTEGER,
    "id_aula" INTEGER,
    "fingerprint_data_user" TEXT,
    "pendiente_sync" SMALLINT DEFAULT 0,
    "id_usuario" INTEGER,
    PRIMARY KEY ("id_estudiante")
);

ALTER TABLE public.estudiantes ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'estudiantes' AND policyname = 'acceso_total_estudiantes') THEN
        CREATE POLICY "acceso_total_estudiantes" ON public.estudiantes FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.tutores
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.tutores (
    "id_tutor" SERIAL,
    "id_usuario" INTEGER,
    "nombre" VARCHAR(100) NOT NULL,
    "apellido" VARCHAR(100) NOT NULL,
    "cedula_identidad" VARCHAR(50) NOT NULL,
    "celular" VARCHAR(50),
    "email" VARCHAR(100),
    "direccion" TEXT,
    "activo" SMALLINT DEFAULT 1,
    "fecha_registro" TIMESTAMPTZ DEFAULT now(),
    PRIMARY KEY ("id_tutor")
);

ALTER TABLE public.tutores ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'tutores' AND policyname = 'acceso_total_tutores') THEN
        CREATE POLICY "acceso_total_tutores" ON public.tutores FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.estudiante_tutores
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.estudiante_tutores (
    "id_estudiante_tutor" SERIAL,
    "id_estudiante" INTEGER NOT NULL,
    "id_tutor" INTEGER NOT NULL,
    "parentesco" VARCHAR(50) DEFAULT 'Padre/Madre/Tutor'::character varying,
    "es_principal" SMALLINT DEFAULT 1,
    "activo" SMALLINT DEFAULT 1,
    "fecha_asignacion" TIMESTAMPTZ DEFAULT now(),
    PRIMARY KEY ("id_estudiante_tutor")
);

ALTER TABLE public.estudiante_tutores ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'estudiante_tutores' AND policyname = 'acceso_total_estudiante_tutores') THEN
        CREATE POLICY "acceso_total_estudiante_tutores" ON public.estudiante_tutores FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.coordinador_grados
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.coordinador_grados (
    "id_coordinador_grado" SERIAL,
    "id_usuario" INTEGER NOT NULL,
    "id_grado" INTEGER NOT NULL,
    "fecha_asignacion" TIMESTAMPTZ DEFAULT now(),
    "activo" SMALLINT DEFAULT 1,
    PRIMARY KEY ("id_coordinador_grado")
);

ALTER TABLE public.coordinador_grados ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'coordinador_grados' AND policyname = 'acceso_total_coordinador_grados') THEN
        CREATE POLICY "acceso_total_coordinador_grados" ON public.coordinador_grados FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.matriculas
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.matriculas (
    "id_matricula" SERIAL,
    "id_estudiante" INTEGER NOT NULL,
    "id_grado" INTEGER NOT NULL,
    "id_materia" INTEGER NOT NULL,
    "id_asignacion" INTEGER,
    "anho_lectivo" INTEGER DEFAULT 2026,
    "activo" SMALLINT DEFAULT 1,
    "fecha_matricula" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_matricula")
);

ALTER TABLE public.matriculas ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'matriculas' AND policyname = 'acceso_total_matriculas') THEN
        CREATE POLICY "acceso_total_matriculas" ON public.matriculas FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.asignaciones
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.asignaciones (
    "id_asignacion" SERIAL,
    "id_profesor" INTEGER NOT NULL,
    "id_materia" INTEGER NOT NULL,
    "id_grado" INTEGER NOT NULL,
    "anho_lectivo" INTEGER NOT NULL,
    "activo" SMALLINT DEFAULT 1,
    PRIMARY KEY ("id_asignacion")
);

ALTER TABLE public.asignaciones ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'asignaciones' AND policyname = 'acceso_total_asignaciones') THEN
        CREATE POLICY "acceso_total_asignaciones" ON public.asignaciones FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.asignacion_docente
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.asignacion_docente (
    "id_asignacion" SERIAL,
    "id_profesor" INTEGER NOT NULL,
    "id_materia" INTEGER NOT NULL,
    "id_grado" INTEGER,
    "a�o_lectivo" INTEGER DEFAULT 2026 NOT NULL,
    "anho_lectivo" INTEGER DEFAULT 2026 NOT NULL,
    "activo" SMALLINT DEFAULT 1,
    "carga_horaria" INTEGER DEFAULT 0,
    "anio_lectivo" INTEGER DEFAULT 2026,
    "clave_asignacion_activa" SMALLINT,
    PRIMARY KEY ("id_asignacion")
);

ALTER TABLE public.asignacion_docente ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'asignacion_docente' AND policyname = 'acceso_total_asignacion_docente') THEN
        CREATE POLICY "acceso_total_asignacion_docente" ON public.asignacion_docente FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.horarios
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.horarios (
    "id_horario" SERIAL,
    "id_asignacion" INTEGER,
    "dia_semana" VARCHAR(20) NOT NULL,
    "hora_inicio" TIME NOT NULL,
    "hora_fin" TIME NOT NULL,
    "activo" SMALLINT DEFAULT 1,
    "id_grado" INTEGER,
    "id_aula" INTEGER,
    "grado" VARCHAR(50),
    "aula" VARCHAR(50),
    "permite_superposicion" SMALLINT DEFAULT 0,
    "id_horario_vinculado" INTEGER,
    "id_grupo_clase_conjunta" INTEGER,
    PRIMARY KEY ("id_horario")
);

ALTER TABLE public.horarios ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'horarios' AND policyname = 'acceso_total_horarios') THEN
        CREATE POLICY "acceso_total_horarios" ON public.horarios FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.nodos_esp32
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.nodos_esp32 (
    "id_nodo" SERIAL,
    "node_id" VARCHAR(100) NOT NULL,
    "id_aula" INTEGER,
    "tipo" VARCHAR(20) NOT NULL,
    "estado" VARCHAR(20) DEFAULT 'offline'::character varying,
    "ultimo_heartbeat" TIMESTAMPTZ,
    "activo" SMALLINT DEFAULT 1,
    "fecha_creacion" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "lora_id" INTEGER,
    "room_id" VARCHAR(50),
    "lora_red_id" INTEGER DEFAULT 18 NOT NULL,
    PRIMARY KEY ("id_nodo")
);

ALTER TABLE public.nodos_esp32 ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'nodos_esp32' AND policyname = 'acceso_total_nodos_esp32') THEN
        CREATE POLICY "acceso_total_nodos_esp32" ON public.nodos_esp32 FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.configuraciones_sistema
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.configuraciones_sistema (
    "clave" VARCHAR(50) NOT NULL,
    "valor" TEXT NOT NULL,
    "categoria" VARCHAR(50) DEFAULT 'red'::character varying NOT NULL,
    "tipo" VARCHAR(20) DEFAULT 'string'::character varying NOT NULL,
    "descripcion" VARCHAR(255),
    "fecha_actualizacion" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY ("clave")
);

ALTER TABLE public.configuraciones_sistema ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'configuraciones_sistema' AND policyname = 'acceso_total_configuraciones_sistema') THEN
        CREATE POLICY "acceso_total_configuraciones_sistema" ON public.configuraciones_sistema FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.configuracion_informes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.configuracion_informes (
    "id_configuracion" SERIAL,
    "membrete_path" VARCHAR(255),
    "activo" SMALLINT DEFAULT 1,
    "updated_by" INTEGER,
    "created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_configuracion")
);

ALTER TABLE public.configuracion_informes ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'configuracion_informes' AND policyname = 'acceso_total_configuracion_informes') THEN
        CREATE POLICY "acceso_total_configuracion_informes" ON public.configuracion_informes FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.instrumentos_evaluativos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.instrumentos_evaluativos (
    "id_instrumento" SERIAL,
    "nombre" VARCHAR(100) NOT NULL,
    "activo" SMALLINT DEFAULT 1,
    "created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_instrumento")
);

ALTER TABLE public.instrumentos_evaluativos ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'instrumentos_evaluativos' AND policyname = 'acceso_total_instrumentos_evaluativos') THEN
        CREATE POLICY "acceso_total_instrumentos_evaluativos" ON public.instrumentos_evaluativos FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.procedimientos_evaluativos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.procedimientos_evaluativos (
    "id_procedimiento" SERIAL,
    "nombre" VARCHAR(100) NOT NULL,
    "activo" SMALLINT DEFAULT 1,
    "created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_procedimiento")
);

ALTER TABLE public.procedimientos_evaluativos ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'procedimientos_evaluativos' AND policyname = 'acceso_total_procedimientos_evaluativos') THEN
        CREATE POLICY "acceso_total_procedimientos_evaluativos" ON public.procedimientos_evaluativos FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.planes_anuales
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.planes_anuales (
    "id_plan" SERIAL,
    "id_asignacion" INTEGER,
    "anio" INTEGER,
    "competencia_general" TEXT,
    "competencia_especifica" TEXT,
    "institucion_fuente" VARCHAR(255),
    "materia_fuente" VARCHAR(255),
    "profesor_fuente" VARCHAR(255),
    "curso_fuente" VARCHAR(100),
    "turno_fuente" VARCHAR(50),
    "anio_fuente" VARCHAR(50),
    "dias_clase_fuente" VARCHAR(100),
    "importacion_origen_json" TEXT,
    "estado" VARCHAR(50) DEFAULT 'BORRADOR'::character varying,
    "observaciones" TEXT,
    "created_by" INTEGER,
    "created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_plan")
);

ALTER TABLE public.planes_anuales ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'planes_anuales' AND policyname = 'acceso_total_planes_anuales') THEN
        CREATE POLICY "acceso_total_planes_anuales" ON public.planes_anuales FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.plan_unidades
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.plan_unidades (
    "id_unidad" SERIAL,
    "id_plan" INTEGER,
    "codigo" VARCHAR(50),
    "nombre" VARCHAR(255),
    "descripcion" TEXT,
    "orden" INTEGER DEFAULT 1,
    "horas_catedra" NUMERIC DEFAULT 0,
    "tiempo_texto" VARCHAR(100),
    "proceso_texto" TEXT,
    "proceso_inicio" DATE,
    "proceso_fin" DATE,
    "area_transversal" TEXT,
    "metodologia" TEXT,
    "medios_verificacion" TEXT,
    "created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_unidad")
);

ALTER TABLE public.plan_unidades ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'plan_unidades' AND policyname = 'acceso_total_plan_unidades') THEN
        CREATE POLICY "acceso_total_plan_unidades" ON public.plan_unidades FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.plan_capacidades
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.plan_capacidades (
    "id_capacidad" SERIAL,
    "id_unidad" INTEGER,
    "descripcion" TEXT NOT NULL,
    "proceso_desarrollo" TEXT,
    "orden" INTEGER DEFAULT 1,
    "created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_capacidad")
);

ALTER TABLE public.plan_capacidades ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'plan_capacidades' AND policyname = 'acceso_total_plan_capacidades') THEN
        CREATE POLICY "acceso_total_plan_capacidades" ON public.plan_capacidades FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.plan_temas
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.plan_temas (
    "id_tema" SERIAL,
    "id_capacidad" INTEGER,
    "codigo" VARCHAR(50),
    "titulo" VARCHAR(255) NOT NULL,
    "contenido" TEXT,
    "horas_catedra" NUMERIC DEFAULT 0,
    "tiempo_texto" VARCHAR(100),
    "fecha_texto" VARCHAR(100),
    "orden" INTEGER DEFAULT 1,
    "created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_tema")
);

ALTER TABLE public.plan_temas ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'plan_temas' AND policyname = 'acceso_total_plan_temas') THEN
        CREATE POLICY "acceso_total_plan_temas" ON public.plan_temas FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.plan_indicadores
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.plan_indicadores (
    "id_indicador" SERIAL,
    "id_tema" INTEGER,
    "codigo" VARCHAR(50),
    "descripcion" TEXT NOT NULL,
    "orden" INTEGER DEFAULT 1,
    "created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_indicador")
);

ALTER TABLE public.plan_indicadores ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'plan_indicadores' AND policyname = 'acceso_total_plan_indicadores') THEN
        CREATE POLICY "acceso_total_plan_indicadores" ON public.plan_indicadores FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.plan_tema_programacion
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.plan_tema_programacion (
    "id_programacion" SERIAL,
    "id_tema" INTEGER,
    "fecha_inicio" DATE,
    "fecha_fin" DATE,
    "horas_catedra_planificadas" NUMERIC DEFAULT 0,
    "orden" INTEGER DEFAULT 1,
    "observaciones" TEXT,
    "created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_programacion")
);

ALTER TABLE public.plan_tema_programacion ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'plan_tema_programacion' AND policyname = 'acceso_total_plan_tema_programacion') THEN
        CREATE POLICY "acceso_total_plan_tema_programacion" ON public.plan_tema_programacion FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.plan_tema_instrumentos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.plan_tema_instrumentos (
    "id_tema" INTEGER NOT NULL,
    "id_instrumento" INTEGER NOT NULL,
    "texto_fuente" TEXT,
    "orden" INTEGER DEFAULT 1,
    PRIMARY KEY ("id_tema", "id_instrumento")
);

ALTER TABLE public.plan_tema_instrumentos ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'plan_tema_instrumentos' AND policyname = 'acceso_total_plan_tema_instrumentos') THEN
        CREATE POLICY "acceso_total_plan_tema_instrumentos" ON public.plan_tema_instrumentos FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.plan_tema_procedimientos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.plan_tema_procedimientos (
    "id_tema" INTEGER NOT NULL,
    "id_procedimiento" INTEGER NOT NULL,
    "texto_fuente" TEXT,
    "orden" INTEGER DEFAULT 1,
    PRIMARY KEY ("id_tema", "id_procedimiento")
);

ALTER TABLE public.plan_tema_procedimientos ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'plan_tema_procedimientos' AND policyname = 'acceso_total_plan_tema_procedimientos') THEN
        CREATE POLICY "acceso_total_plan_tema_procedimientos" ON public.plan_tema_procedimientos FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.clases_diarias
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.clases_diarias (
    "id_clase" SERIAL,
    "id_asignacion" INTEGER,
    "id_horario" INTEGER,
    "id_plan" INTEGER,
    "id_unidad" INTEGER,
    "id_capacidad" INTEGER,
    "id_tema" INTEGER,
    "fecha" DATE,
    "hora_inicio" TIME,
    "hora_fin" TIME,
    "created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "updated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_clase")
);

ALTER TABLE public.clases_diarias ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'clases_diarias' AND policyname = 'acceso_total_clases_diarias') THEN
        CREATE POLICY "acceso_total_clases_diarias" ON public.clases_diarias FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.clase_diaria_indicadores
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.clase_diaria_indicadores (
    "id_clase" INTEGER NOT NULL,
    "id_indicador" INTEGER NOT NULL,
    "cumplido" SMALLINT DEFAULT 0,
    "observaciones" TEXT,
    PRIMARY KEY ("id_clase", "id_indicador")
);

ALTER TABLE public.clase_diaria_indicadores ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'clase_diaria_indicadores' AND policyname = 'acceso_total_clase_diaria_indicadores') THEN
        CREATE POLICY "acceso_total_clase_diaria_indicadores" ON public.clase_diaria_indicadores FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.registros_anecdoticos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.registros_anecdoticos (
    "id_registro" SERIAL,
    "id_asistencia_estudiante" INTEGER,
    "observacion" TEXT,
    "activo" SMALLINT DEFAULT 1,
    "created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "id_clase" INTEGER,
    "id_estudiante" INTEGER,
    "created_by" INTEGER,
    "updated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_registro")
);

ALTER TABLE public.registros_anecdoticos ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'registros_anecdoticos' AND policyname = 'acceso_total_registros_anecdoticos') THEN
        CREATE POLICY "acceso_total_registros_anecdoticos" ON public.registros_anecdoticos FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.huellas_templates
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.huellas_templates (
    "id_huella" SERIAL,
    "id_estudiante" INTEGER,
    "fingerprint_data" TEXT NOT NULL,
    "crc32_hash" VARCHAR(8),
    "formato" VARCHAR(10) DEFAULT 'HEX'::character varying NOT NULL,
    "sensor_slot" INTEGER,
    "activo" SMALLINT DEFAULT 1,
    "pendiente_sync" SMALLINT DEFAULT 1,
    "fecha_registro" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "user_id_global" VARCHAR(50),
    "id_profesor" INTEGER,
    "slot_index" INTEGER,
    "fecha_creacion" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_huella")
);

ALTER TABLE public.huellas_templates ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'huellas_templates' AND policyname = 'acceso_total_huellas_templates') THEN
        CREATE POLICY "acceso_total_huellas_templates" ON public.huellas_templates FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.solicitudes_huella
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.solicitudes_huella (
    "id_solicitud" SERIAL,
    "id_estudiante" INTEGER NOT NULL,
    "estado" VARCHAR(20) DEFAULT 'PENDIENTE'::character varying,
    "huella_id" INTEGER,
    "mensaje" VARCHAR(255),
    "fecha_creacion" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "fecha_actualizacion" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    PRIMARY KEY ("id_solicitud")
);

ALTER TABLE public.solicitudes_huella ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'solicitudes_huella' AND policyname = 'acceso_total_solicitudes_huella') THEN
        CREATE POLICY "acceso_total_solicitudes_huella" ON public.solicitudes_huella FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.sync_biometrica
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.sync_biometrica (
    "id_sync" SERIAL,
    "id_huella" INTEGER NOT NULL,
    "id_aula" INTEGER,
    "estado" VARCHAR(20) DEFAULT 'PENDIENTE'::character varying,
    "intentos" INTEGER DEFAULT 0,
    "mensaje" VARCHAR(255),
    "fecha_creacion" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "fecha_actualizacion" TIMESTAMPTZ,
    "room_id" VARCHAR(50),
    PRIMARY KEY ("id_sync")
);

ALTER TABLE public.sync_biometrica ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'sync_biometrica' AND policyname = 'acceso_total_sync_biometrica') THEN
        CREATE POLICY "acceso_total_sync_biometrica" ON public.sync_biometrica FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.aula_huellas_sync
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.aula_huellas_sync (
    "id" SERIAL,
    "id_sync" INTEGER,
    "id_huella" INTEGER,
    "id_aula" INTEGER,
    "room_id" VARCHAR(50),
    "ci" VARCHAR(20),
    "slot_local" INTEGER,
    "tipo_persona" VARCHAR(20),
    "estado" VARCHAR(20) DEFAULT 'PENDIENTE'::character varying,
    "fecha_creacion" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "id_aula_sync" INTEGER,
    "fecha_registro" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

ALTER TABLE public.aula_huellas_sync ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'aula_huellas_sync' AND policyname = 'acceso_total_aula_huellas_sync') THEN
        CREATE POLICY "acceso_total_aula_huellas_sync" ON public.aula_huellas_sync FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.asistencias
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.asistencias (
    "id_asistencia" SERIAL,
    "user_id_global" VARCHAR(50),
    "id_aula" INTEGER,
    "huella_id" INTEGER,
    "tipo_usuario" VARCHAR(20) NOT NULL,
    "fecha_hora" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "estado" VARCHAR(30) DEFAULT 'PRESENTE'::character varying NOT NULL,
    "activo" SMALLINT DEFAULT 1,
    PRIMARY KEY ("id_asistencia")
);

ALTER TABLE public.asistencias ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'asistencias' AND policyname = 'acceso_total_asistencias') THEN
        CREATE POLICY "acceso_total_asistencias" ON public.asistencias FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.asistencias_estudiantes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.asistencias_estudiantes (
    "id_asistencia" SERIAL,
    "id_estudiante" INTEGER NOT NULL,
    "id_horario" INTEGER,
    "fecha" DATE NOT NULL,
    "hora_marcacion" TIME,
    "estado" VARCHAR(20) NOT NULL,
    "tipo_registro" VARCHAR(20) DEFAULT 'BIOMETRICO'::character varying NOT NULL,
    "nodo_origen" VARCHAR(50),
    "fecha_creacion" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "id_asistencia_estudiante" SERIAL,
    "huella_id" INTEGER,
    "hora" TIME,
    "activo" SMALLINT DEFAULT 1,
    "fecha_registro" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_asistencia_estudiante")
);

ALTER TABLE public.asistencias_estudiantes ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'asistencias_estudiantes' AND policyname = 'acceso_total_asistencias_estudiantes') THEN
        CREATE POLICY "acceso_total_asistencias_estudiantes" ON public.asistencias_estudiantes FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.asistencias_profesores
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.asistencias_profesores (
    "id_asistencia_prof" SERIAL,
    "id_profesor" INTEGER NOT NULL,
    "id_horario" INTEGER,
    "fecha" DATE NOT NULL,
    "hora_marcacion" TIME,
    "estado" VARCHAR(20) NOT NULL,
    "tipo_registro" VARCHAR(20) DEFAULT 'BIOMETRICO'::character varying NOT NULL,
    "nodo_origen" VARCHAR(50),
    "fecha_creacion" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP NOT NULL,
    "id_asistencia_profesor" SERIAL,
    "huella_id" INTEGER,
    "hora" TIME,
    "activo" SMALLINT DEFAULT 1,
    "fecha_registro" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_asistencia_profesor")
);

ALTER TABLE public.asistencias_profesores ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'asistencias_profesores' AND policyname = 'acceso_total_asistencias_profesores') THEN
        CREATE POLICY "acceso_total_asistencias_profesores" ON public.asistencias_profesores FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.eventos_asistencia
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.eventos_asistencia (
    "id_evento" SERIAL,
    "user_id_global" VARCHAR(50),
    "id_aula" INTEGER,
    "room_id" VARCHAR(50),
    "estado" VARCHAR(30) DEFAULT 'PRESENTE'::character varying,
    "timestamp_evento" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    "origen_node_id" VARCHAR(50),
    "sincronizado" SMALLINT DEFAULT 1,
    "activo" SMALLINT DEFAULT 1,
    "fecha_recibido" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_evento")
);

ALTER TABLE public.eventos_asistencia ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'eventos_asistencia' AND policyname = 'acceso_total_eventos_asistencia') THEN
        CREATE POLICY "acceso_total_eventos_asistencia" ON public.eventos_asistencia FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- --------------------------------------------------------
-- Tabla: public.biometric_mapping
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.biometric_mapping (
    "id_mapping" SERIAL,
    "user_id_global" VARCHAR(50) NOT NULL,
    "room_id" VARCHAR(50) NOT NULL,
    "sensor_slot" INTEGER NOT NULL,
    "activo" SMALLINT DEFAULT 1,
    "fecha_creacion" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id_mapping")
);

ALTER TABLE public.biometric_mapping ENABLE ROW LEVEL SECURITY;
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = 'biometric_mapping' AND policyname = 'acceso_total_biometric_mapping') THEN
        CREATE POLICY "acceso_total_biometric_mapping" ON public.biometric_mapping FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- Contrato Biometrico Estricto de 1536 bytes binarios (3072 HEX)
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_huella_template_activa_1536') THEN
        ALTER TABLE public.huellas_templates ADD CONSTRAINT chk_huella_template_activa_1536
        CHECK (activo = 0 OR (formato = 'HEX' AND length(fingerprint_data) = 3072 AND fingerprint_data ~ '^[0-9a-fA-F]+$'));
    END IF;
END $$;

