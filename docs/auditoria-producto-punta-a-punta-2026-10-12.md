# Auditoría del producto de punta a punta — 12 de octubre de 2026

## Dictamen

**El producto todavía no está listo para entrega ni para un piloto completo con el firmware actual.** El recorrido de software PDF → borrador → programación anual → publicación → marca HTTP → clase → informe diario pasa las regresiones. Quedan bloqueos comprobados de seguridad, integración del Gateway, configuración del servidor e integridad de informes históricos. Además, la carga real del colegio es insuficiente para automatizar todas las materias.

No se asigna un porcentaje de avance: los tests aprobados no miden instalación, seguridad, cobertura académica ni funcionamiento físico.

## Push y alcance

- Rama: `frontend-recovery-audit`; repositorio: `dfranco906/sisco-edu`.
- Push inicial confirmado: `94c59331e143b2ef6bd25f6b438a1a88228e9393`. Incluye la espera corregida del smoke y los documentos de simulación/tesis pendientes.
- `origin/main` permanece en `906d8c1`. El push a esta rama **no integra main ni despliega el producto**. Al iniciar esta auditoría, main tenía 144 commits alcanzables desde la rama de trabajo que no estaban en main.
- Se revisaron código PHP/JS, firmware LoRa, migraciones, navegación, configuración local, esquema/datos agregados de `sisco_db` y respuestas HTTP de Apache.
- Las escrituras de prueba se hicieron exclusivamente en bases `sisco_pdf_test_*`. Las consultas a datos reales fueron de lectura; no se registraron marcas ficticias en el colegio ni se aplicaron migraciones a su base.
- No se tuvo acceso operativo a sensores, ESP32 ni al enlace radio. No se compiló ni flasheó firmware: Arduino CLI informó que no había plataformas ni bibliotecas instaladas.
- No se repitió una carga autenticada de PDF sobre la instalación real de Apache. Sí se verificaron la configuración local del ejecutable, el preflight CLI y el flujo HTTP completo en instalaciones temporales.

## Evidencia ejecutada hoy

| Comprobación | Resultado |
|---|---|
| `test/plan_import/run_all.php` | 34/34 suites PASS |
| Flujo PDF → publicación → Gateway HTTP → informe, incluido arriba | 37 PASS |
| Cobertura de publicación, incluida arriba | 33 PASS |
| Ventana de asistencia, incluida arriba | 20 PASS |
| `test/test-gateway-asistencia-real.php` | 38 PASS, BD temporal |
| `test/test_planificacion_pedagogica.php` | 15 OK, BD temporal |
| Clases conjuntas de materias distintas | 20 PASS, HTTP temporal |
| `test/test_planificacion_frontend.php` | 15 PASS en navegador |
| `test/admin_frontend_temporary.php` | 84 OK en navegador |
| `test/admin_crud_smoke.js` servido desde copia temporal | 65 OK; CRUD, actualización, desactivación, restauración y eliminación |
| Migración de entrega aplicada dos veces | PASS, sólo BD temporal |
| Sintaxis | 367 archivos PHP y 16 JS/CJS, sin fallos |
| Preflight PDF CLI con ruta Xpdf configurada explícitamente | PASS |
| Hashes de contenido de las 32 tablas reales, antes/después | Idénticos |
| Bases temporales restantes al cerrar | 0 |

Los servicios Apache/MySQL estaban apagados al comenzar. Las primeras ejecuciones fallaron por conexión rechazada y se repitieron después de iniciarlos; esos fallos no se atribuyen al código. Durante una repetición de las sondas se interrumpió MySQL y falló la limpieza de una base temporal; se reinició, se completaron las sondas y se eliminó el residuo identificado de esta auditoría. La causa de esa interrupción no quedó determinada. Falta validar el arranque y la estabilidad del servicio como parte del despliegue.

Los JSON de fixtures regenerados sólo cambiaron `elapsed_ms`; se verificó el diff y se retiró ese ruido. Las regresiones aprobadas no cubrían varios de los defectos reproducidos abajo.

Evidencia privada, fuera del directorio servido por Apache:

`C:/Users/PC-05/Documents/Codex/auditoria-producto-2026-10-12/`

Contiene logs de regresiones, `runtime-before.json`, `runtime-after.json`, `data-integrity.log`, `isolated-probes.php` y `isolated-probes.json`. No se publican dumps, plantillas biométricas ni credenciales en este informe.

## Preparación real del colegio

