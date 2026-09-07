# Tareas de implementación: importación de planes PDF sin IA

Este backlog comienza después de la POC. Está escrito para que un modelo ligero implemente piezas pequeñas sin rediseñar la solución.

Estado al 07-09-2026: T01–T15 implementadas; batería actual 15/15 PASS al incluir la auditoría de persistencia. T16–T26 pendientes. El mapping quedó **PASS** y el round-trip completo dio 4/4 en una BD temporal; véase `PLAN_IMPORTACION_PDF_PERSISTENCE_AUDIT.md`. No se habilitó confirmación ni se aplicaron migraciones a la BD real. La ampliación independiente de clases conjuntas entre materias distintas está implementada y probada (20/20, CRUD 65/65, frontend 84/84).

## Decisiones cerradas

- SISCO-EDU continúa en PHP 8.2 + MariaDB + frontend actual.
- El extractor será Xpdf `pdftotext -table`, invocado como proceso CLI seguro. No Python, OCR, servidor auxiliar, IA ni APIs externas.
- La lógica pasa de la POC a clases de producción; no se llama código bajo `test/`.
- Se conserva un adaptador por familia y un detector por puntuación. No crear un parser universal.
- El JSON canónico de `scripts/plan_parser_poc/schema/plan-import-poc.schema.json` es el contrato inicial.
- Los temporales se guardan fuera del document root, por token aleatorio y con expiración.
- Siempre hay preview editable. Confidence 100 nunca evita la confirmación.
- La confirmación usa las tablas manuales existentes y una única transacción SQL. El plan nace `BORRADOR`.
- Si ya existe plan para asignación/año, V1 bloquea; no mezcla, reemplaza ni borra.
- No tocar ESP32, AS608, LoRa, Gateway ni sincronización biométrica.

## FASE 1 — Dependencia, configuración y contrato

### [x] T01 — Formalizar Xpdf y el preflight

OBJETIVO:
Definir una configuración mantenible para el ejecutable y comprobar que soporta `-table` antes de habilitar importaciones.

ARCHIVOS:
`src/config/plan_import.php` (nuevo), `scripts/plan_import/check_environment.php` (nuevo), documentación de instalación local que corresponda.

CAMBIOS:
Crear configuración con ruta tomada de `SISCO_PDFTOTEXT_PATH`, máximo de 10 MB, máximo de páginas, timeout, límite de salida y directorio temporal. El preflight debe ejecutar la ruta fija con `-v`, comprobar Xpdf y ejecutar una muestra mínima con `-table`; devolver exit code distinto de cero y mensaje accionable si falta soporte. No asumir que Git for Windows existirá en producción.

NO TOCAR:
Endpoints, BD, vistas, código de hardware ni la POC.

CRITERIO DE ACEPTACIÓN:
Una instalación válida informa ruta y versión; ruta inexistente, ejecutable sin `-table` o carpeta temporal dentro de `DOCUMENT_ROOT` fallan de forma explícita.

TEST:
Ejecutar el preflight con la ruta local verificada y luego con una ruta deliberadamente inválida.

DEPENDENCIAS:
Ninguna.

RESULTADO:
PASS — Configuración por entorno, límites y storage privado definidos. El preflight confirmó Xpdf 4.00 con `-table` y rechazó una ruta inválida con exit code no cero.

### [x] T02 — Crear contratos y modelo canónico de producción

OBJETIVO:
Trasladar el contrato estable de la POC a nombres y namespaces de producción, sin lógica de extracción.

ARCHIVOS:
`src/classes/PlanImport/Contracts/PlanImporterInterface.php`, `PdfTextExtractorInterface.php`, `PlanFormatParserInterface.php`, `src/classes/PlanImport/CanonicalPlan.php`, `src/classes/PlanImport/PlanImportException.php`, `src/schema/plan-import.schema.json` (nuevos).

CAMBIOS:
Definir firmas tipadas, códigos de error estables y constructores/fábricas del JSON. Copiar el schema canónico probado y documentar `schema_version=1.0`. Todos los parsers deben devolver la misma forma; no aceptar arrays parciales silenciosamente.

NO TOCAR:
Clases existentes de Planificación, endpoints, tablas o vistas.

CRITERIO DE ACEPTACIÓN:
Un plan vacío creado por la fábrica tiene todas las claves obligatorias y valida contra el schema; una clave o tipo incorrecto se rechaza.

TEST:
Agregar `test/plan_import/unit/CanonicalPlanTest.php` ejecutable por CLI, con un caso válido y varios inválidos.

