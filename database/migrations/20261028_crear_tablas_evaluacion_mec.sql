-- Migración: Crear tablas para instrumentos de evaluación MEC y calificaciones por clase
-- Fecha: 2026-10-28

CREATE TABLE IF NOT EXISTS public.instrumentos_plantillas (
    id_plantilla SERIAL PRIMARY KEY,
    id_profesor INTEGER NULL REFERENCES public.profesores(id_profesor) ON DELETE CASCADE,
    id_procedimiento INTEGER NOT NULL REFERENCES public.procedimientos_evaluativos(id_procedimiento),
    id_instrumento INTEGER NOT NULL REFERENCES public.instrumentos_evaluativos(id_instrumento),
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    criterios JSONB NOT NULL DEFAULT '[]'::jsonb,
    puntaje_total NUMERIC(5,2) NOT NULL DEFAULT 5.00,
    es_institucional SMALLINT NOT NULL DEFAULT 0,
    activo SMALLINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_inst_plantillas_prof ON public.instrumentos_plantillas(id_profesor);
CREATE INDEX IF NOT EXISTS idx_inst_plantillas_inst ON public.instrumentos_plantillas(id_instrumento);
CREATE INDEX IF NOT EXISTS idx_inst_plantillas_activo ON public.instrumentos_plantillas(activo);

CREATE TABLE IF NOT EXISTS public.clase_diaria_evaluaciones (
    id_evaluacion SERIAL PRIMARY KEY,
    id_clase INTEGER NOT NULL REFERENCES public.clases_diarias(id_clase) ON DELETE CASCADE,
    id_estudiante INTEGER NOT NULL REFERENCES public.estudiantes(id_estudiante) ON DELETE CASCADE,
    id_plantilla INTEGER NULL REFERENCES public.instrumentos_plantillas(id_plantilla),
    id_instrumento INTEGER NULL REFERENCES public.instrumentos_evaluativos(id_instrumento),
    puntaje_obtenido NUMERIC(5,2) NOT NULL DEFAULT 0.00,
    puntaje_maximo NUMERIC(5,2) NOT NULL DEFAULT 5.00,
    criterios_evaluados JSONB NULL DEFAULT '[]'::jsonb,
    detalles_criterios JSONB NULL DEFAULT '[]'::jsonb,
    observacion_retroalimentacion TEXT NULL,
    observaciones TEXT NULL,
    activo SMALLINT NOT NULL DEFAULT 1,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    CONSTRAINT uq_clase_estudiante_eval UNIQUE(id_clase, id_estudiante)
);

CREATE INDEX IF NOT EXISTS idx_cde_clase ON public.clase_diaria_evaluaciones(id_clase);
CREATE INDEX IF NOT EXISTS idx_cde_estudiante ON public.clase_diaria_evaluaciones(id_estudiante);

-- Plantillas Semilla Institucionales Oficiales del MEC
INSERT INTO public.instrumentos_plantillas (id_profesor, id_procedimiento, id_instrumento, titulo, descripcion, criterios, puntaje_total, es_institucional, activo)
VALUES 
(
    NULL, 
    1, 
    1, 
    'Lista de Cotejo Estándar MEC (Observación de Clase)', 
    'Instrumento de verificación dicotómica del cumplimiento de indicadores de desempeño y actitudes en el aula según lineamientos del MEC.',
    '[{"indicador": "Demuestra comprensión del contenido conceptual", "puntaje_max": 1}, {"indicador": "Aplica procedimientos y fórmulas correctamente", "puntaje_max": 2}, {"indicador": "Participa activamente en la resolución de tareas", "puntaje_max": 1}, {"indicador": "Presenta el trabajo en tiempo y forma acordada", "puntaje_max": 1}]'::jsonb,
    5.00,
    1,
    1
),
(
    NULL,
    1,
    2,
    'Registro de Secuencia de Aprendizaje (RSA) - MEC',
    'Seguimiento continuo y procesual de habilidades, destrezas cognitivas y metacognición a lo largo del desarrollo de la unidad temática.',
    '[{"indicador": "Identifica los conceptos centrales del tema", "puntaje_max": 2}, {"indicador": "Explica la secuencia de resolución de problemas", "puntaje_max": 2}, {"indicador": "Argumenta y justifica sus conclusiones", "puntaje_max": 2}, {"indicador": "Muestra autonomía y proactividad en el aprendizaje", "puntaje_max": 2}]'::jsonb,
    8.00,
    1,
    1
),
(
    NULL,
    2,
    3,
    'Rúbrica Analítica de Desempeño y Producción Escrita',
    'Matriz de valoración graduada con criterios cualitativos y cuantitativos para evaluar trabajos prácticos y producciones analíticas.',
    '[{"criterio": "Profundidad y rigor conceptual", "puntaje_max": 3}, {"criterio": "Claridad en la expresión y coherencia lógica", "puntaje_max": 2}, {"criterio": "Originalidad y aporte crítico", "puntaje_max": 3}, {"criterio": "Cumplimiento de normas y pulcritud", "puntaje_max": 2}]'::jsonb,
    10.00,
    1,
    1
),
(
    NULL,
    3,
    4,
    'Prueba Práctica / Demostración Técnica',
    'Evaluación sumativa y formativa en laboratorio o taller donde se verifican destrezas procedimentales y manejo de herramientas técnicas.',
    '[{"criterio": "Manejo seguro de herramientas y equipos", "puntaje_max": 3}, {"criterio": "Precisión y destreza en la ejecución técnica", "puntaje_max": 4}, {"criterio": "Resolución de imprevistos o fallas", "puntaje_max": 3}]'::jsonb,
    10.00,
    1,
    1
)
ON CONFLICT DO NOTHING;
