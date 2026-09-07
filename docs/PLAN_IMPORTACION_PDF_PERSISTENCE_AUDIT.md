# Auditoría de mapping y round-trip del importador PDF

Fecha: 07-09-2026. Alcance: cuatro fixtures reales, JSON canónico 1.0, esquema normal de Planificación Pedagógica, editor manual y una base MariaDB temporal. **No se aplicó SQL a `sisco_db`.**

## Decisión

El mapping es viable mediante ampliaciones aditivas y nullable en las mismas tablas. Los datos académicos siguen teniendo como fuente de verdad `planes_anuales → plan_unidades → plan_capacidades → plan_temas → plan_indicadores`; `importacion_origen_json` conserva solamente procedencia y diagnóstico (`schema_version`, `source`, `warnings`, `confidence` y `debug` opcional), no una segunda copia académica.

## Mapping exhaustivo

“Ya existe” describe el esquema anterior a las dos migraciones propuestas. Cuando se indica “nueva”, el destino está definido en `20260907_plan_importacion_campos_sin_perdida.sql`; los tres códigos están en la migración T03 aún no aplicada.

| Campo JSON | Origen PDF | Tabla destino | Columna destino | Ya existe | Nullable | Observaciones |
|---|---|---|---|---|---|---|
| `schema_version` | Contrato del parser | `planes_anuales` | `importacion_origen_json.schema_version` | NO | SÍ | Procedencia técnica; la columna completa es nullable para planes manuales. |
| `source.filename` | Nombre original validado | `planes_anuales` | `importacion_origen_json.source.filename` | NO | SÍ | No se usa como ruta. |
| `source.format` | Detector determinista | `planes_anuales` | `importacion_origen_json.source.format` | NO | SÍ | Permite reconstruir y auditar el formato. |
| `source.sha256` | SHA-256 del PDF | `planes_anuales` | `importacion_origen_json.source.sha256` | NO | SÍ | Se reutilizará con asignación+año para advertir duplicados. |
| `source.pages` | Xpdf | `planes_anuales` | `importacion_origen_json.source.pages` | NO | SÍ | Diagnóstico. |
| `source.text_layer` | Validación del extractor | `planes_anuales` | `importacion_origen_json.source.text_layer` | NO | SÍ | V1 exige `true`; no OCR. |
| `source.extractor.name` | Configuración | `planes_anuales` | `importacion_origen_json.source.extractor.name` | NO | SÍ | Debe ser `pdftotext`. |
| `source.extractor.mode` | Configuración | `planes_anuales` | `importacion_origen_json.source.extractor.mode` | NO | SÍ | Debe ser `table`. |
| `source.extractor.version` | Xpdf `-v` | `planes_anuales` | `importacion_origen_json.source.extractor.version` | NO | SÍ | Auditoría de reproducibilidad. |
| `source.extractor.elapsed_ms` | Ejecución local | `planes_anuales` | `importacion_origen_json.source.extractor.elapsed_ms` | NO | SÍ | Métrica técnica, no dato académico. |
| `metadata.institucion` | Encabezado | `planes_anuales` | `institucion_fuente` | NO | SÍ | Visible/editable como metadata declarada. |
| `metadata.materia` | Encabezado | `planes_anuales` | `materia_fuente` | NO | SÍ | La asignación seleccionada sigue siendo autoritativa. |
| `metadata.profesor` | Encabezado | `planes_anuales` | `profesor_fuente` | NO | SÍ | No concede ownership ni permisos. |
| `metadata.curso` | Encabezado | `planes_anuales` | `curso_fuente` | NO | SÍ | No sustituye `asignacion_docente.id_grado`. |
| `metadata.turno` | Encabezado | `planes_anuales` | `turno_fuente` | NO | SÍ | Metadata editable. |
| `metadata.anio` | Encabezado | `planes_anuales` | `anio_fuente` | NO | SÍ | Se preserva incluso si falta; `planes_anuales.anio` guarda el año confirmado. |
| `metadata.dias_clase` | Encabezado | `planes_anuales` | `dias_clase_fuente` | NO | SÍ | Texto original; no inventa calendario. |
| `metadata.competencia_general` | Encabezado | `planes_anuales` | `competencia_general` | SÍ | SÍ | Ya editable en el editor manual. |
| `metadata.competencia_especifica` | Encabezado | `planes_anuales` | `competencia_especifica` | SÍ | SÍ | Ya editable en el editor manual. |
| `unidades[]` | Filas/unidades/competencias | `plan_unidades` | filas por `id_plan` | SÍ | NO | Misma jerarquía que un plan manual. |
| `unidades[].orden` | Orden visual | `plan_unidades` | `orden` | SÍ | NO | Reconstrucción ordenada. |
| `unidades[].codigo` | Romano (`I`, `II`...) | `plan_unidades` | `codigo` | NO | SÍ | T03, `VARCHAR(32)`. |
| `unidades[].nombre` | Unidad o competencia | `plan_unidades` | `nombre` | SÍ | NO | En formato Competencia/Contenido, la competencia ocupa este nivel. |
| `unidades[].descripcion` | Descripción de unidad | `plan_unidades` | `descripcion` | SÍ | SÍ | Sin conversión. |
| `unidades[].horas_catedra` | HC/Tiempo numérico o suma | `plan_unidades` | `horas_catedra` | SÍ | SÍ | `DECIMAL(6,2)`. |
| `unidades[].tiempo_texto` | Celda Tiempo/HC de unidad | `plan_unidades` | `tiempo_texto` | NO | SÍ | Nueva; conserva `8 HC`, `16`, etc. |
| `unidades[].proceso_texto` | Mes/período de unidad | `plan_unidades` | `proceso_texto` | NO | SÍ | Nueva; no se fuerza a columnas DATE. |
| `unidades[].area_transversal` | Área transversal | `plan_unidades` | `area_transversal` | SÍ | SÍ | Usado por formato Competencia/Contenido. |
| `unidades[].metodologia` | Metodología | `plan_unidades` | `metodologia` | SÍ | SÍ | Ya está en el editor. |
| `unidades[].medios_verificacion` | Medio de verificación | `plan_unidades` | `medios_verificacion` | SÍ | SÍ | Ya está en el editor. |
| `capacidades[]` | Columna Capacidad | `plan_capacidades` | filas por `id_unidad` | SÍ | NO | Relación normal. |
| `capacidades[].orden` | Orden visual | `plan_capacidades` | `orden` | SÍ | NO | — |
| `capacidades[].descripcion` | Capacidad | `plan_capacidades` | `descripcion` | SÍ | NO | — |
| `capacidades[].proceso_desarrollo` | “Proceso para el desarrollo de las capacidades” | `plan_capacidades` | `proceso_desarrollo` | SÍ | SÍ | Destino exacto ya existente; no se duplica en unidad o tema. |
| `temas[]` | Temas/contenidos | `plan_temas` | filas por `id_capacidad` | SÍ | NO | Relación normal. |
| `temas[].orden` | Orden visual | `plan_temas` | `orden` | SÍ | NO | — |
| `temas[].codigo` | Código `N.N` | `plan_temas` | `codigo` | NO | SÍ | T03, `VARCHAR(32)`. |
| `temas[].titulo` | Tema o título compuesto | `plan_temas` | `titulo` | SÍ | NO | — |
| `temas[].contenido` | Contenido | `plan_temas` | `contenido` | SÍ | SÍ | Preserva temas compuestos. |
| `temas[].horas_catedra` | Tiempo numérico de tema | `plan_temas` | `horas_catedra` | SÍ | SÍ | En Excel es por tema. |
| `temas[].tiempo_texto` | Celda Tiempo del tema | `plan_temas` | `tiempo_texto` | NO | SÍ | Nueva; en Excel preserva `1H`. |
| `temas[].fecha_texto` | Columna Fecha textual | `plan_temas` | `fecha_texto` | NO | SÍ | Nueva; no crea `plan_tema_programacion` sin confirmación humana. |
| `indicadores[]` | Indicadores | `plan_indicadores` | filas por `id_tema` | SÍ | NO | Relación normal. |
| `indicadores[].orden` | Orden visual | `plan_indicadores` | `orden` | SÍ | NO | — |
| `indicadores[].codigo` | Código `N.N.N` | `plan_indicadores` | `codigo` | NO | SÍ | T03, `VARCHAR(32)`. |
| `indicadores[].descripcion` | Texto del indicador | `plan_indicadores` | `descripcion` | SÍ | NO | — |
| `indicadores[].check` | Columna Check de la plantilla Excel | `clase_diaria_indicadores` | derivado de `cumplido` por clase | SÍ (semántica) | NO | No se agrega al plan. Los cuatro fixtures tienen `null`; un valor importado no nulo se bloquea para revisión. |
| `procedimientos_evaluativos[]` | Procedimientos | `plan_tema_procedimientos` + catálogo | relación, `texto_fuente`, `orden` | Relación SÍ; texto/orden NO | Texto SÍ | El texto original evita pérdida por cotejo/acento/puntuación. |
| `instrumentos_evaluativos[]` | Instrumentos | `plan_tema_instrumentos` + catálogo | relación, `texto_fuente`, `orden` | Relación SÍ; texto/orden NO | Texto SÍ | Igual criterio. |
| `warnings[].type/page/line/text/message` | Detector/parser/validador | `planes_anuales` | `importacion_origen_json.warnings` | NO | SÍ | Diagnóstico visible en preview; no altera academia. |
| `confidence.scale/metadata/estructura/asociaciones/global/criteria` | Reglas deterministas | `planes_anuales` | `importacion_origen_json.confidence` | NO | SÍ | No es probabilidad. |
| `debug` (opcional) | Coordenadas/anclas del parser | `planes_anuales` | `importacion_origen_json.debug` | NO | SÍ | Solo diagnóstico; también se incluyó en el round-trip. |