DEPENDENCIAS:
T01 sólo para respetar la configuración acordada; no requiere ejecutar Xpdf.

RESULTADO:
PASS — Contratos tipados, excepción estable, factory y validador estricto creados con `schema_version=1.0`. El schema elimina la ruta interna del binario de la salida canónica y el test rechaza claves faltantes, extras y tipos inválidos.

### [x] T03 — Preparar migración aditiva para códigos jerárquicos

OBJETIVO:
Preservar `I`, `1.1` y `1.1.1` en las mismas entidades que usa el editor manual.

ARCHIVOS:
`database/migrations/<fecha>_planificacion_codigos_jerarquicos.sql` (nuevo) y la sección de rollback del mismo archivo.

CAMBIOS:
Agregar `codigo VARCHAR(...) NULL` a `plan_unidades`, `plan_temas` y `plan_indicadores`; agregar índices sólo si las consultas reales los necesitan. La migración no debe crear tablas `planes_pdf`, `imported_plans` ni duplicar la jerarquía.

NO TOCAR:
Datos existentes, claves foráneas actuales, hardware o tablas de asistencia.

CRITERIO DE ACEPTACIÓN:
La migración aplica sobre una copia del esquema vigente sin perder datos; planes anteriores permanecen válidos con `codigo=NULL`; el rollback elimina únicamente los tres campos/índices añadidos.

TEST:
Aplicar y revertir en una BD temporal; comparar conteos y relaciones antes/después.

DEPENDENCIAS:
Ninguna. No ejecutar todavía sobre la instancia real sin respaldo/autorización.

RESULTADO:
PASS — Migración aditiva/re-ejecutable creada y validada dos veces en una BD temporal. Las filas existentes conservaron `codigo=NULL` y el rollback de prueba quitó únicamente las tres columnas. No se aplicó todavía a la instancia real.

## FASE 2 — Parser determinista

### [x] T04 — Implementar extractor Xpdf endurecido

OBJETIVO:
Extraer texto tabular de un PDF digital con límites de recursos y sin construir comandos concatenados.

ARCHIVOS:
`src/classes/PlanImport/Pdf/XpdfTableExtractor.php` (nuevo), `test/plan_import/unit/XpdfTableExtractorTest.php` (nuevo).

CAMBIOS:
Validar existencia, extensión, MIME real, firma `%PDF-`, tamaño y páginas. Usar `proc_open` con array de argumentos, `bypass_shell`, ruta fija y archivo previamente validado. Consumir stdout/stderr sin deadlock, imponer timeout y límites de bytes, terminar el proceso excedido, separar páginas por form feed y devolver líneas con página, columna de carácter y posición relativa. Si no hay capa de texto devolver `PDF_SCAN_NOT_SUPPORTED` con el mensaje definido.

NO TOCAR:
Uploads HTTP, BD, rutas, OCR ni parsers de formato.

CRITERIO DE ACEPTACIÓN:
Extrae los cuatro fixtures; rechaza extensión falsa, firma falsa, exceso de tamaño, binario inexistente, timeout y PDF sin texto con códigos distintos.

TEST:
Tests con los cuatro fixtures y archivos mínimos inválidos; afirmar que ningún argumento contiene input interpretado por shell.

DEPENDENCIAS:
T01 y T02.

RESULTADO:
PASS — Extractor con validación de archivo, firma/MIME, límites, páginas, timeout y salida acotada. `proc_open` usa argumentos separados y archivos temporales de proceso para evitar bloqueos de pipes en Windows. Extrae 4/4 fixtures y no expone la ruta del binario.

### [x] T05 — Implementar detector puntuado y registro de adaptadores

OBJETIVO:
Elegir un formato sólo cuando varias señales independientes superan umbral y margen.

ARCHIVOS:
`src/classes/PlanImport/Pdf/PlanFormatDetector.php`, `ParserRegistry.php`, `test/plan_import/unit/PlanFormatDetectorTest.php` (nuevos).

CAMBIOS:
Migrar las señales, pesos, umbral mínimo 60 y margen 15 de la POC. Normalizar únicamente para comparar, no el texto de salida. Devolver scores completos y evidencia. Empate, score bajo o margen bajo producen `UNKNOWN_FORMAT`/warning, nunca selección arbitraria.

NO TOCAR:
Adaptadores, JSON académico, endpoints o BD.

CRITERIO DE ACEPTACIÓN:
Los cuatro fixtures detectan `ADMINISTRACION_FINANCIERA`, `EXCEL_AVANZADO` y `COMPETENCIA_CONTENIDO` con los scores esperados; textos sintéticos ambiguos se rechazan.

