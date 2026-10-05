# Auditoría del importador por celdas — Informática, 1° BCB

Fecha: 2026-10-05, America/Asuncion.
Repositorio: dfranco906/sisco-edu.
Rama exclusiva: frontend-recovery-audit.
HEAD inicial y al finalizar las pruebas de la corrección: c97cb0a30c0fdcef49a65392f3a84d57a4abed60.
Corrección autorizada por el usuario; posteriormente solicitó commit y push de los cambios.
El commit de la corrección y este informe se identifica con `git log -1 --format=%H -- docs/auditoria-importador-celdas-informatica-1-bcb-2026-10-05.md`. El cambio de dirección de sincronización del Gateway se registra por separado.

## Problema y causa verificada

El PDF «Plan Anual - segundo curso - BCB - Excel Avanzado.pdf» declara en su encabezado Informática / LIC. DANILO FRANCO / 1° BCB / 2026 / VIERNES.
Su contenido es sistema operativo y Microsoft Office Word, a pesar del nombre del archivo.

El parser anterior asignaba la capacidad a la última competencia cuyo nombre aparecía en el texto.
Una celda combinada empieza en la primera página y muestra el nombre de Word en la segunda.
Por eso cuatro capacidades de Word quedaban bajo sistema operativo: 7/7, cuando los bordes de la tabla muestran 3/11.
Los intervalos por distancia entre textos también desplazaban viñetas, impresión y HTML.
Las expectativas históricas del POC reproducían esas asociaciones incorrectas.

## Corrección

- Se leen bordes impresos de la tabla y sus transformaciones/recortes, con soporte acotado a páginas ordinarias, sin rotación, streams simples o Flate y ocho columnas del formato COMPETENCIA_CONTENIDO.
- Xpdf sigue siendo el extractor de texto. Lee las celdas de copias temporales privadas; el PDF original no cambia. No se incorpora Python ni otro ejecutable al proceso de Apache.
- Los nombres dentro de celdas combinadas no definen el inicio de las competencias.
- Se unen continuaciones entre páginas y los indicadores siguen el inicio de su viñeta. Una línea continuada puede cruzar un borde sin cambiar de indicador.
- Los periodos fuente se conservan por tema. No se convierten en fechas exactas.
- Si los bordes o una asociación no se pueden verificar, se rechaza con HTTP 422; no se vuelve a la inferencia insegura por texto.
- Se mantienen presupuesto global de tiempo, límite de texto, streams acotados y limpieza de copias temporales.
- El contrato de producción revisado se separó del snapshot histórico del POC. El POC permanece como evidencia histórica y su PASS no demuestra la corrección semántica de producción.

No se modificaron schema, firmware, reglas de asistencia ni frontend.

## Archivos de esta corrección

1. src/classes/PlanImport/Pdf/Parser/CompetenciaContenidoParser.php
2. src/classes/PlanImport/Pdf/XpdfTableExtractor.php
3. src/classes/PlanImport/Pdf/PdfTableCells.php
4. test/plan_import/unit/CompetenciaContenidoParserTest.php
5. test/plan_import/unit/PdfTableCellsTest.php
6. test/plan_import/expected/contracts.json
7. test/plan_import/expected/competencia_contenido.cells-reviewed.json
8. docs/auditoria-importador-celdas-informatica-1-bcb-2026-10-05.md

docs/tesis/ se preservó sin seguimiento. Se retiraron exclusivamente variaciones de elapsed_ms regeneradas por los tests, tras comprobar que no existieran otras diferencias en sus salidas.

## Tests ejecutados

| Prueba | Resultado |
| --- | --- |
| php test/plan_import/run_all.php | 34/34 PASS, 0 FAIL |
| CompetenciaContenidoParserTest.php | 72 comprobaciones PASS: asociaciones 3/11, 14 temas, 47 indicadores, periodos fuente, continuidad, rechazo y nombre independiente |
| PdfTableCellsTest.php | 7 comprobaciones PASS: PDF sintético de otra materia, etiquetas centradas, asociaciones, límites y rechazo sin fallback |
| XpdfTableExtractorTest.php | 4/4 fixtures PASS |
| FixturePipelineTest.php | 4/4 PDF → parser → preview → MySQL temporal → lectura normal PASS |
| FullE2ETest.php, dentro de run_all | 4/4 PDF → login → upload → preview → confirmación → editor, BD temporal eliminada |
| PlanAttendanceReportE2ETest.php, dentro de run_all | 37 PASS, 0 FAIL; flujo completo hasta informe, en BD temporal |
| PublicationCoverageTest.php, dentro de run_all | 33 PASS, 0 FAIL |
| AttendanceWindowTest.php, dentro de run_all | 20 PASS, 0 FAIL |
| test/test-gateway-asistencia-real.php | 38 PASS, 0 FAIL, BD temporal |
| test/test-horario/test_clase_conjunta_materias_distintas.php | 20 PASS, 0 FAIL, BD exclusiva temporal |
| test/test_planificacion_pedagogica.php | 15 OK, BD temporal eliminada, identidad positiva |
| test/admin_frontend_temporary.php | 84 OK, 0 FALLAS, BD temporal eliminada |
| test/test_planificacion_frontend.php | 15 PASS, 0 FAIL, BD temporal eliminada |
| PHP lint: 3 fuentes y 2 tests modificados/nuevos | 5/5 sin errores |
| node --check: plan-import-preview.js, planificacion.js, informe-diario.js | 3/3 salida 0 |
| git diff --check | salida 0 |

