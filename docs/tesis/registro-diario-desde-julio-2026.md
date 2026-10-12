# SISCO-EDU: actividades y commits por día

Período: **1 de julio al 5 de octubre de 2026**. Incluye todas las ramas locales y remotas disponibles, con cada commit contado una sola vez.

**73 commits en 16 días con actividad.** Se incluye el commit de cierre de la auditoría publicado el 5 de octubre. Las fechas corresponden a la fecha de commit registrada por Git.

Cada fecha contiene un resumen general y, debajo, los nombres originales de sus commits. Los commits de fusión (`Merge`) registran la integración de ramas.

## 01 de julio de 2026

Se incorporó la documentación técnica de los módulos LoRa y los documentos que definen la especificación y la ruta de implementación de SISCO-EDU.

**Commits del día (2):**

- `35bd01e` — docs: se añadió la hojas de datos técnicos junto al manual de comandos AT para los módulos LoRa RYLR998 de la empresa Reyax
- `d27c9bc` — docs: se añade el resumen técnico junto a la ruta técnica a seguir de SISCO-EDU

## 02 de julio de 2026

Se corrigió la comunicación ESP-NOW y el manejo de huellas en los nodos de aula, gateway y registrador. Se agregaron controles para evitar bloqueos, almacenamiento local que conserva datos tras reinicios y un endpoint de registro de asistencia. También se ajustó el tamaño de las plantillas biométricas y se integraron cambios entre ramas.

**Commits del día (10):**

- `4b1324a` — Merge branch 'backend' into hardware
- `ff75c35` — Fix: Rename HEX variable in dy50_template_transport.h to prevent macro conflict and include minor formatting fixes in test files
- `cbb59f8` — Fix: Empaquetar structs ESP-NOW y agregar guardado DY50 en aula.cpp
- `84faea2` — Fix: Desacoplar I2C/UART del callback de recepcion ESP-NOW y agregar timeout para evitar bloqueos
- `5a22b5f` — Corregir comilla en gateway.cpp, agregar endpoint registrar_asistencia.php y configurar base de datos para AULA_A
- `b09b071` — Fix: Persistir dbLocal en NVS flash para sobrevivir reinicios del ESP32
- `b26419a` — Fix: Parsear protocolo UART del AS608 para extraer template puro en registrar.cpp
- `e08f564` — Fix: Usar Dy50TemplateTransport::readTemplate en registrar.cpp para extraer template de 1536 bytes sin framing UART
- `97d2b0a` — Fix: Actualizar TEMPLATE_BYTES de 1536 a 1408 tras medir template real del AS608
- `ea82968` — Merge branch 'hardware' of https://github.com/dfranco906/sisco-edu into hardware

## 03 de julio de 2026

Se optimizó el uso de memoria del nodo de aula y del gateway. Se reemplazó el armado de datos con cadenas dinámicas por un buffer fijo y se ajustó el procesamiento de los mensajes de sincronización.

**Commits del día (2):**

- `cad755d` — aula: replace dynamic String assembly with static buffer for ESP-NOW template reception
- `1605165` — gateway: reduce DynamicJsonDocument a 16384 bytes y limpiar saltos de linea con trim() en pedirSyncPendiente

## 06 de julio de 2026

Se prepararon y ajustaron programas para ensayar la transferencia de huellas entre módulos LoRa. Se guardaron registros de envío y recepción de cinco pruebas, incluida una de distancia, y se mejoró el control de paquetes. También se amplió la documentación y se sincronizaron las ramas. Los registros describen las pruebas realizadas; no permiten asumir por sí solos que todos los envíos fueron exitosos.

**Commits del día (22):**

