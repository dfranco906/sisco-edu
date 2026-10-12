# Registro de commits de SISCO-EDU desde julio de 2026

Repositorio: [dfranco906/sisco-edu](https://github.com/dfranco906/sisco-edu).
Fecha de corte y actualización remota: **5 de octubre de 2026**.
Rama de trabajo: `frontend-recovery-audit`. HEAD: `ed5d84a2439f2d7b6fca844d2df9d04f92087a22`.

## Alcance y criterio

Se interpretó «desde julio» como desde **2026-07-01 00:00:00 -03:00** hasta el cierre del **2026-10-05**, dado el año del proyecto y la fecha de esta solicitud. Se actualizaron todas las referencias remotas mediante `git fetch --all --tags` y se contrastaron las seis ramas publicadas con `git ls-remote --heads origin`. No se cambió de rama.

Este registro reúne **72 commits únicos**, incluyendo **10 fusiones**, alcanzables desde todas las ramas locales y remotas disponibles. Cada hash aparece una sola vez aunque pertenezca al historial de varias ramas. El filtro y el orden usan la **fecha de commit**; el CSV conserva además la fecha de autor. Ambas fechas mantienen el desplazamiento horario original de Git.

La columna «Ramas que lo contienen» indica pertenencia **actual por ascendencia**, agrupando la rama local y `origin/` del mismo nombre. Git no registra de forma fiable la rama donde se creó cada commit; esta columna no atribuye una rama de origen. Se excluye `origin/HEAD` por ser un alias de `main`. No se puede recuperar con este procedimiento un commit que solo haya existido en una rama eliminada y que ya no sea alcanzable desde las referencias disponibles.

Los resúmenes describen mensajes y cambios de archivos del repositorio. La presencia de código o tests no demuestra por sí sola que el sistema haya funcionado en equipos físicos o en Apache. Las fusiones registran integración de historiales; no duplican el desarrollo ya descrito en otros commits. Se señalaron mensajes que no reflejan exactamente su diff.

El archivo complementario [CSV completo](registro-commits-desde-julio-2026.csv) conserva hash completo, fechas, autor sin correo, mensaje original, resumen, padres, referencias y todos los archivos cambiados frente al primer padre. Ese listado de archivos de una fusión describe el resultado integrado frente a su primer padre, no necesariamente cambios escritos durante la fusión.

## Ramas consultadas

| Referencia | HEAD al corte | Commits del período en su historial |
| --- | --- | ---: |
| backend | `e789d78` | 56 |
| frontend-recovery-audit | `ed5d84a` | 72 |
| hardware | `2c8a9b7` | 54 |
| main | `906d8c1` | 0 |
| origin/backend | `e789d78` | 56 |
| origin/develop | `8c61d47` | 0 |
| origin/frontend | `f633007` | 0 |
| origin/frontend-recovery-audit | `ed5d84a` | 72 |
| origin/hardware | `2c8a9b7` | 54 |
| origin/main | `906d8c1` | 0 |

Los conteos por rama se solapan y no deben sumarse. Las ramas con cero commits en el período también fueron consultadas.

## Conteos por mes

| Mes | Cambios | Fusiones | Total único |
| --- | ---: | ---: | ---: |
| 2026-07 | 30 | 9 | 39 |
| 2026-08 | 23 | 1 | 24 |
| 2026-09 | 4 | 0 | 4 |
| 2026-10 | 5 | 0 | 5 |

## 2026-07

| Fecha de commit | Commit | Autor | Ramas que lo contienen | Qué pasó |
| --- | --- | --- | --- | --- |
| 2026-07-01 12:30:44-03:00 | [35bd01e](https://github.com/dfranco906/sisco-edu/commit/35bd01e30a0096e464bc0d9ecdd3df9074fba75c) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Agregó las hojas técnicas y el manual de comandos AT de los módulos LoRa Reyax RYLR998 como documentación de referencia. |
| 2026-07-01 12:45:01-03:00 | [d27c9bc](https://github.com/dfranco906/sisco-edu/commit/d27c9bc365e16a7f8db78c0fdbfbdd1d8c2aac2e) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Incorporó la especificación general y la ruta técnica de implementación de SISCO-EDU en documentos PDF. |
| 2026-07-02 09:19:23-03:00 | [4b1324a](https://github.com/dfranco906/sisco-edu/commit/4b1324a7e9078a5b248a1666fd58941902d44674) | dfran | backend, frontend-recovery-audit, hardware | **Fusión:** Integró la rama backend en hardware mediante un commit de fusión; registra la unión de historiales. |
| 2026-07-02 09:55:57-03:00 | [ff75c35](https://github.com/dfranco906/sisco-edu/commit/ff75c353924ef2fb0d3ec28790e30733a0325e9f) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Renombró la variable HEX del transporte DY50 para evitar un conflicto con macros y ajustó el formato de las pruebas de exportación e importación. |
| 2026-07-02 10:08:42-03:00 | [cbb59f8](https://github.com/dfranco906/sisco-edu/commit/cbb59f86a6f404f6be3279c43bf82a2dba86da2f) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Ajustó el empaquetado de estructuras ESP-NOW y agregó el guardado de plantillas DY50 en el nodo de aula; también modificó el gateway. |
| 2026-07-02 10:32:47-03:00 | [84faea2](https://github.com/dfranco906/sisco-edu/commit/84faea239b8c09a8eecfea07b3a701fc504dc164) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Separó operaciones I2C/UART del callback de recepción ESP-NOW y agregó tiempos límite para reducir bloqueos del nodo de aula. |
| 2026-07-02 11:20:41-03:00 | [5a22b5f](https://github.com/dfranco906/sisco-edu/commit/5a22b5fb8f6380014dd9348a5f847a1d50e5125d) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Corrigió una comilla del gateway, añadió el endpoint de registro de asistencia y ajustó la configuración de comunicación del entorno AULA_A. |
| 2026-07-02 12:14:24-03:00 | [b09b071](https://github.com/dfranco906/sisco-edu/commit/b09b0719e83b8e5fd6be1e860666243d105d08c2) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Añadió persistencia de la base local del aula en NVS del ESP32 para conservarla tras reinicios. |
| 2026-07-02 13:34:48-03:00 | [b26419a](https://github.com/dfranco906/sisco-edu/commit/b26419a6e664f3785883f1b0666393ac5b9b5f6d) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Cambió la lectura UART del registrador AS608 para extraer la plantilla biométrica separada de las tramas del protocolo. |
| 2026-07-02 14:36:10-03:00 | [e08f564](https://github.com/dfranco906/sisco-edu/commit/e08f564a8f629b0052a1b6169747cb9617d7736f) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Usó Dy50TemplateTransport::readTemplate en el registrador para extraer plantillas de 1536 bytes sin el framing UART. |
| 2026-07-02 15:14:55-03:00 | [97d2b0a](https://github.com/dfranco906/sisco-edu/commit/97d2b0a772c3b4f440bf377089a6dffe96a8824b) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Ajustó TEMPLATE_BYTES de 1536 a 1408 según la medición de ese momento y sincronizó el transporte y sus pruebas. |
| 2026-07-02 15:23:36-03:00 | [ea82968](https://github.com/dfranco906/sisco-edu/commit/ea82968ec3a2f2a8d708b27d0fe24dcaa29f135e) | caballero16 | backend, frontend-recovery-audit, hardware | **Fusión:** Sincronizó el historial local y remoto de hardware mediante una fusión. No se cuenta como una funcionalidad adicional a los cambios de sus ramas padre. |
| 2026-07-03 08:45:31-03:00 | [cad755d](https://github.com/dfranco906/sisco-edu/commit/cad755d2374a1c3d8bd1c2632878a82e99d90342) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Sustituyó el ensamblado con String dinámico por un buffer estático en la recepción de plantillas ESP-NOW del aula. |
| 2026-07-03 09:29:01-03:00 | [1605165](https://github.com/dfranco906/sisco-edu/commit/16051651080308f387c82771f4060d4ab4fea70d) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Redujo el documento JSON dinámico del gateway a 16384 bytes y limpió saltos de línea en la sincronización; también ajustó el nodo de aula. |
| 2026-07-06 10:31:26-03:00 | [dc2ee2a](https://github.com/dfranco906/sisco-edu/commit/dc2ee2a1a92b1f9613b672562eff45eab6cfd00d) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Añadió documentación técnica LoRa en Markdown y modificó el código de referencia del gateway. El alcance excede la documentación anunciada en el mensaje. |
| 2026-07-06 10:34:35-03:00 | [2e66b87](https://github.com/dfranco906/sisco-edu/commit/2e66b879adff7a6a75ef924dcc909f14132c76af) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Añadió un puente serie para ensayar comandos AT entre módulos LoRa y reorganizó ejemplos anteriores en carpetas ESP-NOW. |
| 2026-07-06 11:23:52-03:00 | [2ab2c92](https://github.com/dfranco906/sisco-edu/commit/2ab2c924c7a0107072ff0b5122f461b3096e6b4d) | caballero16 | backend, frontend-recovery-audit, hardware | **Fusión:** Sincronizó el historial local y remoto de hardware mediante una fusión. No se cuenta como una funcionalidad adicional a los cambios de sus ramas padre. |
| 2026-07-06 11:24:41-03:00 | [52f2fb9](https://github.com/dfranco906/sisco-edu/commit/52f2fb904d7c325d0290217fa862a29c3c58b4fd) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Incorporó los programas emisor y receptor para una prueba piloto de envío de huellas por LoRa punto a punto. |
| 2026-07-06 11:25:07-03:00 | [8662295](https://github.com/dfranco906/sisco-edu/commit/866229587f379757edc013ba9b576d3b565d9b30) | caballero16 | backend, frontend-recovery-audit, hardware | **Fusión:** Sincronizó el historial local y remoto de hardware mediante una fusión. No se cuenta como una funcionalidad adicional a los cambios de sus ramas padre. |
| 2026-07-06 11:37:24-03:00 | [f3b9791](https://github.com/dfranco906/sisco-edu/commit/f3b9791f94bb11b8139546941e5a102cebae943a) | caballero16 | backend, frontend-recovery-audit, hardware | **Cambio:** Guardó el registro de recepción de la primera prueba piloto de transferencia por LoRa. |
| 2026-07-06 13:32:36-03:00 | [b7193ff](https://github.com/dfranco906/sisco-edu/commit/b7193ffa7f5a71be6b449f55b5b46006a133beea) | caballero16 | backend, frontend-recovery-audit, hardware | **Cambio:** Actualizó el registro de paquetes recibidos durante la segunda prueba LoRa. |
| 2026-07-06 13:32:40-03:00 | [b55292f](https://github.com/dfranco906/sisco-edu/commit/b55292f8441dc5bc6c37cad253fbcc0eb0859255) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Añadió el registro de paquetes enviados durante la segunda prueba LoRa. |
| 2026-07-06 13:32:57-03:00 | [77ad6e3](https://github.com/dfranco906/sisco-edu/commit/77ad6e38f8d990129255ef4dec227e7f235c1665) | dfran | backend, frontend-recovery-audit, hardware | **Fusión:** Sincronizó el historial local y remoto de hardware mediante una fusión. No se cuenta como una funcionalidad adicional a los cambios de sus ramas padre. |
| 2026-07-06 13:49:50-03:00 | [02b0676](https://github.com/dfranco906/sisco-edu/commit/02b0676f7635d7dd59f0a69cc25f8531c01ee950) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Modificó emisor y receptor de la prueba piloto para controlar los paquetes transmitidos. |
| 2026-07-06 14:02:24-03:00 | [1ad757d](https://github.com/dfranco906/sisco-edu/commit/1ad757d52946651a9adf1a35f569c58ffe96224b) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Actualizó el registro de transmisión correspondiente a la tercera prueba LoRa. |
| 2026-07-06 14:03:02-03:00 | [7a15ae5](https://github.com/dfranco906/sisco-edu/commit/7a15ae5e517efbe1d66c0f6cec12866c72e7391a) | caballero16 | backend, frontend-recovery-audit, hardware | **Cambio:** Actualizó el registro de recepción correspondiente a la tercera prueba LoRa. |
| 2026-07-06 14:03:11-03:00 | [648562e](https://github.com/dfranco906/sisco-edu/commit/648562ec90827dd554981a2594964bab8ea2f843) | caballero16 | backend, frontend-recovery-audit, hardware | **Fusión:** Sincronizó el historial local y remoto de hardware mediante una fusión. No se cuenta como una funcionalidad adicional a los cambios de sus ramas padre. |
| 2026-07-06 14:35:47-03:00 | [4304695](https://github.com/dfranco906/sisco-edu/commit/430469599d63a3f91889333864c18283270eb776) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Optimizó el código emisor de la prueba piloto y ajustó parámetros de transmisión. El mensaje repite 250 kHz; no demuestra por sí solo la secuencia de frecuencias. |
| 2026-07-06 14:39:46-03:00 | [76bb693](https://github.com/dfranco906/sisco-edu/commit/76bb69300975c3e2cbb79ee94cc24cbf86dc450d) | caballero16 | backend, frontend-recovery-audit, hardware | **Cambio:** Actualizó enviado.txt de la prueba piloto. Aunque el mensaje menciona datos recibidos, el diff de este commit afecta el archivo de envíos. |
| 2026-07-06 14:41:15-03:00 | [9d0dcd3](https://github.com/dfranco906/sisco-edu/commit/9d0dcd37add69ee1bd60f7d84c52e47fc36e8328) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Actualizó el registro de envíos de la cuarta prueba piloto LoRa. |
| 2026-07-06 14:43:39-03:00 | [5ddc598](https://github.com/dfranco906/sisco-edu/commit/5ddc59800f366022d7796394578899cc3be8aafd) | dfran | backend, frontend-recovery-audit, hardware | **Fusión:** Sincronizó el historial local y remoto de hardware mediante una fusión. No se cuenta como una funcionalidad adicional a los cambios de sus ramas padre. |
| 2026-07-06 14:44:39-03:00 | [db790aa](https://github.com/dfranco906/sisco-edu/commit/db790aaa7d14187d87e1eddf2b2bd4d98a2d3893) | caballero16 | backend, frontend-recovery-audit, hardware | **Cambio:** Actualizó recibido.txt con los paquetes de la cuarta prueba piloto LoRa. |
| 2026-07-06 14:44:51-03:00 | [651a838](https://github.com/dfranco906/sisco-edu/commit/651a8381198253a23d9db0b52ef3bfad406d9f80) | caballero16 | backend, frontend-recovery-audit, hardware | **Fusión:** Sincronizó el historial local y remoto de hardware mediante una fusión. No se cuenta como una funcionalidad adicional a los cambios de sus ramas padre. |
| 2026-07-06 15:16:29-03:00 | [0a340cc](https://github.com/dfranco906/sisco-edu/commit/0a340cc4afbf1dc9f93ef6375cbe7d8023125267) | caballero16 | backend, frontend-recovery-audit, hardware | **Cambio:** Registró paquetes recibidos en la quinta prueba LoRa, orientada a evaluar distancia. |
| 2026-07-06 15:22:37-03:00 | [17f803c](https://github.com/dfranco906/sisco-edu/commit/17f803c26104ef8b8649088c2d2d694ebb517fe1) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Registró paquetes enviados en la quinta prueba LoRa de distancia. |
| 2026-07-06 16:26:00-03:00 | [eb8c675](https://github.com/dfranco906/sisco-edu/commit/eb8c6751c2995cb36fad370f9a3840684868ea22) | dfran | backend, frontend-recovery-audit, hardware | **Fusión:** Integró cambios de backend en hardware mediante un segundo commit de fusión. |
| 2026-07-27 11:38:47-03:00 | [19412e4](https://github.com/dfranco906/sisco-edu/commit/19412e4ba0a731c50a92b359dfb95f06e676a30e) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Migró el transporte ESP-NOW a LoRa mediante comandos AT, fragmentación y ARQ Stop-and-Wait; añadió firmware de aula y gateway. |
| 2026-07-27 15:26:51-03:00 | [0f5c443](https://github.com/dfranco906/sisco-edu/commit/0f5c4438888b0ba65089be2daf0652f6a86603f5) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Normalizó identificadores room_id/huella_id hacia id_aula/user_id_global en clases, APIs, vistas y JavaScript. |
| 2026-07-27 15:30:28-03:00 | [3835cee](https://github.com/dfranco906/sisco-edu/commit/3835cee353cd1755af14c97e384deeb5b51613cc) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Agregó la generación y actualización de user_id_global al crear o editar estudiantes y profesores. |

## 2026-08

| Fecha de commit | Commit | Autor | Ramas que lo contienen | Qué pasó |
| --- | --- | --- | --- | --- |
| 2026-08-03 09:06:34-03:00 | [044d382](https://github.com/dfranco906/sisco-edu/commit/044d382a503df799d10d7d2c0b54ef36473f8e78) | cesarperalta | backend, frontend-recovery-audit | **Cambio:** Actualizó el documento de estructura de base de datos docs/sisco_db.txt. |
| 2026-08-06 13:36:24-03:00 | [aaf7748](https://github.com/dfranco906/sisco-edu/commit/aaf7748630741a1da1d6d58a5afd1ccf843266dd) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Ajustó firmware de aula y gateway, actualizó la documentación de base de datos y eliminó hola.html. |
| 2026-08-06 14:05:32-03:00 | [a15bf6a](https://github.com/dfranco906/sisco-edu/commit/a15bf6a954ae8da2001be4efbd747a3c05769987) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Alineó la comunicación LoRa con los identificadores biométricos normalizados; añadió una migración SQL y el transporte DY50 compartido. |
| 2026-08-06 14:12:05-03:00 | [63f535e](https://github.com/dfranco906/sisco-edu/commit/63f535e75600d4ee2ac0682376602a11b2d28262) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Añadió el firmware del nodo registrador biométrico DY50. |
| 2026-08-10 11:33:23-03:00 | [ca00ea5](https://github.com/dfranco906/sisco-edu/commit/ca00ea5658a86679a61396f7ed04baa8b698ea9f) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Amplió la salida del monitor serie del gateway para facilitar el diagnóstico de su funcionamiento. |
| 2026-08-10 11:34:30-03:00 | [865d8ba](https://github.com/dfranco906/sisco-edu/commit/865d8ba211ea34e1ce36dd5d4e78a2801e5e6bc0) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Cambió la dirección IP de comunicación utilizada por el JavaScript de captura de plantillas. |
| 2026-08-10 11:52:38-03:00 | [9772f84](https://github.com/dfranco906/sisco-edu/commit/9772f8400807387aefa36c472f5cfc8a7e3ed9f1) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Cambió el tamaño de las plantillas a 1536 bytes en firmware, transporte, ejemplos y endpoints relacionados. |
| 2026-08-10 13:58:57-03:00 | [656d84e](https://github.com/dfranco906/sisco-edu/commit/656d84e92a52de5db8b34a2a56b739f394e54e1a) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Alineó APIs, validaciones, configuración biométrica y migración de base de datos con plantillas de 1536 bytes; ajustó pruebas y código de comunicación. |
| 2026-08-10 14:13:09-03:00 | [2c5e1c7](https://github.com/dfranco906/sisco-edu/commit/2c5e1c7b04238aae2e5714fd7eb21e2f02849b41) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Corrigió la URL de acceso a la API central en el gateway y ejemplos relacionados. |
| 2026-08-13 13:15:25-03:00 | [cbcde49](https://github.com/dfranco906/sisco-edu/commit/cbcde4918ced427270d39cd323e1c5c082912fe1) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Reforzó sincronización y confirmación de entrega de huellas por LoRa en firmware y APIs del gateway. |
| 2026-08-13 13:32:04-03:00 | [f559693](https://github.com/dfranco906/sisco-edu/commit/f559693c82c8aa753f83a966f95c5151ecec059e) | dfran | backend, frontend-recovery-audit, hardware | **Cambio:** Añadió programas emisor/receptor para comprobar el encendido y la comunicación básica de módulos LoRa. |
| 2026-08-13 14:14:58-03:00 | [d9ffdff](https://github.com/dfranco906/sisco-edu/commit/d9ffdffcddb1364546c794ff0291c1b67daee2ff) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Ajustó la captura y las respuestas CORS del registrador; actualizó el JavaScript de obtención de plantillas. |
| 2026-08-13 14:16:11-03:00 | [1113d37](https://github.com/dfranco906/sisco-edu/commit/1113d37db66071d073b5a876cd6da9e7a6068458) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Actualizó la dirección IP en la configuración general de la aplicación. |
| 2026-08-13 14:59:38-03:00 | [6598163](https://github.com/dfranco906/sisco-edu/commit/6598163a04a6dc0ccec8ae05b97525140c716ba7) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Cambió la exportación de plantillas para evitar el uso de la flash del sensor y adaptó el manejo de errores del navegador. |
| 2026-08-17 11:02:43-03:00 | [49a0ef2](https://github.com/dfranco906/sisco-edu/commit/49a0ef236d8c097925a69c1c62080cd29e56ddae) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Añadió controles de integridad y verificación de plantillas durante captura, transporte y confirmación de entrega. El código no prueba por sí solo una ejecución física exitosa. |
| 2026-08-17 11:21:49-03:00 | [2c8a9b7](https://github.com/dfranco906/sisco-edu/commit/2c8a9b70ecd8f16a62c783dd5451e6a8c62f0bf1) | cesarperalta | backend, frontend-recovery-audit, hardware | **Cambio:** Añadió validación CRC de la plantilla en navegador y servidor antes de guardarla. |
| 2026-08-17 11:54:53-03:00 | [e789d78](https://github.com/dfranco906/sisco-edu/commit/e789d78220b97f88accdfb7c23cdbb2158e4988b) | cesarperalta | backend, frontend-recovery-audit | **Fusión:** Integró el historial de hardware en backend mediante un commit de fusión. |
| 2026-08-17 15:16:32-03:00 | [686c3ac](https://github.com/dfranco906/sisco-edu/commit/686c3ac334267fd3b18e3ba43bb6b16dee77bec2) | cesarperalta | frontend-recovery-audit | **Cambio:** Recuperó vistas y contratos CRUD del frontend administrativo, incorporó auditoría y pruebas de humo, y añadió una migración para el autoincremento de usuarios. |
| 2026-08-20 13:51:29-03:00 | [02f5231](https://github.com/dfranco906/sisco-edu/commit/02f52312698de5d242ae9e91f29b03bce3790e6a) | cesarperalta | frontend-recovery-audit | **Cambio:** Completó asignaciones por grado y carga horaria, con validaciones y migración SQL; ajustó creación, actualización, restauración y horarios. |
| 2026-08-20 14:01:49-03:00 | [f294297](https://github.com/dfranco906/sisco-edu/commit/f2942972b27797cb3a5441793fa2ebabe52b70f3) | cesarperalta | frontend-recovery-audit | **Cambio:** Simplificó el catálogo de materias en formulario, API y clase; añadió la migración correspondiente. |
| 2026-08-20 14:27:58-03:00 | [e946aaf](https://github.com/dfranco906/sisco-edu/commit/e946aaf6b9caa694122246913b90fbda6a5a8b3e) | cesarperalta | frontend-recovery-audit | **Cambio:** Organizó la administración de asignaciones por grado y ajustó filtros, selección, tablas y estilos. |
| 2026-08-20 15:24:44-03:00 | [fb0daf9](https://github.com/dfranco906/sisco-edu/commit/fb0daf943b9053141e469eb59761a06dbda171f4) | cesarperalta | frontend-recovery-audit | **Cambio:** Mejoró selectores de horarios y añadió el soporte y la validación de excepciones para clases conjuntas, con migración SQL. |
| 2026-08-24 15:33:36-03:00 | [53fa8f2](https://github.com/dfranco906/sisco-edu/commit/53fa8f2a6e92137886b17b8c196b485595f15a95) | cesarperalta | frontend-recovery-audit | **Cambio:** Amplió la gestión de horarios y carga administrativa; añadió agrupación y vinculación de clases conjuntas y soporte de una misma materia en varios grados. |
| 2026-08-31 11:52:37-03:00 | [4b7f794](https://github.com/dfranco906/sisco-edu/commit/4b7f79435644ca92e8e34d51df012b1f27ab9082) | cesarperalta | frontend-recovery-audit | **Cambio:** Introdujo planificación pedagógica, edición de planes, programación, informes diarios y configuración de membretes; incorporó clases, APIs, vistas y migración SQL. |

## 2026-09

| Fecha de commit | Commit | Autor | Ramas que lo contienen | Qué pasó |
| --- | --- | --- | --- | --- |
| 2026-09-07 11:22:58-03:00 | [d7dd01a](https://github.com/dfranco906/sisco-edu/commit/d7dd01aae7cbb260afa7e53acc0bdae58f212988) | cesarperalta | frontend-recovery-audit | **Cambio:** Introdujo extracción y análisis de planes PDF, staging y API de preview; también agregó configuración de nodos de aula y distribución de huellas de profesores. |
| 2026-09-07 11:26:04-03:00 | [33e9382](https://github.com/dfranco906/sisco-edu/commit/33e9382c639815931a9c25df36e98717ba8fd502) | cesarperalta | frontend-recovery-audit | **Cambio:** Cambió ID_AULA de 18 a 30 y el código mostrado de AULA_A a 3_RO_BTI en el firmware del nodo de aula. |
| 2026-09-07 11:33:30-03:00 | [e990681](https://github.com/dfranco906/sisco-edu/commit/e9906816b1b6d7287d71ce5d44ab7a1aaaf1b9d8) | cesarperalta | frontend-recovery-audit | **Cambio:** Cambió de 18 a 30 el ID del aula asociada a la dirección LoRa 101 en la tabla de rutas del firmware gateway. No modifica la tabla de la base de datos. |
| 2026-09-07 15:23:02-03:00 | [73fccaa](https://github.com/dfranco906/sisco-edu/commit/73fccaa3946cf1ab427e23a07d9fa839ccae6aff) | cesarperalta | frontend-recovery-audit | **Cambio:** Añadió GatewayAttendanceService y completó el registro biométrico de asistencia de estudiantes/profesores y su vínculo con clases diarias; incorporó pruebas y ajustes de configuración. |

## 2026-10

| Fecha de commit | Commit | Autor | Ramas que lo contienen | Qué pasó |
| --- | --- | --- | --- | --- |
| 2026-10-01 13:04:14-03:00 | [d11e6b8](https://github.com/dfranco906/sisco-edu/commit/d11e6b86b334e6efaac045b4b91a73e4c73bf1de) | cesarperalta | frontend-recovery-audit | **Cambio:** Completó formulario y preview de importación, confirmación, persistencia y validación; también modificó clases conjuntas y agregó pruebas del flujo de planes. |
| 2026-10-01 13:09:49-03:00 | [eba8e24](https://github.com/dfranco906/sisco-edu/commit/eba8e241c9fb15aa0ae4953c17144fcfca1a0d64) | cesarperalta | frontend-recovery-audit | **Cambio:** Actualizó únicamente elapsed_ms en cuatro JSON generados de extracción. A pesar del título, el diff no incorpora nuevos casos de prueba ni cambia el contenido pedagógico. |
| 2026-10-01 14:18:18-03:00 | [c298751](https://github.com/dfranco906/sisco-edu/commit/c29875138f55c5cc1dafbeaba98e4ec3b3dbdce6) | cesarperalta | frontend-recovery-audit | **Cambio:** Endureció la validación de sesiones y login: exige usuario con ID positivo, activo y rol coherente con la BD; añadió regresiones de sesión y autenticación. |
| 2026-10-01 14:28:32-03:00 | [3a7a72d](https://github.com/dfranco906/sisco-edu/commit/3a7a72de5be3ab893c19949fcb7b303b50547205) | cesarperalta | frontend-recovery-audit | **Cambio:** Añadió preflight del entorno Apache, comprobación de staging y configuración de pdftotext; integró el preflight en el formulario y guardó evidencia real de upload/preview. |
| 2026-10-01 15:23:13-03:00 | [ed5d84a](https://github.com/dfranco906/sisco-edu/commit/ed5d84a2439f2d7b6fca844d2df9d04f92087a22) | cesarperalta | frontend-recovery-audit | **Cambio:** Añadió una herramienta CLI para reparar la identidad SuperAdmin con ID cero y sus referencias bajo autorización; incorporó regresiones y evidencia del login real en Apache. |

## Uso para la tesis

Esta cronología permite organizar el desarrollo por etapas: pruebas de transporte y documentación LoRa en julio; normalización biométrica, recuperación administrativa y planificación en agosto; importación PDF, nodos y asistencia por gateway en septiembre; cierre de importación, validación de identidad y verificación de Apache en octubre. Estos nombres agrupan cambios históricos y no certifican por sí solos su resultado experimental.

La evidencia específica del importador real está en [auditoría de Apache](../auditoria-importador-apache.md), con una nueva verificación el 5 de octubre. La presente tarea no incorporó commits de código nuevos; los archivos del cierre y de este registro quedan en el directorio de trabajo para revisión.
