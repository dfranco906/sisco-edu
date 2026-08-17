# Auditoría y recuperación del frontend administrativo

Fecha: 2026-08-17
Rama original: `backend`
Rama de trabajo: `frontend-recovery-audit`
Estado inicial: árbol de trabajo limpio, sin archivos modificados previamente.

## 1. Resultado general

**CASI LISTO**

El frontend administrativo principal volvió a ser utilizable: las tablas cargan, Crear/Editar/Desactivar funciona en los módulos administrativos, las relaciones se seleccionan por nombre, los errores relevantes vuelven como JSON válido y los horarios validan asignación y superposición.

La clasificación no es “LISTO” porque quedan inconsistencias de datos históricos y dos clases relacionadas con hardware que no coinciden con la base real. Esos puntos se documentaron y no se corrigieron para respetar el perímetro de seguridad.

Resultados verificables:

- Smoke CRUD administrativo: **43 OK / 0 fallas**.
- Navegador Chrome headless: **74 OK / 0 fallas**.
- Sintaxis PHP: **166 archivos / 0 errores**.
- Sintaxis JavaScript: **9 archivos / 0 errores**.
- Endpoints de lectura administrativos: HTTP 200 y JSON válido.
- Registros temporales de prueba: eliminados; no quedaron datos `AUDIT_`.
- Usuario inicial: `id_usuario=0`, `admin1`, activo.
- Diff protegido: ningún archivo de firmware, asistencia crítica, Gateway crítico, huellas o sync biométrica fue modificado.

## 2. Estado por pestaña

| Pestaña | Listar | Crear | Editar | Desactivar | Filtros | Problemas | Estado final |
|---|---|---|---|---|---|---|---|
| Dashboard | OK | N/A | N/A | N/A | N/A | No tenía métricas reales | OK: 8 métricas activas |
| Profesores | OK | OK | OK | OK | OK | Errores PHP podían llegar como HTML; faltaban datos de huella/estado | OK |
| Estudiantes | OK | OK | OK | OK | OK | Grado manual y datos de grado/aula incompletos | OK con select de grado y aula legible |
| Materias | OK | OK | OK | OK | OK | Validación débil de carga horaria | OK |
| Aulas | OK | OK | OK | OK | OK | Respuesta de lectura sin cabecera JSON coherente | OK |
| Grados | OK | OK | OK | OK | OK | Aula manual; `room_id` podía quedar vacío al crear | OK con select de aula |
| Usuarios | OK | OK | OK | OK | OK | PK sin AUTO_INCREMENT; rol manual; ID 0 ocultaba acciones | OK |
| Asignaciones | OK | OK | OK | OK | OK | IDs manuales, año vacío, error HTTP 500, duplicados | OK con selects y validación |
| Horarios | OK | OK | OK | OK | OK | IDs manuales, nombres legacy y sin control de choques | OK con selects dependientes |
| Asistencia estudiantes | OK | No modificado | No modificado | No modificado | OK visual | Flujo sensible | Verificado solo en lectura |
| Asistencia profesores | OK | No modificado | No modificado | No modificado | OK visual | Flujo sensible | Verificado solo en lectura |
| Administración | OK | N/A | N/A | N/A | N/A | Navegación dependiente de módulos | OK, 13 enlaces detectados |

## 3. Mapa del proyecto auditado

### Frontend

- CRUD compartido: `mvc/views/partials/table_page.php`, `public/js/table-loader.js`, `public/js/crud.js`.
- Horarios: `mvc/views/horarios/index.php`, `public/js/horarios.js`.
- Dashboard: `mvc/views/dashboard.php`, ahora con `public/js/dashboard.js`.
- Vistas reales: profesores, estudiantes, materias, aulas, grados, usuarios, asignaciones, horarios, asistencia de estudiantes, asistencia de profesores, administración y dashboard.
- No hay pestañas administrativas reales para Nodo ESP32, BiometricMapping, HuellaTemplate o SyncBiometrica; existen como APIs/clases.

### Backend

