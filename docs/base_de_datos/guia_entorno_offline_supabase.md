# 🛡️ Guía de Operación Offline y Sincronización Cloud con Supabase

> **SISCO-EDU**: Manual de arquitectura y procedimientos para trabajar 100% desconectado de Internet (offline) en la institución y sincronizar asistencias, huellas y datos a la nube con 1 solo clic.

---

## 🧭 1. Resumen de la Solución

Frente a la inestabilidad de la conexión a Internet en la institución, SISCO-EDU implementa una **arquitectura híbrida local/nube**:

```
 [Hardware ESP32] (Registrador / Gateway / Aulas)
         │
         ▼
 [Servidor Local Apache / XAMPP] (192.168.100.X)
         │
         ├──► MODO OFFLINE:  PostgreSQL Local / Supabase Local (127.0.0.1:54322 o 5432)
         │                   • Cero dependencia de Internet
         │                   • Latencia instantánea (<5ms)
         │                   • Todas las pruebas de huellas y asistencias operativas
         │
         └──► 1 SOLO CLIC:   Sincronizador Híbrido (Local ➔ Supabase Cloud)
                             • Ejecutable desde el navegador o archivo .bat
                             • Sube huellas, asistencias y eventos acumulados
                             • Idempotente (sin duplicaciones gracias a UPSERT)
```

---

## ⚙️ 2. Estructura de Artefactos Incluidos

El repositorio contiene todo lo necesario para inicializar cualquier máquina de forma determinística:

* `supabase/config.toml`: Configuración estándar de Supabase CLI (puertos DB `54322`, Studio `54323`, API `54321`).
* `supabase/migrations/20261026000000_sisco_edu_baseline.sql`: Esquema completo DDL de las 41 tablas, restricciones CHECK biométricas (1536 bytes / 3072 HEX) y políticas RLS.
* `supabase/seed.sql`: Datos semilla completos (docentes, materias, aulas, grados, horarios, matriculas y usuarios).
* `config.local.template.php`: Plantilla de configuración con perfiles duales (`LOCAL_DB` y `CLOUD_DB`).
* `src/classes/SincronizadorNube.php`: Motor de sincronización bidireccional seguro.
* `src/api/Sincronizacion/sincronizar_nube.php`: Endpoint REST para sincronizar desde el panel web.
* `scripts/`:
  * `sincronizar_a_nube.bat`: Sube huellas y asistencias locales a Supabase Cloud con doble clic.
  * `bajar_de_nube.bat`: Actualiza la base local con los datos más recientes de la nube.
  * `switch_env.bat`: Alterna entre `local` y `cloud` con un comando o menú interactivo.
  * `setup_offline.bat`: Inicializador automático de entorno offline para una máquina nueva.
  * `instalar_supabase_cli.bat`: Descarga e instala `supabase.exe` en Windows automáticamente.

---

## 🚀 3. Opciones para Levantar la Base de Datos Local

Puedes elegir cualquiera de las dos alternativas según las herramientas instaladas en tu computadora:

### Opción A: Con Supabase CLI (Requiere Docker Desktop)

1. **Instalar Supabase CLI**:
   * Ejecuta `scripts/instalar_supabase_cli.bat` (o mediante Scoop: `scoop install supabase`).
2. **Iniciar el stack local**:
   ```powershell
   supabase start
   ```
   * *Base de datos local:* `postgresql://postgres:postgres@127.0.0.1:54322/postgres`
   * *Supabase Studio:* `http://127.0.0.1:54323`
3. **Configurar el sistema**:
   * En `config.local.php`, el puerto local predeterminado es `54322`.
   * Ejecuta `scripts\switch_env.bat local`.

---

### Opción B: Con PostgreSQL Nativo para Windows (Sin Docker, Ultra Ligero)

Si la computadora no dispone de Docker Desktop o la conexión no permite descargar imágenes pesadas:

1. **Instalar PostgreSQL**:
   ```powershell
   winget install PostgreSQL.PostgreSQL.16
   ```
   * O descarga el instalador oficial desde [postgresql.org](https://www.postgresql.org/download/windows/).
   * Contraseña recomendada: `postgres` (puerto estándar `5432`).
2. **Ajustar puerto en `config.local.php`**:
   * En la sección `'LOCAL_DB'`, establece `'DB_PORT' => 5432`.
3. **Cargar la base de datos con 1 comando**:
   ```cmd
   scripts\setup_offline.bat
   ```
   * Creará automáticamente las 41 tablas y cargará todos los datos semilla (`seed.sql`).

---

## ☁️ 4. Sincronización a la Nube con 1 Solo Clic

Una vez finalizadas las pruebas con los nodos físicos en la institución y al disponer de Internet (o compartir datos desde el móvil):

### Método 1: Desde el Navegador Web (Recomendado)
1. Ve a **Administración ➔ Configuración de Red & IoT**.
2. En la sección **⚡ Sincronización de Base de Datos (1 Clic)**, presiona el botón:
   * **`🚀 Sincronizar a la Nube (1 Clic)`**
3. El sistema conectará con Supabase Cloud y subirá:
   * Huellas biométricas validadas.
   * Asistencias de docentes y alumnos.
   * Eventos de hardware.
4. Aparecerá una notificación detallando exactamente cuántos registros se transfirieron.

### Método 2: Mediante Archivo `.bat` (Doble Clic en Windows)
* Haz doble clic sobre [`scripts/sincronizar_a_nube.bat`](file:///c:/Users/dfran/develop/proyectos-git/sisco-edu/scripts/sincronizar_a_nube.bat).
* Se ejecutará la sincronización en consola mostrando el resumen en tiempo real.

---

## 💻 5. Cómo Replicar en Cualquier Otra Computadora

Para continuar el desarrollo o realizar pruebas en una nueva laptop:

1. **Clonar el repositorio Git**:
   ```bash
   git clone https://github.com/dfranco906/sisco-edu.git
   cd sisco-edu
   ```
2. **Iniciar la base de datos local**:
   * Con Supabase CLI: `supabase start`
   * O con PostgreSQL nativo: verificar que el servicio esté corriendo en el puerto 5432.
3. **Ejecutar el configurador offline**:
   ```cmd
   scripts\setup_offline.bat
   ```
4. **Listo**: Tu Apache/XAMPP conectará inmediatamente en local, con todos los profesores, materias, aulas y configuraciones listas para operar sin tocar Internet.