La primera ejecución de run_all dio 33/34: FixturePipelineTest aún comparaba contra la asociación histórica incorrecta. Tras revisar las asociaciones contra el PDF y separar su contrato esperado, la ejecución completa final dio 34/34. No se eliminó ni deshabilitó ese test.

## Validación REAL de Apache y navegador

Se usó Edge controlado con la sesión autenticada por el usuario, alternativa autorizada al Browser integrado bloqueado por «apply deny-read ACLs».

Asignación 163: Danilo Franco / INFORMATICA / 1° BCB / 2026.
No existe un plan para esa asignación/año.

- Preflight autenticado HTTP 200, ready=true, runtime apache2handler.
- Usuario, asignaciones, schema, staging, pdftotext y límites: PASS.
- Upload desde la UI real: HTTP 201, success=true, token de staging con formato válido.
- Preview real: HTTP 200, navegable. Se abrió visualmente la unidad Word.
- Conteos: 2 unidades, 14 capacidades, 14 temas compuestos y 47 indicadores.
- Asociaciones: sistema operativo 3 capacidades; Word 11 capacidades.
- Metadata: Informática, Danilo Franco, 1° BCB, 2026, viernes.
- can_confirm=true; dos avisos no bloqueantes: contenido compuesto por capacidad y nombre del archivo distinto del curso declarado.
- Programaciones exactas: 0. Botón de confirmación habilitado, sin pulsarlo.

Algunas frases están incompletas en el propio documento renderizado (por ejemplo «Reconoce el procedimiento de las»). Se conservó el texto fuente; no se inventaron terminaciones.

## Backup y protección de datos

Backup previo: C:/Users/PC-05/Documents/Codex/backups/sisco-db/2026-10-05-113832/sisco_db.sql.
mysqldump salió 0; 146187 bytes, 32 tablas y marcador de finalización presente.
SHA256: DF7CE8B664461BAEFEC0BB4CA4170AF16C559C4DDB76792FE11086B36227E99C.
No se realizó una prueba de restauración.

Escrituras académicas realizadas durante esta corrección y reanálisis: 0.
Se compararon conteos y hashes de contenido de las 32 tablas reales antes y después del nuevo upload: todas iguales.
Planes de la asignación 163/año 2026: 0 antes y después.
Bases sisco_pdf_test_* restantes al finalizar: 0.

La confirmación, programación, publicación y huellas del E2E ocurrieron solo en BD temporal. No equivalen a una prueba física ni a publicación real.

## Evidencias locales fuera del sitio web

Directorio: C:/Users/PC-05/Documents/Codex/2026-10-05/in-app-browser-context-source-ambient/

- parser-correction-run-all.log
- preview-corregido-informatica-1-bcb.json (sin cookies, contraseñas, CSRF ni token de staging)
- preview-corregido-informatica-1-bcb.png
- preview-corregido-unidad-word.png
- pdf-review-bcb/page-1.png y page-2.png
- real-db-parser-before.json y real-db-parser-after.json (solo conteos y hashes, sin filas académicas)

## Próximo paso autorizado por etapas

El upload y la corrección están verificados. La importación REAL todavía NO está confirmada.
Se requiere aprobación del usuario sobre este preview corregido para crear un único plan BORRADOR.
Después el usuario debe suministrar las fechas académicas reales de estos 14 temas. La publicación requiere su aprobación separada.
La prueba física continúa pendiente de programación, publicación, fecha real de prueba y confirmación de hardware encendido. No se simularon huellas.

## Actualización: confirmación REAL aprobada, 2026-10-05

El usuario aprobó explícitamente la confirmación después de revisar el preview corregido. Se volvió a verificar el backup, rama y HEAD, el preview HTTP 200 apto para confirmar, y la ausencia de plan para la asignación 163/año 2026.

Se pulsó «Confirmar e importar» y se aceptó el diálogo real de la UI: HTTP 201, success=true, created=true, id_plan=17. SELECT y API HTTP 200 confirman que existe una sola instancia, estado BORRADOR, 2 unidades, 14 capacidades (3/11), 14 temas y 47 indicadores. Se abrió el editor normal.

Cobertura actual: total_temas=14, temas_con_indicadores=14, temas_programados=0, temas_sin_indicadores=0, temas_sin_programacion=14, solapamientos=0, fechas_invalidas=0, can_publish=false. Los 28 campos de inicio/fin están vacíos.