- APIs: `src/api`.
- Modelos/clases: `src/classes`.
- Configuración: `src/config`.
- El proyecto no usa un router REST central: cada archivo PHP es una ruta.
- Las carpetas reales son mayormente singulares; Asignaciones es plural.
- La clase real de asignaciones es `Asignacion.php`, no `AsignacionDocente.php`.

### Base de datos

Tablas detectadas:

`asignacion_docente`, `asistencias`, `asistencias_estudiantes`, `asistencias_profesores`, `aula_huellas_sync`, `aulas`, `biometric_mapping`, `estudiantes`, `eventos_asistencia`, `grados`, `horarios`, `huellas_templates`, `materias`, `nodos_esp32`, `profesores`, `solicitudes_huella`, `sync_biometrica`, `usuarios`.

## 4. Errores encontrados y causa exacta

| Error | Archivo/endpoint | Causa | Solución |
|---|---|---|---|
| Crear asignación devolvía HTTP 500 | `src/classes/Asignacion.php`, `crear_asignacion.php` | La propiedad era `aÃ±o_lectivo`, el endpoint cargaba `año_lectivo` y PDO intentaba insertar NULL | Contrato interno ASCII `anio_lectivo`; columna SQL escapada; compatibilidad con claves anteriores |
| Año lectivo vacío en tabla | API/listado y vista de asignaciones | JSON devolvía una clave mojibake distinta de la usada por la vista | API devuelve `anio_lectivo` estable |
| Crear usuario: duplicate entry 0 | Tabla `usuarios` | `id_usuario` no tenía AUTO_INCREMENT | Migración aplicada y versionada |
| Editar/desactivar usuario inicial no aparecía | `public/js/table-loader.js` | ID numérico 0 se trataba como false | Validación explícita de null/undefined/cadena vacía |
| Desactivar `admin1` daba “ID requerido” | `desactivar_usuario.php` | Validación booleana de ID 0 | HTTP 409 claro y protección del administrador inicial |
| Crear/editar podía fallar sin explicación | `public/js/crud.js` y APIs | `res.json()` sobre HTML/fatal PHP; respuestas genéricas | Lectura text + parse seguro; status HTTP; mensajes por validación; detalle real al log |
| Botones con datos que contenían apóstrofes podían romper | `table-loader.js` | JSON incrustado dentro de `onclick` HTML | `data-*`, Map por ID y listeners |
| Edición relacional abría con selects vacíos | CRUD compartido | Todos los campos se generaban como inputs; APIs solo listaban activos | Campos estructurados; carga asíncrona; valor histórico marcado “actual, no disponible” |
| Horarios pedían `id_asignacion` e `id_grado` | Vista/JS de horarios | Formulario exponía IDs internos | Profesor + materia dependiente + grado; asignación/aula resueltas internamente |
| Horarios permitían superposición | Clase/API Horario | No había consulta de colisión | Conflicto por grado, aula o profesor en mismo día/franja; HTTP 409 |
| Estudiante/grado mostraban relaciones incompletas | Clases Estudiante/Grado | Faltaban JOINs y campos legibles | JOINs con grado/aula/código y selects de activos |
| Dashboard sin datos operativos | Dashboard | Solo tenía enlaces | Endpoint read-only y 8 métricas |

## 5. Problemas de relaciones

### Corregidos

- Asignación:
  - Se muestra profesor y materia.
  - Crear usa profesores activos y materias activas.
  - Editar precarga IDs reales, incluso si una relación histórica quedó inactiva.
  - Se envían `id_profesor`, `id_materia`, `anio_lectivo`.
  - Se rechaza el duplicado activo profesor + materia + año.
- Horario:
  - Profesor y materia se derivan de asignaciones activas.
  - Materia se filtra según profesor.
  - Grado se selecciona por descripción.
  - Aula es solo lectura y se obtiene del grado.
  - Se envían `id_asignacion`, `id_grado`, día y horas.
- Estudiante:
  - Grado se selecciona por descripción; tabla muestra grado, aula y código.
  - Solo se ofrecen grados activos con aula activa.
