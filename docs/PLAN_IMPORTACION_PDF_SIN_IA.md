# Importación automática de planes anuales desde PDF sin IA

Estado: diseño y prueba de concepto aislada, 3 de septiembre de 2026. Esta sesión no integró el parser con rutas, controladores, vistas ni tablas productivas. La POC no ejecuta SQL.

Actualización de implementación (07-09-2026): el texto anterior describe la auditoría histórica. Actualmente T01–T15 están implementadas, con parser de producción y endpoints de staging/preview/cancelación. No hay formulario de importación ni confirmación SQL habilitados. La integración restante está BLOCKED por el conflicto de persistencia de la sección 31; ver el reporte de implementación y sus comandos de prueba.

## 1. Estado actual de SISCO-EDU

SISCO-EDU es una aplicación web PHP/MySQL ejecutada con XAMPP. El repositorio combina gestión académica, planificación pedagógica, asistencia, informes y firmware. La importación estudiada pertenece solo al área pedagógica; no se modificó `esp/`, biometría, LoRa, Gateway ni sincronización.

La instancia auditada tenía datos reales y el módulo pedagógico ya aplicado. La consulta de solo lectura del 03-09-2026 encontró, entre otros, 3 planes anuales, 2 unidades, 2 capacidades, 4 temas y 4 indicadores. Los conteos de otras entidades pueden cambiar porque la instancia está en uso; no son fixtures de la POC.

## 2. Arquitectura real encontrada

- Servidor: XAMPP en Windows, Apache 2.4.58 Win64.
- Backend: PHP 8.2.12 CLI/Apache, PDO MySQL, sin framework.
- Base: MariaDB 10.4.32, aunque el proyecto la denomina MySQL en varios documentos.
- Frontend: vistas PHP, HTML, CSS propio, JavaScript nativo y Tailwind cargado desde CDN.
- Entrada: `index.php` redirige al login.
- Rutas: no existe router. Las URLs apuntan directamente a vistas y archivos PHP bajo `src/api/`.
- “MVC” real: las vistas están en `mvc/views/`, la lógica de datos en `src/classes/` y los endpoints en `src/api/`. Los nueve archivos de `mvc/controllers/` existen pero están vacíos; no deben tomarse como patrón para la integración.
- Configuración: `src/config/db.php`, `src/config/app.php`, `src/config/api_auth.php` y `src/middleware/auth.php`.
- Dependencias: no hay `composer.json`, `composer.lock`, `vendor/`, `package.json`, `requirements.txt` ni `pyproject.toml`.
- PHP tiene `fileinfo`, `json`, `mbstring`, `PDO`, `pdo_mysql`, `openssl`, `proc_open` y otras extensiones necesarias. `disable_functions` y `open_basedir` están vacíos.
- Límites PHP locales: `upload_max_filesize=40M`, `post_max_size=40M`, `memory_limit=512M`; el importador debe imponer un máximo propio menor.
- Node 24.17.0 está instalado para pruebas auxiliares. No es dependencia del frontend servido.

## 3. Tablas relevantes

La jerarquía anual real es:

```text
asignacion_docente
└── planes_anuales
    └── plan_unidades
        └── plan_capacidades
            └── plan_temas
                ├── plan_indicadores
                ├── plan_tema_procedimientos ── procedimientos_evaluativos
                ├── plan_tema_instrumentos ──── instrumentos_evaluativos
                └── plan_tema_programacion
```

Relaciones de contexto:

- `asignacion_docente`: profesor, materia, grado, carga horaria y año lectivo.
- `profesores`: vínculo opcional y único con `usuarios.id_usuario`; permite ownership de un Profesor.
- `grados`: es la entidad real de grado/curso y referencia un aula.
- `horarios`: referencia la asignación y soporta clases conjuntas.
- `planes_anuales`: único por `(id_asignacion, anio)`, con competencias general/específica y estado.
- `plan_unidades`: nombre, descripción, HC, fechas de proceso, área transversal, metodología y medios de verificación.
- `plan_capacidades`: descripción y proceso de desarrollo.
- `plan_temas`: título, contenido y HC.
- `plan_indicadores`: descripción.
- `procedimientos_evaluativos` e `instrumentos_evaluativos`: catálogos globales activos.
- `plan_tema_programacion`: fecha/periodo y HC programadas por tema.
- `clases_diarias` y `clase_diaria_indicadores`: consumen el plan publicado.
- `registros_anecdoticos`: observaciones por clase y estudiante.
- `configuracion_informes`: membrete de informes.

