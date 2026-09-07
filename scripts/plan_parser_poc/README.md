# POC aislada: importación de planes PDF sin IA

Esta carpeta no está conectada a rutas, controladores, vistas ni base de datos de SISCO-EDU. La POC ejecuta únicamente:

`PDF -> pdftotext -table -> detector determinista -> adaptador -> validador -> JSON`

Requisitos locales verificados:

- `C:\xampp\php\php.exe` 8.2 o superior.
- Xpdf `pdftotext` con soporte para `-table` (la POC se verificó con Xpdf 4.00). Puede indicarse con `SISCO_PDFTOTEXT_PATH` o `--pdftotext=...`.
- PDF digital con capa de texto. No hay OCR.

Uso:

```powershell
C:\xampp\php\php.exe scripts\plan_parser_poc\parse.php --debug --pretty archivo.pdf
```

El JSON se escribe en stdout. `--debug` conserva stdout como JSON, agrega el nodo `debug` y muestra el resumen humano por stderr. Para escribir un archivo:

```powershell
C:\xampp\php\php.exe scripts\plan_parser_poc\parse.php --debug --pretty --output=salida.json archivo.pdf
```

Los cuatro PDFs de referencia están en `test/fixtures/planes/`. Las expectativas revisadas están en `test/plan_parser_poc/expected/` y las salidas generadas se guardan en `test/output/planes/`.

Ejecutar la prueba completa:

```powershell
C:\xampp\php\php.exe test\plan_parser_poc\run.php
```

La POC valida extensión, tamaño máximo (20 MB), firma `%PDF-` y MIME real antes de invocar el ejecutable mediante `proc_open` con un array de argumentos y `bypass_shell`. No concatena comandos y no lee ni escribe la BD.
