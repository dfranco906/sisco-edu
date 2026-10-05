# Auditoría de programación, asistencia e informe — 2026-10-05

El flujo normal `PRESENTE` está implementado y pasó las pruebas integrales HTTP y de navegador en bases temporales. Esta ejecución tiene una **observación de auditoría obligatoria**: la prueba heredada se lanzó accidentalmente una vez contra `sisco_db` antes de migrarla. No se puede declarar «escrituras reales = 0» para toda la sesión. El incidente y las verificaciones posteriores están detallados abajo.

## Alcance y HEAD

- Repositorio: `dfranco906/sisco-edu`.
- Rama inicial y final: `frontend-recovery-audit`.
- Base auditada solicitada: `ed5d84a2439f2d7b6fca844d2df9d04f92087a22`, ancestro del HEAD inicial.
- HEAD inicial: `cef7f8e696de81278fc337b09de2e061b0b3bb9c`.
- HEAD al finalizar las pruebas: `cef7f8e696de81278fc337b09de2e061b0b3bb9c`, con los cambios de esta tarea todavía sin commit en ese momento.
- HEAD final versionado: el commit que incorpora este informe y los cambios listados. Su hash se obtiene con `git log -1 --format=%H -- docs/auditoria-programacion-asistencia-informe-2026-10-05.md`. El usuario autorizó posteriormente el commit y push a `frontend-recovery-audit`; no hubo cambios funcionales después de las pruebas documentadas.
- `git status`, `git diff` y `git diff --cached` se inspeccionaron antes de modificar. El trabajo previo no versionado en `docs/tesis/` se preservó.
- No se editaron firmware ni migraciones. No se ejecutaron nuevas migraciones en la base real. Se reutiliza el esquema existente.

## Modelo y auditoría del flujo

Se mantiene la cadena existente:

```text
planes_anuales → plan_unidades → plan_capacidades → plan_temas
→ plan_indicadores + plan_tema_programacion
→ clases_diarias → clase_diaria_indicadores
→ asistencias_profesores + asistencias_estudiantes → informe diario
```

Se revisaron las clases de planificación, importación, clase diaria y asistencias; las APIs de planes, estructura, programación, preview, confirmación, Gateway e informes; las vistas y JavaScript correspondientes; los tres archivos de migración indicados y el recorrido de `estado` en ambos archivos LoRa. Se conserva un solo modelo de planes y una sola fuente de la clase para el informe.

## PROGRAMACIÓN

- `PlanProgrammingRules` comparte la validación entre el importador y la edición manual: fechas ISO reales, inicio ≤ fin, año del plan, pertenencia del tema, períodos no duplicados y ningún solapamiento inclusivo entre temas distintos. Un tema puede tener varios períodos.
- La cobertura expone `total_temas`, `temas_con_indicadores`, `temas_programados`, `temas_sin_indicadores`, `temas_sin_programacion`, `solapamientos`, `fechas_invalidas`, `duplicados`, `can_publish` y un mensaje accionable.
- Publicar exige al menos un tema y cobertura completa de indicadores y programación válida. El backend devuelve **HTTP 409** cuando no se cumple; un único tema completo ya no basta.
- Las mutaciones de un plan publicado conservan su cobertura o revierten la transacción. Se bloquean, por ejemplo, la eliminación del último indicador o del último período de un tema.
- El editor muestra **PROGRAMACIÓN ANUAL**, una fila por tema/período, unidad, código, título, período fuente, inicio, fin, horas y estado, además de los contadores de pendientes. `fecha_texto` tiene prioridad sobre `proceso_texto` como referencia. No se convierte «Febrero - Marzo» en fechas.
- El guardado anual reemplaza la programación dentro de una transacción con bloqueo del plan. Se probó tanto una fila 8 inválida como un fallo SQL al insertar esa fila: **ninguna de las siete anteriores queda guardada parcialmente**.
- En el preview las nuevas programaciones empiezan con fechas vacías; guardar esas filas se rechaza. En el editor anual dejar ambas fechas vacías mantiene un tema pendiente y no inserta una programación vacía.
- `ARCHIVADO` se protege en backend para cabecera, estado, estructura, reordenamiento, evaluación y programación, incluyendo el guardado masivo. Las once regresiones HTTP de mutación devuelven 409 y preservan íntegramente el plan.

## ASISTENCIA

La ventana usa exactamente:

```text
inicio = MAX(hora_inicio_clase, hora_marca_profesor)
fin    = MIN(hora_fin_clase, inicio + VENTANA_ASISTENCIA_ALUMNOS_MINUTOS)
inicio ≤ momento_alumno ≤ fin
```

- A: profesor 06:50 / alumno 07:05, clase 07:00–07:40 → aceptado.
- B: profesor 06:55 / alumno 07:06 → aceptado.
- C: profesor 07:05 / alumno 07:14 → aceptado.
- D: profesor 06:50 / alumno 07:10:01 → rechazado.
- E: profesor 07:35 / alumno 07:40:01 → rechazado; 07:40:00 se acepta.
- Se verifica el rechazo antes del inicio y la idempotencia en los límites. El informe también cuenta la presencia aceptada exactamente al final.
- Las huellas de alumnos deben estar activas incluso si `estudiantes.huella_id` conserva una referencia a una plantilla desactivada.
- Repetir la huella conserva la clase y las asistencias. Si después se reprograman los temas, una repetición conserva el tema de la clase existente y copia sus propios indicadores, sin mezclarlos con los del nuevo candidato.
- Una marca del profesor sigue abriendo las clases hermanas; cada asignación conserva su plan, tema e indicadores. El evento físico permanece como bitácora incluso cuando falla la operación de dominio.

## INTEGRACIÓN e informe

El nuevo `PlanAttendanceReportE2ETest.php` ejecuta un login HTTP válido, sube el PDF **Plan Anual - tercer curso - BTI - Administracion financiera.pdf** y verifica **6 unidades, 6 capacidades, 18 temas y 18 indicadores**. Resuelve catálogos, confirma únicamente en la BD temporal, comprueba `BORRADOR`, programa los 18 temas sin solapamientos y publica con cobertura 18/18.

Luego crea un horario temporal y seis huellas activas, marca al profesor a las 06:50 mediante el endpoint HTTP de Gateway y verifica los IDs de plan, unidad, capacidad, tema e indicadores de la clase. Marca tres de cinco alumnos y abre por HTTP el informe de esa misma clase: materia, profesor, grado, fecha, horario, unidad, capacidad, tema, contenido, indicadores, membrete configurado, observación anecdótica y **3 PRESENTES / 2 AUSENTES**. Comprueba repeticiones y reprogramación sin alterar el contenido de la clase ya abierta. Finalmente elimina la base temporal y comprueba que dejó de existir.

Las fechas de estas pruebas son un calendario sintético limitado a fixtures; no se infieren del PDF ni se registran en producción. El reloj autoritativo se controla solo en la copia temporal de `db.php`, sin habilitar fechas enviadas por clientes en el Gateway real.

El informe sigue leyendo `clases_diarias` y sus indicadores. Cuando `plan_temas.contenido` existe, ahora lo muestra debajo del título, escapado como texto. La prueba de navegador comprueba ese contenido y los cinco alumnos con sus estados. El E2E comprueba el campo de membrete; no equivale a validar físicamente una impresión o un sensor.

## TESTS ejecutados

Comandos PHP ejecutados con `C:\xampp\php\php.exe`; comprobaciones JavaScript con Node 24 y navegador headless Microsoft Edge. Todos los resultados siguientes corresponden a la versión final de los archivos de producción.

| Comando / suite | Resultado |
| --- | --- |
| `test/plan_import/run_all.php` | **33/33 PASS; 0 FAIL** |
| Nuevo `PlanAttendanceReportE2ETest.php` (incluido en run_all) | **37 PASS; 0 FAIL**, HTTP y BD temporal eliminada |
| `test/test-gateway-asistencia-real.php` | **38 PASS; 0 FAIL**, BD temporal, clases normales y conjuntas |
| `test/test_planificacion_pedagogica.php`, ya migrado | **15 OK**, BD temporal eliminada, identidad positiva |
| `test/test-horario/test_clase_conjunta_materias_distintas.php` | **20 PASS; 0 FAIL**, HTTP y BD temporal |
| `test/test-horario/test_receso_tercer_ciclo.php` | **OK**, RECESO permitido y materia normal bloqueada |
| `test/admin_frontend_temporary.php` → headless existente | **84 OK; 0 FALLAS**, BD temporal eliminada |
| `test/test_planificacion_frontend.php` → nuevo headless | **15 PASS; 0 FAIL**, preview, programación anual, publicación, Gateway, informe y archivo |
| Lint PHP de archivos afectados | **18/18 PASS** |
| `node --check` de JavaScript/CJS afectados | **5/5 PASS** |
| `git diff --check` | **PASS** |