TEST:
Casos unitarios positivos, sin acentos, con encabezado parcial, empate y documento vacío.

DEPENDENCIAS:
T02 y T04.

RESULTADO:
PASS — Detector migrado con umbral 60, margen 15 y evidencia por señal. Reproduce scores 105/105/121/100, reconoce texto sin acentos y rechaza documento vacío, encabezado parcial y scores ambiguos como `UNKNOWN_FORMAT`.

### [x] T06 — Implementar adaptador Administración Financiera

OBJETIVO:
Producir el modelo canónico para los dos planes BTI sin alterar el contenido académico.

ARCHIVOS:
`src/classes/PlanImport/Pdf/Parser/AbstractTableParser.php`, `AdministracionFinancieraParser.php`, `test/plan_import/unit/AdministracionFinancieraParserTest.php` (nuevos).

CAMBIOS:
Migrar reglas de metadata, encabezados, columnas relativas por página, unidades romanas, temas `N.N`, indicadores y viñetas. Implementar herencia vertical de capacidad/procedimiento/instrumento/proceso/HC. En 2.º conservar un tema compuesto por unidad cuando no haya división inequívoca. No emparejar listas si sus cardinalidades o fronteras contradicen la estructura; emitir warning.

NO TOCAR:
Otros formatos, persistencia, catálogos ni editor.

CRITERIO DE ACEPTACIÓN:
Admin 3.º devuelve 6 unidades, 18 temas y 18 indicadores; Admin 2.º devuelve 6 unidades, 6 grupos y 18 indicadores; metadata, HC, proceso y evaluación coinciden con expected JSON.

TEST:
Comparación profunda contra ambos expected JSON y casos recortados que provoquen indicador huérfano.

DEPENDENCIAS:
T02, T04 y T05.

RESULTADO:
PASS — Adaptador aislado promovido. Los dos fixtures reproducen metadata, 6/6 unidades, 6/18 temas, 18/18 indicadores, HC, procesos y evaluación; el hash de la jerarquía completa coincide y una cardinalidad alterada genera `ORPHAN_INDICATOR`.

### [x] T07 — Implementar adaptador Excel Avanzado

OBJETIVO:
Reconstruir la jerarquía numerada y las celdas combinadas del formato Excel.

ARCHIVOS:
`src/classes/PlanImport/Pdf/Parser/ExcelAvanzadoParser.php`, `test/plan_import/unit/ExcelAvanzadoParserTest.php` (nuevos).

CAMBIOS:
Migrar límites relativos por página, detección de Unidad, temas `N.N`, indicadores `N.N.N`, check, proceso de desarrollo, evaluación y tiempo. La relación indicador/tema debe usar prefijo exacto. Los checks vacíos son `null`. Texto u horas sin destino seguro quedan en warnings.

NO TOCAR:
Adaptadores A/C, persistencia, OCR o corrección académica.

CRITERIO DE ACEPTACIÓN:
El fixture produce 3 unidades, 18 temas, 53 indicadores, 17 HC de tema y los tres warnings documentados; ninguna relación viola el prefijo.

TEST:
Comparación contra expected JSON más mutaciones donde `2.1.1` aparece sin `2.1`, que deben producir `ORPHAN_INDICATOR`.

DEPENDENCIAS:
T02, T04 y T05.

RESULTADO:
PASS — Adaptador Excel reproduce 3 unidades, 18 temas, 53 indicadores, 17 HC y checks `null`; el hash completo coincide. La prueba mutada elimina `2.1` y confirma que sus indicadores quedan como `ORPHAN_INDICATOR` en vez de reasignarse.

### [x] T08 — Implementar adaptador Competencia/Contenido

OBJETIVO:
Parsear el formato por filas de competencia y capacidad sin inventar temas atómicos.

ARCHIVOS:
`src/classes/PlanImport/Pdf/Parser/CompetenciaContenidoParser.php`, `test/plan_import/unit/CompetenciaContenidoParserTest.php` (nuevos).

CAMBIOS:
Extraer materia, curso/grado, profesor, días y año; mapear competencia a unidad, capacidad a capacidad y contenido a un tema compuesto por fila. Conservar indicadores, área transversal, metodología, medios y fecha. Unir continuaciones entre páginas sólo cuando comparten frontera de columna/fila. Comparar curso interno con nombre de archivo y emitir mismatch.

NO TOCAR:
División semántica de contenidos, Excel/Admin, BD ni calendario.

