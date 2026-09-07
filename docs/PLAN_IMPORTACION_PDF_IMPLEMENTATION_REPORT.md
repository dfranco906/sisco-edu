# Reporte de implementación de planes PDF sin IA

Fecha: 07-09-2026. Estado: **parcial; mapping y round-trip temporal PASS, migración real pendiente**.

Se retomó desde T10: T01–T09 ya existían. Esta continuación implementó T10–T15. El importador todavía no está listo para uso completo desde el navegador: faltan formulario, editor visual, confirmación, persistencia y E2E. No se creó un plan en la BD real ni se aplicó una migración en ella.

## 1. Arquitectura implementada y prevista

Implementado: PHP autenticado → `PlanImportacionService` → staging privado → `PdfPlanImporter` → Xpdf `pdftotext -table` → detector → adaptador por reglas → validador/confidence → JSON temporal → lectura/edición/cancelación.

Pendiente: interfaz visual → confirmación explícita → una transacción en las mismas tablas del editor manual → plan BORRADOR. El parser no depende de PDO, sesiones, roles ni profesores.

## 2. Dependencias

- PHP 8.2, PDO MySQL, fileinfo, mbstring, JSON y proc_open.
- Xpdf pdftotext 4.00, verificado localmente en `C:\Program Files\Git\mingw64\bin\pdftotext.exe`.
- Configuración mediante `SISCO_PDFTOTEXT_PATH` y `SISCO_PLAN_IMPORT_STORAGE`.
- Para tests HTTP: PHP cURL y MariaDB local; crean una BD exclusiva desde una copia del esquema, sin copiar datos reales.
- Para regresión visual: Node y Chrome, usando la prueba existente.

No se agregó Python, OCR, IA ni API externa de interpretación.

## 3. Archivos creados en esta continuación

Producción:

- `src/classes/PlanImport/bootstrap.php`
- `src/classes/PlanImport/PdfPlanImporter.php`
- `src/classes/PlanImport/PlanImportStaging.php`
- `src/classes/PlanImport/PlanImportacionService.php`
- `src/classes/PlanImport/PreviewValidator.php`
- `src/classes/PlanImport/CatalogMatcher.php`
- `src/api/Planificacion/import_common.php`
- `src/api/Planificacion/importar_plan_pdf.php`
- `src/api/Planificacion/preview_importacion.php`
- `src/api/Planificacion/cancelar_importacion.php`
- `scripts/plan_import/parse.php`
- `scripts/plan_import/cleanup.php`

Pruebas y documentación:

- `test/plan_import/cli/ParseCliTest.php`
- `test/plan_import/unit/PlanImportStagingTest.php`
- `test/plan_import/unit/CatalogMatcherTest.php`
- `test/plan_import/http/UploadTest.php`
- `test/plan_import/http/PreviewTest.php`
- `test/plan_import/support/TestEnvironment.php`
- `test/plan_import/support/PdfFixture.php`
- `test/plan_import/support/CanonicalPlanRoundTripRepository.php`
- `test/plan_import/migration/CanonicalRoundTripTest.php`
- `test/plan_import/run_current.php`
- `database/migrations/20260907_plan_importacion_campos_sin_perdida.sql`
- `docs/PLAN_IMPORTACION_PDF_PERSISTENCE_AUDIT.md`
- Este reporte.

Las clases del extractor, detector, adaptadores, contrato, validador/confidence, configuración, migración de códigos y tests T01–T09 ya estaban en el árbol de trabajo al retomar. La POC y sus cuatro fixtures se conservaron.

## 4. Archivos modificados

- `src/classes/PlanImport/ProcessRunner.php`: limita también la lectura final de salida para evitar cargar un archivo excesivo en memoria.
- `scripts/plan_import/check_environment.php`: acceso exclusivo CLI; no revela rutas de configuración por HTTP.
- `docs/PLAN_IMPORTACION_PDF_SIN_IA.md`: estado real y conflicto de esquema.
- `docs/PLAN_IMPORTACION_PDF_TASKS.md`: resultados T10–T15 y siguiente tarea.
- Por solicitud adicional del usuario: `public/js/horarios.js` y un caso en `test/admin_frontend_headless.js`; corrección detallada al final. Este último ya contenía cambios ajenos a PDF, conservados.

## 5. Tablas creadas

Ninguna en producción. Las pruebas crean bases temporales con nombres aleatorios y las eliminan al terminar.