Resultados individuales de las 33 suites del importador:

| Grupo | Suite | Resultado |
| --- | --- | --- |
| CLI | ParseCliTest | PASS, 4 fixtures |
| HTTP | ApiPreflightTest | PASS |
| HTTP | ConfirmImportTest | PASS |
| HTTP | FullE2ETest | PASS, 4/4 PDFs |
| HTTP | ImportEntryUiTest | PASS |
| HTTP | PlanAttendanceReportE2ETest | PASS, 37 comprobaciones |
| HTTP | PreviewDecisionsTest | PASS |
| HTTP | PreviewTest | PASS |
| HTTP | PublicationCoverageTest | PASS, 33 comprobaciones |
| HTTP | SecurityMatrixTest | PASS |
| HTTP | SessionLoginTest | PASS |
| HTTP | SessionValidationTest | PASS, 21 comprobaciones |
| HTTP | UploadTest | PASS |
| Migración / integración | AttendanceWindowTest | PASS, 20 comprobaciones |
| Migración / integración | CanonicalRoundTripTest | PASS, 4/4 PDFs |
| Migración / integración | FixturePipelineTest | PASS, 4/4 PDFs |
| Migración / integración | ManualEditorImportTest | PASS |
| Migración / integración | ManualImportedParityTest | PASS, ambos planes con cobertura completa |
| Migración / integración | PlanCodesMigrationTest | PASS, solo entorno temporal |
| Migración / integración | PlanImportPersistenceTest | PASS |
| Migración / integración | ZeroUserIdentityRepairTest | PASS, solo entorno temporal |
| Unidad | AdministracionFinancieraParserTest | PASS, 2/2 fixtures |
| Unidad | CanonicalPlanTest | PASS |
| Unidad | CatalogMatcherTest | PASS |
| Unidad | CompetenciaContenidoParserTest | PASS |
| Unidad | EnvironmentPreflightTest | PASS |
| Unidad | ExcelAvanzadoParserTest | PASS |
| Unidad | PlanFormatDetectorTest | PASS |
| Unidad | PlanImportStagingTest | PASS |
| Unidad | PlanValidatorTest | PASS |
| Unidad | ProgrammingRulesTest | PASS, 20 comprobaciones |
| Unidad | XpdfTableExtractorTest | PASS, 4/4 fixtures |
| Parser POC | plan_parser_poc/run.php | PASS, 4/4 expectativas revisadas |

Durante la preparación hubo fallos corregidos antes de los resultados finales: el test de paridad debía programar todos los temas para publicar; el smoke administrativo temporal requería fixtures de huellas y docentes separados para los niveles; el nuevo test de navegador buscó inicialmente un nombre de botón incorrecto. No se sustituyeron respuestas de las APIs para obtener los PASS del nuevo flujo. El smoke administrativo original conserva su mock preexistente del mensaje de reset de membrete; ese control aislado no demuestra una carga real de membrete.

## BD REAL — incidente y evidencia

**Escrituras durante toda esta sesión: distintas de cero. No se cumplió esa restricción del encargo.**

Un script de preparación invocó `python`, que no estaba disponible. Al continuar el comando, se lanzó la versión antigua de `test/test_planificacion_pedagogica.php` antes de reemplazar su conexión real y su identidad `id_usuario=0`. La creación del plan falló con `PedagogiaException`; el bloque `finally` original realizó la limpieza de los fixtures.

Según el recorrido ejecutado de esa versión, los registros de fixture creados antes de fallar fueron: 1 aula, 1 grado, 1 profesor, 1 usuario, 1 materia, 1 asignación, 1 horario y 5 estudiantes (**12 inserciones**), más la actualización que vinculó profesor y usuario. El código de limpieza eliminó esos 12 registros. Esto representa **25 operaciones DML exitosas inferidas del código y la ejecución**, más el intento fallido de insertar el plan; no es un conteo procedente de un log SQL. Los contadores AUTO_INCREMENT pudieron avanzar y la limpieza no deshace ese hecho.

Verificación posterior de solo lectura, sin nuevas eliminaciones ni reparaciones sobre la base real:

