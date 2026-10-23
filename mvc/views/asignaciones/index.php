<?php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/middleware/auth.php';
verificarRol(['SuperAdmin', 'Administrador', 'Administracion']);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Breadcrumb Accesible -->
        <nav class="flex items-center gap-2 text-base text-slate-600 font-medium" aria-label="Migas de pan">
            <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="hover:text-blue-600 transition flex items-center gap-1.5 focus:outline-none focus:underline">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Inicio
            </a>
            <span class="text-slate-400">/</span>
            <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="hover:text-blue-600 transition font-medium focus:outline-none focus:underline">
                Administración
            </a>
            <span class="text-slate-400">/</span>
            <span class="text-slate-900 font-bold">Asignaciones Docentes</span>
        </nav>

        <!-- Cabecera de Página Accesible -->
        <div class="app-page-header p-6 sm:p-8 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span class="px-3.5 py-1 text-sm font-extrabold rounded-full bg-blue-100 text-blue-900 border border-blue-200 uppercase tracking-wide">
                        Malla & Cátedras
                    </span>
                    <span class="text-base text-slate-600 font-medium hidden sm:inline">
                        Distribución Jerárquica por Docentes y Ciclos
                    </span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Gestión de Asignaciones Docentes
                </h1>
                <p class="text-base sm:text-lg text-slate-700 mt-2 max-w-3xl leading-relaxed">
                    Visualizá la carga horaria semanal consolidada por profesor y organizá las cátedras por ciclos formativos de manera transparente y eficiente.
                </p>
            </div>

            <!-- Botonera de Acción Primaria -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <button type="button" onclick="abrirModalCrearAsignacion()" class="min-h-[48px] px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-base font-bold rounded-2xl shadow-sm transition flex items-center gap-2.5 focus:outline-none focus:ring-4 focus:ring-blue-200">
                    <span class="text-xl font-bold">+</span>
                    <span>Registrar Asignación</span>
                </button>
                <a href="<?= base_url('mvc/views/asignaciones/desactivadas.php') ?>" class="btn btn-muted min-h-[48px] px-5 py-2.5 text-base font-semibold rounded-2xl border border-slate-300">
                    <span>🗑️</span>
                    <span>Asignaciones Inactivas</span>
                </a>
                <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="btn btn-muted min-h-[48px] px-5 py-2.5 text-base font-semibold rounded-2xl">
                    ← Volver
                </a>
            </div>
        </div>

        <!-- Franja de Indicadores Métricos (KPI Strip) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Total Docentes Asignados -->
            <div class="p-6 rounded-3xl bg-blue-50/70 border-2 border-blue-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-blue-900">Docentes con Cátedra</span>
                    <div id="kpi-total-profesores" class="text-4xl font-black text-slate-900 mt-2">0</div>
                    <p class="text-sm text-slate-600 mt-0.5">Profesores con horas activas</p>
                </div>
                <span class="text-4xl">🧑‍🏫</span>
            </div>

            <!-- Total Asignaciones -->
            <div class="p-6 rounded-3xl bg-indigo-50/70 border-2 border-indigo-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-indigo-900">Asignaciones / Cátedras</span>
                    <div id="kpi-total-asignaciones" class="text-4xl font-black text-slate-900 mt-2">0</div>
                    <p class="text-sm text-slate-600 mt-0.5">Total de materias distribuidas</p>
                </div>
                <span class="text-4xl">📋</span>
            </div>

            <!-- Horas Cátedra Totales -->
            <div class="p-6 rounded-3xl bg-emerald-50/70 border-2 border-emerald-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-emerald-900">Horas Cátedra Semanales</span>
                    <div id="kpi-total-horas" class="text-4xl font-black text-slate-900 mt-2">0</div>
                    <p class="text-sm text-slate-600 mt-0.5">Carga horaria total sumada</p>
                </div>
                <span class="text-4xl">⏱️</span>
            </div>

            <!-- Cursos Cubiertos -->
            <div class="p-6 rounded-3xl bg-amber-50/70 border-2 border-amber-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-amber-900">Cursos / Secciones</span>
                    <div id="kpi-total-cursos" class="text-4xl font-black text-slate-900 mt-2">0</div>
                    <p class="text-sm text-slate-600 mt-0.5">Cursos con materias activas</p>
                </div>
                <span class="text-4xl">🏫</span>
            </div>
        </div>

        <!-- Barra de Controles: Búsqueda, Botones Fijos de Agrupación (Docente / Ciclo) y Filtros -->
        <div class="bg-white p-6 rounded-3xl border-2 border-slate-200/90 shadow-sm space-y-5">
            <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
                
                <!-- Búsqueda en Tiempo Real -->
                <div class="relative flex-1 min-w-[280px]">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                    <input type="text" id="input-busqueda-asignacion"
                           placeholder="Buscar por profesor, cédula, materia o curso..."
                           class="w-full min-h-[48px] pl-12 pr-10 py-3 text-base text-slate-900 font-semibold bg-slate-50 border border-slate-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                           aria-label="Buscar asignación">
                    <button type="button" id="btn-limpiar-busqueda-asignacion" onclick="limpiarBusquedaAsignacion()"
                            class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-700 hidden text-lg font-bold"
                            aria-label="Limpiar búsqueda">
                        ✕
                    </button>
                </div>

                <!-- Botones Fijos de Agrupación Solicitados: 'Docente' y 'Ciclo' -->
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-extrabold uppercase tracking-wider text-slate-500 mr-1 hidden sm:inline">
                        Modo de Vista:
                    </span>

                    <!-- Botón Fijo: Docente -->
                    <button type="button" id="btn-modo-docente" onclick="establecerModoPrincipal('docente')"
                            class="min-h-[48px] px-5 py-2.5 rounded-2xl text-base font-extrabold flex items-center gap-2 border-2 transition shadow-xs bg-blue-600 text-white border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-200"
                            title="Agrupar las asignaciones por profesor responsable">
                        <span>🧑‍🏫</span>
                        <span>Docente</span>
                    </button>

                    <!-- Botón Fijo: Ciclo (Toggle para activar/desactivar agrupación por ciclo) -->
                    <button type="button" id="btn-toggle-ciclo" onclick="alternarAgrupacionCiclo()"
                            class="min-h-[48px] px-5 py-2.5 rounded-2xl text-base font-extrabold flex items-center gap-2 border-2 transition shadow-xs bg-slate-100 text-slate-800 border-slate-300 hover:bg-slate-200 focus:outline-none focus:ring-4 focus:ring-slate-200"
                            title="Activar o desactivar la agrupación por ciclos académicos">
                        <span>🏫</span>
                        <span>Ciclo</span>
                        <span id="badge-estado-ciclo" class="px-2 py-0.5 rounded-lg text-xs font-black uppercase bg-slate-300 text-slate-800">
                            Off
                        </span>
                    </button>

                    <!-- Botón para Expandir/Colapsar Todos -->
                    <button type="button" id="btn-toggle-expandir" onclick="alternarExpandirTodos()"
                            class="min-h-[48px] px-4 py-2.5 rounded-2xl text-base font-bold bg-white text-slate-700 border-2 border-slate-300 hover:bg-slate-50 transition flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-slate-400"
                            title="Expandir o colapsar todas las tarjetas de profesores">
                        <span id="icono-expandir-todos">↕️</span>
                        <span id="texto-expandir-todos">Expandir todos</span>
                    </button>

                    <!-- Selector de Año Lectivo Optimizado (Consulta solo lo necesario a la BD) -->
                    <select id="filtro-anio-asignacion" onchange="cambiarAnhoLectivo()"
                            class="min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-extrabold bg-slate-50 border-2 border-slate-300 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="2026" selected>Año Lectivo 2026</option>
                        <option value="2025">Año Lectivo 2025</option>
                        <option value="2024">Año Lectivo 2024</option>
                        <option value="">Todos los años</option>
                    </select>
                </div>

            </div>

            <!-- Barra de Filtros Rápidos por Ciclo (Visible solo cuando 'Ciclo' está activado) -->
            <div id="franja-filtro-ciclos" class="hidden pt-3 border-t border-slate-200/90 flex flex-wrap items-center gap-2 animate-in fade-in duration-150">
                <span class="text-sm font-extrabold uppercase tracking-wider text-slate-600 mr-2">
                    Filtrar Ciclo:
                </span>
                <button type="button" onclick="filtrarPorCicloSeleccionado(null)" id="btn-ciclo-todos"
                        class="btn-ciclo-pill px-4 py-1.5 rounded-xl text-sm font-extrabold bg-blue-600 text-white border border-blue-600 transition">
                    Todos los ciclos
                </button>
                <button type="button" onclick="filtrarPorCicloSeleccionado('EEB3')" id="btn-ciclo-eeb3"
                        class="btn-ciclo-pill px-4 py-1.5 rounded-xl text-sm font-bold bg-slate-100 text-slate-800 border border-slate-300 hover:bg-slate-200 transition">
                    EEB - 3° Ciclo (7°, 8°, 9°)
                </button>
                <button type="button" onclick="filtrarPorCicloSeleccionado('NM')" id="btn-ciclo-nm"
                        class="btn-ciclo-pill px-4 py-1.5 rounded-xl text-sm font-bold bg-slate-100 text-slate-800 border border-slate-300 hover:bg-slate-200 transition">
                    Nivel Medio / Bachilleratos (1°, 2°, 3°)
                </button>
            </div>

            <!-- Contador de Docentes y Asignaciones Visibles -->
            <div class="flex items-center justify-between text-base text-slate-700 font-bold pt-1">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    <span id="contador-asignaciones-visibles">Cargando datos...</span>
                </div>
                <span id="indicador-modo-activo" class="text-sm text-slate-500 font-semibold hidden sm:inline">
                    Organización agrupada por profesor titular
                </span>
            </div>
        </div>

        <!-- Contenedor Principal de Docentes Agrupados (Tarjetas / Acordeones Accesibles) -->
        <div id="contenedor-docentes-asignaciones" class="space-y-5">
            <!-- Skeleton / Estado de Carga -->
            <div class="p-12 text-center rounded-3xl bg-white border-2 border-slate-200 shadow-sm space-y-3">
                <div class="animate-spin inline-block w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full mb-2"></div>
                <p class="text-lg font-bold text-slate-700">Cargando y agrupando asignaciones docentes...</p>
                <p class="text-sm text-slate-500">Consultando distribución horaria por ciclo institucional</p>
            </div>
        </div>

        <!-- Estado Vacío cuando no hay coincidencias -->
        <div id="empty-busqueda-asignaciones" class="hidden p-14 text-center rounded-3xl bg-white border-2 border-slate-200 shadow-sm space-y-4">
            <span class="text-5xl block">🔍</span>
            <h3 class="text-2xl font-bold text-slate-900">No encontramos asignaciones con esos criterios</h3>
            <p class="text-base text-slate-600 max-w-md mx-auto">
                Probá modificando la búsqueda, seleccionando otro año lectivo o limpiá los filtros aplicados.
            </p>
            <button type="button" onclick="limpiarBusquedaAsignacion()" class="btn btn-primary min-h-[46px] px-6 text-base font-bold rounded-2xl">
                Restablecer lista completa
            </button>
        </div>

    </div>