- Grado:
  - Aula se selecciona por nombre.
  - Al crear/editar se conserva `grados.room_id = aulas.codigo`, sin cambios masivos a datos existentes.
- Usuario:
  - Rol es un select limitado al enum real.

### Inconsistencias de datos pendientes

- Grados activos 3, 4 y 5 apuntan a aulas inactivas 15, 16 y 17.
- Todos los estudiantes activos revisados tienen `id_aula=NULL`; varios tienen `room_id=NULL`.
- El estudiante 23 no coincide con el room del grado.
- Asignaciones activas 4, 5 y 7 apuntan a profesores inactivos.
- Asignaciones 2 y 3 duplican profesor 1 + materia 1 + año 2026.
- Horarios 19, 20 y 22 conservan texto legacy y tienen `id_grado`/`id_aula` nulos.

No se normalizaron esos datos porque una actualización masiva puede afectar asistencia y sincronización.

## 6. Auditoría de APIs

### Rutas administrativas principales comprobadas

| Módulo | Leer | Crear | Actualizar | Desactivar |
|---|---|---|---|---|
| Profesor | `Profesor/leer_profesores.php` | `crear_profesor.php` | `actualizar_profesor.php` | `desactivar_profesor.php` |
| Estudiante | `Estudiante/leer_estudiantes.php` | `crear_estudiante.php` | `actualizar_estudiante.php` | `desactivar_estudiante.php` |
| Materia | `Materia/leer_materias.php` | `crear_materia.php` | `actualizar_materia.php` | `desactivar_materia.php` |
| Aula | `Aula/leer_aulas.php` | `crear_aula.php` | `actualizar_aula.php` | `desactivar_aula.php` |
| Grado | `Grado/leer_grados.php` | `crear_grado.php` | `actualizar_grado.php` | `desactivar_grado.php` |
| Usuario | `Usuario/leer_usuarios.php` | `crear_usuario.php` | `actualizar_usuario.php` | `desactivar_usuario.php` |
| Asignación | `Asignaciones/leer_asignaciones.php` | `crear_asignacion.php` | `actualizar_asignacion.php` | `desactivar_asignacion.php` |
| Horario | `Horario/leer_horarios.php` | `crear_horario.php` | `actualizar_horario.php` | `desactivar_horario.php` |

Hallazgos:

- Se conservaron rutas antiguas; no se borraron endpoints.
- `?opciones=1` se agregó a Grado y Asignaciones para selects seguros.
- Los endpoints corregidos mantienen `status` por compatibilidad y agregan `success`.
- Crear/actualizar Profesor, Estudiante, Materia, Aula, Grado, Usuario, Asignación y Horario valida los campos obligatorios.
- Las respuestas de errores corregidas son JSON incluso ante excepciones.
- `Asistencia/leer_asistencias.php` devuelve HTTP 404 cuando la tabla unificada está vacía; se reporta, no se modificó.
- Existe `Evento/recibir_asistencia,.php` con una coma en el nombre; parece una ruta vieja o accidental. No se modificó.

## 7. Auditoría de clases

### Corregidas

- `Asignacion.php`: año lectivo, JOINs legibles, IDs para edición, opciones activas, duplicados y dependencias.
- `Estudiante.php`: validación grado/aula activos, JOINs legibles y estado derivado de huella sin exponer el template.
- `Profesor.php`: listado completo y estado de huella derivado.
- `Grado.php`: aula activa, opciones y consistencia de código/room para nuevas operaciones.
- `Horario.php`: asignación activa, relaciones legibles, fallback legacy y detección de conflictos.

### Reportadas, no modificadas

- `BiometricMapping.php` y APIs usan `id_aula`, pero la tabla real `biometric_mapping` usa `room_id`.
- `NodoEsp32.php` y APIs usan `id_aula`, pero la tabla real `nodos_esp32` usa `room_id`.
- Clases/endpoints de asistencia, SyncBiometrica y HuellaTemplate se dejaron intactos.

## 8. Auditoría de base de datos