CRITERIO DE ACEPTACIÓN:
El fixture produce 2 unidades, 14 capacidades/temas, 47 indicadores y conserva las 34 etiquetas de contenido; reporta exactamente la agrupación conservadora y el mismatch de curso.

TEST:
Comparación contra expected JSON y un caso de continuación de página aislado.

DEPENDENCIAS:
T02, T04 y T05.

RESULTADO:
PASS — El adaptador conserva 2 competencias, 14 capacidades/temas, 47 indicadores y todos los campos transversales. La continuación entre páginas se fusiona y se emiten exactamente `TABLE_STRUCTURE_WARNING` y `SOURCE_METADATA_MISMATCH`.

### [x] T09 — Implementar validador y confidence explicable

OBJETIVO:
Separar la validación del parseo y puntuar completitud/consistencia, no probabilidad.

ARCHIVOS:
`src/classes/PlanImport/PlanValidator.php`, `ConfidenceCalculator.php`, `test/plan_import/unit/PlanValidatorTest.php` (nuevos).

CAMBIOS:
Validar jerarquía, órdenes, códigos, padre de tema/indicador, metadata plausible, HC, requerimientos por formato y huérfanos. Implementar los pesos documentados para metadata, estructura y asociaciones; incluir desglose y reglas aplicadas. Nunca reparar contenido.

NO TOCAR:
Adaptadores, UI, BD ni catálogos.

CRITERIO DE ACEPTACIÓN:
Los cuatro resultados reproducen confianza 100, 100, 96 y 93; cada corrupción sintética baja el componente correcto y añade warning estable.

TEST:
Matrices unitarias para falta de metadata, HC inválida, código incoherente y huérfanos.

DEPENDENCIAS:
T02 y T06–T08.

RESULTADO:
PASS — Validador y confidence separados reproducen 100/100/96/93. Casos sintéticos verifican metadata, año, órdenes, HC y códigos incoherentes; no se corrige automáticamente ningún contenido.

### [x] T10 — Crear orquestador y CLI de producción

OBJETIVO:
Exponer `PDF → JSON` sin depender de HTTP ni de la POC.

ARCHIVOS:
`src/classes/PlanImport/PdfPlanImporter.php`, `scripts/plan_import/parse.php`, `test/plan_import/cli/ParseCliTest.php` (nuevos).

CAMBIOS:
Orquestar extractor, detector, adaptador, validador y confidence. Admitir `--debug`, `--pretty` y `--output`, con JSON limpio en stdout y diagnóstico en stderr. Los errores deben ser JSON/códigos estables y exit codes documentados.

NO TOCAR:
Endpoints, sesión, BD, POC ni vistas.

CRITERIO DE ACEPTACIÓN:
Los cuatro fixtures generan JSON conforme al schema; stdout sigue siendo parseable con `--debug`; un archivo inválido sale no-cero.

TEST:
Ejecutar CLI sobre cuatro PDFs y validar JSON/exit code/stdout/stderr.

DEPENDENCIAS:
T04–T09.

RESULTADO:
PASS (07-09-2026) — `PdfPlanImporter`, autoload aislado y CLI implementados. 4/4 fixtures válidos con debug/pretty; stdout JSON, stderr separado, archivo inexistente con exit 2 y salida nueva con `--output` verificados. POC 4/4, ocho tests unitarios, migración temporal y regresión frontend 84/84 PASS. No se escribieron planes.

## FASE 3 — Backend PHP y staging

### [x] T11 — Implementar almacenamiento temporal privado

OBJETIVO:
Guardar PDF y JSON de preview fuera del área pública, ligados a usuario y asignación.

ARCHIVOS:
`src/classes/PlanImport/PlanImportStaging.php`, `test/plan_import/unit/PlanImportStagingTest.php` (nuevos).

CAMBIOS:
Crear nombres criptográficamente aleatorios, permisos restrictivos, manifiesto con usuario/asignación/año/hash/expiración y operaciones create/read/update/delete. Rechazar symlinks, traversal, token ajeno, expirado o reutilizado. Escribir de forma atómica. Configurar TTL corto y limpieza segura por archivos explícitamente resueltos.

NO TOCAR:
`public/uploads`, BD, sesión global, parser o hardware.

CRITERIO DE ACEPTACIÓN:
No se deriva ninguna ruta del nombre original; un usuario no lee tokens ajenos; el directorio queda fuera de `DOCUMENT_ROOT`; cancelación/expiración elimina sólo su carpeta.

TEST:
Casos de ownership, traversal, token inválido, expiración y escritura interrumpida.

DEPENDENCIAS:
T01 y T02.