</main>

<!-- Submodales de Asignación Docente -->
<?php require_once __DIR__ . '/modal_crear_asignacion.php'; ?>
<?php require_once __DIR__ . '/modal_editar_asignacion.php'; ?>
<?php require_once __DIR__ . '/modal_detalle_asignacion.php'; ?>

<script>
let listaAsignacionesGlobal = [];
let agruparPorCiclo = false;
let filtroCicloActivo = null; // null = todos, 'EEB3', 'NM', etc.
let todosExpandidos = false;

document.addEventListener('DOMContentLoaded', function() {
    cargarAsignaciones();

    const inputBuscar = document.getElementById('input-busqueda-asignacion');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', renderizarDocentesAgrupados);
    }
});

/**
 * Consulta optimizada: solo trae las asignaciones del año lectivo seleccionado
 */
async function cargarAsignaciones() {
    const contenedor = document.getElementById('contenedor-docentes-asignaciones');
    const labelContador = document.getElementById('contador-asignaciones-visibles');
    const filtroAnio = document.getElementById('filtro-anio-asignacion');
    const anhoSel = filtroAnio ? filtroAnio.value : '';

    if (contenedor) {
        contenedor.innerHTML = `
            <div class="p-12 text-center rounded-3xl bg-white border-2 border-slate-200 shadow-sm space-y-3">
                <div class="animate-spin inline-block w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full mb-2"></div>
                <p class="text-lg font-bold text-slate-700">Cargando asignaciones docentes...</p>
                <p class="text-sm text-slate-500">Consultando asignaciones para el período seleccionado</p>
            </div>
        `;
    }

    try {
        let url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Asignaciones/leer_asignaciones.php';
        if (anhoSel) {
            url += '?anho=' + encodeURIComponent(anhoSel);
        }

        const res = await fetch(url);
        const json = await res.json();

        if (json.status !== 'success' || !Array.isArray(json.data)) {
            if (contenedor) {
                contenedor.innerHTML = `<div class="p-10 text-center text-rose-600 font-bold bg-white rounded-3xl border-2 border-rose-200">Error al cargar asignaciones: ${json.message || 'Respuesta inválida'}</div>`;
            }
            return;
        }

        listaAsignacionesGlobal = json.data;
        actualizarKPIs(listaAsignacionesGlobal);
        renderizarDocentesAgrupados();

        // Precargar catálogos sin bloquear
        if (typeof obtenerCatalogosAsignacion === 'function') {
            obtenerCatalogosAsignacion();
        }

    } catch (e) {
        console.error("Error cargando asignaciones:", e);
        if (contenedor) {
            contenedor.innerHTML = `<div class="p-10 text-center text-rose-600 font-bold bg-white rounded-3xl border-2 border-rose-200">Error de conexión al cargar la lista de asignaciones.</div>`;
        }
    }
}

