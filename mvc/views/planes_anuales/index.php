<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$esProfesor = ($_SESSION['rol'] ?? '') === 'Profesor';
$rolUsuario = $_SESSION['rol'] ?? 'Usuario';
?>

<main class="flex-1 p-6 md:p-10 overflow-y-auto">
    <!-- Notificaciones Toast -->
    <div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <!-- ========================================== -->
    <!-- VISTA 1: CATÁLOGO DE PLANES ANUALES (MASTER) -->
    <!-- ========================================== -->
    <section id="vista-catalogo" class="transition-opacity duration-200">
        <!-- Encabezado de la Sección -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-slate-800 tracking-tight">
                    <?= $esProfesor ? 'Mis Planes Anuales Pedagógicos' : 'Gestión de Planes Anuales' ?>
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Estructura curricular oficial MEC Paraguay · 1 Hora Cátedra (HC) = 40 minutos.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button id="btn-abrir-crear-plan" type="button" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow-sm shadow-indigo-100 transition-all hover:shadow-md">
                    <span>➕</span>
                    <span>Crear Nuevo Plan Anual</span>
                </button>
            </div>
        </div>

        <!-- Barra de Estadísticas Rápidas -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold">
                    📚
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Total de Planes</span>
                    <p id="stat-total-planes" class="text-xl font-bold text-slate-800">0</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                    📝
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Lecciones Planificadas</span>
                    <p id="stat-total-temas" class="text-xl font-bold text-slate-800">0</p>
                </div>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold">
                    ⏱️
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Unidad de Tiempo</span>
                    <p class="text-sm font-bold text-slate-800 mt-1">40 min / Hora Cátedra</p>
                </div>
            </div>
        </div>

        <!-- Buscador y Filtros -->
        <div class="bg-white rounded-xl border border-slate-200 p-4 mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="w-full sm:w-96 relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">🔍</span>
                <input id="input-buscar-plan" type="text" placeholder="Buscar por materia, curso o año..." class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="text-xs text-slate-500 font-medium">
                Selecciona un plan para ver y editar su desglose curricular detallado.
            </div>
        </div>

        <!-- Grid de Tarjetas de Planes -->
        <div id="grid-planes" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="col-span-full py-16 text-center text-slate-400">
                <div class="animate-spin inline-block w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mb-3"></div>
                <p class="text-sm font-medium">Cargando catálogo de planes anuales...</p>
            </div>
        </div>
    </section>

    <!-- ========================================== -->
    <!-- VISTA 2: DETALLE / EDITOR CURRICULAR (DETAIL) -->
    <!-- ========================================== -->
    <section id="vista-detalle" class="hidden transition-opacity duration-200 space-y-6">
        <!-- Barra de Navegación Superior -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
            <button id="btn-volver-catalogo" type="button" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-indigo-600 transition-colors">
                <span>←</span>
                <span>Volver al Catálogo de Planes</span>
            </button>

            <div class="flex items-center gap-3">
                <button id="btn-editar-plan-header" type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 rounded-lg text-xs font-semibold shadow-sm transition-colors">
                    <span>✏️</span>
                    <span>Editar Encabezado</span>
                </button>
                <button id="btn-eliminar-plan-actual" type="button" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-50 border border-rose-200 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold shadow-sm transition-colors">
                    <span>🗑️</span>
                    <span>Eliminar Plan</span>
                </button>
                <button id="btn-nueva-unidad" type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors">
                    <span>➕</span>
                    <span>Nueva Unidad Temática</span>
                </button>
            </div>
        </div>

        <!-- Encabezado Institucional del Plan -->
        <div id="plan-header-card" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pb-6 border-b border-slate-100">
                <div>
                    <span class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Institución</span>
                    <p id="det-institucion" class="text-sm font-bold text-slate-800 mt-0.5">-</p>
                </div>
                <div>
                    <span class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Materia y Curso</span>
                    <p id="det-materia-curso" class="text-sm font-bold text-indigo-700 mt-0.5">-</p>
                </div>
                <div>
                    <span class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Profesor Encargado</span>
                    <p id="det-profesor" class="text-sm font-bold text-slate-800 mt-0.5">-</p>
                </div>
                <div>
                    <span class="text-[11px] uppercase tracking-wider text-slate-400 font-semibold">Carga Horaria / Turno</span>
                    <p id="det-carga-turno" class="text-sm font-bold text-slate-800 mt-0.5">-</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-xs font-bold text-indigo-900 uppercase tracking-wide flex items-center gap-1.5">
                        <span>🎯</span> Competencia General
                    </span>
                    <p id="det-comp-general" class="text-xs text-slate-600 mt-2 leading-relaxed whitespace-pre-line">-</p>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <span class="text-xs font-bold text-indigo-900 uppercase tracking-wide flex items-center gap-1.5">
                        <span>📌</span> Competencia Específica
                    </span>
                    <p id="det-comp-especifica" class="text-xs text-slate-600 mt-2 leading-relaxed whitespace-pre-line">-</p>
                </div>
            </div>

            <div id="det-obs-wrapper" class="mt-4 pt-4 border-t border-slate-100 hidden">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wide">Observaciones Generales</span>
                <p id="det-observaciones" class="text-xs text-slate-600 mt-1 italic">-</p>
            </div>
        </div>

        <!-- Contenedor Dinámico de Unidades, Capacidades y Temas -->
        <div id="unidades-container" class="space-y-6">
            <div class="p-12 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
                <div class="animate-spin inline-block w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mb-3"></div>
                <p class="text-sm font-medium">Cargando desglose pedagógico...</p>
            </div>
        </div>
    </section>
</main>