RESULTADO:
PASS (07-09-2026) — Staging privado, token aleatorio, manifiesto ligado a usuario/asignación/año/hash, bloqueo, escritura atómica, expiración, consumo y eliminación acotada. `PlanImportStagingTest` verifica round trip, ownership, traversal, escritura interrumpida, dry-run, expiración y consumo. La matriz hostil ampliada de T24 sigue pendiente.

### [x] T12 — Crear endpoint de carga y parseo

OBJETIVO:
Aceptar un único PDF validado, verificar ownership de la asignación y devolver token + preview.

ARCHIVOS:
`src/api/Planificacion/importar_plan_pdf.php`, ajustes mínimos a `src/config/api_auth.php` sólo si hace falta una función reusable, tests HTTP nuevos.

CAMBIOS:
Permitir `POST` multipart a roles vigentes; exigir sesión, CSRF de importación, `id_asignacion` y `anio`. Reutilizar `asegurarAccesoAsignacion(..., true)`, validar límites antes de mover el upload, ejecutar `PdfPlanImporter`, guardar staging y devolver JSON. El endpoint no abre una transacción académica ni crea plan.

NO TOCAR:
Endpoints actuales, tablas, publicación, editor manual ni hardware.

CRITERIO DE ACEPTACIÓN:
Profesor dueño obtiene preview; asignación ajena, MIME/extensión/firma inválidos, archivo grande, scan, parser fallido o CSRF inválido se rechazan sin residuo.

TEST:
Pruebas HTTP por rol/ownership y cada error; comprobar que conteos de tablas pedagógicas no cambian.

DEPENDENCIAS:
T10 y T11.

RESULTADO:
PASS (07-09-2026) — Servicio y endpoint multipart autenticado con CSRF/ownership. `UploadTest` usa HTTP real con copia servida y BD exclusiva: preview válido, rol, sesión, CSRF, asignación ajena, extensión, firma, vacío, corrupto, sin texto, UNKNOWN, tamaño y timeout. Cero planes creados; sólo queda el temporal de la carga válida. El error de proceso conserva un código público controlado.

### [x] T13 — Crear lectura/actualización de preview

OBJETIVO:
Permitir recargar y guardar correcciones del modelo temporal sin persistirlas en BD.

ARCHIVOS:
`src/api/Planificacion/preview_importacion.php`, `src/classes/PlanImport/PreviewValidator.php`, tests HTTP nuevos.

CAMBIOS:
Implementar GET por token y PUT con JSON canónico editado. Verificar usuario, asignación, expiración, CSRF, tamaño de payload, schema y reglas estructurales. Recalcular warnings/confidence del documento editado; no confiar en los enviados por el navegador.

NO TOCAR:
Tablas, endpoints existentes, publicación ni parser original.

CRITERIO DE ACEPTACIÓN:
Una edición válida se recupera igual; IDs, campos extra, orden duplicado, tema sin padre o token ajeno se rechazan.

TEST:
Round trip completo y matriz de payloads manipulados.

DEPENDENCIAS:
T09 y T11–T12.

RESULTADO:
PASS (07-09-2026) — GET/PUT con token, ownership, CSRF, límite JSON, origen inmutable y validación estricta. `PreviewTest` comprueba corrección de indicador y recarga, rechazos de campos extra/IDs, orden, jerarquía vacía, origen modificado y JSON inválido. Warnings/confidence se calculan en servidor. El texto HTML se conserva como texto; la prueba XSS en navegador pertenece a T17/T24 y está pendiente.

### [x] T14 — Implementar reconciliación de catálogos

OBJETIVO:
Convertir textos extraídos de procedimientos/instrumentos en decisiones explícitas de preview.

ARCHIVOS:
`src/classes/PlanImport/CatalogMatcher.php`, integración de lectura en `preview_importacion.php`, tests unitarios.

CAMBIOS:
Proponer sólo coincidencias exactas tras normalizar mayúsculas, espacios y acentos; conservar texto original. Las coincidencias ambiguas quedan sin seleccionar. El preview debe guardar por texto una decisión: ID existente, crear explícitamente o excluir. No usar similitud difusa silenciosa.

NO TOCAR:
Catálogos en BD durante esta tarea, `catalogos.php`, parser o contenidos académicos.

CRITERIO DE ACEPTACIÓN:
Match único se sugiere; dos matches o cero matches requieren acción; ninguna sugerencia cambia BD.

TEST:
Casos exactos, acentos, espacios, duplicados lógicos, desconocidos y strings vacíos.

DEPENDENCIAS:
T13 y acceso de sólo lectura a catálogos existentes.

