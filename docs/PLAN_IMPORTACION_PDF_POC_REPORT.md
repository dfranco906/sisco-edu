# Reporte de la POC de importación de planes PDF sin IA

Fecha de ejecución: 3 de septiembre de 2026.

La prueba de concepto está aislada de la aplicación: no carga la configuración de base de datos, no abre conexiones PDO y no ejecuta `INSERT`, `UPDATE` ni `DELETE`. Los resultados se generan exclusivamente con Xpdf `pdftotext -table`, PHP y reglas deterministas.

## Resumen ejecutivo

| PDF | Formato detectado | Unidades esperadas | Unidades detectadas | Temas esperados | Temas detectados | Indicadores esperados | Indicadores detectados | Warnings | Confianza | Resultado |
|---|---|---:|---:|---:|---:|---:|---:|---:|---:|---|
| Plan Anual - tercer curso - BTI - Administracion financiera.pdf | `ADMINISTRACION_FINANCIERA` | 6 | 6 | 18 | 18 | 18 | 18 | 0 | 100 | PASS |
| Plan Anual - segundo curso - BTI - Administracion financiera.pdf | `ADMINISTRACION_FINANCIERA` | 6 | 6 | 6 compuestos | 6 compuestos | 18 | 18 | 0 | 100 | PASS |
| Plan Anual - tercer curso - BCB - Excel Avanzado.pdf | `EXCEL_AVANZADO` | 3 | 3 | 18 | 18 | 53 | 53 | 3 | 96 | PASS_WITH_REVIEW |
| Plan Anual - segundo curso - BCB - Excel Avanzado.pdf | `COMPETENCIA_CONTENIDO` | 2 | 2 | 14 compuestos | 14 compuestos | 47 | 47 | 2 | 93 | PASS_WITH_REVIEW |

Los conteos esperados provienen de fixtures estructurales revisados, no de una inferencia del mismo parser. En los formatos sin código inequívoco, “tema compuesto” significa una agrupación conservadora por fila/capacidad; el texto de los contenidos internos se conserva completo.

## Comando y artefactos

```powershell
C:\xampp\php\php.exe test\plan_parser_poc\run.php
```

Resultado:

```text
PDF | FORMATO | U | C | T | I | WARNINGS | CONFIANZA
Plan Anual - segundo curso - BTI - Administracion financiera.pdf | ADMINISTRACION_FINANCIERA | 6 | 6 | 6 | 18 | 0 | 100
Plan Anual - tercer curso - BTI - Administracion financiera.pdf | ADMINISTRACION_FINANCIERA | 6 | 6 | 18 | 18 | 0 | 100
Plan Anual - segundo curso - BCB - Excel Avanzado.pdf | COMPETENCIA_CONTENIDO | 2 | 14 | 14 | 47 | 2 | 93
Plan Anual - tercer curso - BCB - Excel Avanzado.pdf | EXCEL_AVANZADO | 3 | 3 | 18 | 53 | 3 | 96
OK: 4/4 PDFs coinciden con sus expectativas revisadas.
```

Los JSON producidos están en `test/output/planes/`. Los contratos esperados están en `test/plan_parser_poc/expected/` y fijan hash del PDF, metadata, conteos, nombres/códigos clave, evaluación, tiempos, warnings y confianza. Un segundo SHA-256 cubre el árbol canónico `unidades` completo, por lo que también detecta cualquier cambio de texto, padre o asociación no transcrito por separado en el expected.

## Resultado por documento

### Administración Financiera, 3.º BTI

- Detector: 105 puntos para Administración Financiera, por encima del umbral y margen exigidos.
- Metadata principal: institución, materia, profesor, curso, turno y año.
- 6 unidades, con HC `16, 32, 32, 32, 32, 16`.
- 18 temas numerados desde `1.1` hasta `6.3`.
- 18 indicadores, asociados uno a uno en el mismo orden estructural.
- Procedimientos, instrumentos y proceso/meses conservados y heredados por tema.
- Sin warnings.

Muestra comprobada: Unidad I, “La Administración Financiera y la Información”, con temas `1.1`, `1.2` y `1.3`.

### Administración Financiera, 2.º BTI

- Detector: 105 puntos para Administración Financiera.
- 6/6 unidades y 6/6 capacidades.
- El documento no provee códigos de tema inequívocos. Por seguridad se crea un tema compuesto por unidad/capacidad, sin inventar divisiones.
- 18/18 indicadores conservados y asociados a su unidad mediante fronteras de filas.
- HC por unidad: `8, 8, 12, 20, 12, 20`.
- Procedimientos, instrumentos y proceso/meses conservados.
- Sin warnings.

### Excel Avanzado, 3.º BCB

