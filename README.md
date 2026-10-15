# SISCO-EDU: Sistema Integral de Gestión Pedagógica e IoT

## 🎯 1. Objetivo del Proyecto
Desarrollar una solución tecnológica modular que automatice el control de asistencia y el seguimiento del desarrollo curricular en instituciones educativas. El proyecto busca eliminar la burocracia del registro físico mediante la integración de hardware biométrico (ESP32), comunicación de largo alcance (LoRa) y una plataforma web centralizada basada en el paradigma orientado a objetos.

---

## 🛠️ 2. Descripción de la Herramienta
**SISCO-EDU** es un ecosistema que conecta el aula con la administración escolar. El sistema detecta la presencia del docente y el alumnado mediante huellas dactilares y, de forma inteligente, cruza esa información con la planificación anual previamente cargada. 

Al registrarse la entrada del profesor, el sistema "dispara" automáticamente el tema y los indicadores que corresponden a esa fecha y hora cátedra, generando planillas de asistencia y contenido en tiempo real sin intervención manual.

---

## 🧩 3. Módulos y Funcionalidades

### A. Módulo de Identidad y Roles (RBAC)
* **Administrador:** Creación y gestión de cuentas de usuario.
* **Coordinador:** Matriculación de alumnos, asignación de materias y configuración de horarios (HC de 40 min).
* **Profesor:** Gestión de carga académica propia.
* **Estudiante:** Consulta de asistencia y progreso.

### B. Módulo de Planificación Anual (Carga Docente)
Interfaz para que el profesor desglose su materia en:
* **Unidades Temáticas y Capacidades:** Estructura jerárquica del contenido.
* **Temas e Indicadores:** Vinculación de contenidos con fechas específicas para la automatización.
* **Evaluación:** Registro de procedimientos e instrumentos (RSA, Lista de cotejo, etc.).

### C. Módulo IoT (Trigger & Gateway)
* **Dispositivo Trigger (Aula):** Captura de huellas dactilares y almacenamiento temporal. Envío de ráfagas de datos cada 15 min vía LoRa.
* **Dispositivo Gateway (Servidor):** Recepción de datos LoRa y reenvío al backend mediante protocolos HTTP/Serial.

### D. Módulo de Automatización y Reportes
* **Cruce de Datos:** Algoritmo de detección automática de materia/tema basado en la marca biométrica.
* **Planillas:** Generación automática de planillas diarias, mensuales y semestrales.
* **Estadísticas:** Panel de control para el administrador con horas cátedras cubiertas y métricas de ausentismo.

---

## 💻 4. Requerimientos Técnicos y Herramientas

### Software (Stack de Desarrollo)
* **Servidor Local:** [XAMPP](https://www.apachefriends.org/) (Apache + MySQL).
* **Backend:** PHP 8.x (Paradigma Orientado a Objetos).
* **Frontend:** HTML5, JavaScript (Vanilla), CSS3 y [Tailwind CSS](https://tailwindcss.com/).
* **Base de Datos:** MySQL (Diseño Relacional Normalizado).
* **Firmware:** Arduino IDE (C++ para ESP32).

### Hardware (Prototipo)
* 2x Microcontroladores **ESP32**.
* 2x Módulos de radiofrecuencia **LoRa SX1278**.
* 1x Lector de huellas dactilares **AS608 / FPM10A**.

### Control de Versiones
* **Git:** Para el registro histórico de cambios.
* **GitHub:** Repositorio centralizado para el trabajo colaborativo.

---

## 📅 5. Ruta de Trabajo Estándar

| Fase | Periodo | Hito Principal |
| :--- | :--- | :--- |
| **Análisis** | Marzo | Diseño de Base de Datos y Prototipos UI. |
| **Desarrollo I** | Abril - Mayo | Comunicación LoRa y CRUDs básicos de usuarios. |
| **Desarrollo II** | Junio - Julio | Lógica de cruce de datos y carga de planes anuales. |
| **Integración** | Agosto | Pruebas de hardware contra el servidor real. |
| **Entrega** | Septiembre | Documentación final y Defensa del proyecto. |

---

## 👥 6. Estructura del Equipo
* **1 Líder de Proyecto:** Coordinación e integración técnica.
* **3 Desarrolladores Backend:** Base de datos, lógica POO y API.
* **3 Desarrolladores Frontend:** Diseño UI, Tailwind CSS y validaciones JS.
* **2 Desarrolladores de Hardware:** Programación ESP32, biometría y enlace LoRa.

---

> **Nota:** Este proyecto se desarrolla bajo las capacidades educativas del Bachillerato Técnico en Informática, enfatizando la integridad de datos y el ciclo de vida del software.

---

## 📁 Estructura del Proyecto

Consulta [**`PRODUCT.md`**](PRODUCT.md) para la visión del producto y [**`AGENTS.md`**](AGENTS.md) para las reglas de desarrollo.

```
SISCO-EDU/
├── database/                # Scripts de migraciones SQL versionadas.
│   └── migrations/          # Integridad relacional y contratos biométricos.
├── docs/                    # Documentación técnica, manuales y datasheets.
│   ├── base_de_datos/       # Dumps y esquemas SQL de referencia.
│   ├── datasheets/          # Hojas de datos de componentes (LoRa RYLR998, etc.).
│   └── guias/               # Guías institucionales, flujos de Git y especificaciones.
├── hardware/                # Firmware para microcontroladores ESP32 (Arduino IDE).
│   ├── nodo_aula/           # Terminal biométrica de aula (nodo_aula.ino).
│   ├── nodo_gateway/        # Puente LoRa <-> Wi-Fi (nodo_gateway.ino).
│   ├── nodo_registrador/    # Estación de enrolamiento de huellas (nodo_registrador.ino).
│   ├── comun/               # Configuraciones (configuracion_red.h) y protocolo DY50.
│   └── laboratorio_prototipos/ # Pruebas de encendido, LoRa y prototipos previos.
├── mvc/                     # Capa de presentación web.
│   └── views/               # Vistas modulares por entidad (estudiantes, profesores, etc.).
├── pruebas/                 # Scripts de prueba y verificación por módulo.
├── public/                  # Recursos estáticos servidos al navegador.
│   ├── css/                 # Estilos y variables de diseño (Tailwind).
│   └── js/                  # Lógica del cliente, CRUDs y comunicación biométrica.
├── src/                     # Núcleo backend y lógica de negocio.
│   ├── api/                 # Endpoints REST modulares.
│   ├── classes/             # Modelos y clases orientadas a objetos (PDO).
│   ├── config/              # Configuraciones centrales (red.php, app.php, db.php).
│   └── middleware/          # Verificación de sesiones y roles (auth.php).
├── AGENTS.md                # Reglas de ingeniería, Clean Code y estándares del proyecto.
├── PRODUCT.md               # Visión, misión, actores, alcance y especificaciones técnicas.
├── config.local.example.php # Plantilla de configuración local (IPs y Base de Datos).
├── index.php                # Punto de entrada de la aplicación web.
└── README.md                # Documento general del proyecto.
```