| Consulta agregada | Resultado |
| --- | --- |
| Aulas con `codigo LIKE 'PLAN_TEST_%'` | 0 |
| Grados con `nombre LIKE 'PLAN_TEST_%'` | 0 |
| Profesores con `apellido LIKE 'PLAN_TEST_%'` | 0 |
| Usuarios con `usuario LIKE 'PLAN_TEST_%'` | 0 |
| Materias con `nombre LIKE 'PLAN_TEST_%'` | 0 |
| Estudiantes con `apellido LIKE 'PLAN_TEST_%'` | 0 |
| Planes con `created_by=0` | 0 |
| Filas cambiadas durante las verificaciones posteriores, comparando hashes de las 32 tablas | **0 tablas cambiadas** |

No se confirmó el PDF real, no se insertaron programaciones reales, no se publicó un plan real y no se generaron asistencias falsas reales. El incidente sí creó y eliminó fixtures en tablas reales; no se presenta el estado final sin residuos como si significara cero escrituras. La comparación de hashes empieza **después** del incidente y no permite certificar retrospectivamente las filas anteriores a él. Las consultas no expusieron contraseñas, cookies, IDs de sesión ni filas personales.

La regresión responsable quedó migrada a `PlanImportTestEnvironment`, con una conexión temporal comprobada, usuario positivo, huellas propias y limpieza mediante eliminación de la BD temporal. Las ejecuciones posteriores del test ya no escriben en `sisco_db`.

## RET_ANTICIPADO y bloqueos restantes

**P2 pendiente:** `esp_aula.ino` genera `RET_ANTICIPADO`; `esp_gateway.ino` lo envía por HTTP; `registrar_asistencia.php` lo lee, pero no lo pasa al servicio. `GatewayAttendanceService` sigue registrando el evento como `PRESENTE`. Por tanto, no se debe presentar una marca de retiro como funcionalmente soportada.

El esquema actual permite cualquier texto en `eventos_asistencia.estado`, pero los estados de ambas tablas de asistencia son `ENUM('PRESENTE','AUSENTE','TARDANZA')`. Resolver completamente un retiro exige definir su asociación con una clase y su representación en el informe; no se agregó una migración ni una semántica de salidas dentro de este P0. Este pendiente no invalida los tests del flujo normal `PRESENTE`.

- Bloqueos técnicos observados del flujo normal en BD temporal: **ninguno**.
- Cierre de auditoría sin observaciones: **pendiente**, por el incidente de escrituras reales.
- Prueba física de sensores, LoRa e impresión: **no ejecutada** en esta fase.
- **LISTO PARA PRUEBA FÍSICA REAL: SÍ para el flujo normal PRESENTE, técnicamente; requiere una prueba posterior autorizada. No incluye RET_ANTICIPADO ni equivale a un cierre de auditoría sin observaciones.**

## Archivos modificados o creados por esta tarea

Producción:

- `src/classes/PlanProgrammingRules.php` — nuevo dominio compartido.
- `src/classes/PlanImport/ProgrammingValidator.php`.
- `src/classes/PlanificacionPedagogica.php`.
- `src/classes/ClaseDiaria.php`.
- `src/classes/AsistenciaEstudiante.php`.
- `src/classes/GatewayAttendanceService.php`.
- `src/api/Planificacion/programacion.php`.
- `mvc/views/planificacion/editor.php`.
- `public/js/planificacion.js`.
- `public/js/plan-import-preview.js`.
- `public/js/informe-diario.js`.

Pruebas y soporte:

- `test/plan_import/unit/ProgrammingRulesTest.php`.
- `test/plan_import/http/PublicationCoverageTest.php`.
- `test/plan_import/http/PlanAttendanceReportE2ETest.php`.
- `test/plan_import/migration/AttendanceWindowTest.php`.
- `test/plan_import/migration/ManualImportedParityTest.php`.
- `test/plan_import/support/TestEnvironment.php`.
- `test/plan_import/support/TestHttp.php`.
- `test/plan_import/support/PlanningBrowser.cjs`.
- `test/test_planificacion_pedagogica.php`.
- `test/admin_frontend_temporary.php`.
- `test/test_planificacion_frontend.php`.
- `test/planning_annual_headless.cjs`.
- Este informe: `docs/auditoria-programacion-asistencia-informe-2026-10-05.md`.

Los cuatro JSON de salida del parser generaron únicamente nuevos tiempos de ejecución; esos valores se devolvieron a los anteriores, sin descartar trabajo previo. `docs/tesis/` sigue apartado de esta tarea.