No existe tabla de calendario académico o feriados. Tampoco existen campos para códigos `I`, `1.1` o `1.1.1`. La integración definitiva debe agregar campos `codigo` opcionales a las tres tablas existentes (`plan_unidades`, `plan_temas`, `plan_indicadores`) y mostrarlos también en el editor manual. Esto conserva la numeración sin crear una estructura académica paralela.

No se propone una tabla `planes_pdf`. El resultado confirmado debe persistirse en las tablas anteriores, exactamente igual que un plan manual.

## 4. Archivos relevantes

- `database/migrations/20260831_planificacion_pedagogica.sql`: esquema pedagógico vigente.
- `src/classes/PlanificacionPedagogica.php`: listado, creación, edición, publicación, jerarquía, catálogos y programación.
- `src/classes/ClaseDiaria.php`: resolución del contenido publicado por asignación/fecha.
- `src/config/api_auth.php`: sesión, roles, ownership de asignación y respuestas JSON.
- `src/api/Planificacion/*.php`: cinco endpoints actuales.
- `mvc/views/planificacion/index.php`: crear/listar planes.
- `mvc/views/planificacion/editor.php`: editor manual.
- `public/js/planificacion.js`: UI nativa y llamadas fetch.
- `docs/PLANIFICACION_PEDAGOGICA.md`: documentación vigente del módulo.
- `test/test_planificacion_pedagogica.php`: prueba integral existente; crea y limpia datos temporales.
- `src/api/Configuracion/configuracion_informes.php`: referencia útil de validación `finfo`, tamaño y nombre aleatorio de upload.
- `public/uploads/`: ubicación pública actual para imágenes de informes; no es adecuada para PDFs temporales.
- `scripts/plan_parser_poc/`: POC aislada creada en esta sesión.
- `test/fixtures/planes/`: cuatro PDFs reales copiados como fixtures.
- `test/plan_parser_poc/expected/`: ground truth estructural revisado.
- `test/output/planes/`: JSON extraído por la POC.

## 5. Autenticación y RBAC

El login usa consulta preparada, `password_verify`, usuario activo y `session_regenerate_id(true)`. La sesión almacena `id_usuario`, `usuario` y `rol`. Los roles reales son `SuperAdmin`, `Administracion`, `Coordinador` y `Profesor`.

Los endpoints de planificación admiten esos cuatro roles. `api_auth.php` restringe al Profesor a asignaciones cuyo profesor activo está vinculado a su usuario; los otros tres roles tienen administración pedagógica. La futura carga, preview y confirmación deben reutilizar `usuarioActual()` y `asegurarAccesoAsignacion(..., true)`; nunca deben confiar en `id_profesor` enviado por el navegador.

No se observó protección CSRF general. Los nuevos POST de upload/confirmación deben incorporar token CSRF de sesión como requisito del módulo, sin debilitar el ownership actual.

## 6. Implementación actual de Planificación Pedagógica

El flujo manual deriva profesor, materia, grado/aula y año desde una asignación activa. El editor guarda unidades, capacidades, temas, indicadores, evaluaciones y rangos de programación. Las operaciones de estructura usan transacciones pequeñas por elemento. Para publicar se exige al menos un tema con indicador y programación.

La importación masiva no debe encadenar esos métodos si cada uno abre/cierra su propia transacción. Se diseñará un único método de persistencia confirmado que inserte todo el árbol bajo una sola transacción.

Los procedimientos e instrumentos se relacionan con temas, aunque en algunos PDFs aparecen como celdas combinadas de unidad. El adaptador replica la relación hacia cada tema de la unidad; no genera varias lecturas visuales de la misma celda.

## 7. Herramientas PDF evaluadas