## 6. Tablas modificadas

Ninguna en la BD real. La migración existente `20260903_planificacion_codigos_jerarquicos.sql` agrega `codigo VARCHAR(32) NULL` a unidades, temas e indicadores; sólo se verificó sobre una BD temporal, incluyendo repetición y rollback.

La auditoría exhaustiva resolvió el conflicto. La migración propuesta `20260907_plan_importacion_campos_sin_perdida.sql`, todavía **no ejecutada sobre la BD real**, agrega metadata fuente y procedencia al plan, texto de tiempo/proceso a unidades, texto de tiempo/fecha a temas y texto/orden original a los puentes evaluativos. Junto con T03 permite reconstruir el contrato completo.

No se agrega `plan_indicadores.check`: la columna del PDF representa avance/cumplimiento operativo, no definición académica. Los cuatro fixtures contienen `null`; un valor futuro no nulo se rechaza para revisión y el avance real continúa en `clase_diaria_indicadores.cumplido`. El mapping completo está en `PLAN_IMPORTACION_PDF_PERSISTENCE_AUDIT.md`.

## 7. Endpoints

| Método | Ruta bajo `src/api/Planificacion/` | Resultado |
|---|---|---|
| POST multipart | `importar_plan_pdf.php` | Token, JSON, formato/confianza, contexto y advertencia de plan existente; cero INSERT académicos. |
| GET | `preview_importacion.php?token=…` | Recupera preview propio vigente. |
| PUT JSON | `preview_importacion.php` | Guarda plan corregido y decisiones de catálogo; recalcula warnings/confidence. |
| DELETE JSON | `cancelar_importacion.php` | Elimina únicamente el temporal propio. |

La carga recibe `pdf`, `id_asignacion`, `anio`. Los cambios requieren `X-CSRF-Token` o token multipart de sesión. PUT recibe `token`, `plan` y opcionalmente `decisions`. La programación de fechas exactas aún no está implementada y no se acepta silenciosamente en PUT.

No existe aún endpoint de confirmación. La creación del token CSRF para la interfaz se integrará en T16. Los tests preparan sesiones de prueba en su instancia aislada.

## 8. Parser

`PdfPlanImporter` orquesta las clases promovidas de la POC y expone CLI sin HTTP/BD. `--debug` conserva JSON parseable en stdout y escribe un resumen a stderr; `--pretty` formatea; `--output` crea un archivo JSON nuevo sin sobrescribir archivos existentes.

Exit codes: 0 éxito; 2 archivo/límites de entrada; 3 sin capa de texto; 4 configuración/inicio del extractor; 5 extracción/formato; 6 contrato/validación; 7 límite de salida; 8 timeout; 64 argumentos CLI; 70 error interno; 73 error de salida CLI. La respuesta incluye un código de error estable más específico.

## 9. Formatos soportados y resultados

| Fixture | Unidades | Capacidades | Temas | Indicadores | Confianza | Warnings |
|---|---:|---:|---:|---:|---:|---:|
| Administración Financiera 2.º | 6 | 6 | 6 compuestos | 18 | 100 | 0 |
| Administración Financiera 3.º | 6 | 6 | 18 | 18 | 100 | 0 |
| Excel Avanzado 3.º | 3 | 3 | 18 | 53 | 96 | 3 |
| Competencia/Contenido | 2 | 14 | 14 compuestos | 47 | 93 | 2 |

Se mantienen las comparaciones de jerarquía completa, metadata, evaluaciones, códigos, HC y procesos de los tests previos. UNKNOWN se rechaza con el mensaje acordado, sin intentar un parser alternativo por adivinación.

## 10. Confidence

Sin cambios de pesos: 30% metadata, 40% estructura y 30% asociaciones. Es cumplimiento de criterios, no probabilidad. La edición recalcula el resultado en servidor. Nunca provoca importación automática.

## 11. Warnings

Se mantienen HC faltante y texto introductorio no asociado en Excel, agrupación conservadora y discrepancia archivo/curso en Competencia/Contenido. El preview conserva warnings de extracción y recalcula los estructurales. Un plan existente genera aviso de conflicto por asignación/año; no se sobrescribe.

SHA-256 se conserva en el manifiesto y el JSON. La política confirmada de V1 bloquea cualquier plan existente para asignación/año. Aún no hay comprobación persistente de hash posterior a confirmación, porque la confirmación no está implementada.

