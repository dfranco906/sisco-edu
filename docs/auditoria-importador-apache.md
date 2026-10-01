# Auditoria del upload real en Apache/XAMPP

Repositorio: `dfranco906/sisco-edu`. Rama exclusiva: `frontend-recovery-audit`.
Revision inicial: `eba8e241c9fb15aa0ae4953c17144fcfca1a0d64`.
Fecha de la auditoria: 2026-10-01.

## INVALID_CONTEXT: causa comprobada

Se reprodujo el formulario en Microsoft Edge mediante Playwright, contra
`http://localhost/tiago3roBTI2026/sisco-edu`, Apache en el puerto 80 y `sisco_db`.
Se reutilizo una sesion de login real existente; no se creo una identidad ficticia,
una BD temporal ni un servidor PHP -S para esta reproduccion.
El navegador bloqueo las solicitudes de escritura excepto el upload de PDF.
No se llamo a `confirmar_importacion.php`.

Fixture: `Plan Anual - tercer curso - BTI - Administracion financiera.pdf`.
El upload anterior al endurecimiento respondio HTTP 422, `INVALID_CONTEXT`.
Un diagnostico temporal del request, retirado luego de recopilar la evidencia,
registro exclusivamente estos campos seguros:

| Campo | Resultado real |
| --- | --- |
| PHP SAPI | `apache2handler` |
| session_status | `2` (PHP_SESSION_ACTIVE) |
| isset(id_usuario) | true |
| id_usuario / owner | `0` |
| rol | SuperAdmin |
| id_asignacion recibido | `33` |
| anio recibido | `2026` |
| anio_lectivo en BD | `2026` |
| usuario existente | true |
| usuario activo | true |
| profesor vinculado | false (no requerido para SuperAdmin) |
| owner < 1 | **true** |
| assignment < 1 | false |
| year fuera de 2000..2100 | false |

La causa exacta es `owner=0`. La cuenta SuperAdmin real tiene ID cero en la BD.
`isset()` admitia esa identidad y la autorizacion administrativa permitia seguir
hasta staging, cuyo contrato exige un propietario positivo.
En la auditoria inicial no se cambio esa cuenta ni sus referencias en datos reales.
La reparacion posteriormente autorizada se documenta al final de este informe.
La comprobacion del hash SHA-256 del endpoint servido por Apache coincidio con
el archivo de esta copia de la rama; no se estaba sirviendo otra copia.

## Sesiones

`usuarioActual()` exige un ID entero positivo, un usuario existente y activo y
un rol de sesion que coincida con la BD. Una identidad invalida recibe HTTP 401
`INVALID_SESSION` antes del importador. Un fallo de validacion en BD recibe 503.
El login tampoco crea una sesion autenticada con ID cero.
Los IDs positivos provenientes de PDO como cadenas siguen siendo validos.

`SessionValidationTest` y `SessionLoginTest` son regresiones en BD temporal.
Sus PASS no prueban que Apache funcione. La prueba con sesion valida en Apache
necesita un login real con una cuenta positiva existente.

## Configuracion PDF de Apache

Antes de configurar Apache, el probe local devolvio:
`getenv('SISCO_PDFTOTEXT_PATH')` ausente, archivo inexistente, no ejecutable.
El proceso CLI y sus tests no demostraban ninguna configuracion de Apache.

Se instalo `src/api/Planificacion/.htaccess` local (ignorado por Git), a partir
de `.htaccess.example`, con `SetEnv SISCO_PDFTOTEXT_PATH` apuntando al Xpdf
instalado. Requiere `mod_env` y `AllowOverride FileInfo` o `All`.
No requiere que Apache herede variables del CLI ni modifica firmware.
Las rutas de instalacion pertenecen a esta configuracion del servidor y no
se devuelven al navegador.

Un probe loopback temporal ejecutado por Apache comprobo despues:
variable presente, ejecutable existente y ejecutable, extraccion real con
`-table` PASS (9466 bytes de texto), staging con escritura/lectura/borrado PASS.
El probe temporal se retira al terminar la auditoria.

El preflight autenticado `preflight_importacion.php` verifica identidad,
asignaciones permitidas, columnas requeridas, staging privado escribible,
extraccion Xpdf y limites de PHP/configuracion. Devuelve estados y tipos de
error, sin rutas internas, cookies, IDs de sesion ni credenciales.

## Upload y preview reales despues de corregir

Se uso la cuenta Profesor positiva existente mediante su sesion de login real,
con usuario activo y profesor activo vinculado. Se mantuvieron sus 15 asignaciones
permitidas. No se crearon usuarios ni asignaciones en `sisco_db`.
En Edge contra el Apache real, el mismo fixture y la asignacion 33/anio 2026:

| Comprobacion | Resultado |
| --- | --- |
| Upload multipart generado por el formulario | HTTP **201** |
| Token staging | Formato valido, 64 caracteres hexadecimales; valor no publicado |
| Pagina de preview | Navegable, renderizada |
| GET preview API con la misma sesion | HTTP **200** |
| Conteos del upload y del preview visible | **6 unidades, 6 capacidades, 18 temas, 18 indicadores** |
| Preflight con la misma sesion | HTTP **200**, ready=true, runtime=apache2handler |
| Usuario / asignaciones / schema / staging / pdftotext / limites | **6 PASS** |
| Peticiones de confirmacion | **0** |

La captura [conteos del preview en Apache](audit-assets/apache-import-preview-counts.png)
contiene solamente el contador visible, sin datos personales ni tokens.
La respuesta resumida esta guardada en
[evidencia segura del upload](audit-assets/apache-upload-evidence.json).
La automatizacion observo el fetch real del formulario y la respuesta del servidor;
no sustituyo respuestas ni cargo JSON prefabricado. Todas las peticiones de escritura
excepto el upload estaban bloqueadas en el navegador de auditoria.

