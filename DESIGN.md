# DESIGN.md — Sistema de Diseño y Guía de Estilo UI/UX de SISCO-EDU

> **Propósito del Documento:**  
> Este archivo establece las directrices maestras de diseño de interfaz (UI), experiencia de usuario (UX) y estándares de accesibilidad para el software educativo y biométrico **SISCO-EDU**.  
> Todos los desarrolladores y agentes de IA deben seguir estas directrices para asegurar coherencia, elegancia visual y facilidad de uso.

---

## 🎯 1. Público Objetivo y Filosofía de Accesibilidad

### Perfil de Usuario Principal
* **Audiencia Real:** Directivos escolares, coordinadores pedagógicos, secretarias y docentes, predominantemente **adultos y personas mayores**.
* **Condición Visual Habitual:** Presbicia, disminución natural de la agudeza visual y uso común de anteojos de lectura o bifocales.
* **Entorno de Trabajo:** Monitores de resolución variada en secretarías, laptops en salas de profesores y tablets en pasillos o aulas.

### Reglas Innegociables de Accesibilidad
1. **Regla Estricta de Tipografía (≥ 12 pt / 16 px base):**
   * **PROHIBIDO** el uso de fuentes menores a `16 px` (`text-base`) para datos clave de tablas, etiquetas de formularios y contenido de lectura.
   * **PROHIBIDO** el uso de micro-fuentes como `text-[10px]`, `text-[11px]` y `text-xs` (12 px / 9 pt) en filas de tablas, badges de datos o botones.
   * Los metadatos secundarios nunca deben descender de `14 px - 15 px` (`text-sm font-semibold` o `font-bold` con alto contraste).
2. **Dimensiones de Clic y Zonas Táctiles (Touch Targets ≥ 44 px):**
   * Todo botón interactivo, campo de texto (`<input>`), selector (`<select>`) o disparador de modal debe tener una **altura mínima de 44 px** (recomendado `46 px - 48 px`).
   * Quedan prohibidos los botones diminutos de 20-24 px que únicamente contienen un emoji o un ícono pequeño sin texto.
3. **Iconografía con Etiqueta Textual Explicativa:**
   * Las acciones principales deben acompañar el ícono con texto descriptivo legible: `"Detalles"`, `"Editar"`, `"Desactivar"`, `"Expediente"`, `"Llamar"`.
4. **Contraste Cromático de Alto Impacto (WCAG 2.1 AA):**
   * Fondo claro (`bg-white` o `bg-slate-50`) con texto principal oscuro (`text-slate-900` o `text-slate-800`).
   * Evitar grises deslavados (`text-slate-400` en párrafos) que dificultan la lectura a personas con gafas.

---

## 🏛️ 2. Arquitectura de Revelación Progresiva (Progressive Disclosure)

### El Principio del Primer Plano Limpio
* Las tablas principales y vistas de lista no deben sobrecargarse intentando mostrar 15 columnas al mismo tiempo.
* **Primer Plano (Tabla/Grid):** Muestra únicamente los datos esenciales para el reconocimiento inmediato y el estado del registro (máximo 4 a 5 columnas clave).
* **Segundo Plano (Submodales):** Los datos complementarios (expedientes familiares, asignaciones en cursos, histórico de asistencias, asistentes biométricos paso a paso) se descubren mediante **submodales dedicados**.

### Anatomía de un Submodal Accesible
1. **Cabecera Amplia:** Ícono temático de 48-56 px, título grande (`text-2xl font-extrabold`) y botón de cierre táctil (48 px) accesible con teclado (`Escape`).
2. **Cuerpo Espacioso:** Tarjetas informativas con bordes de 2 px, padding generoso (`p-5` a `p-6`) y scroll independiente si el contenido es extenso.
3. **Pie con Acción Clara:** Botón de confirmación o cierre (`min-h-[46px] px-6 text-base font-bold`).

---

## 🎨 3. Paleta Cromática y Tokens Semánticos

| Rol Semántico | Clases Tailwind (Fondo / Borde / Texto) | Uso Obligatorio |
| :--- | :--- | :--- |
| **Primario / Marca** | `bg-blue-600 hover:bg-blue-700 text-white` | Botones de guardado, acciones primarias, marcas institucionales |
| **Éxito / Presencia** | `bg-emerald-100 border-emerald-300 text-emerald-950` | Asistencia presente, huella activa, materia/usuario activo |
| **Alerta / Tardanza** | `bg-amber-100 border-amber-300 text-amber-950` | Marcación tardía, falta de huella, advertencias pedagógicas |
| **Peligro / Inactivo** | `bg-rose-100 border-rose-300 text-rose-950` | Ausente, registro inactivo, acciones destructivas (desactivar/eliminar) |
| **Académico / Ciclo** | `bg-indigo-100 border-indigo-300 text-indigo-950` | Grados, coordinación, cursos y bachilleratos |
| **Neutro / Fondo** | `bg-slate-50 border-slate-200 text-slate-900` | Superficies de lectura, contenedores y tarjetas secundarias |