function cambiarAnhoLectivo() {
    cargarAsignaciones();
}

function actualizarKPIs(lista) {
    const totalEl = document.getElementById('kpi-total-asignaciones');
    const horasEl = document.getElementById('kpi-total-horas');
    const profesEl = document.getElementById('kpi-total-profesores');
    const cursosEl = document.getElementById('kpi-total-cursos');

    const total = lista.length;
    let totalHoras = 0;
    const profesSet = new Set();
    const cursosSet = new Set();

    lista.forEach(a => {
        totalHoras += (parseInt(a.carga_horaria) || 0);
        if (a.id_profesor) profesSet.add(a.id_profesor);
        if (a.id_grado) cursosSet.add(a.id_grado);
    });

    if (totalEl) totalEl.textContent = total;
    if (horasEl) horasEl.textContent = totalHoras;
    if (profesEl) profesEl.textContent = profesSet.size;
    if (cursosEl) cursosEl.textContent = cursosSet.size;
}

/**
 * Conmutador para el botón fijo "Ciclo"
 */
function alternarAgrupacionCiclo() {
    agruparPorCiclo = !agruparPorCiclo;
    const btnCiclo = document.getElementById('btn-toggle-ciclo');
    const badgeCiclo = document.getElementById('badge-estado-ciclo');
    const franjaFiltro = document.getElementById('franja-filtro-ciclos');

    if (agruparPorCiclo) {
        btnCiclo.className = "min-h-[48px] px-5 py-2.5 rounded-2xl text-base font-extrabold flex items-center gap-2 border-2 transition shadow-xs bg-emerald-600 text-white border-emerald-600 focus:outline-none focus:ring-4 focus:ring-emerald-200";
        badgeCiclo.className = "px-2 py-0.5 rounded-lg text-xs font-black uppercase bg-white text-emerald-800";
        badgeCiclo.textContent = "Agrupado";
        if (franjaFiltro) franjaFiltro.classList.remove('hidden');
    } else {
        btnCiclo.className = "min-h-[48px] px-5 py-2.5 rounded-2xl text-base font-extrabold flex items-center gap-2 border-2 transition shadow-xs bg-slate-100 text-slate-800 border-slate-300 hover:bg-slate-200 focus:outline-none focus:ring-4 focus:ring-slate-200";
        badgeCiclo.className = "px-2 py-0.5 rounded-lg text-xs font-black uppercase bg-slate-300 text-slate-800";
        badgeCiclo.textContent = "Off";
        if (franjaFiltro) franjaFiltro.classList.add('hidden');
        filtroCicloActivo = null;
    }

    renderizarDocentesAgrupados();
}