Los conteos reales antes del primer diagnostico y despues del upload siguen siendo:
planes 3, unidades 2, capacidades 2, temas 4, indicadores 4, programaciones 4,
procedimientos 7, instrumentos 7, relaciones tema-procedimiento 8 y
tema-instrumento 9. Ademas, una comparacion por hash de todas las filas de estas
10 tablas antes/despues de la verificacion final dio `unchanged=true`.
Las consultas de auditoria a `sisco_db` fueron exclusivamente SELECT/SHOW.

## Restricciones y resultado

No se modifican reglas de publicacion, programacion, ClaseDiaria, asistencia,
GatewayAttendanceService, informes ni firmware.
Datos academicos insertados por esta auditoria: **0**.
Upload y preview reales: **PASS**, detenidos antes de confirmar.
Listo para comenzar una auditoria posterior de programacion: **SI**.
Esto no valida ni modifica las reglas de programacion/publicacion.
Al cierre inicial, la cuenta SuperAdmin con ID cero se rechazo sin renumerar
datos reales. Su reparacion posterior requirio autorizacion explicita y se
documenta a continuacion.

## Regresiones y archivos

`php test/plan_import/run_all.php`: **28/28 PASS, 0 FAIL** (BDs temporales).
`SessionValidationTest`: 21 comprobaciones. `SessionLoginTest` conserva el login
positivo y rechaza el ID cero. `ApiPreflightTest` comprueba sesion, schema incompleto,
ausencia de asignaciones/extractor, limites invalidos y staging dentro del directorio
publico. `UploadTest` ahora exige 401 para un rol de sesion que no coincide con la BD,
en lugar de tratar esa sesion adulterada como una identidad valida sin permisos.
Los PHP modificados y el JS del formulario pasaron sus comprobaciones de sintaxis.
La configuracion Apache `.htaccess.example` responde HTTP 403 al intentar leerla
por navegador. Los probes y el registro temporal de contexto fueron retirados.

Archivos de los cambios (sin archivos de tiempos generados por tests):

- `src/config/api_auth.php`
- `src/config/session_identity.php`
- `src/api/login.php`
- `mvc/views/auth/login.php`
- `src/api/Planificacion/preflight_importacion.php`
- `src/api/Planificacion/.htaccess.example`
- `.gitignore`
- `src/classes/PlanImport/ImportEnvironmentPreflight.php`
- `src/classes/PlanImport/PlanImportStaging.php`
- `mvc/views/planificacion/index.php`
- `public/js/plan-import.js`
- `test/plan_import/http/SessionValidationTest.php`
- `test/plan_import/http/SessionLoginTest.php`
- `test/plan_import/http/ApiPreflightTest.php`
- `test/plan_import/http/UploadTest.php`
- `test/plan_import/support/TestEnvironment.php`
- `docs/auditoria-importador-apache.md`
- `docs/audit-assets/apache-upload-evidence.json`
- `docs/audit-assets/apache-import-preview-counts.png`

Configuracion local instalada fuera del commit:
`src/api/Planificacion/.htaccess` (ruta de Xpdf especifica de esta PC).

## Reparacion autorizada del login SuperAdmin

El login real de `admin1` verificaba correctamente su clave, pero redirigia con
`error=sesion`: el usuario estaba activo, tenia rol SuperAdmin e ID cero.
La validacion de identidad positiva rechazo correctamente esa cuenta.
El usuario autorizo explicitamente migrar su identidad al ID libre **29**, con
las referencias de autor asociadas. No se debilito la validacion del login.

Se ejecuto `scripts/maintenance/repair_zero_admin_identity.php --dry-run` y
despues `--apply` contra `sisco_db`. La herramienta es exclusivamente CLI,
esta fijada a la cuenta y destino autorizados y no se ejecuta automaticamente.
Dentro de una transaccion InnoDB actualizo la identidad mediante las claves
foraneas `ON UPDATE CASCADE`:

| Referencia | Filas actualizadas |
| --- | --- |
| `planes_anuales.created_by` | 2 |
| `configuracion_informes.updated_by` | 14 |
| `profesores.id_usuario` | 0 |
| `registros_anecdoticos.created_by` | 0 |

La comparacion por hash de todas las filas de todas las tablas, normalizando
solamente estas columnas de identidad, coincidio antes/despues. El resultado
fue `only_identity_references_changed=true`, `rows_inserted=0`. Se conservaron
usuario, hash de la clave, rol, estado activo y contenido academico. Una nueva
ejecucion informa `already_repaired` sin cambiar datos.

Se hizo un login nuevo mediante el formulario en Edge/Playwright contra Apache:
HTTP **302**, dashboard accesible, preflight HTTP **200**, `ready=true`,
`runtime=apache2handler` y **6 PASS** (usuario, schema, asignaciones, limites,
staging, pdftotext). El navegador bloqueo toda escritura excepto el login.
Peticiones de confirmacion: **0**. No se repitio ni confirmo la importacion.
La evidencia segura esta en
[verificacion del login](audit-assets/apache-admin-login-evidence.json).
Las sesiones antiguas con ID cero siguen siendo invalidas; hay que iniciar
sesion nuevamente con la misma cuenta y clave.

`ZeroUserIdentityRepairTest` verifica en BD temporal dry-run, rechazo de destino
ocupado, rollback ante cambios adicionales, cascada, preservacion de contenido
y credencial, identidad valida e idempotencia. Resultado: **PASS**.
`SessionValidationTest` (**21 comprobaciones**) y `SessionLoginTest` tambien
pasaron, junto con la sintaxis PHP y `git diff --check`. Estos tests son regresiones y la
prueba del entorno real es el login y preflight de Apache descriptos arriba.
