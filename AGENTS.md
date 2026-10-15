# AGENTS.md — Reglas de Ingeniería y Desarrollo para SISCO-EDU

> Este archivo define las directrices, estándares de arquitectura, convenciones de código y reglas operativas que cualquier desarrollador o agente de IA debe seguir rigurosamente al trabajar en este repositorio.

---

## 🧭 1. Principios Fundamentales y Filosofía (Clean Code)

* **Código Expresivo y Auto-documentado:** Nombres de variables, funciones y clases que expresen su intención sin requerir comentarios obvios.
* **Funciones Atómicas y Pequeñas:** Cada función debe hacer **una sola cosa** y hacerla bien (máximo 25-30 líneas recomendadas).
* **Patrón Early Return (Guard Clauses):** Validar condiciones de borde y fallos al inicio de la función retornando inmediatamente; evitar anidamientos profundos de `if/else` (máximo 2 niveles de indentación lógica).
* **Cero Archivos Muertos:** No dejar archivos vacíos (0 bytes), placeholders sin implementar, ni archivos de configuración de IDEs en carpetas de código fuente.

---

## 🐘 2. Estándares Backend (PHP 8+)

1. **Uso Exclusivo de PDO con Sentencias Preparadas:**
   * **PROHIBIDO** concatenar variables directamente en sentencias SQL.
   * Usar siempre `$stmt = $conn->prepare(...)` y pasar parámetros por array asociativo en `$stmt->execute([...])` o mediante `$stmt->bindParam()`.
2. **Respuestas REST Uniformes en APIs:**
   * Todos los endpoints en `src/api/` deben declarar:
     `header("Content-Type: application/json; charset=UTF-8");`
   * Las respuestas deben seguir siempre la estructura estándar:
     ```json
     {
       "status": "success" | "error",
       "message": "Descripción legible para el usuario o cliente",
       "data": {} // Opcional
     }
     ```
   * En caso de error, establecer el código HTTP semántico correspondiente (`400`, `401`, `403`, `404`, `422`, `500`) mediante `http_response_code($codigo)` antes de emitir el JSON.
3. **Manejo Seguro de Excepciones:**
   * Nunca hacer `echo` de cadenas de error planas ni de `$e->getMessage()` directamente a la salida en producción.
   * Capturar excepciones mediante bloques `try / catch (Throwable $e)`, registrar el detalle con `error_log()` y responder con JSON controlado.
4. **Separación de Responsabilidades:**
   * La lógica de consulta y manipulación de datos reside en las clases de [`src/classes/`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/src/classes/).
   * Los archivos en `src/api/` actúan como controladores livianos: reciben la petición HTTP, validan los datos de entrada, delegan a la clase y responden en JSON.
   * Las vistas en `mvc/views/` nunca ejecutan consultas SQL directamente; se comunican con el backend mediante peticiones `fetch()` a la API.

---

## 🔒 3. Contrato de Integridad Biométrica (Regla Innegociable)

El sistema opera bajo un contrato estricto de biometría acordado entre el sensor DY50/AS608, el backend y el transporte LoRa:

* **Tamaño del Template:** Exactamente **1536 bytes binarios**, representados como **3072 caracteres hexadecimales** (`[0-9a-fA-F]{3072}`).
* **Formato:** Exclusivamente `HEX` en minúsculas en la base de datos.
* **Integridad (CRC32):** Cada template debe contar con su suma de verificación **CRC32** de 8 caracteres hexadecimales. Si el CRC32 calculado en el servidor no coincide con el enviado por el hardware, el registro se rechaza inmediatamente con HTTP 422.
* **Restricción de Base de Datos:** Toda huella activa está protegida por la restricción CHECK `chk_huella_template_activa_1536`. Ninguna modificación de código puede violar esta regla.

---

## 🌐 4. Gestión de Red y Regla Anti-Hardcoding

Queda estrictamente prohibido colocar direcciones IP fijas (`192.168.x.x`) o rutas locales del desarrollador (ej. `/tiago3roBTI...`) en el código de producción.

* **Backend / Web:**
  * Toda URL o ruta base debe resolverse mediante la función `base_url($path)` definida en [`src/config/app.php`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/src/config/app.php).
  * Las direcciones de los nodos IoT se leen de [`src/config/red.php`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/src/config/red.php), la cual permite sobreescrituras en `config.local.php` o variables de entorno.
* **Frontend Web:**
  * Los scripts JS consumen las variables globales inyectadas en el layout: `window.BASE_URL` y `window.CONFIG_RED.ipRegistrador`.
* **Firmware ESP32:**
  * Las credenciales Wi-Fi, URLs del servidor y claves de enlace se configuran exclusivamente en [`hardware/comun/configuracion_red.h`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/hardware/comun/configuracion_red.h).

---

## 🌿 5. Flujo de Trabajo en Git y Commits

Siguiendo la guía de supervivencia del equipo técnico:

### Estructura de Ramas
* `main`: Rama de entrega final congelada y defendida.
* `develop`: Rama de integración donde conviven las funcionalidades probadas.
* `backend`, `frontend`, `hardware`: Ramas base de los tres equipos de trabajo.
* `feat-<nombre>` / `fix-<nombre>`: Ramas individuales de trabajo que parten de su respectiva rama de equipo.

### Idioma de los Commits (Commits Convencionales)
Formato obligatorio: `tipo: descripción corta en infinitivo o presente`
* `feat:` Nueva funcionalidad (ej. `feat: agregar vista semanal de horarios`).
* `fix:` Corrección de errores (ej. `fix: corregir validacion de formato hex en huella`).
* `refactor:` Mejora de estructura sin cambiar comportamiento funcional.
* `style:` Cambios puramente visuales (CSS, espaciados).
* `docs:` Cambios en documentación o manuales.

---

## 🛠️ 6. Checklist de Verificación Previa a Modificaciones

Antes de considerar terminada una tarea o realizar un commit:

1. [ ] **Sintaxis PHP:** Ejecutar `php -l <archivo>` en todos los archivos PHP modificados.
2. [ ] **Sin Hardcoding:** Verificar que no se hayan introducido IPs locales quemadas en el código fuente.
3. [ ] **Integridad JSON:** Probar que las APIs respondan con el `Content-Type` adecuado y sin emitir advertencias PHP que corrompan el JSON.
4. [ ] **Respeto a las Rutas:** Asegurar que los enlaces utilicen `base_url()` y rutas relativas portables.
5. [ ] **Limpieza:** No dejar archivos de prueba sueltos en carpetas de producción.
