# PRODUCT.md — SISCO-EDU

> **Sistema Integral de Gestión Pedagógica y Control Biométrico Distribuido vía IoT (ESP32 + LoRa + Web)**

---

## 🎯 1. Visión y Misión del Producto

### Visión
Transformar la gestión escolar en las instituciones educativas, eliminando por completo la burocracia del registro físico de asistencia en papel y conectando en tiempo real la presencia en el aula física con el seguimiento curricular de materias, temas e indicadores pedagógicos.

### Misión
Proveer un ecosistema tecnológico distribuido, resiliente, modular y de bajo costo que funcione sin fricciones tanto en entornos conectados como ante caídas de red local, desarrollado con altos estándares de ingeniería en el marco formativo del **Bachillerato Técnico en Informática (BTI)**.

---

## 💡 2. Problemas que Resuelve

1. **Pérdida de Tiempo Lectivo:** Los docentes dedican entre 10 y 15 minutos por hora cátedra a pasar lista manualmente y completar carpetas pedagógicas físicas.
2. **Falsificación y Falta de Trazabilidad:** Las planillas en papel pueden ser adulteradas, dañadas o extraviadas sin registro de auditoría.
3. **Cobertura Inalámbrica Deficiente en Escuelas:** La mayoría de los colegios no cuentan con señal Wi-Fi estable en todas las aulas. SISCO-EDU resuelve esto utilizando **radiofrecuencia de largo alcance LoRa (P2P)** entre las aulas y el servidor.
4. **Desconexión Curricular:** La asistencia física no suele estar vinculada automáticamente con los temas programados según el calendario escolar anual. Al ingresar el docente, SISCO-EDU vincula su presencia con la materia y el tema planificado.

---

## 👥 3. Actores y Modelo de Roles (RBAC)

* **Administrador del Sistema:** Gestión total de usuarios, roles, catálogo de aulas, grados, materias y configuración de nodos IoT.
* **Coordinador Pedagógico:** Matriculación de estudiantes, asignación docente por materia y año lectivo, estructuración de horarios semanales y monitoreo de ausentismo institucional.
* **Profesor / Docente:** Visualización de sus horarios semanales, verificación de sus asistencias registradas y seguimiento de temas e indicadores curriculares.
* **Estudiante:** Consulta de su registro de asistencias y porcentaje de presencialidad por materia.
* **Dispositivos IoT Autónomos:** Nodos ESP32 que operan en las aulas validando identidad de forma local y reportando eventos.

---

## 🏛️ 4. Arquitectura y Flujo de Valor

```
┌────────────────────────────────┐         Wi-Fi / LAN         ┌────────────────────────────────┐
│      Estación Registradora     │ ──────────────────────────> │        Servidor Central        │
│   (ESP32 + DY50 Enrolador)     │      HTTP REST / JSON       │     (PHP 8 + MySQL + Apache)   │
└────────────────────────────────┘                             └───────────────┬────────────────┘
                                                                               │
                                                                               │ HTTP Webhook /sync
                                                                               ▼
┌────────────────────────────────┐       Radiofrecuencia       ┌────────────────────────────────┐
│          Nodos de Aula         │ <────────────────────────── │         Gateway Central        │
│  (ESP32 + DY50 + OLED + LoRa)  │        LoRa (RYLR998)       │       (ESP32 + LoRa + Wi-Fi)   │
│   Almacenamiento Local NVS     │        Stop-and-Wait        │     Orquestador de Enlace      │
└────────────────────────────────┘                             └────────────────────────────────┘
```

### Flujo de Vida del Dato:
1. **Enrolamiento Central:** El estudiante o profesor registra su huella una única vez en la estación registradora. Se genera un template estandarizado de **1536 bytes (3072 caracteres HEX)** con verificación **CRC32**.
2. **Sincronización Inteligente:** El servidor determina qué aula le corresponde al usuario según su grado lectivo o asignación docente y encola la sincronización en `sync_biometrica`.
3. **Despacho LoRa:** El Gateway fragmenta el template en paquetes de 128 caracteres y los transmite mediante LoRa P2P con confirmación por ráfagas (ACK) hasta que el sensor del aula lo almacena en su memoria local.
4. **Validación Offline en Aula:** El usuario coloca su dedo en el sensor del aula. El ESP32 valida la huella localmente en milisegundos sin requerir conexión a internet en ese instante.
5. **Reporte y Auditoría:** El nodo aula emite un evento `AST` vía LoRa al Gateway, quien impacta la base de datos central calculando presentes y ausentes en tiempo real.