| Opción | Disponibilidad local | Prueba/resultado | Decisión |
|---|---:|---|---|
| PyMuPDF / `fitz` | No | Python general no existe. Los Python 3.12.11/3.12.9 encontrados pertenecen a GIMP/Inkscape, sin PyMuPDF. Una instalación temporal intentó compilar un paquete fuente de 87,9 MB por no existir wheel compatible con ese runtime MinGW; se canceló y se retiró `pip`. | No depender de aplicaciones gráficas ni agregar Python en v1. |
| `pdfplumber` | No | No está instalado en ninguno de los runtimes encontrados. | No probar/instalar además de PyMuPDF. |
| Biblioteca PHP existente | No | No hay Composer, `vendor/` ni referencias a Smalot/PDFParser, TCPDF, Dompdf, mPDF o equivalentes de lectura. | Descartada por inexistente. |
| Biblioteca PHP liviana nueva | No | Exigiría introducir Composer o mantener un parser PDF propio. | No justificada frente a la herramienta local. |
| Xpdf `pdftotext` 4.00 | Sí | Ejecutable en `C:\Program Files\Git\mingw64\bin\pdftotext.exe`; soporta `-layout` y `-table`. Extrajo texto de los 4/4 PDFs. | Elegida. |

Solo se exploraron dos candidatos reales: Xpdf y la viabilidad de PyMuPDF. No se instalaron múltiples soluciones.

## 8. Comparación local `-layout` frente a `-table`

Mediana de cinco ejecuciones en el equipo auditado:

| PDF abreviado | `layout` ms | `table` ms | líneas `layout` | líneas `table` |
|---|---:|---:|---:|---:|
| BCB “segundo” / Competencia | 22 | 21 | 132 | 181 |
| Administración 2.º | 17 | 18 | 75 | 94 |
| Excel 3.º | 21 | 20 | 108 | 147 |
| Administración 3.º | 20 | 19 | 73 | 100 |

La velocidad es equivalente. `-table` fue elegido porque separa mejor las líneas envueltas y conserva columnas como posiciones de caracteres. `-layout` combina más fragmentos en líneas muy largas y dificulta reconocer inicios de celda.

La variante elegida es específicamente Xpdf `pdftotext -table`, no cualquier binario homónimo. Poppler normalmente debe verificarse por separado; el despliegue debe ejecutar un self-check que confirme soporte de `-table`.

## 9. Arquitectura del parser

Arquitectura actual de la POC y propuesta para promoción:

```text
PlanImporterInterface                         (futuro punto común)
├── PdfPlanImporter
│   ├── PdfTextExtractorInterface
│   │   └── XpdfPdftotextExtractor
│   ├── PlanFormatDetector
│   ├── PlanPdfParserInterface
│   │   ├── AdministracionFinancieraParser
│   │   ├── ExcelAvanzadoParser
│   │   └── CompetenciaContenidoParser
│   ├── PlanValidator
│   └── ConfidenceCalculator
└── XlsxPlanImporter                         (futuro, no POC)
```

No hay un parser universal con condicionales mezclados. El detector solo selecciona el adaptador. Cada adaptador conoce sus encabezados, columnas, herencia y señales jerárquicas.

La POC está escrita en PHP y se ejecuta por CLI. La integración productiva puede llamar las mismas clases en proceso y usar `proc_open` únicamente para el binario fijo de Xpdf; no necesita Python, Flask, FastAPI ni proceso permanente.

## 10. Detector de formato

El detector elimina variaciones de espacios, mayúsculas y acentos solo para comparar señales; nunca usa esa versión para el contenido académico. Requiere `score >= 60` y una ventaja mínima de 15 puntos sobre el segundo formato.

Señales máximas actuales:

- Administración Financiera: título 25; Unidad/Capacidades 20; Procedimientos 15; Instrumentos 15; Tiempo 10; Proceso 10; competencias 5+5. Máximo 105.
- Excel Avanzado: título 25; Unidad/Capacidades/Temas 20; Check 18; proceso de desarrollo 22; procedimientos 8; instrumentos 8; códigos de tema 10; códigos de indicador 10. Máximo 121.
- Competencia/Contenido: Competencia/Capacidad 20; Contenidos 15; Área Transversal 15; Metodología 20; Medios 20; Fecha 5; Días de clase 5. Máximo 100.