| Dato agregado de la instalación local | Observación |
|---|---|
| Asignaciones activas de 2026 | 169 |
| Asignaciones sin horario activo | 6 |
| Asignaciones sin plan publicado | 166; sólo 3 tienen uno |
| Planes | 3 publicados, 1 borrador, 1 archivado |
| Profesores activos | 31; 30 sin cuenta vinculada |
| Profesores con huella activa | 1 de 31 |
| Estudiantes activos / con huella | 8 / 1 |
| Horarios del lunes 12/10/2026 sin tema publicado que cubra la fecha | 64 de 64 bloques |
| Nodos registrados activos | 1 aula, sin heartbeat registrado |
| Sincronizaciones | 10 CONFIRMADO y 1 ERROR; esos estados no prueban instalación física por la ruta alternativa insegura |
| Clases registradas | 1, del 07/09/2026 |
| Membrete | 1 configuración activa |

Las cifras describen esta base local, no otra instalación remota. Tener un plan publicado con todos sus temas programados no garantiza cubrir cada clase del año: las fechas pueden dejar huecos respecto de los horarios reales.

## Bloqueos antes de entrega

### P0-01. APIs administrativas permiten operaciones sin sesión

**Reproducido:** crear un usuario `SuperAdmin` sin cookie ni token devolvió HTTP 201 en la copia temporal. Los 65 pasos del smoke CRUD también funcionaron sin autenticación. En Apache real, lecturas anónimas de usuarios, profesores y estudiantes devolvieron HTTP 200 con datos. La lectura de huellas devuelve `SELECT *`, incluido `fingerprint_data`, sin autorización.

Referencias: [crear_usuario.php](../src/api/Usuario/crear_usuario.php), [leer_usuarios.php](../src/api/Usuario/leer_usuarios.php), [leer_huella_template.php](../src/api/HuellaTemplate/leer_huella_template.php), [HuellaTemplate.php](../src/classes/HuellaTemplate.php). La autorización de [crear_horario.php](../src/api/Horario/crear_horario.php) sólo se activa para la modalidad de materias distintas; incluir un helper no protege el flujo básico.

**Falta:** aplicar sesión válida, roles y permisos sobre la entidad a todos los CRUD; limitar explícitamente la lectura de biometría; proteger escrituras con CSRF; probar anónimo, profesor, coordinador y administrador para cada familia de endpoints. Las comprobaciones del importador PDF no cubren automáticamente las APIs antiguas.

**Cierre:** ningún usuario anónimo puede leer datos protegidos, crear cuentas, alterar horarios/asistencias ni confirmar sincronizaciones; un profesor no puede operar recursos ajenos.

### P0-02. Apache publica archivos internos

**Reproducido en Apache:** `.git/HEAD`, `docs/sisco_db.txt` y `docs/sql.txt` devolvieron HTTP 200. El directorio de pruebas también entrega artefactos por HTTP. El `.gitignore` no controla lo que Apache sirve.

**Falta:** definir una superficie pública de despliegue, denegar `.git`, dumps, documentación interna, scripts y pruebas; retirar materiales sensibles del árbol servido y revisar el historial ya publicado. Revisar y rotar secretos compilados presentes en el firmware. La clave del Gateway y datos Wi-Fi están literales en [esp_gateway.ino](../esp/LoRa/esp_gateway.ino); no se transcriben aquí.

**Cierre:** solicitudes a esos recursos reciben 403/404; el paquete de entrega sólo contiene lo necesario y las credenciales se configuran fuera de Git.

### P0-03. Aula y Gateway tienen protocolos incompatibles

**Comprobado en código:** [esp_aula.ino](../esp/LoRa/esp_aula.ino) encola `AST2:<source_event_id>:...` y sólo elimina la marca con `ASTACK`. [esp_gateway.ino](../esp/LoRa/esp_gateway.ino) acepta únicamente `AST:` y no emite `ASTACK`. Al instalar ambos archivos actuales, el Gateway ignora las nuevas marcas y la cola del aula permanece pendiente.

**Falta:** completar el Gateway para AST2, cola persistente, reintentos y validación de respuesta HTTP `ok=true` con identidad coincidente antes del ACK. Evitar perder tráfico durante esperas de fragmentos. Compilar los tres firmwares con versiones de core/librerías fijadas y actualizar equipos en un orden compatible.

**Cierre:** una huella real atraviesa sensor → aula → LoRa → Gateway → Apache → asistencia → informe; repetir, cortar Wi-Fi y reiniciar no duplica ni pierde la marca.