RESULTADO:
PASS (07-09-2026) — Match exacto normalizado, sin selección automática ni fuzzy matching. Tests de acentos/espacios, match único, ambigüedad, desconocido y decisiones explícitas. La prueba HTTP guarda/recarga decisiones de creación y demuestra que no se crean catálogos durante preview.

### [x] T15 — Implementar cancelación y limpieza programable

OBJETIVO:
Eliminar una importación temporal de forma segura y limpiar expiradas.

ARCHIVOS:
`src/api/Planificacion/cancelar_importacion.php`, `scripts/plan_import/cleanup.php`, tests nuevos.

CAMBIOS:
DELETE autenticado/CSRF por token y script CLI de limpieza. Resolver y verificar cada ruta dentro del storage antes de borrar. El script debe soportar dry-run y no seguir symlinks.

NO TOCAR:
Planes confirmados, BD, carpetas amplias, `public/uploads` ni hardware.

CRITERIO DE ACEPTACIÓN:
Sólo se elimina el token propio/expirado; dry-run lista sin modificar; rutas manipuladas se rechazan.

TEST:
Crear varios tokens de usuarios/edades distintas y comprobar el conjunto exacto conservado/eliminado.

DEPENDENCIAS:
T11–T13.

RESULTADO:
PASS (07-09-2026) — DELETE con sesión/CSRF/ownership y limpieza CLI (`--dry-run` por defecto, `--delete` explícito). Prueba HTTP de cancelación/CSRF y token inexistente después de cancelar; prueba de staging confirma conjunto exacto de expiradas eliminadas y activas conservadas.

## FASE 4 — Preview de frontend

### T16 — Agregar entrada de importación y formulario de carga

OBJETIVO:
Iniciar la importación desde Planificación Pedagógica sin alterar el flujo manual.

ARCHIVOS:
`mvc/views/planificacion/index.php`, `public/js/plan-import.js` (nuevo), CSS existente sólo si es necesario.

CAMBIOS:
Agregar “Importar plan” y modal/pantalla con asignación/año, PDF, máximo permitido y aviso de PDF digital. Incluir token CSRF de importación. Enviar multipart, mostrar progreso/errores y navegar al preview recibido.

NO TOCAR:
Creación manual, hardware, otras vistas ni persistencia.

CRITERIO DE ACEPTACIÓN:
El flujo manual sigue funcionando; archivo válido abre preview; error muestra el mensaje del backend y permite reintentar.

TEST:
Prueba manual y headless de selección, carga válida, MIME inválido, scan y asignación no autorizada.

DEPENDENCIAS:
T12.

### T17 — Construir editor jerárquico de preview

OBJETIVO:
Editar metadata, unidad, capacidad, tema e indicador antes de confirmar.

ARCHIVOS:
`mvc/views/planificacion/import_preview.php` (nuevo), `public/js/plan-import.js`.

CAMBIOS:
Renderizar árbol ordenado con controles para editar, agregar y eliminar cada nivel. Permitir códigos, HC, proceso, contenido, check, fechas y metadata. Mantener identificadores sólo locales, recalcular órdenes al mover/borrar y guardar con PUT. No interpolar HTML no confiable; usar `textContent`/atributos seguros.

NO TOCAR:
Editor manual, BD, parseo ni publicación.

CRITERIO DE ACEPTACIÓN:
Todas las operaciones solicitadas sobreviven a recarga; no se puede guardar jerarquía inválida; texto del PDF no ejecuta HTML/JS.

TEST:
Headless: editar metadata, borrar/agregar unidad/capacidad/tema/indicador, recargar y comparar estado.

DEPENDENCIAS:
T13 y T16.

### T18 — Mostrar warnings, confianza y reconciliación

OBJETIVO:
Hacer visibles incertidumbres y decisiones de catálogo/fechas.

ARCHIVOS:
`mvc/views/planificacion/import_preview.php`, `public/js/plan-import.js`.

CAMBIOS:
Mostrar formato y scores, confidence por componente, warnings con página/texto/mensaje, HC faltante y discrepancias. Agregar selección/creación/exclusión explícita de procedimiento/instrumento. Fechas textuales no se convierten automáticamente: el usuario debe confirmar fechas de programación válidas o dejarlas sin programación.

NO TOCAR:
Weights del backend, catálogos directamente, calendario inexistente ni contenido original.

CRITERIO DE ACEPTACIÓN:
Ningún warning queda oculto; confirmación se bloquea mientras haya decisiones obligatorias sin resolver; confidence se etiqueta como consistencia, no probabilidad.