Scores reales: 105 (Administración 2.º), 105 (Administración 3.º), 121 (Excel 3.º) y 100 (Competencia/Contenido). Si no se alcanza score/margen se devuelve `UNKNOWN_FORMAT`; no se elige por nombre de archivo.

## 11. Parsers disponibles

### Administración Financiera

Detecta límites desde los encabezados Unidad, Capacidades, Temas, Indicadores, Procedimientos, Instrumentos, Tiempo y Proceso. Las unidades romanas son anclas verticales. En 3.º, los temas `N.N` se separan y los indicadores se asignan por orden solo cuando la cantidad coincide. En 2.º no hay numeración interna: se conserva una celda-tema compuesta por unidad.

### Excel Avanzado

Deriva límites por página porque Xpdf compacta de forma distinta la segunda página. Los temas `N.N` y los indicadores `N.N.N` se enlazan por prefijo exacto, por ejemplo `2.2.3 -> 2.2`. La asociación no depende de proximidad cuando existe código. Check queda `null` porque las 53 celdas reales están vacías.

### Competencia/Contenido

Las competencias se modelan como unidades y cada capacidad como capacidad. Al no haber numeración ni acceso a líneas horizontales, conserva todos los textos de `CONTENIDOS` en un tema compuesto por capacidad. Emite `TABLE_STRUCTURE_WARNING`; la preview puede subdividirlo sin pérdida textual. Se preservaron 34 etiquetas/contenidos atómicos dentro de 14 temas compuestos y 47 indicadores.

## 12. Modelo JSON canónico

El contrato completo está en `scripts/plan_parser_poc/schema/plan-import-poc.schema.json`. Todos los adaptadores emiten exactamente las mismas claves:

```json
{
  "schema_version": "1.0-poc",
  "source": {
    "filename": "...pdf",
    "format": "ADMINISTRACION_FINANCIERA",
    "sha256": "...",
    "pages": 1,
    "text_layer": true,
    "extractor": { "name": "pdftotext", "mode": "table" }
  },
  "metadata": {
    "institucion": null,
    "materia": null,
    "profesor": null,
    "curso": null,
    "turno": null,
    "anio": null,
    "dias_clase": null,
    "competencia_general": null,
    "competencia_especifica": null
  },
  "unidades": [
    {
      "orden": 1,
      "codigo": "I",
      "nombre": "...",
      "descripcion": null,
      "horas_catedra": 16,
      "tiempo_texto": "16",
      "proceso_texto": "Febrero - Marzo",
      "area_transversal": null,
      "metodologia": null,
      "medios_verificacion": null,
      "capacidades": [
        {
          "orden": 1,
          "descripcion": "...",
          "proceso_desarrollo": null,
          "temas": [
            {
              "orden": 1,
              "codigo": "1.1",
              "titulo": "...",
              "contenido": null,
              "horas_catedra": null,
              "tiempo_texto": null,
              "fecha_texto": null,
              "indicadores": [
                { "orden": 1, "codigo": null, "descripcion": "...", "check": null }
              ],
              "procedimientos_evaluativos": [],
              "instrumentos_evaluativos": []
            }
          ]
        }
      ]
    }
  ],
  "warnings": [],
  "confidence": {}
}
```

Los campos `*_texto` preservan valores que todavía no son insertables, como “Febrero - Marzo”. La POC no convierte meses a fechas ni crea IDs de catálogo.

## 13. Reglas por formato

- Administración 3.º: una unidad romana, una capacidad combinada, varios temas codificados, indicadores con viñeta y evaluación/HC/proceso heredados de la celda de unidad.
- Administración 2.º: una unidad, una capacidad y una celda temática compuesta; todos los indicadores de la fila pertenecen a ese tema compuesto.
- Excel 3.º: unidad `UNIDAD I/II/III`; una capacidad combinada; tema `N.N`; indicador `N.N.N`; evaluación y proceso heredados por unidad; HC tomada de la columna Tiempo.
- Competencia/Contenido: Competencia -> Unidad; Capacidad -> Capacidad; Contenidos de esa franja -> Tema compuesto; Indicadores de esa franja -> Indicadores; Área/Metodología/Medios/Fecha se agregan sin repetir fragmentos al nivel de unidad que admite la BD real.