## 12. Seguridad

- Sesión, roles pedagógicos y `asegurarAccesoAsignacion(..., true)`.
- CSRF obligatorio en cambios, origen del documento inmutable y rechazo de IDs/campos adicionales dentro del modelo.
- Archivo validado por extensión, firma, MIME y tamaño; límite 10 MB, 100 páginas, 15 segundos, salida 5 MB.
- Nombre original sólo como metadata; ruta temporal fija dentro de un token aleatorio de 32 bytes.
- Proceso con argumentos separados y bypass_shell; no acepta binario/opciones del request.
- Storage fuera del document root, manifiesto propietario/asignación/año/hash, TTL dos horas y escritura atómica.
- Bloqueo de operaciones de staging; rechazo de enlaces detectables, traversal, expiración, consumo y tokens ajenos.
- Limpieza limitada a nombres de archivo conocidos en carpetas de token resueltas; no sigue recursivamente rutas del usuario.
- Respuestas públicas sin stderr del extractor ni rutas internas; logs con tipo de error, sin texto académico ni tokens.
- Scripts operativos CLI no se ejecutan por HTTP.

El bloqueo actual de staging es global por directorio; puede serializar importaciones concurrentes. La matriz ampliada de seguridad T24, incluyendo comprobación específica de symlinks/junctions y concurrencia de confirmación, permanece pendiente. No se declara seguridad de liberación completada.

## 13. Tests ejecutados

`test/plan_import/run_current.php`: 15 archivos de prueba, todos PASS. Incluye preflight válido/inválido, contrato, extractor, detector, tres adaptadores, confidence, CLI, staging, catálogos, migraciones temporales, round-trip 4/4 y HTTP upload/preview/cancelación.

HTTP verifica sesión/rol/ownership/CSRF, PDF inválido/no PDF/vacío/corrupto/sin texto/desconocido/grande, timeout real, JSON inválido, origen manipulado, jerarquía incompleta y corrección de indicador recuperada desde staging. Confirma cero planes antes de confirmación y cero catálogos creados por decisiones temporales. Las cargas inválidas no dejan carpetas residuales.

POC histórica: 4/4 PASS. Regresión administrativa/horarios: 84 PASS, 0 FAIL; sin errores JS/HTTP. La corrección de Matemática se verifica en navegador. La ampliación de clases conjuntas entre materias distintas pasó 20/20 comprobaciones HTTP/BD; el CRUD administrativo pasó 65/65.

La auditoría ejecutó una transacción de prueba y rollback total mediante un repositorio exclusivo de tests; eso valida el diseño del mapping, pero no marca T20/T21 como implementadas. Siguen pendientes el escritor productivo, doble confirmación, paridad del editor y E2E (T16–T26).

## 14. Checkpoints por fase

| Fase | Estado | Tareas | Evidencia / pendiente |
|---|---|---|---|
| 1 | PASS | 3/3 | Configuración/contrato y migración temporal revalidados. |
| 2 | PASS | 7/7 | Parser determinista y CLI, 4 fixtures; se completó T10. |
| 3 | PASS | 5/5 | Servicio, staging, preview, catálogos y cancelación; HTTP sobre BD separada. |
| 4 | Pendiente, no iniciada | 0/4 | T16–T19; se espera aplicar primero la migración real con respaldo. |
| 5 | Mapping PASS; implementación pendiente | 0/3 | T20–T22 deben usar el mapping auditado y dar paridad al editor. |
| 6 | Pendiente por dependencias | 0/4 | T23–T26 pendientes; no se declara liberación. |

Se revisaron diff y archivos del alcance. Los únicos PDFs del alcance están en `test/fixtures/planes/`. No se agregaron PDFs en uploads ni temporales al repositorio. Los cambios ajenos de hardware/biometría ya existentes se conservaron.

## 15. Limitaciones

No hay OCR, XLSX, fechas inferidas ni importación de formatos desconocidos. Los temas compuestos se conservan. No está disponible todavía el flujo visual «Importar Plan Anual». La base de producción no tiene ni siquiera la migración de códigos aplicada; se mantiene pendiente hasta la integración y respaldo correspondientes.

El bloqueo conceptual quedó resuelto con una ampliación aditiva y el round-trip 4/4. Se detiene antes de la BD real tal como fue solicitado; no se marcó T16–T26 ni se creó una persistencia paralela.