## Semántica de Check

Decisión: **B, avance/cumplimiento**, no atributo académico del indicador. Evidencias: el encabezado está junto a indicadores en la planilla operativa; las 53 celdas extraídas están vacías; el parser nunca inventa valores; y SISCO-EDU ya registra el cumplimiento contextual por clase e indicador en `clase_diaria_indicadores.cumplido`. Guardarlo en `plan_indicadores` mezclaría una definición anual reutilizable con un estado que cambia durante el año.

Solución mínima: no crear `plan_indicadores.check`. La preview puede mostrar el estado como informativo/no importable. Los `null` se reconstruyen sin pérdida; si un formato futuro produce `true/false`, la confirmación debe detenerse con `CHECK_IS_PROGRESS_NOT_PLAN_DEFINITION` hasta que el profesor decida el avance desde una clase real. No se necesita otra tabla.

## Tiempo, proceso y fecha

- Administración Financiera 3.º: `Tiempo` y `Proceso` pertenecen a la fila de unidad; se guardan en unidad. Sus varios temas no reciben tiempo propio.
- Administración Financiera 2.º: el PDF tiene una sola celda-tema compuesta por unidad. El contrato actual replica HC/tiempo en ese tema además de la unidad para conservar exactamente la salida histórica; ambos destinos son editables y el round-trip los distingue. Semánticamente la unidad es la fuente principal.
- Excel Avanzado: cada tema tiene `1H`; `plan_temas.horas_catedra/tiempo_texto` son el destino correcto. `plan_unidades.horas_catedra` es el agregado numérico calculado; no se crea `tiempo_texto` de unidad.
- Competencia/Contenido: “De marzo a junio” y “De Julio a Noviembre” abarcan la competencia/unidad, por lo que van a `plan_unidades.proceso_texto`. La fecha textual de un tema, cuando exista, va a `plan_temas.fecha_texto`; solo fechas confirmadas por el profesor generan programación normal.
- “Proceso para el desarrollo de las capacidades” ya tiene destino exacto en `plan_capacidades.proceso_desarrollo`. No se agrega otra columna.