## 14. Estrategia de celdas combinadas

Se detecta la posición vertical de la etiqueta de cada celda padre y se calculan intervalos entre anclas. Los fragmentos vacíos heredan el último padre solo dentro de su intervalo. Las continuaciones entre páginas se unen cuando el texto de la capacidad se repite de forma exacta.

La evaluación y el proceso visibles una sola vez se asocian al padre y luego a sus temas en el modelo relacional. No se crea un nuevo procedimiento por cada línea envuelta.

Cuando una celda sin numeración puede subdividirse de varias maneras, se conserva compuesta y se genera warning. Esta regla implementa “no asociar/subdividir antes que asociar mal”.

## 15. Estrategia de coordenadas

Xpdf `-table` proyecta coordenadas PDF a texto monoespaciado. La POC registra por columna:

- `x_char_start`, `x_char_end`;
- `x_relative_start = x_char_start / ancho_de_línea_máximo`;
- `x_relative_end`;
- página y línea para warnings/anclas.

Los límites se descubren desde encabezados y posiciones mínimas de códigos, no desde píxeles absolutos. Excel y Competencia/Contenido recalculan por página porque la proyección puede compactarse.

Limitación: este extractor no expone `x0/x1/y0/y1` reales ni líneas vectoriales. Si nuevos fixtures no pueden resolverse de forma conservadora, el disparador de cambio es instalar un Python soportado y reevaluar PyMuPDF, no agregar excepciones gigantes al parser.

## 16. Regex relevantes

```text
Unidad romana:      ^(?:UNIDAD\s+)?([IVXLCDM]+)\.?
Tema:               ^(?:TEMA\s*)?(\d+\.\d+)\b
Indicador:          ^(\d+\.\d+\.\d+)\b
Año:                \bA[ÑN]O\s*:\s*((?:19|20|21)\d{2})
Horas:              (\d+(?:[.,]\d+)?)\s*(?:HC|H|HORAS?)
Firma PDF:          bytes iniciales == %PDF-
```

La numeración es una señal adicional. Administración 2.º y Competencia/Contenido funcionan sin códigos.

## 17. Validaciones

- PDF existente, extensión `.pdf`, tamaño 1 byte–20 MB en POC, firma `%PDF-` y MIME `application/pdf`/`application/x-pdf`.
- Formato supera score y margen.
- Año entre 2000 y 2100.
- Existe al menos una unidad.
- Todas las unidades tienen capacidad.
- Todas las capacidades tienen tema.
- Todos los temas tienen indicador cuando el formato lo requiere.
- Órdenes comienzan en 1 y son secuenciales.
- Tema `N.N` pertenece a unidad N.
- Indicador `N.N.N` pertenece exactamente al tema `N.N`.
- Códigos no duplicados.
- HC numéricas y no negativas; Excel avisa si falta HC.
- No hay indicadores/temas huérfanos o incoherentes.
- Nombre de archivo versus curso interno: solo warning; manda la metadata interna y la asignación elegida.

No se corrige contenido académico ni se inventan fechas.

## 18. Confidence sin IA

No es una probabilidad. Es un porcentaje de criterios deterministas cumplidos:

- Metadata: campos esperados encontrados; resta 15 ante mismatch archivo/curso.
- Estructura (100): existe unidad 15; todas con capacidad 20; todas las capacidades con tema 20; todos los temas con indicador 20; órdenes 5; códigos coherentes/no aplicables 10; HC requeridas 10.
- Asociaciones (100): sin temas huérfanos 40; sin indicadores huérfanos 40; sin columnas ambiguas 10; sin reconstrucción conservadora 10.
- Global: `30% metadata + 40% estructura + 30% asociaciones`.

Resultados globales: 100, 100, 96 y 93. Un 100 no evita la preview.

## 19. Warnings

Tipos implementados/diseñados:

- `UNKNOWN_FORMAT`
- `PDF_SCAN_NOT_SUPPORTED`
- `MISSING_METADATA`
- `SOURCE_METADATA_MISMATCH`
- `AMBIGUOUS_COLUMN`
- `ORPHAN_TOPIC`
- `ORPHAN_INDICATOR`
- `MISSING_UNIT`, `MISSING_CAPACITY`, `MISSING_TOPIC`, `MISSING_INDICATOR`
- `MISSING_HOURS`, `INVALID_HOURS`, `INVALID_YEAR`
- `DUPLICATE_CODE`, `INCOHERENT_TOPIC_CODE`, `INCOHERENT_INDICATOR_CODE`
- `UNRECOGNIZED_TEXT`
- `TABLE_STRUCTURE_WARNING`

Cada warning puede incluir página, línea y texto. Nunca se rellena una asociación dudosa para silenciarlo.

## 20. Texto original

El parser mantiene mayúsculas, acentos, puntuación y redacción extraídas. Solo colapsa whitespace técnico y une líneas inequívocamente continuadas. La versión sin acentos/mayúsculas se usa únicamente para detector y comparación, nunca como salida académica.

Los códigos separados no eliminan contenido: se conservarán en campos `codigo` opcionales. Procedimientos/instrumentos se guardan primero como textos fuente; la preview debe mostrar el catálogo destino antes de confirmar cualquier normalización.

## 21. PDFs escaneados

Los 4/4 fixtures poseen capa de texto; no se ejecutó OCR. La POC considera escaneado/no soportado un resultado con menos de 40 caracteres alfanuméricos y devuelve:

`PDF_SCAN_NOT_SUPPORTED: Este PDF parece ser una imagen escaneada. Utilice un PDF digital o cargue el plan manualmente.`

No se agrega OCR en v1.

## 22. Seguridad

Requisitos productivos cerrados:

- Máximo recomendado v1: 10 MB, aunque PHP local permita 40 MB.
- Validar error de upload, extensión, `finfo`, firma `%PDF-` y número máximo razonable de páginas.
- Guardar temporalmente fuera de `public/`, por ejemplo `storage/plan_imports/`, con ID aleatorio de 32 bytes; nunca usar el nombre del usuario como ruta.
- Resolver con `realpath` y comprobar que el archivo permanezca dentro del directorio permitido.
- Ejecutar una ruta de Xpdf fija/configurada; el request no puede elegir binario ni opciones.
- `proc_open` con array de argumentos y `bypass_shell`; no concatenar shell.
- Añadir timeout (15 s), límite de stdout (5 MB), limpieza, usuario de proceso sin privilegios y logging sin texto académico completo.
- No abrir enlaces ni ejecutar JavaScript/adjuntos del PDF; Xpdf solo lee.
- CSRF, sesión, rol y ownership en upload y confirmación.
- Token de preview aleatorio, asociado a usuario/asignación/SHA-256, vencimiento corto y uso único.
- Borrar PDF/JSON temporal al confirmar, cancelar o vencer; tarea de limpieza idempotente.
- No aceptar rutas del cliente ni IDs de registros pedagógicos dentro del JSON.

La auditoría observó un secreto de Gateway definido en configuración versionada; es un riesgo existente ajeno a esta POC y no fue modificado.

## 23. Integración PHP ↔ extractor

No se adopta Python. El flujo definitivo será:

```text
endpoint PHP autenticado
  -> PlanImportacionService
  -> PdfPlanImporter (PHP)
  -> XpdfPdftotextExtractor
  -> proc_open([ruta_fija, -enc, UTF-8, -table, --, ruta_validada, -])
  -> detector/adaptador/validador
  -> JSON canónico temporal
```

La ruta de Xpdf será configuración local no enviada por formulario. El arranque debe comprobar versión y opción `-table`.

## 24. Preview obligatoria

Flujo UI:

```text
Profesor -> Planificación pedagógica -> Importar plan
-> seleccionar asignación/año -> subir PDF
-> validación + parser -> preview editable + warnings
-> confirmar importación -> transacción
```

La preview debe permitir editar/eliminar/agregar metadata visible, unidad, capacidad, tema, indicador, procedimiento e instrumento. También debe resolver:

- coincidencia entre materia/profesor/curso/año del PDF y asignación;
- mes textual a fechas exactas;
- HC faltante de tema `2.3` en Excel 3.º;
- fila introductoria no numerada de Excel;
- subdivisión opcional de temas compuestos de Competencia/Contenido;
- mapping explícito de procedimientos/instrumentos a catálogos.

Siempre se muestra `[CONFIRMAR IMPORTACIÓN]`, incluso con confianza 100.

## 25. Integración futura con BD

El backend debe validar nuevamente el JSON editado. Mapping:

- asignación seleccionada -> `planes_anuales.id_asignacion`; nombres del PDF no se duplican.
- año -> debe coincidir con `asignacion_docente.anio_lectivo`.
- competencias metadata -> `planes_anuales`.
- unidad -> `plan_unidades`.
- capacidad -> `plan_capacidades`.
- tema -> `plan_temas`.
- indicador -> `plan_indicadores`.
- evaluación -> catálogos y tablas puente existentes.
- fechas confirmadas -> `plan_tema_programacion` y/o proceso de unidad según la semántica elegida en preview.

V1 bloquea importación si ya existe plan para asignación/año; no mezcla ni reemplaza automáticamente un plan existente. El plan nuevo nace `BORRADOR`.

Se propone una migración aditiva de `codigo` a las tablas existentes, no tablas PDF paralelas. El editor manual debe poder leer/escribir esos códigos.

## 26. Transacción y rollback

Confirmación futura:

```text
BEGIN
  validar ownership/asignación/año y ausencia de plan
  crear planes_anuales en BORRADOR
  crear plan_unidades
  crear plan_capacidades
  crear plan_temas
  crear plan_indicadores
  resolver/crear catálogos explícitamente confirmados
  crear plan_tema_procedimientos / plan_tema_instrumentos
  crear plan_tema_programacion confirmada
COMMIT
```

Ante cualquier excepción: `ROLLBACK`. Solo después del commit se invalida el token y se eliminan temporales. Un fallo no debe dejar medio plan. No se publica automáticamente.

## 27. Tests y fixtures

Los cuatro PDFs reales están en `test/fixtures/planes/`. Cada uno tiene un `*.expected.json` revisado y un `*.extracted.json` generado. `test/plan_parser_poc/run.php` comprueba SHA-256 del PDF, formato, páginas, metadata, conteos, unidades, capacidades clave, códigos, evaluación, tiempos, warnings, confidence y muestras de texto. Además compara un SHA-256 de todo el árbol `unidades`, de modo que cualquier cambio en textos, padres, HC o asociaciones de la jerarquía completa rompe el contrato esperado sin duplicar toda la transcripción.

Comando:

```powershell
C:\xampp\php\php.exe test\plan_parser_poc\run.php
```

Resultado actual: `OK: 4/4 PDFs coinciden con sus expectativas revisadas.`

La prueba no incluye `db.php`, no abre PDO y no ejecuta INSERT/UPDATE/DELETE.

## 28. Resultados reales y decisión

Resumen:

- Administración 2.º: 6/6 unidades, 6/6 temas compuestos, 18/18 indicadores; metadata/evaluación/HC/proceso completos.
- Administración 3.º: 6/6 unidades, 18/18 temas, 18/18 indicadores; asociación uno a uno y evaluación/HC/proceso completos.
- Excel 3.º: 3/3 unidades, 18/18 temas, 53/53 indicadores con asociación exacta por código. 17 temas tienen HC; `2.3` no presenta una HC extraíble y genera warning. La actividad inicial no numerada queda como warning.
- Archivo BCB “segundo”: formato real Competencia/Contenido, 2/2 unidades, 14/14 capacidades/temas compuestos y 47/47 indicadores. Contiene 34 etiquetas de contenido conservadas. El nombre dice segundo, la metadata dice `1° BCB`.

Veredicto: **GO_WITH_REVIEW**. La extracción determinista es apta para una integración con preview obligatoria, pero no para persistencia silenciosa. No se declara GO por los warnings de Excel, la subdivisión conservadora del formato C, la resolución de fechas y el mapping de catálogos.