### P0-04. Backend nuevo sin migración ni clave de dispositivo configurada

**Comprobado:** `sisco_db` no tiene `gateway_delivery_receipts` ni `eventos_asistencia.source_event_id`. La configuración CLI observada no tiene clave de Gateway; no se encontró configuración local/Apache que la suministrara. El backend ya exige una clave explícita.

**Reproducido en copia del esquema actual:** POST con `source_event_id` devolvió HTTP 500. Aplicando [20261005_entrega_asistencia_confiable.sql](../database/migrations/20261005_entrega_asistencia_confiable.sql) exclusivamente en la copia, el mismo recorrido funcionó: primera entrega aceptada, repetición idempotente y reutilización del ID con otro contenido rechazada.

**Falta:** respaldar, completar Gateway, preparar migración y configuración privada coordinadas y verificar el contrato en Apache real. No aplicar el checkpoint incompleto como una actualización aislada.

**Cierre:** esquema, clave y firmware compatibles; comprobación autenticada real sin error SQL ni marcas ficticias.

### P0-05. Se puede confirmar una sincronización sin instalación

**Reproducido en copia temporal:** [SyncBiometrica/confirmar_sync.php](../src/api/SyncBiometrica/confirmar_sync.php) aceptó POST anónimo y dejó una fila `CONFIRMADO`, sin CRC ni prueba de almacenamiento. [SyncBiometrica.php](../src/classes/SyncBiometrica.php) todavía permite ese cambio directo. Esta ruta evita los controles nuevos de `Gateway/confirmar_entrega_aula.php`.

**Falta:** cerrar rutas alternativas de confirmación/actualización, centralizar la transición y exigir identidad, aula, plantilla, slot y verificación de entrega. Recuperar leases vencidos y probar concurrencia. El Gateway sólo consulta pendientes cuando recibe `/sync`; no tiene un ciclo periódico que drene la cola después de una notificación perdida o reinicio.

**Cierre:** `CONFIRMADO` únicamente después de instalación verificada; un trabajo pendiente o vencido se recupera sin depender de otro clic del usuario.

## Correcciones funcionales y de operación

### P1-01. Completar datos y programación académica

Crear/vincular cuentas docentes, completar matrícula, horarios, huellas y rutas de aulas; cargar los 166 planes faltantes según el alcance de entrega y completar/publicar el borrador. Para un piloto reducido, delimitar explícitamente las materias y personas del piloto y completar ese subconjunto.

El editor y el importador ya conservan unidades, capacidades, temas, indicadores, evaluaciones y códigos. La publicación verifica indicadores/programación de todos los temas y rechaza fechas inválidas, duplicados y solapamientos. **Falta** contrastar esa programación con todos los días de clase, calendario escolar, feriados, suspensiones y recuperación de contenidos. [PlanProgrammingRules.php](../src/classes/PlanProgrammingRules.php) valida fechas por año; no contiene un calendario académico ni valida cobertura de cada horario.

La selección automática es por asignación y **fecha**, con un tema candidato; varios temas solapados se rechazan. Si se necesitan temas distintos en diferentes horas del mismo día, hay que extender el modelo a horario/bloque. No es un defecto accidental: es una limitación del modelo actual que debe quedar resuelta en el alcance final.

El PDF soporta los formatos reconocidos y fixtures revisados; no se verificó OCR de documentos escaneados ni compatibilidad universal con cualquier plantilla. Las fechas no presentes en el PDF requieren programación explícita del docente.

### P1-02. Una asistencia puede aparecer en dos clases consecutivas

**Reproducido:** con clases 07:00–07:40 y 07:40–08:20, una única marca del alumno a las 07:40 se asignó por Gateway a la segunda clase, pero ambos informes mostraron PRESENTE.

Referencias: [ClaseDiaria.php](../src/classes/ClaseDiaria.php), método `informe`, y [AsistenciaEstudiante.php](../src/classes/AsistenciaEstudiante.php), método `registrarOReutilizar`. La asistencia se recupera por alumno/fecha/hora con límites inclusivos y no tiene vínculo persistido a la clase.

**Falta:** conservar la relación asistencia–clase y definir el límite entre bloques; migrar sin alterar históricos y probar clases consecutivas, materias distintas y clases conjuntas. No resolverlo cambiando el fin inclusivo sin revisar la política existente.

### P1-03. Los informes históricos cambian con los datos actuales