<!-- ======================================================== -->
<!-- MODAL 1: CREAR / EDITAR ENCABEZADO DEL PLAN ANUAL        -->
<!-- ======================================================== -->
<div id="modal-plan" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl my-8 overflow-hidden animate-scale-up">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <h3 id="modal-plan-title" class="font-bold text-slate-800 text-base">Crear Nuevo Plan Anual</h3>
            <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
        </div>

        <form id="form-plan" class="p-6 space-y-4">
            <input type="hidden" id="plan-id" name="id_plan">

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Materia y Grado / Curso Asignado *</label>
                <select id="plan-id-asignacion" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">Cargando asignaciones...</option>
                </select>
                <span class="text-[11px] text-slate-400 mt-1 block">Solo se muestran materias asignadas activas en el colegio.</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Año Lectivo *</label>
                    <input type="number" id="plan-anio" required min="2020" max="2035" value="2026" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Turno *</label>
                    <select id="plan-turno" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="MAÑANA">Mañana</option>
                        <option value="TARDE">Tarde</option>
                        <option value="NOCHE">Noche</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Institución Educativa</label>
                <input type="text" id="plan-institucion" value="COLEGIO PRIV. PRESB. SAN PABLO" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Competencia General (MEC)</label>
                <textarea id="plan-comp-general" rows="3" placeholder="Formular e implementar soluciones técnicas y éticas acordes al perfil de egreso..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Competencia Específica de la Disciplina</label>
                <textarea id="plan-comp-especifica" rows="3" placeholder="Aplica las normativas, métodos de análisis y herramientas prácticas del área..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Observaciones</label>
                <input type="text" id="plan-observaciones" placeholder="Planificación ajustada a cronograma institucional" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" class="btn-close-modal px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 font-medium">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm">Guardar Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 2: CREAR / EDITAR UNIDAD TEMÁTICA                  -->
<!-- ======================================================== -->
<div id="modal-unidad" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden animate-scale-up">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <h3 id="modal-unidad-title" class="font-bold text-slate-800 text-base">Nueva Unidad Temática</h3>
            <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
        </div>

        <form id="form-unidad" class="p-6 space-y-4">
            <input type="hidden" id="unidad-id" name="id_unidad">

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Título de la Unidad *</label>
                <input type="text" id="unidad-nombre" required placeholder="Ej: Unidad I: Fundamentos y Conceptos Clave" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Período / Tiempo Estimado</label>
                    <input type="text" id="unidad-tiempo" placeholder="Ej: Febrero a Abril" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Orden Secuencial</label>
                    <input type="number" id="unidad-orden" min="1" max="50" value="1" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" class="btn-close-modal px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 font-medium">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm">Guardar Unidad</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 3: CREAR / EDITAR CAPACIDAD MEC                    -->
<!-- ======================================================== -->
<div id="modal-capacidad" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg overflow-hidden animate-scale-up">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <h3 id="modal-capacidad-title" class="font-bold text-slate-800 text-base">Capacidad Pedagógica MEC</h3>
            <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
        </div>

        <form id="form-capacidad" class="p-6 space-y-4">
            <input type="hidden" id="capacidad-id" name="id_capacidad">
            <input type="hidden" id="capacidad-id-unidad" name="id_unidad">

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Descripción de la Capacidad (MEC) *</label>
                <textarea id="capacidad-desc" required rows="4" placeholder="Comprende la importancia de los estados financieros en la gestión moderna..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Orden Secuencial</label>
                <input type="number" id="capacidad-orden" min="1" max="50" value="1" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" class="btn-close-modal px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 font-medium">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm">Guardar Capacidad</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 4: CREAR / EDITAR TEMA / LECCIÓN DETALLADA         -->
<!-- ======================================================== -->
<div id="modal-tema" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl my-8 overflow-hidden animate-scale-up">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <h3 id="modal-tema-title" class="font-bold text-slate-800 text-base">Planificar Lección / Tema</h3>
            <button type="button" class="btn-close-modal text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
        </div>

        <form id="form-tema" class="p-6 space-y-4">
            <input type="hidden" id="tema-id" name="id_tema">

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Capacidad MEC Asociada *</label>
                <select id="tema-id-capacidad" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">Seleccione capacidad...</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tema / Contenido de la Lección *</label>
                <input type="text" id="tema-titulo" required placeholder="Ej: 1.1 El rol del administrador financiero en la era digital" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Horas Cátedra (HC)
                        <span id="label-duracion-minutos" class="text-indigo-600 font-semibold normal-case">(= 80 min)</span>
                    </label>
                    <input type="number" id="tema-horas" min="1" max="10" step="1" value="2" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Fecha Programada de la Clase</label>
                    <input type="date" id="tema-fecha" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                    Indicadores de Logro
                    <span class="text-slate-400 font-normal">(Escribe un indicador por línea)</span>
                </label>
                <textarea id="tema-indicadores" rows="3" placeholder="- Reconoce las variables financieras&#10;- Calcula el presupuesto operativo&#10;- Participa activamente en clase" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono text-xs"></textarea>
            </div>

            <!-- Procedimientos e Instrumentos de Evaluación -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-100">
                <div>
                    <span class="block text-xs font-bold text-slate-700 uppercase mb-2">Procedimientos Evaluativos</span>
                    <div id="container-procedimientos" class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                        <span class="text-xs text-slate-400">Cargando procedimientos...</span>
                    </div>
                </div>

                <div>
                    <span class="block text-xs font-bold text-slate-700 uppercase mb-2">Instrumentos Evaluativos</span>
                    <div id="container-instrumentos" class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                        <span class="text-xs text-slate-400">Cargando instrumentos...</span>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" class="btn-close-modal px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 font-medium">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm">Guardar Lección</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- LÓGICA JAVASCRIPT: MASTER-DETAIL LAZY LOADING Y CRUD     -->