---

## 📐 4. Escala Tipográfica Dinámica y Selector de Densidad (11pt / 12pt / 13pt)

Para dar libertad a los usuarios de elegir entre mayor densidad de información en pantalla o lectura descansada y accesible, el sistema cuenta con un selector de escala tipográfica ubicado en la barra lateral:

* **Modo Compacto (`11pt` / ~14.67 px base):** Diseñado para usuarios que prefieren ver la mayor cantidad de registros y columnas a la vez sin necesidad de desplazarse, optimizando la densidad en pantallas estándar.
* **Modo Estándar (`12pt` / 16 px base — Por Defecto):** Tamaño regular y equilibrado del estándar web para trabajo continuo.
* **Modo Grande (`13pt` / ~17.33 px base):** Pensado para directivos, docentes o personal que requiera mayor tamaño de letra y descanso visual.

### Proporciones de Elementos según la Escala Dinámica:
* **Títulos de Página:** `text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight` (escala proporcionalmente según rem).
* **Títulos de Sección / Modales:** `text-xl sm:text-2xl font-bold text-slate-900`.
* **Títulos de Tarjetas / Nombres:** `text-lg sm:text-xl font-bold text-slate-900`.
* **Texto Base / Tablas / Cuerpos:** `text-base text-slate-800 leading-relaxed font-normal`.
* **Metadatos Secundarios / Badges:** `text-sm font-bold uppercase tracking-wider`.

---

## 🗂️ 5. Patrón Estándar para Vistas de Gestión Tabular

Toda pantalla administrativa de gestión de catálogo (ej. Estudiantes, Tutores, Materias, Profesores) debe implementar esta estructura:

1. **Breadcrumb de Navegación:**
   `Inicio / [Módulo Superior] / [Vista Actual]` en `text-base text-slate-600 font-medium`.
2. **Cabecera de Página:**
   Título de 36 px, descripción clara y botonera con acción principal (`+ Crear Registro`, `min-h-[46px]`) y acceso a papelera/desactivados.
3. **Franja Superior de KPIs:**
   2 a 4 tarjetas métricas con números de 36-40 px que resumen el estado actual (Total Activos, En Uso, Inactivos).
4. **Buscador Predictivo en Tiempo Real:**
   Barra de búsqueda con altura de 48 px, ícono de lupa, botón de limpieza instantánea `✕` y contador dinámico (`X de Y registros visibles`).
5. **Tabla de Datos Accesible:**
   - Cabecera en `text-sm font-bold uppercase text-slate-700 bg-slate-50`.
   - Celdas con padding generoso (`p-4` a `p-5`).
   - Identificadores visuales con avatares o iniciales de 44 px con bordes nítidos.
   - Botonera de acciones por fila con alturas táctiles de 40-44 px.
6. **Estado Vacío Inteligente (Empty State):**
   Si la búsqueda no arroja coincidencias o la tabla está vacía, mostrar mensaje cordial en 18 px con botón grande de restablecimiento.

---

## 📋 6. Reglas de Formularios y Modales de Creación/Edición

* Los campos de texto `<input>` y selectores `<select>` deben contar con:
  - Altura mínima de `46 px - 48 px`.
  - Letra interna en `text-base text-slate-900 font-semibold`.
  - Borde visible `border border-slate-300` que al enfocar resalte con anillo azul `focus:ring-2 focus:ring-blue-500`.
  - Etiquetas (`<label>`) siempre visibles arriba del campo con `text-base font-bold text-slate-800 mb-1.5`.
* Mensajes de validación claros y legibles (nunca alertas microscópicas).

---

## 🚫 7. Lista Negra de Anti-patrones de Diseño en SISCO-EDU

* ❌ **Prohibido:** Tablas con más de 6 columnas horizontales que fuercen barras de desplazamiento ilegibles.
* ❌ **Prohibido:** Usar fuentes `text-[10px]` o `text-xs` en nombres de personas, materias o cédulas.
* ❌ **Prohibido:** Botones de acción que sean un único emoji de 16 px sin etiqueta o tooltip accesible.
* ❌ **Prohibido:** Campos de formulario con altura menor a 40 px.
* ❌ **Prohibido:** Modales con fuentes apretadas y sin scroll asistido para resoluciones medianas.
* ❌ **Prohibido:** Duplicar datos que pertenecen a otra vista o jerarquía (ej. Carga Horaria Semanal en el catálogo general de materias).