**Reproducido:** editar título/contenido de tema o descripción de indicador desde el servicio normal modificó el informe de una clase ya creada. Desactivar al estudiante hizo que desapareciera del informe anterior: pasó de un alumno a cero. El sistema conserva IDs de contenido, pero consulta sus textos y la lista de alumnos activos actuales al generar el informe.

**Falta:** conservar versión/snapshot de contenidos, indicadores, matrícula, profesor/materia/grado y membrete de la clase, o bloquear y versionar modificaciones usadas por históricos. Añadir cierre de informe, trazabilidad de correcciones y política de reapertura. [ClaseDiaria.php](../src/classes/ClaseDiaria.php) y [PlanificacionPedagogica.php](../src/classes/PlanificacionPedagogica.php) son los puntos principales.

**Cierre:** editar un plan, trasladar/desactivar un alumno o cambiar el membrete no modifica un informe cerrado de otra fecha.

### P1-04. Recuperación offline, rechazos y retiro anticipado

La hora efectiva es la recepción en MySQL. Reenviar marcas antiguas puede dejarlas fuera de la clase o ventana estudiantil de 10 minutos. No hay una hora de captura confiable en AST2.

El aula también encola `RET_ANTICIPADO`; [GatewayDeliveryService.php](../src/classes/GatewayDeliveryService.php) sólo acepta PRESENTE. El camino antiguo ignora el estado recibido y registra PRESENTE. Falta definir y aplicar entrada, tardanza, salida/retiro y justificación de forma consistente.

La cola del aula admite 32 marcas y sólo libera con ACK exitoso. Falta resolver rechazos definitivos: una marca sin horario o con estado no admitido puede quedar reintentándose y ocupar la cola. Añadir registro visible de rechazos, recuperación administrativa y pruebas de cola llena/NVS, orden de entrega y corte de alimentación.

### P1-05. El informe automático depende de que marque el profesor

El profesor abre la clase; un alumno no puede abrirla. Si el profesor no marca, no hay clase ni informe diario automático de ese horario. La creación manual permite recuperar una clase, pero no existe un proceso verificado que cierre todos los horarios del día y distinga ausencia docente, clase cancelada y suspensión.

**Falta:** definir cierre diario y excepciones del calendario; generar/reconciliar informes de clases esperadas, registrar ausencia/justificación docente y diferenciar AUSENTE de una marca pendiente de transmisión. Actualmente los ausentes estudiantiles se calculan al consultar el informe, no mediante un cierre definitivo.

### P1-06. Permisos y experiencia según rol

Login y propiedad de planes/informes pasan pruebas, pero la navegación muestra módulos administrativos de manera general. **Reproducido:** el usuario Profesor recibe HTTP 403 de `Dashboard/resumen.php`, aunque se redirige al Dashboard al iniciar sesión.

**Falta:** Dashboard docente útil, navegación y formularios por permisos, revisión de sesión activa en vistas y protección CSRF consistente. Las sondas autenticadas lograron crear un plan sin token (HTTP 201) y guardar una observación sin token (HTTP 200); el importador PDF sí tiene controles propios.

No existe el rol/portal Estudiante prometido en README: los roles reales son SuperAdmin, Administracion, Coordinador y Profesor. Si la entrega mantiene la consulta estudiantil de asistencia/progreso, falta implementarla y vincular la identidad; si se reduce el alcance, actualizar la especificación.

### P1-07. Captura biométrica y salud de dispositivos

La captura web apunta al registrador en una IP literal en [obtener_template.js](../public/js/obtener_template.js). El registrador ofrece captura, descarga y limpieza por HTTP, sin autenticación en esas rutas y con CORS `*`; no se probó físicamente. Falta parametrizar/detectar su dirección, autenticar o emparejar el equipo y verificar acceso del navegador, especialmente al desplegar con HTTPS.

Rutas de tres aulas siguen compiladas en Gateway; el backend ya tiene rutas configurables, pero el Gateway no las consume ni cachea. Heartbeat autenticado y estado derivado por 180 segundos existen en backend; los firmwares no emiten/transportan esos heartbeats. Completarlo y comprobar ONLINE → OFFLINE automáticamente, última marca, errores de sync y alarmas visibles.

Verificar sensor encendido, enrolamiento, CRC, instalación DY50, capacidad de slots, reconocimiento, reinicio, distancia/interferencia y coexistencia de varias aulas. La simulación de software no demuestra nada de esa cadena física.