- Detector: 121 puntos para Excel Avanzado.
- 3/3 unidades, 18/18 temas y 53/53 indicadores.
- La asociación se valida por prefijo exacto: por ejemplo, `1.1.2` sólo puede quedar bajo `1.1`.
- `check` se conserva como `null`: las casillas están vacías y no se inventa un estado.
- Proceso de desarrollo, procedimientos e instrumentos se heredan según las celdas combinadas visibles.
- 17 de 18 temas tienen HC detectada.

Errores o ambigüedades concretas:

1. El tema `2.3` no presenta una HC recuperable; se emite `MISSING_HOURS`.
2. Hay una actividad introductoria no numerada antes de la jerarquía de temas; se emite `UNRECOGNIZED_TEXT`.
3. La marca `1H` asociada a esa actividad introductoria tampoco se fuerza sobre un tema; se emite un segundo `UNRECOGNIZED_TEXT`.

La decisión deliberada es no atribuir esa hora a un tema arbitrario.

### Archivo llamado “Excel Avanzado, 2.º BCB”

El contenido real no es Excel Avanzado. El detector obtiene 100 puntos para `COMPETENCIA_CONTENIDO` por sus encabezados `COMPETENCIA`, `CAPACIDAD`, `ÁREA TRANSVERSAL`, `METODOLOGÍA DE ENSEÑANZA` y `MEDIOS DE VERIFICACIÓN`.

- La metadata interna dice `1° BCB`; el nombre del archivo dice segundo curso.
- 2/2 competencias/unidades.
- 14/14 capacidades y 14 temas compuestos, uno por fila/capacidad.
- 47/47 indicadores.
- 34 etiquetas de contenido conservadas dentro de los temas compuestos.
- Área transversal, metodología, medios de verificación y fecha se conservan en los campos canónicos.

Warnings concretos:

1. `SOURCE_METADATA_MISMATCH`: contradicción entre nombre del archivo y curso interno.
2. `TABLE_STRUCTURE_WARNING`: los contenidos no tienen numeración suficiente para dividirlos en temas atómicos sin riesgo; se conserva una agrupación por capacidad.

## Métricas de aceptación

| Criterio | Evidencia | Estado |
|---|---|---|
| 100% de unidades | 17/17 filas estructurales esperadas entre los cuatro archivos | Cumple |
| 100% de temas | 56/56 temas o grupos conservadores esperados | Cumple |
| 100% de indicadores | 136/136 indicadores | Cumple |
| Ningún indicador asignado deliberadamente al tema incorrecto | códigos jerárquicos estrictos en Excel; fronteras de tabla en los demás; lo dudoso queda en warning | Cumple en fixtures |
| Ningún tema asignado deliberadamente a unidad incorrecta | código `N.x` versus Unidad N o frontera de fila/competencia | Cumple en fixtures |
| Metadata principal | completa en A/B; mismatch explícito en el cuarto archivo | Cumple con warning |
| Procedimientos e instrumentos | reconstruidos en Administración y Excel; el formato C usa metodología/medios, no esos catálogos | Cumple en fixtures |

Los porcentajes describen únicamente estos cuatro PDFs y los contratos revisados. No constituyen una garantía sobre plantillas futuras.

## Comparación local de extracción

Se midieron cinco ejecuciones por modo sobre cada PDF. Las medianas observadas fueron:

| PDF abreviado | `-layout` | `-table` | Líneas layout/table |
|---|---:|---:|---:|
| Administración 2.º | 17 ms | 18 ms | 75 / 94 |
| Administración 3.º | 20 ms | 19 ms | 73 / 100 |
| Excel 3.º | 21 ms | 20 ms | 108 / 147 |
| Competencia/Contenido | 22 ms | 21 ms | 132 / 181 |

`-table` fue elegido porque separa mejor columnas y texto envuelto con costo temporal equivalente. PyMuPDF, pdfplumber y bibliotecas PHP no estaban instalados; el Python disponible pertenece a GIMP/Inkscape y no es una base operativa mantenible para XAMPP. No se incorporó ninguna dependencia durante la POC.

## Decisión

**GO_WITH_REVIEW**.

La POC demuestra que los cuatro archivos actuales se pueden reconstruir determinísticamente con precisión estructural suficiente. No corresponde `GO` porque:

- Excel contiene una hora y una actividad sin destino jerárquico seguro;
- el formato Competencia/Contenido exige agrupaciones conservadoras;
- el nombre de un fixture contradice su metadata;
- las fechas/meses no pueden convertirse de forma segura en calendario académico, porque esa entidad no existe en la BD;
- procedimientos e instrumentos deben mapearse contra catálogos reales, con confirmación humana;
- cualquier cambio de plantilla necesita regresión o un nuevo adaptador.

La recomendación es integrar sólo con preview editable obligatoria y transacción confirmada por el profesor. No se autoriza importación silenciosa, publicación automática ni escritura desde esta POC.