- `dc2ee2a` — docs: se añadio hoja de datos tecnicos de los modulos LoRa RYLR998
- `2e66b87` — fix: se añade primera prueba comandos AT para comunicar dos modulos LoRa RYLR998
- `2ab2c92` — Merge branch 'hardware' of https://github.com/dfranco906/sisco-edu into hardware
- `52f2fb9` — fix: se añade codigo c++ para prueba piloto de envio de huella via LoRa P2P
- `8662295` — Merge branch 'hardware' of https://github.com/dfranco906/sisco-edu into hardware
- `f3b9791` — docs: datos recibidos del nodo emisor. Prueba 1
- `b7193ff` — Docs: log de paquetes recibidos via Lora. Prueba 2
- `b55292f` — docs: log de paquetes enviados via LoRa. Prueba 2
- `77ad6e3` — Merge branch 'hardware' of https://github.com/dfranco906/sisco-edu into hardware
- `02b0676` — fix: codigo c++ para 2da prueba de envio por LoRa - Control de paquetes enviados
- `1ad757d` — docs: log de datos enviados via LoRa. Prueba 3
- `7a15ae5` — Docs: log de datos recibidos via lora. Prueba 3
- `648562e` — Merge branch 'hardware' of https://github.com/dfranco906/sisco-edu into hardware
- `4304695` — fix: codigo c++ optimizado para envio y control de paquetes via LoRa. Se pasó de 150Khz a 250Khz y luego a 250Khz
- `76bb693` — Docs: log recibidos via lora. Prueba 4
- `9d0dcd3` — docs: log de envíos via LoRa. Prueba 4
- `5ddc598` — Merge branch 'hardware' of https://github.com/dfranco906/sisco-edu into hardware
- `db790aa` — Docs: log recibidos via lora. Prueba 4
- `651a838` — Merge branch 'hardware' of https://github.com/dfranco906/sisco-edu into hardware
- `0a340cc` — docs: log de paquetes recibidos via LoRa. Prueba 5 de distancia
- `17f803c` — docs: log de paquetes enviados via LoRa. Prueb 5 - de distancia
- `eb8c675` — Merge branch 'backend' into hardware

## 27 de julio de 2026

Se migró la comunicación de ESP-NOW a LoRa con comandos AT, fragmentación de mensajes y confirmaciones de recepción. En el sistema web se unificaron los identificadores de aula y de usuario biométrico, y se agregó la generación de identificadores globales para estudiantes y profesores.

**Commits del día (3):**

- `19412e4` — Refactor: Migrar transporte de red de ESP-NOW a LoRa AT (RYLR896/998) con esquema de fragmentación y ARQ Stop-and-Wait
- `0f5c443` — refactor: normalize schema from room_id/huella_id to id_aula/user_id_global
- `3835cee` — fix: generar user_id_global al crear/actualizar estudiantes y profesores

## 03 de agosto de 2026

Se actualizó la documentación de la estructura de la base de datos de SISCO-EDU.

**Commits del día (1):**

- `044d382` — feat: se agrego la base de  datos actualizada de sisco-edu

## 06 de agosto de 2026

Se ajustaron los programas del aula y del gateway para trabajar con la sincronización biométrica normalizada. Se incorporaron una migración de base de datos, el transporte compartido de plantillas y el programa del nodo registrador DY50.

**Commits del día (3):**

- `aaf7748` — fix: cambios en los firmwares de los esp aula y el gateway
- `a15bf6a` — fix: alinear LoRa con sincronizacion biometrica normalizada
- `63f535e` — feat: agregar nodo registrador biometrico DY50

## 10 de agosto de 2026

Se adoptaron plantillas biométricas de 1536 bytes y se alinearon las validaciones, las APIs y la base de datos con ese tamaño. Se mejoraron los mensajes de diagnóstico del gateway y se corrigieron direcciones IP y la URL de la API central.

**Commits del día (5):**

- `ca00ea5` — feat(esp): mejorar monitor serie del gateway
- `865d8ba` — feat: Cambio de ip
- `9772f84` — feat(biometria): ampliar templates a 1536 bytes
- `656d84e` — fix(biometria): alinear APIs y base a 1536 bytes
- `2c5e1c7` — fix(esp): corregir URL de la API central

## 13 de agosto de 2026

Se reforzó la sincronización de huellas por LoRa y se añadieron programas de prueba básica de los módulos. Se corrigieron la captura de huellas y los permisos de comunicación del navegador con el registrador. La exportación de plantillas se ajustó para evitar el uso de la memoria flash del sensor y se actualizó la dirección IP del sistema.

**Commits del día (5):**