### P1-08. Funcionalidades prometidas aún ausentes

| Función | Estado observado / trabajo pendiente |
|---|---|
| Informe diario y observaciones | Implementados y probados; corregir asociación, históricos y cierre |
| Imprimir / guardar PDF diario | Usa `window.print()`; validar A4, varias páginas y membrete con datos reales. No hay exportador PDF de servidor |
| Informe mensual/semestral y consolidado anual | No se encontró implementación equivalente a la promesa del README; faltan consultas, filtros y salida |
| Métricas pedagógicas | Dashboard muestra conteos básicos; faltan horas previstas/cubiertas, ausentismo y avance curricular |
| Cumplimiento de indicadores | Hay campos en `clase_diaria_indicadores`; no se encontró un flujo de interfaz/API para registrar el cumplimiento |
| Plan anual como documento final | Editor disponible; no se encontró una exportación/impresión dedicada del plan completo |
| Justificaciones y revisión de asistencia | CRUD de estado disponible; falta flujo con permisos, motivos, evidencia y trazabilidad |
| Cambio de año / promoción y matrícula histórica | No se encontró flujo completo verificable; no debe sobrescribir el historial de cursos |
| Uso sin Internet | Interfaz depende de Tailwind CDN; generar/servir estilos locales para operación desconectada |

Las funciones ausentes de la promesa del README deben implementarse o retirarse formalmente del alcance. No se consideran entregadas por la existencia de una tabla o de un botón.

### P1-09. Instalación y mantenimiento reproducibles

No se verificó instalar el sistema desde una base vacía. Los tests copian el esquema existente de `sisco_db`, de modo que no prueban bootstrap completo. Hay migraciones incrementales y dumps históricos en `docs`, pero falta un procedimiento vigente que cree esquema/catálogos, aplique versiones, configure permisos/secretos y arranque los servicios.

**Falta:** paquete limpio de despliegue, configuración privada de DB/dispositivos/Xpdf, usuario DB de privilegios mínimos (actualmente root sin contraseña), comprobación de entorno para Apache, backup y restauración ensayados, tareas de limpieza de staging, supervisión de colas/logs y arranque automático. No se encontró pipeline CI versionado que ejecute las regresiones. Preparar integración revisada a la rama de entrega cuando los bloqueos estén corregidos.

## Orden para cerrar el producto

1. Cerrar APIs/archivos expuestos y las confirmaciones alternativas de sincronización; separar secretos de Git y dispositivos.
2. Completar y compilar Gateway/Aula/Registrador; coordinar protocolo, migración, clave, rutas, colas y heartbeat en una instalación de ensayo.
3. Corregir asociación de asistencias e históricos; cerrar política de offline, límites de horario, retiro, ausencia y correcciones.
4. Completar datos del alcance elegido: cuentas, matrícula, huellas, horarios, planes publicados y programación que cubra las clases reales.
5. Terminar experiencia de roles y funciones prometidas: informes consolidados, métricas, evaluación y salida documental; resolver explícitamente el portal estudiantil.
6. Validar instalación limpia, respaldo/restauración y piloto físico con fallos controlados. Integrar y desplegar la versión validada.

## Criterios mínimos de aceptación de punta a punta

- Administrador autorizado prepara curso, profesor con cuenta, matrícula, aula/nodo, asignación y horario; los roles ajenos no pueden modificarlo.
- Docente crea/importa un plan, revisa catálogos/indicadores/programación, publica con cobertura de los días previstos y puede recuperar lo guardado.
- Se captura e instala una huella real con CRC y confirmación comprobada; estados y errores son visibles y se recuperan tras reinicios.
- Profesor abre el bloque correcto; alumno queda asociado a esa clase dentro de la política acordada. Repetir la marca no duplica y una clase consecutiva no toma la asistencia de otra.
- El informe muestra contenido, indicadores, matrícula, presentes/ausentes, observaciones y membrete; se imprime completo y conserva su versión después de cambios posteriores.
- Se cierra el día incluso si un profesor falta o hay una excepción; se producen los consolidados incluidos en la entrega.
- Cortes de red/energía, cola llena, ACK perdido y reintentos no pierden marcas ni falsean presentes. Los rechazos quedan identificados para revisión.
- La instalación limpia y una restauración de backup producen una instancia utilizable; los recursos privados no se sirven por HTTP.

**Resultado final de esta auditoría: software pedagógico base verificado; entrega completa y flujo físico, pendientes.**