Relaciones principales detectadas:

- `asignacion_docente.id_profesor -> profesores.id_profesor`.
- `asignacion_docente.id_materia -> materias.id_materia`.
- `horarios.id_asignacion -> asignacion_docente.id_asignacion`.
- Horarios contiene `id_grado` e `id_aula`, además de campos de texto legacy `grado` y `aula`.
- Estudiantes contiene `id_grado`, `id_aula`, `room_id`, `huella_id` y `user_id_global`.
- Grados contiene `id_aula NOT NULL` y `room_id NOT NULL`, pero no tiene FK declarada a aulas.

Problemas:

- Faltan varias claves foráneas declaradas entre grados/aulas y estudiantes/grados.
- No hay índice único para profesor + materia + año en asignaciones.
- Existen relaciones activas hacia registros inactivos.
- El SQL mode no es estricto, por lo que columnas obligatorias pueden recibir valores vacíos implícitos.
- La tabla unificada `asistencias` está vacía mientras existen registros en tablas históricas/específicas.

Cambio de esquema aplicado:

- `usuarios.id_usuario` ahora es AUTO_INCREMENT.
- Se preservó `admin1` con ID 0 usando `NO_AUTO_VALUE_ON_ZERO`.
- La migración reproducible está en `database/migrations/20260817_corregir_autoincrement_usuarios.sql`.

No se agregaron FKs, índices únicos ni migraciones de relaciones históricas por el riesgo de impacto operativo.

## 9. Contrato frontend ↔ API

Todas las altas/ediciones administrativas usan `application/x-www-form-urlencoded` mediante `FormData`; las lecturas responden JSON.

| Módulo | Campos que envía el frontend |
|---|---|
| Profesor | `nombre`, `apellido`, `cedula_identidad` |
| Estudiante | `nombre`, `apellido`, `cedula_identidad`, `id_grado` |
| Materia | `nombre`, `descripcion`, `carga_horaria_semanal` |
| Aula | `nombre`, `codigo`, `ubicacion` |
| Grado | `nombre`, `id_aula` |
| Usuario | `nombre`, `apellido`, `usuario`, `email`, `celular`, `password`, `rol` |
| Asignación | `id_profesor`, `id_materia`, `anio_lectivo`, `activo` |
| Horario | `id_asignacion`, `id_grado`, `dia_semana`, `hora_inicio`, `hora_fin` |

Respuesta exitosa:

```json
{
  "success": true,
  "status": "success",
  "message": "Registro creado correctamente",
  "data": { "id_registro": 12 }
}
```

Error de negocio:

```json
{
  "success": false,
  "status": "error",
  "message": "Ya existe una asignación activa para este profesor, materia y año lectivo."
}
```

## 10. Archivos modificados

| Área | Archivos | Cambio |
|---|---|---|
| CRUD compartido | `public/js/crud.js`, `public/js/table-loader.js`, `mvc/views/partials/table_page.php` | JSON robusto, acciones seguras, modales, selects relacionales, filtros y mensajes |
| Asignaciones | vista activa/desactivada, 3 APIs, `src/classes/Asignacion.php` | Año estable, selects, nombres, validación, duplicados |
| Horarios | vista, JS, APIs crear/actualizar, clase | Profesor/materia/grado, aula derivada, edición y choques |
| Estudiantes | vistas, APIs crear/actualizar, clase | Select de grado, joins, validación y columnas legibles |
| Profesores | vistas, APIs crear/actualizar, clase | JSON de errores, validación, estado de huella |
| Materias | vista, APIs crear/actualizar | Validación y campos coherentes |
| Aulas | vista, APIs crear/actualizar/leer | JSON, validación y edición |
| Grados | vistas, APIs crear/actualizar/leer, clase | Select de aula, opciones activas y consistencia de room en nuevas operaciones |
| Usuarios | vista, APIs crear/actualizar/desactivar | AUTO_INCREMENT, rol select, ID 0 y protección de admin |
| Dashboard | vista, `public/js/dashboard.js`, `src/api/Dashboard/resumen.php` | 8 métricas read-only |
| Base de datos | migración de usuarios | AUTO_INCREMENT preservando ID 0 |
| Pruebas | `test/admin_crud_smoke.js`, `test/admin_frontend_headless.js` | Regresión CRUD y navegador |