<!-- ======================================================== -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Endpoints
    const API_LISTAR = `${window.BASE_URL}src/api/PlanesAnuales/listar.php`;
    const API_OBTENER = `${window.BASE_URL}src/api/PlanesAnuales/obtener.php`;
    const API_GUARDAR_PLAN = `${window.BASE_URL}src/api/PlanesAnuales/guardar_plan.php`;
    const API_ELIMINAR_PLAN = `${window.BASE_URL}src/api/PlanesAnuales/eliminar_plan.php`;
    const API_GUARDAR_UNIDAD = `${window.BASE_URL}src/api/PlanesAnuales/guardar_unidad.php`;
    const API_ELIMINAR_UNIDAD = `${window.BASE_URL}src/api/PlanesAnuales/eliminar_unidad.php`;
    const API_GUARDAR_CAPACIDAD = `${window.BASE_URL}src/api/PlanesAnuales/guardar_capacidad.php`;
    const API_ELIMINAR_CAPACIDAD = `${window.BASE_URL}src/api/PlanesAnuales/eliminar_capacidad.php`;
    const API_GUARDAR_TEMA = `${window.BASE_URL}src/api/PlanesAnuales/guardar_tema.php`;
    const API_ELIMINAR_TEMA = `${window.BASE_URL}src/api/PlanesAnuales/eliminar_tema.php`;
    const API_MIS_ASIGNACIONES = `${window.BASE_URL}src/api/PlanesAnuales/mis_asignaciones.php`;
    const API_RECURSOS_EVALUACION = `${window.BASE_URL}src/api/PlanesAnuales/recursos_evaluacion.php`;

    // Vistas
    const vistaCatalogo = document.getElementById('vista-catalogo');
    const vistaDetalle = document.getElementById('vista-detalle');
    const gridPlanes = document.getElementById('grid-planes');
    const unidadesContainer = document.getElementById('unidades-container');
    const inputBuscar = document.getElementById('input-buscar-plan');

    // Botones maestros
    const btnVolverCatalogo = document.getElementById('btn-volver-catalogo');
    const btnAbrirCrearPlan = document.getElementById('btn-abrir-crear-plan');
    const btnEditarPlanHeader = document.getElementById('btn-editar-plan-header');
    const btnEliminarPlanActual = document.getElementById('btn-eliminar-plan-actual');
    const btnNuevaUnidad = document.getElementById('btn-nueva-unidad');

    // Modales
    const modalPlan = document.getElementById('modal-plan');
    const modalUnidad = document.getElementById('modal-unidad');
    const modalCapacidad = document.getElementById('modal-capacidad');
    const modalTema = document.getElementById('modal-tema');

    // Formularios
    const formPlan = document.getElementById('form-plan');
    const formUnidad = document.getElementById('form-unidad');
    const formCapacidad = document.getElementById('form-capacidad');
    const formTema = document.getElementById('form-tema');

    // Selects dinámicos
    const selectAsignacion = document.getElementById('plan-id-asignacion');
    const selectTemaCapacidad = document.getElementById('tema-id-capacidad');
    const containerProcedimientos = document.getElementById('container-procedimientos');
    const containerInstrumentos = document.getElementById('container-instrumentos');

    // Horas Cátedra live badge
    const inputHoras = document.getElementById('tema-horas');
    const labelMinutos = document.getElementById('label-duracion-minutos');

    inputHoras.addEventListener('input', () => {
        const val = parseFloat(inputHoras.value) || 1;
        labelMinutos.textContent = `(= ${val * 40} min)`;
    });

    // Estados en memoria
    let planesCache = [];
    let planActual = null;
    let recursosCache = null;

    // Toast feedback
    function mostrarToast(mensaje, tipo = 'success') {
        if (window.Toast) {
            if (tipo === 'error') return Toast.error(mensaje);
            if (tipo === 'warning') return Toast.warning(mensaje);
            return Toast.success(mensaje);
        }
        console.log(`[${tipo}] ${mensaje}`);
    }

    // Modal close listeners
    document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            modalPlan.classList.add('hidden');
            modalUnidad.classList.add('hidden');
            modalCapacidad.classList.add('hidden');
            modalTema.classList.add('hidden');
        });
    });

    // Cargar Recursos de Evaluación (procedimientos e instrumentos)
    function cargarRecursosEvaluacion() {
        if (recursosCache) return Promise.resolve(recursosCache);
        return fetch(API_RECURSOS_EVALUACION)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    recursosCache = res.data;
                    renderizarCheckboxesRecursos(res.data);
                    return res.data;
                }
            })
            .catch(e => console.error("Error al cargar recursos de evaluación", e));
    }

    function renderizarCheckboxesRecursos(data) {
        if (data.procedimientos) {
            containerProcedimientos.innerHTML = data.procedimientos.map(p => `
                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:text-indigo-600">
                    <input type="checkbox" name="procedimientos[]" value="${p.id_procedimiento}" class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                    <span>${p.nombre}</span>
                </label>
            `).join('');
        }
        if (data.instrumentos) {
            containerInstrumentos.innerHTML = data.instrumentos.map(i => `
                <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer hover:text-indigo-600">
                    <input type="checkbox" name="instrumentos[]" value="${i.id_instrumento}" class="rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                    <span>${i.nombre}</span>
                </label>
            `).join('');
        }
    }

    // Cargar Asignaciones del Docente para el select del plan
    function cargarAsignacionesDocente(selectedId = null) {
        return fetch(API_MIS_ASIGNACIONES)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    selectAsignacion.innerHTML = '<option value="">Seleccione materia y curso...</option>' +
                        res.data.map(a => `
                            <option value="${a.id_asignacion}" ${selectedId && a.id_asignacion == selectedId ? 'selected' : ''}>
                                ${a.materia_nombre} — ${a.grado_nombre || 'Sin Grado'} (${a.seccion || 'Única'}) [${a.carga_horaria || 2} HC]
                            </option>
                        `).join('');
                } else {
                    selectAsignacion.innerHTML = '<option value="">No hay asignaciones disponibles</option>';
                }
            })
            .catch(e => console.error("Error al cargar asignaciones", e));
    }

    // ==========================================
    // 1. CARGA INICIAL DEL CATÁLOGO DE PLANES
    // ==========================================
    function cargarCatalogoPlanes() {
        gridPlanes.innerHTML = `
            <div class="col-span-full py-16 text-center text-slate-400">
                <div class="animate-spin inline-block w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mb-3"></div>
                <p class="text-sm font-medium">Cargando catálogo de planes anuales...</p>
            </div>
        `;

        fetch(API_LISTAR)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    planesCache = res.data || [];
                    document.getElementById('stat-total-planes').textContent = planesCache.length;
                    
                    const totalTemas = planesCache.reduce((acc, p) => acc + parseInt(p.total_temas || 0), 0);
                    document.getElementById('stat-total-temas').textContent = totalTemas;

                    renderizarCatalogo(planesCache);
                } else {
                    gridPlanes.innerHTML = `<div class="col-span-full p-8 text-center text-rose-500 font-semibold">${res.message}</div>`;
                }
            })
            .catch(err => {
                gridPlanes.innerHTML = `<div class="col-span-full p-8 text-center text-rose-500 font-semibold">Error de conexión al cargar planes: ${err.message}</div>`;
            });
    }

    function renderizarCatalogo(planes) {
        if (!planes || planes.length === 0) {
            gridPlanes.innerHTML = `
                <div class="col-span-full py-16 px-4 text-center bg-white rounded-2xl border border-slate-200">
                    <span class="text-4xl block mb-3">📋</span>
                    <h3 class="text-base font-bold text-slate-800">No hay planes anuales registrados</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        Aún no tienes planes anuales asignados o creados. Puedes crear tu primer plan anual pedagógico ahora.
                    </p>
                    <button type="button" onclick="document.getElementById('btn-abrir-crear-plan').click()" class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors">
                        ➕ Crear Mi Primer Plan Anual
                    </button>
                </div>
            `;
            return;
        }

        gridPlanes.innerHTML = planes.map(p => {
            const materia = p.materia_nombre || p.materia_fuente || 'Materia';
            const curso = p.grado_nombre || p.curso_fuente || 'Curso General';
            const seccion = p.seccion ? ` · Sec. ${p.seccion}` : '';
            const turno = p.turno_fuente || p.turno || 'MAÑANA';
            const anio = p.anio || 2026;
            const profesor = p.profesor_nombre ? `${p.profesor_nombre} ${p.profesor_apellido}` : (p.profesor_fuente || 'Docente');
            const temas = parseInt(p.total_temas || 0);
            const carga = p.carga_horaria || 2;

            return `
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition-shadow overflow-hidden flex flex-col justify-between">
                    <div class="p-6">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700">
                                ${turno} · ${anio}
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                ${carga} HC/sem (${carga * 40}m)
                            </span>
                        </div>

                        <h3 class="text-base font-bold text-slate-800 leading-snug hover:text-indigo-600 transition-colors cursor-pointer" onclick="window.abrirPlanDetalle(${p.id_plan})">
                            ${materia}
                        </h3>
                        <p class="text-xs font-semibold text-indigo-700 mt-1">
                            ${curso}${seccion}
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                            <span>👨‍🏫</span> ${profesor}
                        </p>

                        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span class="text-slate-500 font-medium">Contenido:</span>
                            <span class="font-bold text-slate-700 bg-slate-50 px-2 py-0.5 rounded border border-slate-100">
                                📝 ${temas} lección${temas === 1 ? '' : 'es'}
                            </span>
                        </div>
                    </div>

                    <div class="px-6 py-3.5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between gap-2">
                        <button type="button" onclick="window.abrirPlanDetalle(${p.id_plan})" class="flex-1 py-1.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors text-center">
                            📖 Abrir Plan
                        </button>
                        <button type="button" onclick="window.abrirEditarPlanHeader(${p.id_plan})" title="Editar Encabezado" class="p-1.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 rounded-lg text-xs transition-colors">
                            ✏️
                        </button>
                        <button type="button" onclick="window.confirmarEliminarPlan(${p.id_plan})" title="Eliminar Plan" class="p-1.5 bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 rounded-lg text-xs transition-colors">
                            🗑️
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Buscador interactivo
    inputBuscar.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        if (!query) {
            renderizarCatalogo(planesCache);
            return;
        }
        const filtrados = planesCache.filter(p => {
            const mat = (p.materia_nombre || p.materia_fuente || '').toLowerCase();
            const cur = (p.grado_nombre || p.curso_fuente || '').toLowerCase();
            const prof = (p.profesor_nombre || p.profesor_fuente || '').toLowerCase();
            const anio = (p.anio || '').toString();
            return mat.includes(query) || cur.includes(query) || prof.includes(query) || anio.includes(query);
        });
        renderizarCatalogo(filtrados);
    });

    // ==========================================
    // 2. VISTA DE DETALLE / LAZY LOADING PEDAGÓGICO
    // ==========================================
    window.abrirPlanDetalle = function(idPlan) {
        vistaCatalogo.classList.add('hidden');
        vistaDetalle.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });

        unidadesContainer.innerHTML = `
            <div class="p-12 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
                <div class="animate-spin inline-block w-8 h-8 border-4 border-indigo-500 border-t-transparent rounded-full mb-3"></div>
                <p class="text-sm font-medium">Cargando árbol curricular completo...</p>
            </div>
        `;

        fetch(`${API_OBTENER}?id_plan=${idPlan}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    planActual = res.data;
                    renderizarDetallePlan(res.data);
                } else {
                    unidadesContainer.innerHTML = `<div class="p-8 text-center text-rose-500 font-semibold">${res.message}</div>`;
                }
            })
            .catch(err => {
                unidadesContainer.innerHTML = `<div class="p-8 text-center text-rose-500 font-semibold">Error al obtener detalle del plan: ${err.message}</div>`;
            });
    };

    function renderizarDetallePlan(plan) {
        // Cabecera institucional
        document.getElementById('det-institucion').textContent = plan.institucion_fuente || 'COLEGIO PRIV. PRESB. SAN PABLO';
        document.getElementById('det-materia-curso').textContent = `${plan.materia_nombre || plan.materia_fuente || 'Materia'} — ${plan.grado_nombre || plan.curso_fuente || 'Curso'}`;
        document.getElementById('det-profesor').textContent = plan.profesor_nombre ? `${plan.profesor_nombre} ${plan.profesor_apellido}` : (plan.profesor_fuente || '-');

        const cargaHC = plan.carga_horaria || 2;
        const duracionMin = cargaHC * 40;
        document.getElementById('det-carga-turno').textContent = `${cargaHC} HC (${duracionMin} min) / ${plan.turno_fuente || 'MAÑANA'}`;

        document.getElementById('det-comp-general').textContent = plan.competencia_general || 'Competencia general orientada a la formación integral técnica y académica.';
        document.getElementById('det-comp-especifica').textContent = plan.competencia_especifica || 'Aplica los conceptos, procedimientos e instrumentos correspondientes al ámbito de estudio.';

        const obsWrapper = document.getElementById('det-obs-wrapper');
        if (plan.observaciones) {
            obsWrapper.classList.remove('hidden');
            document.getElementById('det-observaciones').textContent = plan.observaciones;
        } else {
            obsWrapper.classList.add('hidden');
        }

        // Llenar selector de capacidades para el modal de nuevo tema
        selectTemaCapacidad.innerHTML = '<option value="">Seleccione capacidad...</option>';
        if (plan.unidades) {
            plan.unidades.forEach(u => {
                if (u.capacidades) {
                    u.capacidades.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id_capacidad;
                        opt.textContent = `${u.nombre ? u.nombre + ': ' : ''}${c.descripcion.substring(0, 75)}...`;
                        selectTemaCapacidad.appendChild(opt);
                    });
                }
            });
        }

        // Renderizar Unidades
        if (!plan.unidades || plan.unidades.length === 0) {
            unidadesContainer.innerHTML = `
                <div class="p-12 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
                    <span class="text-4xl block mb-3">📖</span>
                    <h3 class="text-base font-bold text-slate-800">El plan no tiene unidades temáticas</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        Comienza agregando la primera unidad temática del programa curricular (ej: Unidad I).
                    </p>
                    <button type="button" onclick="window.abrirModalNuevaUnidad()" class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-sm transition-colors">
                        ➕ Agregar Unidad Temática
                    </button>
                </div>
            `;
            return;
        }

        let html = '';
        plan.unidades.forEach(u => {
            html += `
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <!-- Cabecera de la Unidad -->
                    <div class="bg-gradient-to-r from-slate-50 to-indigo-50/40 p-4 sm:p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <span class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">Unidad Temática</span>
                            <h3 class="text-base font-bold text-slate-800">${u.nombre || 'Unidad de Aprendizaje'}</h3>
                            ${u.tiempo_texto ? `<span class="text-xs text-slate-500 font-medium">📅 Período: ${u.tiempo_texto}</span>` : ''}
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" onclick="window.abrirModalNuevaCapacidad(${u.id_unidad})" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors">
                                <span>➕</span> Capacidad
                            </button>
                            <button type="button" onclick="window.abrirModalEditarUnidad(${u.id_unidad})" title="Editar Unidad" class="p-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-lg text-xs transition-colors">
                                ✏️
                            </button>
                            <button type="button" onclick="window.confirmarEliminarUnidad(${u.id_unidad})" title="Eliminar Unidad" class="p-1.5 bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 rounded-lg text-xs transition-colors">
                                🗑️
                            </button>
                        </div>
                    </div>

                    <!-- Capacidades dentro de la Unidad -->
                    <div class="p-5 space-y-6">
            `;

            if (u.capacidades && u.capacidades.length > 0) {
                u.capacidades.forEach(c => {
                    html += `
                        <div class="border-l-4 border-indigo-500 pl-4 space-y-3 bg-slate-50/50 p-3 rounded-r-xl border-y border-r border-slate-100">
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-wider">Capacidad Pedagógica MEC</span>
                                    <p class="text-sm font-semibold text-slate-800 leading-snug mt-0.5">${c.descripcion}</p>
                                </div>
                                <div class="flex items-center gap-1.5 self-end sm:self-auto shrink-0">
                                    <button type="button" onclick="window.abrirModalNuevoTema(${c.id_capacidad})" class="inline-flex items-center gap-1 px-2.5 py-1 bg-white border border-indigo-200 hover:bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-lg shadow-sm transition-colors">
                                        <span>➕</span> Lección
                                    </button>
                                    <button type="button" onclick="window.abrirModalEditarCapacidad(${c.id_capacidad})" title="Editar Capacidad" class="p-1 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-lg text-xs transition-colors">
                                        ✏️
                                    </button>
                                    <button type="button" onclick="window.confirmarEliminarCapacidad(${c.id_capacidad})" title="Eliminar Capacidad" class="p-1 bg-white border border-rose-200 hover:bg-rose-50 text-rose-600 rounded-lg text-xs transition-colors">
                                        🗑️
                                    </button>
                                </div>
                            </div>

                            <!-- Tabla de temas/lecciones -->
                            <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                                        <tr>
                                            <th class="py-2.5 px-3 font-semibold">Tema / Contenido</th>
                                            <th class="py-2.5 px-3 font-semibold w-28">Duración</th>
                                            <th class="py-2.5 px-3 font-semibold w-32">Fecha Clase</th>
                                            <th class="py-2.5 px-3 font-semibold">Indicadores de Logro</th>
                                            <th class="py-2.5 px-3 font-semibold w-36">Evaluación</th>
                                            <th class="py-2.5 px-3 font-semibold w-20 text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                    `;

                    if (c.temas && c.temas.length > 0) {
                        c.temas.forEach(t => {
                            const hc = parseFloat(t.horas_catedra || 1);
                            const min = hc * 40;
                            const fecha = t.fecha_inicio ? t.fecha_inicio : 'Por definir';

                            const indHtml = t.indicadores && t.indicadores.length > 0
                                ? `<ul class="list-disc pl-4 space-y-0.5 text-slate-600">${t.indicadores.map(i => `<li>${i.descripcion}</li>`).join('')}</ul>`
                                : '<span class="text-slate-400 italic">Sin indicadores cargados</span>';

                            const evalHtml = [
                                ...(t.procedimientos || []),
                                ...(t.instrumentos || [])
                            ].map(item => `<span class="inline-block px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] mr-1 mb-1">${item}</span>`).join('') || '<span class="text-slate-400 italic">Por definir</span>';

                            html += `
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3 px-3 font-semibold text-slate-800">${t.titulo}</td>
                                    <td class="py-3 px-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700">
                                            ${hc} HC (${min}m)
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-600 font-mono text-[11px]">
                                        📅 ${fecha}
                                    </td>
                                    <td class="py-3 px-3">${indHtml}</td>
                                    <td class="py-3 px-3">${evalHtml}</td>
                                    <td class="py-3 px-3 text-center">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" onclick="window.abrirModalEditarTema(${t.id_tema})" title="Editar Lección" class="p-1 hover:bg-slate-100 text-slate-600 rounded transition-colors">
                                                ✏️
                                            </button>
                                            <button type="button" onclick="window.confirmarEliminarTema(${t.id_tema})" title="Eliminar Lección" class="p-1 hover:bg-rose-50 text-rose-600 rounded transition-colors">
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        html += `<tr><td colspan="6" class="py-4 px-3 text-center text-slate-400 italic">No hay temas programados para esta capacidad aún. Haz clic en "+ Lección" para programar.</td></tr>`;
                    }

                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                });
            } else {
                html += `
                    <div class="p-6 text-center text-slate-400 bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
                        <p class="text-xs">No hay capacidades registradas en esta unidad temática.</p>
                        <button type="button" onclick="window.abrirModalNuevaCapacidad(${u.id_unidad})" class="mt-2 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            ➕ Agregar Capacidad MEC
                        </button>
                    </div>
                `;
            }

            html += `
                    </div>
                </div>
            `;
        });

        unidadesContainer.innerHTML = html;
    }

    // Volver al Catálogo
    btnVolverCatalogo.addEventListener('click', () => {
        vistaDetalle.classList.add('hidden');
        vistaCatalogo.classList.remove('hidden');
        cargarCatalogoPlanes();
    });

    // ==========================================
    // 3. CRUD DE PLANES ANUALES (CABECERA)
    // ==========================================
    btnAbrirCrearPlan.addEventListener('click', () => {
        document.getElementById('modal-plan-title').textContent = 'Crear Nuevo Plan Anual';
        formPlan.reset();
        document.getElementById('plan-id').value = '';
        document.getElementById('plan-anio').value = '2026';
        cargarAsignacionesDocente().then(() => {
            modalPlan.classList.remove('hidden');
        });
    });

    window.abrirEditarPlanHeader = function(idPlan) {
        const plan = (planActual && planActual.id_plan == idPlan) ? planActual : planesCache.find(p => p.id_plan == idPlan);
        if (!plan) return;

        document.getElementById('modal-plan-title').textContent = 'Editar Encabezado del Plan';
        document.getElementById('plan-id').value = plan.id_plan;
        document.getElementById('plan-anio').value = plan.anio || 2026;
        document.getElementById('plan-turno').value = plan.turno_fuente || 'MAÑANA';
        document.getElementById('plan-institucion').value = plan.institucion_fuente || 'COLEGIO PRIV. PRESB. SAN PABLO';
        document.getElementById('plan-comp-general').value = plan.competencia_general || '';
        document.getElementById('plan-comp-especifica').value = plan.competencia_especifica || '';
        document.getElementById('plan-observaciones').value = plan.observaciones || '';

        cargarAsignacionesDocente(plan.id_asignacion).then(() => {
            modalPlan.classList.remove('hidden');
        });
    };

    btnEditarPlanHeader.addEventListener('click', () => {
        if (planActual) window.abrirEditarPlanHeader(planActual.id_plan);
    });

    formPlan.addEventListener('submit', (e) => {
        e.preventDefault();
        const payload = {
            id_plan: document.getElementById('plan-id').value || null,
            id_asignacion: parseInt(selectAsignacion.value),
            anio: parseInt(document.getElementById('plan-anio').value),
            turno_fuente: document.getElementById('plan-turno').value,
            institucion_fuente: document.getElementById('plan-institucion').value.trim(),
            competencia_general: document.getElementById('plan-comp-general').value.trim(),
            competencia_especifica: document.getElementById('plan-comp-especifica').value.trim(),
            observaciones: document.getElementById('plan-observaciones').value.trim()
        };

        fetch(API_GUARDAR_PLAN, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                modalPlan.classList.add('hidden');
                mostrarToast(res.message);
                if (planActual && planActual.id_plan == payload.id_plan) {
                    window.abrirPlanDetalle(planActual.id_plan);
                } else {
                    cargarCatalogoPlanes();
                }
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    });

    window.confirmarEliminarPlan = async function(idPlan) {
        const confirmado = await SISCO.confirm({
            title: '¿Eliminar Plan Anual?',
            message: '¿Estás seguro de eliminar este plan anual completo junto con todas sus unidades, capacidades y temas? Esta acción no se puede deshacer.',
            confirmText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger'
        });
        if (!confirmado) return;

        fetch(API_ELIMINAR_PLAN, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_plan: idPlan })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                mostrarToast(res.message);
                if (!vistaDetalle.classList.contains('hidden')) {
                    btnVolverCatalogo.click();
                } else {
                    cargarCatalogoPlanes();
                }
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    };

    btnEliminarPlanActual.addEventListener('click', () => {
        if (planActual) window.confirmarEliminarPlan(planActual.id_plan);
    });

    // ==========================================
    // 4. CRUD DE UNIDADES TEMÁTICAS
    // ==========================================
    window.abrirModalNuevaUnidad = function() {
        if (!planActual) return;
        document.getElementById('modal-unidad-title').textContent = 'Nueva Unidad Temática';
        formUnidad.reset();
        document.getElementById('unidad-id').value = '';
        const siguienteOrden = (planActual.unidades ? planActual.unidades.length : 0) + 1;
        document.getElementById('unidad-orden').value = siguienteOrden;
        document.getElementById('unidad-nombre').value = `Unidad ${siguienteOrden}: `;
        modalUnidad.classList.remove('hidden');
    };

    btnNuevaUnidad.addEventListener('click', () => {
        window.abrirModalNuevaUnidad();
    });

    window.abrirModalEditarUnidad = function(idUnidad) {
        if (!planActual || !planActual.unidades) return;
        const uni = planActual.unidades.find(u => u.id_unidad == idUnidad);
        if (!uni) return;

        document.getElementById('modal-unidad-title').textContent = 'Editar Unidad Temática';
        document.getElementById('unidad-id').value = uni.id_unidad;
        document.getElementById('unidad-nombre').value = uni.nombre || '';
        document.getElementById('unidad-tiempo').value = uni.tiempo_texto || '';
        document.getElementById('unidad-orden').value = uni.orden || 1;
        modalUnidad.classList.remove('hidden');
    };

    formUnidad.addEventListener('submit', (e) => {
        e.preventDefault();
        const payload = {
            id_plan: planActual.id_plan,
            id_unidad: document.getElementById('unidad-id').value || null,
            nombre: document.getElementById('unidad-nombre').value.trim(),
            tiempo_texto: document.getElementById('unidad-tiempo').value.trim(),
            orden: parseInt(document.getElementById('unidad-orden').value) || 1
        };

        fetch(API_GUARDAR_UNIDAD, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                modalUnidad.classList.add('hidden');
                mostrarToast(res.message);
                window.abrirPlanDetalle(planActual.id_plan);
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    });

    window.confirmarEliminarUnidad = async function(idUnidad) {
        const confirmado = await SISCO.confirm({
            title: '¿Eliminar Unidad Temática?',
            message: '¿Eliminar esta unidad temática y todas sus capacidades y temas asociados?',
            confirmText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger'
        });
        if (!confirmado) return;
        fetch(API_ELIMINAR_UNIDAD, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_unidad: idUnidad })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                mostrarToast(res.message);
                window.abrirPlanDetalle(planActual.id_plan);
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    };

    // ==========================================
    // 5. CRUD DE CAPACIDADES MEC
    // ==========================================
    window.abrirModalNuevaCapacidad = function(idUnidad) {
        formCapacidad.reset();
        document.getElementById('modal-capacidad-title').textContent = 'Nueva Capacidad MEC';
        document.getElementById('capacidad-id').value = '';
        document.getElementById('capacidad-id-unidad').value = idUnidad;
        modalCapacidad.classList.remove('hidden');
    };

    window.abrirModalEditarCapacidad = function(idCapacidad) {
        if (!planActual || !planActual.unidades) return;
        let capEncontrada = null;
        let unidadPadre = null;
        for (const u of planActual.unidades) {
            const c = (u.capacidades || []).find(x => x.id_capacidad == idCapacidad);
            if (c) {
                capEncontrada = c;
                unidadPadre = u;
                break;
            }
        }
        if (!capEncontrada) return;

        document.getElementById('modal-capacidad-title').textContent = 'Editar Capacidad MEC';
        document.getElementById('capacidad-id').value = capEncontrada.id_capacidad;
        document.getElementById('capacidad-id-unidad').value = unidadPadre.id_unidad;
        document.getElementById('capacidad-desc').value = capEncontrada.descripcion || '';
        document.getElementById('capacidad-orden').value = capEncontrada.orden || 1;
        modalCapacidad.classList.remove('hidden');
    };

    formCapacidad.addEventListener('submit', (e) => {
        e.preventDefault();
        const payload = {
            id_unidad: parseInt(document.getElementById('capacidad-id-unidad').value),
            id_capacidad: document.getElementById('capacidad-id').value || null,
            descripcion: document.getElementById('capacidad-desc').value.trim(),
            orden: parseInt(document.getElementById('capacidad-orden').value) || 1
        };

        fetch(API_GUARDAR_CAPACIDAD, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                modalCapacidad.classList.add('hidden');
                mostrarToast(res.message);
                window.abrirPlanDetalle(planActual.id_plan);
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    });

    window.confirmarEliminarCapacidad = async function(idCapacidad) {
        const confirmado = await SISCO.confirm({
            title: '¿Eliminar Capacidad?',
            message: '¿Eliminar esta capacidad MEC y todas sus lecciones programadas?',
            confirmText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger'
        });
        if (!confirmado) return;
        fetch(API_ELIMINAR_CAPACIDAD, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_capacidad: idCapacidad })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                mostrarToast(res.message);
                window.abrirPlanDetalle(planActual.id_plan);
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    };

    // ==========================================
    // 6. CRUD DE TEMAS / LECCIONES Y EVALUACIÓN
    // ==========================================
    window.abrirModalNuevoTema = function(idCapacidadPreseleccionada = null) {
        formTema.reset();
        document.getElementById('modal-tema-title').textContent = 'Planificar Nueva Lección / Tema';
        document.getElementById('tema-id').value = '';
        document.getElementById('tema-horas').value = '2';
        labelMinutos.textContent = '(= 80 min)';
        document.getElementById('tema-fecha').value = new Date().toISOString().substring(0, 10);

        if (idCapacidadPreseleccionada) {
            selectTemaCapacidad.value = idCapacidadPreseleccionada;
        }

        // Desmarcar todos los checkboxes de procedimientos e instrumentos
        containerProcedimientos.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);
        containerInstrumentos.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);

        modalTema.classList.remove('hidden');
    };

    window.abrirModalEditarTema = function(idTema) {
        if (!planActual || !planActual.unidades) return;
        let temaEncontrado = null;
        for (const u of planActual.unidades) {
            for (const c of (u.capacidades || [])) {
                const t = (c.temas || []).find(x => x.id_tema == idTema);
                if (t) {
                    temaEncontrado = t;
                    break;
                }
            }
            if (temaEncontrado) break;
        }
        if (!temaEncontrado) return;

        document.getElementById('modal-tema-title').textContent = 'Editar Lección / Tema Pedagógico';
        document.getElementById('tema-id').value = temaEncontrado.id_tema;
        selectTemaCapacidad.value = temaEncontrado.id_capacidad;
        document.getElementById('tema-titulo').value = temaEncontrado.titulo || '';

        const hc = parseFloat(temaEncontrado.horas_catedra || 1);
        document.getElementById('tema-horas').value = hc;
        labelMinutos.textContent = `(= ${hc * 40} min)`;

        document.getElementById('tema-fecha').value = temaEncontrado.fecha_inicio || '';

        // Indicadores
        const indList = (temaEncontrado.indicadores || []).map(i => i.descripcion || i).join('\n');
        document.getElementById('tema-indicadores').value = indList;

        // Marcar checkboxes de procedimientos
        const procIds = (temaEncontrado.procedimientos_ids || []).map(String);
        containerProcedimientos.querySelectorAll('input[type="checkbox"]').forEach(c => {
            c.checked = procIds.includes(c.value);
        });

        // Marcar checkboxes de instrumentos
        const instIds = (temaEncontrado.instrumentos_ids || []).map(String);
        containerInstrumentos.querySelectorAll('input[type="checkbox"]').forEach(c => {
            c.checked = instIds.includes(c.value);
        });

        modalTema.classList.remove('hidden');
    };

    formTema.addEventListener('submit', (e) => {
        e.preventDefault();
        const indRaw = document.getElementById('tema-indicadores').value;
        const indicadores = indRaw.split('\n').map(s => s.replace(/^[-*•]\s*/, '').trim()).filter(s => s.length > 0);

        const procsSeleccionados = Array.from(containerProcedimientos.querySelectorAll('input[type="checkbox"]:checked')).map(c => parseInt(c.value));
        const instsSeleccionados = Array.from(containerInstrumentos.querySelectorAll('input[type="checkbox"]:checked')).map(c => parseInt(c.value));

        const payload = {
            id_capacidad: parseInt(selectTemaCapacidad.value),
            id_tema: document.getElementById('tema-id').value || null,
            titulo: document.getElementById('tema-titulo').value.trim(),
            horas_catedra: parseFloat(document.getElementById('tema-horas').value) || 1,
            fecha_inicio: document.getElementById('tema-fecha').value || null,
            fecha_fin: document.getElementById('tema-fecha').value || null,
            indicadores: indicadores,
            procedimientos: procsSeleccionados,
            instrumentos: instsSeleccionados
        };

        fetch(API_GUARDAR_TEMA, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                modalTema.classList.add('hidden');
                mostrarToast(res.message);
                window.abrirPlanDetalle(planActual.id_plan);
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    });

    window.confirmarEliminarTema = async function(idTema) {
        const confirmado = await SISCO.confirm({
            title: '¿Eliminar Lección?',
            message: '¿Eliminar este tema pedagógico y sus indicadores programados?',
            confirmText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger'
        });
        if (!confirmado) return;
        fetch(API_ELIMINAR_TEMA, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_tema: idTema })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                mostrarToast(res.message);
                window.abrirPlanDetalle(planActual.id_plan);
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    };

    // Inicialización: cargar recursos y catálogo
    cargarRecursosEvaluacion();
    cargarCatalogoPlanes();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