## 16. Guía paso por paso para probar lo disponible

### A. Corrección de Matemática de 9.º en Horarios

1. Abrir SISCO-EDU → Horarios y recargar con **Ctrl+F5**.
2. Abrir **Crear horario**.
3. En el buscador de asignaciones escribir **9**.
4. Desplazarse por las 18 coincidencias; aparece **9° · MATEMATICA · Rosalia Florentin · 2026**.
5. Seleccionarla y comprobar materia, profesora, grado y aula del formulario.
6. Cancelar si sólo se desea probar la selección. Este cambio no crea horarios automáticamente.

La asignación activa es 230. Existe una anterior 182 desactivada; no se reactivó ni se duplicó. La API ya devolvía correctamente Matemática. La causa era `.slice(0, 12)` en el buscador: Matemática ocupaba el puesto 14. Se retiró el recorte y se conserva el scroll del panel.

### B. Preparar el parser

1. Iniciar MySQL desde XAMPP para las pruebas integrales. El parser/POC por sí solos no necesitan MySQL.
2. Abrir PowerShell en el repositorio:

```powershell
Set-Location C:\xampp\htdocs\tiago3roBTI2026\sisco-edu
$env:SISCO_PDFTOTEXT_PATH = 'C:\Program Files\Git\mingw64\bin\pdftotext.exe'
& C:\xampp\php\php.exe scripts\plan_import\check_environment.php
```

3. Esperar `ok: true`, versión `4.00` y `supports_table: true`. La variable anterior sólo configura esta sesión PowerShell, no el servicio Apache.

### C. Ejecutar toda la batería implementada

```powershell
& C:\xampp\php\php.exe test\plan_import\run_current.php
& C:\xampp\php\php.exe test\plan_parser_poc\run.php
node test\admin_frontend_headless.js
```

Resultados esperados: **15/15 PASS**, **4/4 PDFs** y **84 OK / 0 FALLAS** respectivamente. El último comando usa la configuración y cuenta de prueba ya previstas por el test existente. Requiere Apache y Chrome. Los tests HTTP crean su propio servidor local, copia servida y BD temporal; los limpian al finalizar. No usan los planes reales para escribir pruebas.

### D. Inspeccionar un JSON real

```powershell
$salidaPlanPdf = Join-Path $env:TEMP ('sisco-plan-' + [guid]::NewGuid().ToString('N') + '.json')
& C:\xampp\php\php.exe scripts\plan_import\parse.php --pretty --debug --output $salidaPlanPdf 'test\fixtures\planes\Plan Anual - tercer curso - BTI - Administracion financiera.pdf'
$planPdf = Get-Content -Raw -Encoding UTF8 $salidaPlanPdf | ConvertFrom-Json
$planPdf.debug.counts
$planPdf.confidence
$planPdf.unidades[0]
```

Esperado: 6 unidades, 6 capacidades, 18 temas, 18 indicadores, confianza 100 y warnings vacíos. El JSON queda en la ruta `$salidaPlanPdf` para revisión; crearlo no importa nada a MySQL.

Repetir cambiando el PDF y generando una nueva ruta de salida para los otros tres fixtures. Comparar con la tabla de la sección 9.

### E. Probar errores y limpieza

```powershell
& C:\xampp\php\php.exe scripts\plan_import\parse.php 'archivo-inexistente.pdf'
$LASTEXITCODE
& C:\xampp\php\php.exe scripts\plan_import\cleanup.php --dry-run
```

Esperado: `PDF_NOT_FOUND`, exit 2; cleanup informa `dry_run: true` sin borrar archivos. Para probar corrección/recarga del preview por HTTP ejecutar `test\plan_import\http\PreviewTest.php`: el test guarda un indicador modificado, vuelve a leerlo y compara el texto. Esto valida staging, **todavía no persistencia en BD**.

## 17. Pendientes y decisión requerida

Respaldar y aplicar de forma segura T03 y `20260907_plan_importacion_campos_sin_perdida.sql`. Después continuar en orden T16–T26: UI jerárquica, advertencias/decisiones/programación, confirmación transaccional/idempotente, códigos y campos compartidos con editor manual, seguridad ampliada, rollback y cuatro E2E hasta reabrir el plan normal.

No deben aplicarse automáticamente las migraciones propuestas a la BD real mientras la decisión esté pendiente.
