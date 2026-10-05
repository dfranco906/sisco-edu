# Auditoría y checkpoints de despliegue

Inicio: rama `frontend-recovery-audit`, HEAD `0b3bc45b8757639f7dcd2c2b26bf0c7d05e4c62b`.
Cambios previos preservados: `test/admin_frontend_headless.js`, documentación de simulación y tesis.

## Auditoría previa a implementar

- P0.1: AST no tiene identidad ni ACK remoto. Gateway descarta sin Wi-Fi; las esperas de chunks descartan tráfico recibido. Backend deduplica asistencia oficial por dominio, pero crea otro evento crudo por reintento. Un error SQL puede ocurrir después de insertar el evento crudo.
- P0.2: GET sólo consulta PENDIENTE; ENVIADO queda atrapado. La toma del trabajo no es transaccional. ERROR de enlace es definitivo en el primer intento. Aula reutiliza slot por CI, pero debe incluir tipo al identificarlo.
- P0.3: rutas existen en backend pero Gateway usa tres destinos compilados. No hay validación de unicidad del conjunto de rutas.
- P0.4: identidad RF y aula compiladas; ya existe Preferences para slots, utilizable sin Wi-Fi.
- Salud: heartbeat anónimo; ONLINE es manual y no expira. Lectura de nodos carece de último evento y contadores sync.
- Seguridad: guardar_template, solicitar_captura, esp32_obtener_solicitud, esp32_confirmar_huella y Dashboard/resumen sin autorización. Gateway usa secreto fijo y comparación sensible a mayúsculas del nombre del header. confirmar_sync permite CONFIRMADO sin prueba de instalación.

## Decisiones mínimas

1. Migración aditiva: recibos de entrega únicos por `source_event_id`, hash del contenido y primera respuesta. `eventos_asistencia.source_event_id` nullable/único permite trazabilidad sin cambiar eventos históricos. Transacción del recibo, evento y asistencia; llamadas antiguas conservan comportamiento. Se conserva el reloj backend autoritativo actual.
2. Aula genera identidad aleatoria de 128 bits prefijada por MAC, persiste antes de transmitir. Gateway y Aula mantienen colas NVS acotadas, sin sobrescribir al llenarse. ACK únicamente HTTP 200 con `ok=true` e ID coincidente; reintentos con backoff. Rechazos de dominio no se convierten en éxito.
3. Lease sync de 120 s y máximo de 5 envíos; tomar fila bajo bloqueo. Sólo confirmar_entrega_aula con CRC valida CONFIRMADO. Reintentos reutilizan id_sync/template/slot.
4. Rutas completas validadas y cacheadas en NVS; conservar última versión válida al fallar HTTP. Identidad Aula provisionada por puerto serie físico, una vez; Gateway recibe Wi-Fi/URL/clave por configuración local/NVS.
5. Heartbeat autenticado y estado derivado con timeout de 180 s; Aula reporta por LoRa y Gateway valida su ruta.

## Límites a verificar antes del piloto

La retransmisión offline conserva la marca, pero la hora efectiva sigue siendo la recepción backend: una caída prolongada puede dejar la marca fuera de la ventana de clase. No se modifica la política pedagógica ni se inventa la hora de captura. Las pruebas de software no sustituyen pruebas RF, cortes de alimentación y DY50 en tres dispositivos reales. LoRa identifica origen por dirección, sin autenticación criptográfica; red privada/control físico es requisito actual.

## Checkpoint WIP solicitado al interrumpir el trabajo

El usuario autorizó commit y push de lo realizado para continuar después. Este checkpoint es **incompleto y no desplegable**.

| Checkpoint | Resultado de cierre |
|---|---|
| P0.1 | FAIL: recibos backend y cola Aula implementados; falta integrar Gateway y pruebas de fallos |
| P0.2 | FAIL: lease implementado; faltan pruebas de crash/timeout/concurrencia y firmware Gateway |
| P0.3 | FAIL: validación backend implementada; falta cache/refresco en Gateway |
| P0.4 | FAIL: provisioning Aula implementado; falta compilar/verificar en ESP32 |
| HEARTBEAT | FAIL: endpoint autenticado y lectura derivada implementados; falta emisión Gateway y relay Aula |

