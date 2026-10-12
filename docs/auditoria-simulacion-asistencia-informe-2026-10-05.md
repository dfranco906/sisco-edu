# Auditoría de simulación de asistencia e informe — 5 de octubre de 2026

## Resultado y alcance

La simulación de los envíos del profesor y del alumno pasó de punta a punta mediante el endpoint HTTP Gateway de una instalación temporal. Se verificaron asistencia, selección automática de tema, indicadores, informe visible y repetición sin duplicar clases o asistencias.

No se envió ninguna marca simulada al Gateway HTTP de producción ni se generaron asistencias falsas en `sisco_db`. El sensor no pudo encender, según lo informado por el usuario: reconocimiento biométrico, comunicación LoRa y transmisión del dispositivo físico siguen pendientes. Esta prueba no demuestra que esos componentes funcionen ni sustituye una prueba del upload en Apache real.

## Repositorio

- Repositorio: `dfranco906/sisco-edu`.
- Rama inicial/final: `frontend-recovery-audit`.
- HEAD inicial/final: `0b3bc45b8757639f7dcd2c2b26bf0c7d05e4c62b`.
- HEAD esperado en el prompt anterior: `c97cb0a30c0fdcef49a65392f3a84d57a4abed60`. Antes de esta ejecución ya existían los commits posteriores de corrección del importador y dirección local del Gateway; esta tarea no los modificó.
- Cambio de código: una condición de espera en `test/admin_frontend_headless.js`.
- Documento nuevo: este informe.
- Se preservó `docs/tesis/`, que ya estaba sin seguimiento. No se hizo commit ni push en esta ejecución.

## Nuevo horario utilizado

Se consultaron los horarios reales por SELECT. El horario anterior 47, de 12:50 a 13:30, ya había terminado. La simulación reprodujo el horario vigente 49, sin modificar horarios reales:

| Campo | Valor |
|---|---|
| Momento observado en MySQL, America/Asuncion | 2026-10-05 14:41:14 |
| Profesor | Danilo Franco, ID 21 |
| Materia / curso | Laboratorio Programación / 3° BTI |
| Asignación / aula | 23 / 30 |
| Horario | 49, lunes 14:10–14:50 |
| Plan | 8, PUBLICADO |
| Unidad / capacidad / tema | 17 / 17 / 19 |
| Tema | Sistema informático |
| Indicador | 29: Conoce los SDBD relacionales |
| Alumno marcado en la copia | Thiago Zarate, ID 85 |

Este contexto corresponde a Laboratorio de Programación. Es distinto del plan 17 de Informática de 1° BCB importado y publicado anteriormente.

## Aislamiento y método

1. Se creó un entorno `sisco_pdf_test_*` con el esquema actual, sin migraciones nuevas.
2. Se copiaron mediante SELECT las filas académicas necesarias del plan 8, su programación, profesor, curso, aula, horario y estudiantes. Sólo se escribieron en la copia temporal.
3. Se comprobó que profesor y alumno tienen huella activa en origen. Los bytes biométricos reales no se copiaron: se usaron plantillas sintéticas exclusivamente dentro del entorno temporal.
4. Se hizo login HTTP válido con una identidad positiva de prueba.
5. Se fijó el reloj únicamente en las conexiones de la copia temporal al momento real observado, para que la prueba fuese reproducible. No se alteró el reloj real ni se aceptaron timestamps enviados por el Gateway en producción.
6. Se enviaron POST al endpoint existente `src/api/Gateway/registrar_asistencia.php` del servidor temporal PHP. Se comprobaron HTTP 200 y `ok=true`; HTTP 200 solo no se trató como prueba de éxito.
7. La marca del profesor creó automáticamente la clase del horario 49, asociada al plan 8 y tema 19. Se copió el indicador 29.
8. La marca del alumno, 30 segundos después en el reloj de prueba, se asoció a esa misma clase, dentro de la ventana permitida.
9. El informe se consultó por HTTP y se abrió en Edge headless desde «Clases de la fecha», sin pulsar «Crear o recuperar clase». Mostró los datos correctos, ocho estudiantes, **1 PRESENTE y 7 AUSENTES**, sin excepciones JavaScript.
10. Se repitieron ambas marcas: se reutilizaron clase y asistencias. Los eventos permanecen como bitácora; la idempotencia no exige eliminar los eventos repetidos.
11. Se eliminó la base temporal y su instalación al finalizar.