## 11. Archivos NO modificados por seguridad

- Todo `esp/`: ESP Aula, Gateway, registrador y transporte DY50/AS608.
- `src/api/Gateway/confirmar_entrega_aula.php`.
- `src/api/Gateway/obtener_sync_aula.php`.
- `src/api/Huella/guardar_template.php`.
- Endpoints y clases de registro real de asistencia.
- `registrar_asistencia.php`.
- `SyncBiometrica.php`, `HuellaTemplate.php`, `BiometricMapping.php`, `NodoEsp32.php`.
- Tablas históricas de asistencia, eventos, huellas y sync.

La nueva métrica “Marcas recibidas hoy” solo ejecuta un `COUNT` read-only sobre `eventos_asistencia`.

## 12. Tests realizados

### HTTP/API

- Lecturas de Dashboard, Profesor, Estudiante, Materia, Aula, Grado, Usuario, Asignaciones y Horario: HTTP 200 + JSON.
- Opciones de Grado y Asignaciones: HTTP 200 + listas filtradas.
- Profesor: crear, editar y duplicado HTTP 409.
- Materia, Aula, Grado, Estudiante y Usuario: crear y editar.
- Asignación: crear, editar y duplicado HTTP 409.
- Horario: crear, editar y choque HTTP 409.
- Todos los módulos anteriores: desactivar, restaurar y eliminar.
- Administrador inicial: desactivación rechazada con HTTP 409; permanece activo.

### Navegador

- Login real con sesión.
- Tabla, Crear, Editar, Desactivar, filtros y UTF-8 en siete CRUD compartidos.
- Selects de Crear y Editar cargados desde la BD.
- Asignación histórica con profesor inactivo preservada como valor actual.
- Horario: cuatro selects, aula read-only y edición precargada.
- Asistencias: visualización únicamente.
- Administración: navegación.
- Dashboard: ocho métricas.
- Consola JavaScript: sin errores.
- Requests del mismo origen: sin HTTP >= 400 durante el recorrido.

### Estático

- PHP lint: 166/166.
- Node `--check`: 9/9.
- `git diff --check`: sin errores de whitespace; solo advertencias de conversión LF/CRLF.

## 13. Riesgos pendientes

1. Normalizar los datos inconsistentes exige una migración específica y respaldo previo.
2. Agregar FKs o un UNIQUE de asignaciones podría fallar mientras existan duplicados.
3. Los horarios legacy con IDs nulos no pueden validarse igual que un horario relacional moderno.
4. `BiometricMapping` y `NodoEsp32` siguen desalineados con `room_id`; corregirlos requiere una tarea separada con pruebas de hardware.
5. El endpoint unificado de asistencia devuelve 404 si no hay registros; conviene decidir si el contrato debe ser 200 + lista vacía.
6. Los scripts de prueba históricos del repositorio mutan datos y algunos reflejan contratos viejos; no se ejecutaron.
7. Algunas APIs antiguas de desactivación/restauración todavía conservan estilo de respuesta legacy, aunque el frontend nuevo las interpreta y las pruebas pasaron.

## 14. Próximos pasos

1. Respaldar la BD y preparar una migración de datos para grados/aulas, estudiantes y horarios legacy.
2. Resolver los duplicados de `asignacion_docente`; luego agregar un índice único controlado.
3. Probar manualmente en la resolución/pantalla usada para la presentación.
4. Ejecutar `node test/admin_crud_smoke.js` y `node test/admin_frontend_headless.js` antes de fusionar.
5. Hacer una tarea separada para el desajuste `id_aula`/`room_id` de NodoEsp32/BiometricMapping, con hardware disponible.
6. Tras aceptar este lote, hacer commit en `frontend-recovery-audit` y fusionar por revisión, nunca directamente sobre hardware.