function filtrarPorCicloSeleccionado(codigoCiclo) {
    filtroCicloActivo = codigoCiclo;

    // Actualizar estilo visual de las píldoras de filtro
    document.querySelectorAll('.btn-ciclo-pill').forEach(btn => {
        btn.className = "btn-ciclo-pill px-4 py-1.5 rounded-xl text-sm font-bold bg-slate-100 text-slate-800 border border-slate-300 hover:bg-slate-200 transition";
    });

    if (!codigoCiclo) {
        document.getElementById('btn-ciclo-todos')?.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
    } else if (codigoCiclo === 'EEB3') {
        document.getElementById('btn-ciclo-eeb3')?.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
    } else if (codigoCiclo === 'NM') {
        document.getElementById('btn-ciclo-nm')?.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
    }

    renderizarDocentesAgrupados();
}

function establecerModoPrincipal(modo) {
    // Si se hace clic en Docente, asegura la vista docente
    const btnDoc = document.getElementById('btn-modo-docente');
    if (btnDoc) {
        btnDoc.className = "min-h-[48px] px-5 py-2.5 rounded-2xl text-base font-extrabold flex items-center gap-2 border-2 transition shadow-xs bg-blue-600 text-white border-blue-600 focus:outline-none focus:ring-4 focus:ring-blue-200";
    }
    renderizarDocentesAgrupados();
}

function alternarExpandirTodos() {
    todosExpandidos = !todosExpandidos;
    const btnTexto = document.getElementById('texto-expandir-todos');
    if (btnTexto) {
        btnTexto.textContent = todosExpandidos ? "Colapsar todos" : "Expandir todos";
    }

    document.querySelectorAll('.docente-card-body').forEach(body => {
        if (todosExpandidos) body.classList.remove('hidden');
        else body.classList.add('hidden');
    });

    document.querySelectorAll('.docente-card-chevron').forEach(chev => {
        if (todosExpandidos) chev.classList.add('rotate-180');
        else chev.classList.remove('rotate-180');
    });
}