## Editor manual

Al aplicar la migración, T22 debe ampliar el editor normal (no una pantalla exclusiva del PDF): metadata fuente en datos generales; `codigo`, `tiempo_texto` y `proceso_texto` en unidad; `codigo`, `tiempo_texto` y `fecha_texto` en tema; `codigo` en indicador; y texto/orden reconciliado en evaluaciones. `importacion_origen_json` será visible como procedencia de solo lectura porque editar diagnósticos no corresponde. El código productivo no se cambió todavía para evitar que intente guardar columnas ausentes antes de aprobar/aplicar la migración real.

## Prueba temporal de round-trip

`CanonicalRoundTripTest.php` crea una BD aleatoria, copia solo estructuras desde `sisco_db`, aplica T03 y la migración propuesta dos veces, analiza los cuatro PDFs con debug, persiste el árbol en las tablas normales, lo reconstruye y compara todas las claves. También rechaza `check=true` y fuerza un error después de insertar catálogos para comprobar rollback completo. Finalmente elimina la BD temporal.

Resultado: **4/4 PASS, sin datos perdidos**. La igualdad considera equivalentes `8` y `8.0`, como hace JSON; textos, nulls, arrays, orden, jerarquía y diagnóstico se comparan completos.

## Migración propuesta

1. `20260903_planificacion_codigos_jerarquicos.sql`: códigos nullable en unidad, tema e indicador.
2. `20260907_plan_importacion_campos_sin_perdida.sql`: metadata fuente y procedencia en plan; tiempo/proceso textual en unidad; tiempo/fecha textual en tema; texto original y orden en puentes evaluativos.

Ninguna migración fue aplicada a la BD real durante esta auditoría.