La sección anterior de cero escrituras describe la corrección y reanálisis previos. En esta etapa posterior SÍ se insertó el plan académico real autorizado con su estructura: 1 plan, 2 unidades, 14 capacidades, 14 temas y 47 indicadores. No se insertó programación ni se publicó el plan; no se generaron asistencias o huellas de prueba.

Evidencias fuera del webroot: importacion-real-confirmada-informatica-1-bcb.json, plan-17-borrador-programacion-real.png y programacion-pendiente-plan-17-informatica-1-bcb.md. El usuario debe suministrar las fechas académicas reales. La aprobación de importación no autoriza publicación.

## Actualización: programación ESTIMADA autorizada, 2026-10-05

El usuario cambió expresamente la restricción anterior de fechas reales: solicitó cargar fechas estimadas teniendo en cuenta los horarios de Informática. SELECT confirmó el horario 243, viernes 08:20–09:00, y cero programaciones anteriores para el plan 17.

Se distribuyeron los 14 temas entre 39 viernes potenciales de marzo–junio y julio–noviembre, en bloques de dos o tres viernes. El guardado anual por la UI/API real devolvió HTTP 200. Cada fila identifica en observaciones su carácter ESTIMADO y la necesidad de validarlo contra el calendario académico. No se afirmaron clases históricas realizadas ni se simularon asistencias. Las horas cátedra permanecen nulas.

SELECT y API verifican 14/14 temas programados, 14 con indicadores, 0 pendientes, 0 duplicados, 0 fechas inválidas, 0 solapamientos y can_publish=true. Cada viernes potencial de ambos periodos tiene exactamente un tema. El estado continúa BORRADOR; no se pulsó Publicar. Se insertaron 14 filas de programación reales con autorización del usuario.

Evidencias fuera del webroot: programacion-estimada-plan-17.md, programacion-estimada-plan-17.json, programacion-estimada-plan-17-api.json y plan-17-programacion-estimada-guardada.png. La siguiente aprobación requerida es publicar el plan con esta programación estimada.

## Actualización: PUBLICADO y prueba en horario real, 2026-10-05

El usuario aprobó publicar el plan 17 con la programación estimada. La UI/API real devolvió HTTP 200 y API/SELECT verifican estado PUBLICADO, 14/14 temas programados y cero solapamientos. No se hicieron cambios de código, firmware, horarios o reloj.

El usuario eligió usar fecha, hora y materia actuales para la prueba física. A las 13:04:59 del 2026-10-05, el horario real vigente de Danilo es Laboratorio de Programación / 3° BTI, asignación 23, horario 47 (lunes 12:50–13:30), aula 30. Esa asignación tiene su propio plan 8 PUBLICADO; no se usará el plan de Informática de otra asignación.

La fase 5 queda bloqueada: el plan 8 tiene un único tema, ID 19 «Sistema informático», con un indicador, programado únicamente para 2026-09-07. SELECT devuelve cero temas para hoy. Se solicitó aprobación para agregar solo una programación puntual del tema 19 para 2026-10-05, preservando la fecha anterior. No se realizó aún esta mutación ni se pidieron huellas. Falta además confirmación de hardware encendido.

Evidencia fuera del webroot: publicacion-y-bloqueo-prueba-fisica-2026-10-05.md, plan-17-publicado-real.png, preflight-prueba-fisica-ahora-2026-10-05.json y diagnostico-plan-8-sin-tema-hoy.json. La publicación real fue autorizada; el circuito físico y su informe no están completados ni se afirman como PASS.

## Preparación de la prueba física — 2026-10-05, 13:18 aprox.

- El usuario autorizó expresamente agregar una programación puntual para hoy al tema 19, «Sistema informático», del plan 8 PUBLICADO, asignación 23: Danilo Franco / Laboratorio Programación / 3° BTI.
- Guardado mediante el editor normal: HTTP 200. Nueva programación 39: 2026-10-05 a 2026-10-05. La programación anterior 12 del 7 de septiembre se conservó.
- SELECT confirmó exactamente un tema programado para hoy: unidad 17, capacidad 17, tema 19; indicador 29, «Conoce los SDBD relacionales».
- Horario real 47: lunes 12:50–13:30, aula 30. No se modificó el horario ni el reloj.
- Línea base a las 13:12:58: último evento 32; ninguna clase de la asignación 23 para hoy; ninguna asistencia de Danilo en esta franja. Evidencia privada fuera del sitio: `baseline-huellas-reales-2026-10-05.json`.
- SELECT a las 13:17:53: ningún evento posterior a 32, ninguna asistencia del profesor o alumno objetivo y ninguna clase creada. Evidencia: `huellas-reales-hardware-encendido.json`.
- El usuario aclaró que el hardware aún está terminando de encender y que Danilo marcará después. La prueba física sigue PENDIENTE; encender hardware no demuestra recepción de huellas.
- Informe diario abierto en la sesión real; no se pulsó «Crear o recuperar clase». No se simularon huellas ni se enviaron POST al Gateway.
- Después de las 13:30 debe verificarse nuevamente el horario vigente antes de atribuir una nueva marca a esta asignación.