TEST:
Headless con los fixtures Excel y Competencia/Contenido, comprobando sus warnings específicos.

DEPENDENCIAS:
T14 y T17.

### T19 — Agregar confirmación/cancelación explícitas

OBJETIVO:
Cerrar el preview con acciones inequívocas y resistentes a doble clic.

ARCHIVOS:
`mvc/views/planificacion/import_preview.php`, `public/js/plan-import.js`.

CAMBIOS:
Agregar `[CONFIRMAR IMPORTACIÓN]` y cancelar. Antes de confirmar, guardar el último estado y mostrar resumen de conteos/asignación/año. Deshabilitar doble submit, manejar idempotencia y redirigir al editor del plan creado sólo tras respuesta exitosa.

NO TOCAR:
Auto-publicación, reemplazo de plan, BD desde JavaScript ni endpoints manuales.

CRITERIO DE ACEPTACIÓN:
No existe confirmación automática; cancelar limpia staging; doble clic/red duplicada crea como máximo un plan.

TEST:
Prueba headless de cancelar, confirmar, doble clic y fallo de red reintentable.

DEPENDENCIAS:
T15, T17 y T18; endpoint de T21 para éxito real.

## FASE 5 — Persistencia confirmada

### T20 — Crear escritor transaccional de jerarquía

OBJETIVO:
Insertar un plan canónico en las tablas manuales con una sola transacción controlada externamente.

ARCHIVOS:
`src/classes/PlanImport/PlanImportPersistence.php`, cambios mínimos y tipados en `src/classes/PlanificacionPedagogica.php` sólo para aceptar `codigo` y reutilizar validaciones sin commits internos.

CAMBIOS:
Implementar inserts parametrizados para plan/unidades/capacidades/temas/indicadores/puentes/programación. Recibir IDs de catálogo o decisiones explícitas; cualquier catálogo nuevo se crea dentro de la misma transacción. No llamar métodos que hagan commit parcial. Verificar existencia/estado de asignación, año y unicidad inmediatamente antes de insertar. Crear `BORRADOR`.

NO TOCAR:
Plan existente, publicación, clases diarias, hardware ni esquema fuera de T03.

CRITERIO DE ACEPTACIÓN:
Éxito crea una jerarquía idéntica a la del editor manual; excepción en cualquier nivel revierte plan, catálogos nuevos y relaciones; no hay commits parciales.

TEST:
BD temporal: éxito y fallos inyectados tras plan, unidad, tema, catálogo y programación; todos los conteos vuelven al baseline en rollback.

DEPENDENCIAS:
T03, T09 y T14.

### T21 — Crear endpoint de confirmación idempotente

OBJETIVO:
Confirmar el token validado, persistirlo una vez y devolver el ID del plan.

ARCHIVOS:
`src/api/Planificacion/confirmar_importacion.php`, tests HTTP/DB nuevos.

CAMBIOS:
Exigir POST, sesión, CSRF, ownership y token. Bloquear/consumir token, leer el JSON guardado, revalidar schema/estructura/asignación/año y decisiones, iniciar una transacción, llamar al escritor y commit/rollback. Marcar token consumido de forma idempotente; sólo eliminar PDF/JSON después del commit. Si ya hay plan, devolver conflicto sin tocarlo.

NO TOCAR:
Publicación automática, merge/reemplazo, endpoints actuales ni hardware.

CRITERIO DE ACEPTACIÓN:
Una confirmación crea un plan `BORRADOR`; dos solicitudes concurrentes crean uno; error devuelve estado útil y cero escrituras parciales.

TEST:
Pruebas de confirmación válida, token ajeno/expirado/manipulado, plan preexistente, doble request y rollback inyectado.

DEPENDENCIAS:
T13–T15 y T20.

### T22 — Adaptar lectura/escritura manual de códigos

OBJETIVO:
Hacer que un plan importado y uno manual compartan totalmente el mismo modelo visible/editable.

ARCHIVOS:
`src/classes/PlanificacionPedagogica.php`, `src/api/Planificacion/estructura.php`, `mvc/views/planificacion/editor.php`, `public/js/planificacion.js`.

CAMBIOS:
Incluir los tres campos `codigo` opcionales en consultas, respuestas, validación y formularios del editor. No hacerlos obligatorios para datos anteriores. Escapar salida y limitar longitudes.

NO TOCAR:
Semántica de publicación, tablas nuevas, parser, hardware ni planes existentes.

CRITERIO DE ACEPTACIÓN:
El editor muestra/edita códigos importados; un plan manual puede dejarlos vacíos; la estructura devuelta es la misma sin importar origen.