function toggleDocenteCard(idProfesor) {
    const body = document.getElementById(`docente-body-${idProfesor}`);
    const chevron = document.getElementById(`docente-chevron-${idProfesor}`);
    if (body) {
        const isHidden = body.classList.contains('hidden');
        if (isHidden) {
            body.classList.remove('hidden');
            if (chevron) chevron.classList.add('rotate-180');
        } else {
            body.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }
    }
}

/**
 * Genera iniciales y color distintivo para avatar
 */
function obtenerAvatarDocente(nombreCompleto) {
    const partes = (nombreCompleto || 'Docente').trim().split(/\s+/);
    let ini = 'D';
    if (partes.length >= 2) {
        ini = (partes[0].charAt(0) + partes[1].charAt(0)).toUpperCase();
    } else if (partes[0].length > 0) {
        ini = partes[0].substring(0, 2).toUpperCase();
    }

    const colores = [
        'bg-blue-100 text-blue-800 border-blue-300',
        'bg-indigo-100 text-indigo-800 border-indigo-300',
        'bg-emerald-100 text-emerald-800 border-emerald-300',
        'bg-purple-100 text-purple-800 border-purple-300',
        'bg-amber-100 text-amber-900 border-amber-300',
        'bg-cyan-100 text-cyan-800 border-cyan-300'
    ];
    let hash = 0;
    for (let i = 0; i < nombreCompleto.length; i++) {
        hash = nombreCompleto.charCodeAt(i) + ((hash << 5) - hash);
    }
    const colorClass = colores[Math.abs(hash) % colores.length];

    return { iniciales: ini, colorClass: colorClass };
}

/**
 * Renderizado agrupado por Docente (y sub-agrupado por Ciclo si está activo)
 */