FAIL aquí significa que el checkpoint aún no satisface sus criterios, no que las comprobaciones ejecutadas hayan fallado.

### Diff realizado

- Migración aditiva y servicio de recibos: identidad única, hash para rechazar reutilización con otro contenido y respuesta persistida en transacción.
- Servicio de asistencia admite transacción externa, conservando contratos previos. No se modifican Planificación, ClaseDiaria ni Informes.
- Lease de sync (120 s, 5 envíos), toma bloqueada, retry de ERROR de enlace y restricción de CONFIRMADO a entrega verificada por CRC.
- Rutas backend validan LoRa IDs y aulas únicos (máximo 32).
- Autenticación de dispositivos sin clave implícita; secreto por entorno o devices.local.php ignorado por git. Captura web usa sesión administrativa y CSRF.
- Heartbeat autenticado, estado online derivado de 180 s, tabla de Administración con último evento y contadores sync.
- Firmware Aula: identidad en NVS, CONFIG/PROVISION por serie, cola de 32 marcas, IDs con MAC/epoch/contador NVS, backoff y ASTACK. Primer escaneo ya no queda inhibido durante cinco minutos.

### Tests ejecutados antes de guardar

- PHP lint: 27 archivos PASS.
- node --check public/js/obtener_template.js: PASS.
- git diff --check: PASS.
- test/reliability_migration.php: PASS; migración aplicada dos veces sobre BD temporal, sin copiar registros reales.
- test-gateway-asistencia-real: 38 PASS / 0 FAIL, BD temporal eliminada.
- Resto de regresiones obligatorias: PENDIENTE en este checkpoint.
- Compilación firmware y pruebas delivery/retry/idempotencia, sync stale, rutas, provisioning, heartbeat/offline: PENDIENTE.

### Migración de dispositivos y seguridad pendientes

**No aplicar este checkpoint al colegio.** esp_gateway.ino conserva el firmware anterior: no interpreta AST2 ni produce ASTACK y aún usa la clave compilada. Completar su migración antes de actualizar las Aulas.

El backend ahora requiere `SISCO_GATEWAY_API_KEY` o `src/config/devices.local.php` con `gateway_api_key`. Para mantener temporalmente dispositivos antiguos, el operador puede configurar explícitamente su clave actual en ese archivo local; nunca subirla a git. Después rotar clave y actualizar Gateway. La migración SQL sólo fue aplicada a BD temporal; producción aún no tiene las nuevas tablas/columnas.

Para Aula, después de completar Gateway: provisionar por serie a 115200 baudios con `PROVISION {"id_aula":30,"codigo":"3_RO_BTI","lora_id":101,"gateway_lora_id":100,"network_id":18}`; `CONFIG` imprime identidad y pendientes. No borrar namespace eventseq ni NVS durante actualizaciones. El mismo binario sirve para todas las aulas. Sin Wi-Fi en Aula. Provisión por puerto físico bajo control del instalador.

Pendiente revisar orden y hora efectiva de recuperación tras caída prolongada; RET_ANTICIPADO se rechaza por el nuevo contrato y requiere decisión de dominio (no convertir silenciosamente a PRESENTE). Pendiente prueba de agotamiento de NVS, reinicios/cortes durante escritura y cola llena. Pendiente validar permisos/CSRF y contratos de los clientes biométricos actuales con HTTP real.

La descarga de Arduino CLI se hizo exclusivamente en `%TEMP%/sisco-firmware-check`; no se instalaron aún core ni librerías y no se compiló firmware.

FIRMWARE MODIFICADO: esp/LoRa/esp_aula.ino y helpers nuevos. esp_gateway.ino todavía sin modificar.

DATOS REALES MODIFICADOS: **0**.

LISTO PARA PILOTO DE 3 AULAS: **NO**.