## Resultados ejecutados

| Prueba | Resultado |
|---|---|
| Simulación del horario actual con contexto real copiado | 16 PASS, 0 FAIL |
| `php test/plan_import/run_all.php` | 34/34 suites PASS, 0 FAIL |
| `PlanAttendanceReportE2ETest.php`, incluido en run_all | 37 PASS, 0 FAIL; PDF financiero → BORRADOR → 18/18 → PUBLICADO → Gateway HTTP → informe 3/5 presentes |
| `PublicationCoverageTest.php`, incluido en run_all | 33 PASS, 0 FAIL |
| `AttendanceWindowTest.php`, incluido en run_all | 20 PASS, 0 FAIL |
| `ProgrammingRulesTest.php`, incluido en run_all | 20 verificaciones PASS |
| `test/test-gateway-asistencia-real.php` | 38 PASS, 0 FAIL; pese a su nombre, usa BD temporal |
| `test/test_planificacion_pedagogica.php` | 15 OK; identidad positiva, BD temporal eliminada |
| `test/test-horario/test_clase_conjunta_materias_distintas.php` | 20 PASS, 0 FAIL |
| `test/test_planificacion_frontend.php` | 15 PASS, 0 FAIL |
| `test/admin_frontend_temporary.php`, ejecución final | 84 OK, 0 FALLAS |
| PHP lint: GatewayAttendanceService, ClaseDiaria, registrar_asistencia y script privado de simulación | PASS |
| Node syntax: planificacion, plan-import-preview, informe-diario, smoke administrativo y script privado del informe | PASS |
| `git diff --check` | PASS |

### Fallo inicial del smoke administrativo

La primera ejecución dio **83 OK / 1 FALLA** en «Informe diario: filtros y clases», con `lista=0`, sin errores HTTP ni JavaScript. La condición anterior terminaba al cargarse el selector, aunque la consulta de clases aún no había rellenado el contenedor, que inicialmente está vacío.

Se corrigió exclusivamente la espera del test para requerir una tarjeta `[data-clase]` o el resultado vacío `.plan-empty`. Se conservó la aserción final y no se modificó código de producción. La versión final pasó **84/84**.

Las salidas JSON generadas por el parser sólo variaron en `elapsed_ms`; se comprobó igualdad semántica excluyendo ese campo y se retiró ese ruido del diff.

## Base real y evidencia

- Escrituras de esta simulación y regresiones en `sisco_db`: **0**.
- Las **32 tablas** tenían hashes de contenido idénticos antes y después de la simulación del contexto real.
- Bases `sisco_pdf_test_*` restantes al finalizar: **0**.
- No se borraron datos reales, archivaron planes, cambiaron horarios reales o modificaron firmware.

Evidencias privadas, fuera del directorio público de Apache:

`C:/Users/PC-05/Documents/Codex/2026-10-05/in-app-browser-context-source-ambient/`

- `simulacion-horario-actual.log`: 16 verificaciones y comprobación de integridad de la BD real.
- `simulacion-horario-actual-api.json`: respuestas HTTP y datos del informe temporal.
- `simulacion-horario-actual-navegador.json`: contenido visible del informe y ausencia de errores JavaScript.
- `regresion-simulacion-run-all.log`: 34 suites.
- `regresion-simulacion-dominio.log`: Gateway, planificación y clases conjuntas.
- `regresion-simulacion-frontend.log`: planificación frontend y fallo inicial del smoke.
- `regresion-simulacion-admin-final.log`: resultado final 84/84.
- `current-simulation-test.php` y `current-simulation-report.cjs`: scripts privados utilizados, sin credenciales reales embebidas.

## Pendientes físicos

Flujo de software simulado: **PASS**.

Prueba física completa: **PENDIENTE**. Debe resolverse el encendido del sensor y verificarse reconocimiento real, LoRa, recepción HTTP en Apache real y generación de clase/informe a partir de esas marcas físicas. No se investigó ni reparó el sensor en esta tarea.