function renderizarDocentesAgrupados() {
    const inputBuscar = document.getElementById('input-busqueda-asignacion');
    const btnLimpiar = document.getElementById('btn-limpiar-busqueda-asignacion');
    const contenedor = document.getElementById('contenedor-docentes-asignaciones');
    const labelContador = document.getElementById('contador-asignaciones-visibles');
    const emptyBox = document.getElementById('empty-busqueda-asignaciones');

    const q = (inputBuscar ? inputBuscar.value : '').trim().toLowerCase();

    if (btnLimpiar) {
        btnLimpiar.classList.toggle('hidden', q.length === 0);
    }

    // 1. Filtrar asignaciones por búsqueda y filtro de ciclo activo
    const asignacionesFiltradas = listaAsignacionesGlobal.filter(a => {
        const prof = (a.profesor || '').toLowerCase();
        const ci = (a.profesor_ci || '').toLowerCase();
        const mat = (a.materia || '').toLowerCase();
        const grad = (a.grado || '').toLowerCase();
        const ciclo = (a.nivel_nombre || '').toLowerCase();
        const bach = (a.bachillerato_nombre || '').toLowerCase();

        const matchQ = !q || prof.includes(q) || ci.includes(q) || mat.includes(q) || grad.includes(q) || ciclo.includes(q) || bach.includes(q);
        
        let matchCiclo = true;
        if (agruparPorCiclo && filtroCicloActivo) {
            matchCiclo = (a.nivel_codigo === filtroCicloActivo);
        }

        return matchQ && matchCiclo;
    });

    if (asignacionesFiltradas.length === 0) {
        contenedor.innerHTML = '';
        if (emptyBox) emptyBox.classList.remove('hidden');
        if (labelContador) labelContador.textContent = '0 asignaciones encontradas';
        return;
    }

    if (emptyBox) emptyBox.classList.add('hidden');

    // 2. Agrupar por profesor en memoria
    const gruposDocentes = new Map();

    asignacionesFiltradas.forEach(asig => {
        const idProf = asig.id_profesor || 0;
        if (!gruposDocentes.has(idProf)) {
            gruposDocentes.set(idProf, {
                id_profesor: idProf,
                nombre: asig.profesor || 'Docente no asignado',
                ci: asig.profesor_ci || '',
                total_horas: 0,
                cursosMap: new Map(),
                ciclosMap: new Map(),
                asignaciones: []
            });
        }

        const doc = gruposDocentes.get(idProf);
        const horas = parseInt(asig.carga_horaria) || 0;
        doc.total_horas += horas;
        doc.asignaciones.push(asig);

        const nombreCurso = asig.grado + (asig.seccion ? ' ' + asig.seccion : '');
        doc.cursosMap.set(asig.id_grado, nombreCurso);

        const codCiclo = asig.nivel_codigo || 'SC';
        const nomCiclo = asig.nivel_nombre || 'Sin Ciclo Definido';
        if (!doc.ciclosMap.has(codCiclo)) {
            doc.ciclosMap.set(codCiclo, {
                codigo: codCiclo,
                nombre: nomCiclo,
                id_nivel: asig.id_nivel,
                horas: 0,
                asignaciones: []
            });
        }
        const cicloItem = doc.ciclosMap.get(codCiclo);
        cicloItem.horas += horas;
        cicloItem.asignaciones.push(asig);
    });

    const totalDocentes = gruposDocentes.size;
    const totalAsignaciones = asignacionesFiltradas.length;

    if (labelContador) {
        labelContador.textContent = `${totalDocentes} docentes asignados (${totalAsignaciones} cátedras)`;
    }

    // 3. Renderizar Tarjetas de Docentes
    let html = '';

    gruposDocentes.forEach(doc => {
        const avatar = obtenerAvatarDocente(doc.nombre);
        const cantCursos = doc.cursosMap.size;
        const cantCatedras = doc.asignaciones.length;
        const isExpanded = todosExpandidos;

        // Píldoras de ciclos para la cabecera
        let pillsCiclos = '';
        doc.ciclosMap.forEach(c => {
            const esMedio = c.codigo === 'NM' || c.nombre.toLowerCase().includes('medio');
            const pillClass = esMedio ? 'bg-purple-100 text-purple-900 border-purple-200' : 'bg-blue-100 text-blue-900 border-blue-200';
            pillsCiclos += `
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-sm font-extrabold border ${pillClass}">
                    <span>${esMedio ? '🎓' : '🏫'}</span>
                    <span>${c.nombre} (${c.horas} hs)</span>
                </span>
            `;
        });

        html += `
            <div class="docente-card bg-white rounded-3xl border-2 border-slate-200/90 shadow-sm overflow-hidden transition-all hover:border-slate-300">
                
                <!-- Cabecera de la Tarjeta del Docente (Botón Accesible de Acordeón) -->
                <div onclick="toggleDocenteCard(${doc.id_profesor})"
                     class="p-5 sm:p-6 bg-slate-50/70 hover:bg-slate-100/80 cursor-pointer flex flex-col lg:flex-row lg:items-center justify-between gap-4 transition select-none"
                     role="button" tabindex="0" aria-expanded="${isExpanded}">
                    
                    <!-- Lado Izquierdo: Avatar y Datos del Docente -->
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center font-black text-xl border-2 shrink-0 ${avatar.colorClass}">
                            ${avatar.iniciales}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-snug truncate">
                                ${doc.nombre}
                            </h3>
                            <div class="flex items-center gap-2 mt-0.5 text-base text-slate-600 font-semibold">
                                ${doc.ci ? `<span>C.I. ${doc.ci}</span>` : '<span>Sin documento</span>'}
                                <span class="text-slate-400">•</span>
                                <span class="text-slate-700">${cantCatedras} ${cantCatedras === 1 ? 'materia' : 'materias'} asignadas</span>
                            </div>
                        </div>
                    </div>

                    <!-- Lado Derecho: Métricas Consolidadas, Acción Rápida y Chevron -->
                    <div class="flex flex-wrap items-center gap-3 self-start lg:self-auto">
                        
                        <!-- Píldora de Horas Cátedra Semanales Totales -->
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-indigo-50 border-2 border-indigo-200 shadow-2xs">
                            <span class="text-xl font-black text-indigo-900">${doc.total_horas} hs</span>
                            <span class="text-xs font-black uppercase text-indigo-700">cátedra/sem</span>
                        </div>

                        <!-- Cursos a su cargo -->
                        <span class="px-3.5 py-1.5 rounded-xl bg-slate-200 text-slate-800 text-sm font-extrabold">
                            ${cantCursos} ${cantCursos === 1 ? 'curso' : 'cursos'}
                        </span>

                        <!-- Ciclos en los que enseña -->
                        <div class="hidden sm:flex flex-wrap items-center gap-1.5">
                            ${pillsCiclos}
                        </div>

                        <!-- Botón de Acción Rápida: Asignar a este profesor -->
                        <button type="button" onclick="event.stopPropagation(); abrirModalCrearAsignacion({ id_profesor: ${doc.id_profesor} })"
                                class="min-h-[44px] px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-800 text-sm font-extrabold border border-blue-200 transition shadow-2xs flex items-center gap-1.5 focus:outline-none"
                                title="Agregar nueva cátedra a este profesor">
                            <span>+</span>
                            <span class="hidden md:inline">Asignar Materia</span>
                        </button>

                        <!-- Icono Chevron Expandir/Colapsar -->
                        <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-500 font-bold transition-transform duration-200 docente-card-chevron ${isExpanded ? 'rotate-180' : ''}"
                             id="docente-chevron-${doc.id_profesor}">
                            ▼
                        </div>

                    </div>

                </div>

                <!-- Cuerpo Desplegable de la Tarjeta (Listado de Cátedras) -->
                <div id="docente-body-${doc.id_profesor}" class="docente-card-body ${isExpanded ? '' : 'hidden'} border-t-2 border-slate-200">
                    ${renderizarContenidoDocente(doc)}
                </div>

            </div>
        `;
    });

    contenedor.innerHTML = html;
}

