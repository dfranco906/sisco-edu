# Sincronización de la huella de un profesor con aulas

## Flujo desde SISCO-EDU

La huella se captura una sola vez y el mismo template puede distribuirse a varias aulas donde el profesor tiene horarios activos.

### Profesor sin huella registrada

1. Iniciar sesión como `SuperAdmin`, `Administracion` o `Coordinador`.
2. Abrir **Profesores**.
3. Presionar **Registrar huella** en la fila del profesor.
4. Seleccionar una o varias aulas de su horario.
5. Presionar **Continuar con captura**.
6. Colocar dos veces el dedo en el ESP32 registrador cuando sea solicitado.
7. El backend guarda un solo registro en `huellas_templates` y crea una sincronización `PENDIENTE` en `sync_biometrica` por cada aula seleccionada.

### Profesor con huella registrada

1. Abrir **Profesores**.
2. Presionar **Enviar a aula**.
3. Seleccionar las aulas que figuran como **Sin enviar** o **Error anterior**.
4. Presionar **Enviar huella**.

No se vuelve a capturar el dedo. El backend reutiliza el último template activo del profesor. Una combinación huella/aula que ya esté `PENDIENTE`, `ENVIADO` o `CONFIRMADO` no se duplica.

## Recorrido técnico

```text
Profesores > Registrar huella / Enviar a aula
  -> aulas_profesor.php valida las aulas del horario
  -> guardar_template.php o sincronizar_profesor.php
  -> sync_biometrica (una fila por aula)
  -> POST /sync al Gateway
  -> Gateway consulta obtener_sync_aula.php?id_aula=N
  -> Gateway busca la dirección LoRa del aula
  -> ESP32 del aula guarda el template en el DY50
  -> confirmar_entrega_aula.php marca CONFIRMADO
```

La marca posterior del profesor se envía desde el aula como:

```text
AST:<ID_AULA>:<CI>:profesor:PRESENTE
```

El backend usa `id_aula`, profesor, fecha, hora y horarios para identificar la asignación y crear o recuperar la clase diaria.

## Configuración obligatoria del hardware

Cada ESP32 de aula debe tener en `esp/LoRa/esp_aula.ino`:

- `ID_AULA`: debe coincidir con `aulas.id_aula`.
- `MI_LORA_ID`: dirección LoRa única del dispositivo.
- El mismo `LORA_NETWORK_ID` utilizado por el Gateway.

El Gateway debe incluir el mismo par en `tablaAulas` de `esp/LoRa/esp_gateway.ino`:

```cpp
DispositivoAula tablaAulas[] = {
    {ID_AULA_BASE_DE_DATOS, DIRECCION_LORA_DEL_ESP_AULA}
};
```

No se deben inventar direcciones LoRa. Deben configurarse de acuerdo con los módulos físicos instalados.

En la base actual las aulas activas tienen IDs 22 a 30, mientras que el firmware versionado conserva rutas de ejemplo para 18, 15 y 16. Hasta actualizar y cargar el firmware con los IDs físicos correctos, las sincronizaciones de las aulas nuevas permanecerán pendientes. La interfaz muestra **Nodo/Gateway no configurado** cuando no encuentra un nodo de aula asociado en la base.

## Endpoints administrativos

- `GET src/api/Huella/aulas_profesor.php?user_id_global=...`
- `POST src/api/Huella/sincronizar_profesor.php`
  - `user_id_global`
  - `id_aulas[]`
- `POST src/api/Huella/guardar_template.php`
  - acepta varios `id_aulas[]` al registrar un profesor.

Los dos primeros endpoints requieren sesión con rol `SuperAdmin`, `Administracion` o `Coordinador`.