---

## ⚙️ 5. Especificaciones de Hardware

| Componente | Modelo / Chip | Función |
| :--- | :--- | :--- |
| **Microcontrolador Principal** | ESP32-WROOM-32 (Dual Core 240MHz) | Controlador en Nodos Aula, Gateway y Registrador |
| **Lector Biométrico** | DY50 / AS608 / FPM10A (UART 57600) | Captura, extracción de minucias y cotejo local |
| **Transceptor de Radio** | Reyax RYLR998 / RYLR896 (LoRa AT) | Comunicación inalámbrica de largo alcance (Red 18) |
| **Pantalla Informativa** | Pantalla OLED SSD1306 128x64 (I2C) | Estado de marcación, hora y confirmación visual |
| **Memoria No Volátil** | NVS (ESP32 Preferences) | Mapeo persistente de slots del sensor a cédulas |

---

## 📁 6. Mapa de Estructura del Repositorio

* [`src/`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/src/): Núcleo backend del sistema.
  * `api/`: Endpoints REST modulares agrupados por entidad (`Gateway`, `Estudiante`, `Profesor`, `Huella`, etc.).
  * `classes/`: Clases de dominio y acceso a datos mediante PDO (`Estudiante.php`, `Horario.php`, `Asistencia.php`, etc.).
  * `config/`: Configuración del sistema (`app.php`, `red.php`, `db.php`, `biometria.php`).
  * `middleware/`: Control de sesiones y autenticación basada en roles (`auth.php`).
* [`mvc/views/`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/mvc/views/): Vistas de la aplicación web estilizadas con Tailwind CSS.
* [`public/`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/public/): Recursos accesibles desde el navegador (`css/theme.css`, `js/crud.js`, `js/obtener_template.js`, etc.).
* [`hardware/`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/hardware/): Códigos fuente y firmwares de los dispositivos ESP32.
  * `nodo_aula/`: Firmware para el terminal de marcación en el aula (`nodo_aula.ino`).
  * `nodo_gateway/`: Firmware para el puente LoRa-WiFi (`nodo_gateway.ino`).
  * `nodo_registrador/`: Firmware para la estación de enrolamiento biométrico (`nodo_registrador.ino`).
  * `comun/`: Cabeceras compartidas (`configuracion_red.h`, `dy50_template_transport.h`).
  * `laboratorio_prototipos/`: Pruebas de encendido, calibración y código experimental previo.
* [`database/`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/database/): Scripts de migración y restricciones relacionales SQL versionadas.
* [`docs/`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/docs/): Documentación institucional, guías de Git, manuales y datasheets de hardware.
* [`pruebas/`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/pruebas/): Scripts de prueba funcionales y unitarios organizados por módulo.

---

## 🌐 7. Gestión Centralizada de IPs y Red

Para evitar modificar el código fuente al cambiar de entorno de prueba o encargado de proyecto:
1. **Web / Backend:** Edita los valores en [`src/config/red.php`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/src/config/red.php) o crea un archivo local `config.local.php` (ignorado por Git) copiando [`config.local.example.php`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/config.local.example.php).
2. **Microcontroladores:** Edita [`hardware/comun/configuracion_red.h`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/hardware/comun/configuracion_red.h) antes de flashear los ESP32.
3. **Frontend (JS):** Las vistas web inyectan automáticamente las IPs en `window.CONFIG_RED`, por lo que los scripts en JavaScript nunca contienen IPs quemadas.

---

## 🚀 8. Hoja de Ruta hacia Producción

- [x] Unificación del contrato biométrico a 1536 bytes con suma de verificación CRC32.
- [x] Migración del transporte de red de ESP-NOW a LoRa P2P con confirmación Stop-and-Wait.
- [x] Centralización de configuraciones de red y desacople de rutas fijas (`tiago3roBTI2026`).
- [x] Reorganización de la estructura de carpetas en módulos limpios y legibles.
- [ ] Exportación de reportes de asistencia consolidados en PDF y Excel para la Dirección.
- [ ] Cifrado AES-128 en las tramas de radio LoRa para entornos de producción extendidos.
- [ ] Incorporación de WebSockets o SSE para actualización instantánea del Dashboard.