/**
 * Renderiza el contenido interno del profesor:
 * Si agruparPorCiclo está activo, genera bloques por ciclo.
 * Si está inactivo, genera una lista continua de sus materias.
 */
function renderizarContenidoDocente(doc) {
    if (agruparPorCiclo) {
        // Renderizar subdividido por ciclo
        let ciclosHtml = '';

        doc.ciclosMap.forEach(ciclo => {
            if (filtroCicloActivo && ciclo.codigo !== filtroCicloActivo) {
                return;
            }

            const esMedio = ciclo.codigo === 'NM' || ciclo.nombre.toLowerCase().includes('medio');
            const headerColor = esMedio ? 'bg-purple-50/80 border-purple-200 text-purple-900' : 'bg-blue-50/80 border-blue-200 text-blue-900';

            ciclosHtml += `
                <div class="p-4 sm:p-6 space-y-4 border-b last:border-b-0 border-slate-200">
                    
                    <!-- Encabezado de la Sección del Ciclo -->
                    <div class="flex items-center justify-between p-3.5 rounded-2xl border ${headerColor}">
                        <div class="flex items-center gap-2.5">
                            <span class="text-2xl">${esMedio ? '🎓' : '🏫'}</span>
                            <div>
                                <h4 class="text-base sm:text-lg font-black tracking-tight">${ciclo.nombre}</h4>
                                <span class="text-xs font-bold uppercase opacity-80">${ciclo.asignaciones.length} ${ciclo.asignaciones.length === 1 ? 'materia' : 'materias'} en este ciclo</span>
                            </div>
                        </div>
                        <div class="px-3.5 py-1 rounded-xl bg-white border border-slate-300 font-black text-sm">
                            Subtotal: ${ciclo.horas} hs cátedra
                        </div>
                    </div>

                    <!-- Tabla de Cátedras en este Ciclo -->
                    <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-2xs">
                        <table class="w-full text-left text-base">
                            <thead class="bg-slate-100 text-slate-700 uppercase font-bold text-xs sm:text-sm border-b border-slate-200">
                                <tr>
                                    <th class="p-4">Materia / Asignatura</th>
                                    <th class="p-4">Curso / Grado & Bachillerato</th>
                                    <th class="p-4">Turno</th>
                                    <th class="p-4">Carga Horaria</th>
                                    <th class="p-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-slate-800 bg-white">
                                ${ciclo.asignaciones.map(a => renderizarFilaCatedra(a, doc)).join('')}
                            </tbody>
                        </table>
                    </div>

                </div>
            `;
        });

        return ciclosHtml || '<p class="p-6 text-slate-500 font-medium">No hay cátedras en el ciclo seleccionado.</p>';

    } else {
        // Renderizar tabla continua
        return `
            <div class="p-4 sm:p-6">
                <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-2xs">
                    <table class="w-full text-left text-base">
                        <thead class="bg-slate-100 text-slate-700 uppercase font-bold text-xs sm:text-sm border-b border-slate-200">
                            <tr>
                                <th class="p-4">Materia / Asignatura</th>
                                <th class="p-4">Curso / Grado & Bachillerato</th>
                                <th class="p-4">Ciclo / Nivel</th>
                                <th class="p-4">Turno</th>
                                <th class="p-4">Carga Horaria</th>
                                <th class="p-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-slate-800 bg-white">
                            ${doc.asignaciones.map(a => renderizarFilaCatedra(a, doc, true)).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }
}

/**
 * Fila individual de una cátedra
 */
function renderizarFilaCatedra(a, doc, incluirColumnaCiclo = false) {
    const matNombre = a.materia || 'Materia';
    const gradoNombre = a.grado || 'Curso no definido';
    const seccion = a.seccion ? ` "${a.seccion}"` : '';
    const turno = a.turno || 'Regular';
    const carga = a.carga_horaria || 0;
    const bach = a.bachillerato_nombre || '';
    const cicloNom = a.nivel_nombre || 'Sin ciclo';

    return `
        <tr class="hover:bg-slate-50/80 transition-colors">
            <!-- Materia -->
            <td class="p-4">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-base shrink-0">
                        📚
                    </span>
                    <span class="text-base font-extrabold text-slate-900">${matNombre}</span>
                </div>
            </td>

            <!-- Curso y Bachillerato -->
            <td class="p-4">
                <div>
                    <span class="text-base font-bold text-slate-800 block">${gradoNombre}${seccion}</span>
                    ${bach ? `<span class="text-xs font-semibold text-slate-500 block truncate max-w-xs">${bach}</span>` : ''}
                </div>
            </td>

            <!-- Ciclo (si está visible en tabla continua) -->
            ${incluirColumnaCiclo ? `
                <td class="p-4">
                    <span class="px-2.5 py-1 rounded-xl bg-slate-100 text-slate-800 border border-slate-200 text-xs font-extrabold">
                        ${cicloNom}
                    </span>
                </td>
            ` : ''}

            <!-- Turno -->
            <td class="p-4 text-slate-600 font-semibold text-sm">
                <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-700 border border-slate-200">
                    ${turno}
                </span>
            </td>

            <!-- Carga Horaria -->
            <td class="p-4">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-900 font-extrabold text-sm">
                    <span>⏱️</span>
                    <span>${carga} hs</span>
                </span>
            </td>

            <!-- Acciones Accesibles -->
            <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-2 flex-wrap">
                    <button type="button" onclick="abrirModalDetalleAsignacion(${a.id_asignacion})"
                            class="min-h-[40px] px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-800 text-sm font-bold rounded-xl border border-blue-200 transition shadow-2xs flex items-center gap-1"
                            title="Ver detalles, horarios y alumnos matriculados">
                        <span>📋</span>
                        <span>Detalles</span>
                    </button>
                    <button type="button" onclick="abrirModalEditarAsignacionPorId(${a.id_asignacion})"
                            class="min-h-[40px] px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 text-sm font-bold rounded-xl border border-amber-200 transition shadow-2xs flex items-center gap-1"
                            title="Modificar asignación">
                        <span>✏️</span>
                        <span>Editar</span>
                    </button>
                    <button type="button" onclick="desactivarAsignacion(${a.id_asignacion}, '${(doc.nombre).replace(/'/g, "\\'")} - ${(matNombre).replace(/'/g, "\\'")}')"
                            class="min-h-[40px] px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-800 text-sm font-bold rounded-xl border border-rose-200 transition shadow-2xs flex items-center gap-1"
                            title="Dar de baja esta cátedra">
                        <span>🗑️</span>
                        <span>Desactivar</span>
                    </button>
                </div>
            </td>
        </tr>
    `;
}

function abrirModalEditarAsignacionPorId(idAsignacion) {
    const item = listaAsignacionesGlobal.find(x => Number(x.id_asignacion) === Number(idAsignacion));
    if (item && typeof abrirModalEditarAsignacion === 'function') {
        abrirModalEditarAsignacion(item);
    } else {
        if (window.Toast) Toast.warning('No se encontraron los datos de la cátedra seleccionada.');
        else alert('No se encontraron los datos de la cátedra seleccionada.');
    }
}

function limpiarBusquedaAsignacion() {
    const inputBuscar = document.getElementById('input-busqueda-asignacion');
    if (inputBuscar) inputBuscar.value = '';
    renderizarDocentesAgrupados();
}

async function desactivarAsignacion(id, descripcion) {
    let confirmado = false;
    if (window.SISCO && typeof window.SISCO.confirm === 'function') {
        confirmado = await window.SISCO.confirm({
            title: '¿Desactivar asignación docente?',
            message: `¿Desea dar de baja la cátedra de "${descripcion}"? Podrá reactivarla luego desde la sección de asignaciones inactivas.`,
            confirmText: 'Sí, desactivar',
            cancelText: 'Cancelar',
            type: 'danger'
        });
    } else {
        confirmado = confirm(`¿Estás seguro de desactivar la asignación de ${descripcion}?`);
    }

    if (!confirmado) return;

    try {
        const formData = new FormData();
        formData.append('id_asignacion', id);

        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Asignaciones/desactivar_asignacion.php';
        const res = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const json = await res.json();

        if (json.status === 'success') {
            if (window.Toast) Toast.success(json.message || 'La cátedra fue dada de baja correctamente.', 'Asignación desactivada');
            else alert(json.message || 'La cátedra fue dada de baja correctamente.');
            cargarAsignaciones();
        } else {
            if (window.Toast) Toast.error(json.message || 'No se pudo desactivar la asignación.', 'Error al desactivar');
            else alert(json.message || 'No se pudo desactivar la asignación.');
        }
    } catch (e) {
        console.error("Error desactivando asignación:", e);
        if (window.Toast) Toast.error('Ocurrió un problema de comunicación con el servidor.', 'Error de red');
        else alert('Ocurrió un problema de comunicación con el servidor.');
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