TEST:
Extender `test/test_planificacion_pedagogica.php` con códigos y con `NULL`; prueba headless del editor.

DEPENDENCIAS:
T03.

## FASE 6 — Verificación y liberación

### T23 — Consolidar regresión de cuatro fixtures

OBJETIVO:
Impedir cambios accidentales en conteos, texto y asociaciones probadas.

ARCHIVOS:
`test/plan_import/run_all.php`, `test/plan_import/expected/*.json`, runner de tests existente si corresponde.

CAMBIOS:
Copiar/revisar los expected de la POC como contratos de producción y comparar hash, metadata, jerarquía completa, evaluación, HC, warnings y confidence. La salida debe indicar diff exacto, no sólo PASS/FAIL.

NO TOCAR:
Fixtures PDF, contenido esperado para hacer pasar un bug, BD real ni POC histórica.

CRITERIO DE ACEPTACIÓN:
4/4 pasan y cualquier texto/código/padre alterado falla con ruta JSON precisa.

TEST:
Ejecutar suite limpia y luego una copia expected deliberadamente corrupta para validar el reporte.

DEPENDENCIAS:
T10.

### T24 — Probar seguridad de archivos y procesos

OBJETIVO:
Validar las fronteras hostiles del upload y `proc_open` antes de habilitar usuarios.

ARCHIVOS:
`test/plan_import/security/*` (nuevos) y correcciones acotadas donde fallen.

CAMBIOS:
Cubrir doble extensión, MIME falso, firma falsa, nombre con metacaracteres, traversal, PDF corrupto/protegido, tamaño/páginas/salida excesivos, scan, symlink, timeout, token ajeno y XSS en texto. Confirmar que no se pasa el nombre original al comando.

NO TOCAR:
Hardware, datos reales ni reducción de validaciones para pasar tests.

CRITERIO DE ACEPTACIÓN:
Todos los vectores se rechazan con código controlado, sin ejecución lateral, archivo público o proceso huérfano.

TEST:
Suite automatizada en Windows/XAMPP y revisión de procesos/temporales al terminar.

DEPENDENCIAS:
T04, T11–T13 y T16–T18.

### T25 — Probar transacción y paridad con creación manual

OBJETIVO:
Demostrar que el plan importado usa exactamente la jerarquía normal y que rollback es total.

ARCHIVOS:
`test/test_plan_import_persistence.php` (nuevo), helpers de fixture de BD temporales.

CAMBIOS:
Crear una asignación temporal, importar un JSON pequeño confirmado, leerlo por `PlanificacionPedagogica::estructura`, compararlo con uno creado manualmente y limpiar. Inyectar fallos en cada nivel. Verificar bridges, códigos, HC, fechas, ownership y estado.

NO TOCAR:
Datos permanentes, publicación, firmware o esquema fuera de la migración.

CRITERIO DE ACEPTACIÓN:
La API de lectura no distingue origen; no existe tabla/consulta paralela; cada fallo deja cero filas nuevas.

TEST:
Ejecutar contra BD exclusiva de tests dentro de transacciones/fixtures identificables.

DEPENDENCIAS:
T20–T22.

### T26 — Ejecutar E2E y decidir habilitación

OBJETIVO:
Validar el flujo Profesor → upload → preview → corrección → confirmación → editor.

ARCHIVOS:
`test/plan_import_e2e.js` y documentación final de operación.

CAMBIOS:
Automatizar los cuatro PDFs, incluyendo resolución de warnings. Comprobar roles, ownership, CSRF, preview obligatorio, conteos, edición, cancelación, doble submit y plan `BORRADOR`. Registrar versión/ruta de Xpdf y tiempos.

NO TOCAR:
Publicación automática, hardware, fixtures ni el umbral para ocultar fallos.

CRITERIO DE ACEPTACIÓN:
Los cuatro flujos terminan con los conteos revisados, sin huérfanos ni writes previos a confirmar; los casos negativos no crean plan.

TEST:
Suite headless completa en un entorno XAMPP limpio y revisión manual final de preview.

DEPENDENCIAS:
T16–T25.

## Primera tarea a ejecutar

El mapping y el round-trip temporal están resueltos. Antes de continuar por **T16 — Entrada de importación y formulario**, respaldar y aplicar de forma controlada las dos migraciones propuestas. T01–T15 están implementadas; no repetir la POC ni el parser salvo regresión. No marcar T20–T26 PASS hasta verificar transacción productiva, paridad del editor y E2E real.