## 29. Riesgos y limitaciones

- Xpdf actualmente proviene de Git for Windows; producción debe formalizar una ruta/versionado y licencia, no asumir que Git está instalado.
- `-table` proyecta posiciones, pero no entrega líneas vectoriales/celdas reales.
- Cambios de plantilla, fuente, orientación o encabezados pueden requerir un nuevo adaptador o ajuste acotado.
- PDFs protegidos/corruptos pueden fallar; deben generar error, nunca fallback inventado.
- No OCR.
- Administración 2.º y formato C tienen temas compuestos por falta de numeración inequívoca.
- Excel 3.º tiene una HC ausente y una fila introductoria fuera de jerarquía.
- El nombre del cuarto PDF es incorrecto respecto de su contenido.
- La BD no tiene calendario académico; meses no pueden transformarse en programación lectiva exacta sin confirmación.
- Los catálogos vigentes usan grafías distintas a algunos PDFs y requieren mapping visible.
- La POC no implementa timeout del proceso; es requisito antes de exponer uploads no confiables.
- La UI actual no tiene CSRF general.

## 30. Estrategia XLSX futura

No se implementa XLSX ahora. Se agregará `XlsxPlanImporter` detrás de `PlanImporterInterface`, usando el mismo JSON canónico, validador, confidence, staging, preview y persistencia. Solo cambia la etapa de extracción: hoja/celda/fila/columna en lugar de Xpdf.

El detector XLSX se basará en nombres de hojas y conjuntos de encabezados, y cada familia mantendrá su adaptador. No se mezclará lógica XLSX dentro de los parsers PDF. Los mismos fixtures esperados podrán expresarse como contratos canónicos para comprobar paridad PDF/XLSX.

## 31. Mapping de persistencia revalidado el 07-09-2026

La inspección de la migración pedagógica, la migración T03, `PlanificacionPedagogica::configElemento` y `information_schema.COLUMNS` confirma que la ampliación aprobada sólo agrega tres códigos. No existe destino relacional para:

- `unidades[].tiempo_texto` y `unidades[].proceso_texto`;
- `temas[].tiempo_texto` y `temas[].fecha_texto`;
- `indicadores[].check`.

No es sólo una posibilidad de futuros PDFs: ambos fixtures de Administración tienen `proceso_texto` en sus seis unidades; Competencia/Contenido lo tiene en sus dos unidades. Los checks de Excel actualmente son null, pero T17 exige poder editarlos y conservar las correcciones. Las columnas `proceso_inicio`/`proceso_fin` son DATE y no admiten el texto original. `clase_diaria_indicadores.cumplido` describe una clase concreta y no puede usarse como check de un plan anual.

El plan exige conservar los campos y borrar el JSON temporal después de confirmar. Con sólo la migración T03, un INSERT directo omitiría estos valores o exigiría cambiar el significado de campos existentes. No se adopta ninguna de esas alternativas sin una decisión explícita, ni se inventa una tabla paralela.

La auditoría exhaustiva posterior resolvió este bloqueo sin crear un modelo paralelo. `Check` representa avance/cumplimiento y no se agregará a `plan_indicadores`; el cumplimiento ya se registra por clase en `clase_diaria_indicadores.cumplido`. Los cuatro fixtures tienen `check=null` y cualquier valor futuro no nulo se bloqueará para revisión. El resto del contrato tiene mapping aditivo y nullable en las tablas normales; los detalles y el resultado 4/4 del round-trip están en `PLAN_IMPORTACION_PDF_PERSISTENCE_AUDIT.md`.

Estado: **mapping PASS; migración real pendiente de respaldo y aprobación**. Se creó `20260907_plan_importacion_campos_sin_perdida.sql` y se probó únicamente en una BD temporal junto con T03. No se aplicó ninguna migración a `sisco_db` y T16–T26 continúan pendientes.

El backend ya disponible es: servicio PHP → extractor Xpdf → adaptador → JSON temporal → GET/PUT preview; DELETE y limpieza CLI eliminan temporales autorizados. Batería implementada: `php test/plan_import/run_current.php`. No equivale a T23–T26 ni a liberación de la función.