- `cbcde49` — fix(biometria): reforzar sincronizacion de huellas por LoRa
- `f559693` — fix: se añade codigo c++ para probar el encendido y funsionamiento básico de los modulos LoRa
- `d9ffdff` — fix(biometria): corregir captura y CORS del registrador
- `1113d37` — feat: cambio de ip
- `6598163` — fix(biometria): exportar template sin usar flash del sensor

## 17 de agosto de 2026

Se añadieron verificaciones de integridad de las huellas, incluida la validación CRC antes de guardarlas, y se integró el trabajo de hardware en backend. También se recuperaron las pantallas y operaciones administrativas del frontend, se añadieron pruebas de esas operaciones y una migración para el autoincremento de usuarios.

**Commits del día (4):**

- `49a0ef2` — fix: verificar templates biometricos de extremo a extremo
- `2c8a9b7` — fix: validar CRC del template antes de guardarlo
- `e789d78` — feat: Fusion con hardware
- `686c3ac` — fix: recuperar frontend administrativo y contratos CRUD

## 20 de agosto de 2026

Se completó la gestión de asignaciones por grado y carga horaria, se simplificó el catálogo de materias y se mejoró la selección de asignaciones. Se incorporó soporte para excepciones de horarios de clases conjuntas, junto con sus validaciones y migraciones de base de datos.

**Commits del día (4):**

- `02f5231` — feat: completar asignaciones por grado y carga horaria
- `f294297` — refactor: simplificar catalogo de materias
- `e946aaf` — feat: organizar asignaciones por grado
- `fb0daf9` — feat: mejorar seleccion y excepciones de horarios

## 24 de agosto de 2026

Se amplió la gestión de horarios y la carga administrativa. Se agregaron agrupación y vinculación de clases conjuntas, soporte para una misma materia en varios grados y mejoras en formularios y pantallas.

**Commits del día (1):**

- `53fa8f2` — feat: mejorar horarios y carga administrativa

## 31 de agosto de 2026

Se incorporó la planificación pedagógica: edición de planes anuales, programación de temas, informes diarios y configuración de membretes. Se añadieron las pantallas, las APIs, las clases y la estructura de base de datos necesarias.

**Commits del día (1):**

- `4b7f794` — feat: integrar planificación pedagógica e informes diarios

## 07 de septiembre de 2026

Se incorporó el análisis de planes anuales PDF y su vista previa temporal, junto con la configuración de nodos de aula y la distribución de huellas de profesores. Se ajustaron los identificadores del aula y las rutas del gateway. También se completó el registro de asistencia biométrica y su relación con las clases diarias. El commit titulado «cambio de de tabla de aulas» modifica la tabla de rutas del firmware, no una tabla de la base de datos.

**Commits del día (4):**

- `d7dd01a` — feat: importar planes PDF y configurar nodos de aula
- `33e9382` — feat: cambio de id aula en esp y codigo de aula
- `e990681` — feat: cambio de de tabla de aulas
- `73fccaa` — fix: completar asistencia biometrica por gateway

## 01 de octubre de 2026

Se completó el flujo de importación de planes, su vista previa, confirmación, persistencia y pruebas. Se endurecieron el login y la validación de sesiones para rechazar identidades inválidas. Se añadió la comprobación del entorno Apache y se verificó el upload real. También se documentó la reparación autorizada del SuperAdmin con ID cero y su login real. El commit «tests plan anual» actualiza tiempos de extracción en archivos generados; no agrega casos de prueba nuevos.

**Commits del día (5):**

- `d11e6b8` — feat: completar importacion de planes y clases conjuntas
- `eba8e24` — feat: tests plan anual
- `c298751` — fix: rechazar sesiones con identidad invalida antes de importar
- `3a7a72d` — fix: verificar importador en Apache antes del upload PDF
- `ed5d84a` — fix(auth): repair approved zero-ID admin and verify Apache login

## 05 de octubre de 2026

Se repitió y documentó la verificación del importador en Apache/XAMPP real: upload HTTP 201, vista previa accesible y conteos de 6 unidades, 6 capacidades, 18 temas y 18 indicadores. Se comprobaron 29 pruebas de regresión y la conservación de los datos académicos. Se publicaron el informe y las evidencias sin confirmar el plan ni incluir los archivos de la tesis.

**Commits del día (1):**

- `cef7f8e` — docs: cerrar verificacion real del importador en Apache
