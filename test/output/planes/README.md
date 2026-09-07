# Salidas generadas de la POC

`test/plan_parser_poc/run.php` regenera aquí un archivo `*.extracted.json` por cada PDF de referencia. Son artefactos de prueba PDF→JSON; no se insertan en la BD.

Cada expected fija además un SHA-256 de `unidades` (toda la jerarquía, textos, asociaciones, HC y evaluación). Así, el test detecta cambios en cualquier nodo aunque la transcripción completa no se duplique en el archivo expected.
