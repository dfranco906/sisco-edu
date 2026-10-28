<?php
declare(strict_types=1);

$titulo = "Sesión Pedagógica de Clase (Docente)";

require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/middleware/auth.php';

verificarRol(['SuperAdmin', 'Administrador', 'Coordinador', 'Profesor']);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$rolUsuario = $_SESSION['rol'] ?? '';
$idProfesorSesion = $_SESSION['id_profesor'] ?? null;
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full space-y-6 text-base">

    <!-- Encabezado Principal de la Sesión -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-3">
                <span class="p-2.5 rounded-2xl bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 text-2xl font-bold shrink-0 border border-purple-200 dark:border-purple-800">
                    👨‍🏫
                </span>
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Mi Sesión Pedagógica de Clase
                    </h2>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1">
                        Gestión integrada de asistencia biométrica, registros anecdotarios y evaluación de los aprendizajes (MEC).
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <!-- Hora de Paraguay -->
            <div class="flex items-center gap-2 bg-white dark:bg-slate-900 min-h-[44px] px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs text-sm font-mono font-bold text-slate-700 dark:text-slate-300">
                <span>🇵🇾</span>
                <span id="sesion-reloj-paraguay">Hora Paraguay...</span>
            </div>

            <a href="<?= base_url('mvc/views/evaluaciones/instrumentos.php') ?>" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2" title="Gestionar catálogo de instrumentos evaluativos">
                <span>📋 Banco de Instrumentos MEC</span>
            </a>

            <a href="<?= base_url('mvc/views/profesores/mi_panel.php') ?>" class="btn btn-primary text-base font-semibold py-2.5 px-4 min-h-[44px]">
                Volver a Mi Panel
            </a>
        </div>
    </div>

    <!-- Selector de Cátedra Activa / Asignación del Docente -->
    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex-1 max-w-xl">
            <label for="selector-catedra-activa" class="block text-sm font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300 mb-1.5">
                Cátedra / Materia a Desarrollar en Esta Sesión
            </label>
            <select id="selector-catedra-activa" class="app-input w-full text-base font-bold py-2.5 px-3 rounded-xl min-h-[46px] border-purple-300 dark:border-purple-700 focus:border-purple-600" onchange="cambiarCatedraSesion()">
                <option value="">Cargando mis cursos y asignaciones...</option>
            </select>
        </div>

        <div id="sesion-badge-aula-wrap" class="flex items-center gap-3">
            <div class="p-3 rounded-2xl bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800 text-sm">
                <span class="text-xs uppercase font-bold text-slate-500 block">Aula Asignada:</span>
                <span id="sesion-nombre-aula" class="text-base font-black text-purple-700 dark:text-purple-300">Detectando...</span>
            </div>
            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-sm">
                <span class="text-xs uppercase font-bold text-slate-500 block">Horario Lectivo:</span>
                <span id="sesion-horario-aula" class="text-base font-bold text-slate-900 dark:text-white">--:-- a --:--</span>
            </div>
        </div>
    </div>

    <!-- Tira Pedagógica de la Lección del Día (Vinculada al Plan Anual) -->
    <div id="banner-leccion-pedagogica" class="p-6 rounded-3xl bg-gradient-to-r from-purple-50 via-white to-indigo-50 dark:from-slate-900 dark:via-purple-950/20 dark:to-slate-900 border border-purple-200 dark:border-purple-900 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-purple-100 dark:border-purple-900/50">
            <div class="flex items-center gap-2.5">
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-purple-600 text-white shadow-2xs">
                    Lección Curricular del Día
                </span>
                <span class="text-slate-400">•</span>
                <span id="leccion-unidad-nombre" class="text-sm font-bold text-slate-700 dark:text-slate-300">Unidad temática</span>
            </div>
            <div class="text-sm font-mono font-bold text-purple-700 dark:text-purple-300">
                <span id="leccion-horas-catedra">1</span> hora(s) cátedra programadas
            </div>
        </div>

        <div class="space-y-2">
            <h3 id="leccion-tema-titulo" class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white leading-tight">
                Título del Tema Curricular
            </h3>
            <p id="leccion-capacidad-texto" class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed font-medium">
                Capacidad del programa de estudios MEC...
            </p>
        </div>

        <!-- Indicadores de Evaluación de la Lección -->
        <div class="pt-2">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block mb-2">
                Indicadores de Logro a Desarrollar:
            </span>
            <div id="leccion-indicadores-lista" class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                <!-- Indicadores dinámicos -->
            </div>
        </div>
    </div>

    <!-- NAVEGACIÓN DE LOS 3 COMPONENTES DESACOPLADOS DE LA SESIÓN -->
    <div class="border-b border-slate-200 dark:border-slate-800">
        <nav class="flex space-x-4 sm:space-x-8" aria-label="Componentes de la Sesión">
            <button type="button" onclick="cambiarPestanaSesion('asistencia')" id="tab-btn-asistencia" class="tab-sesion-btn py-4 px-2 border-b-2 font-black text-base inline-flex items-center gap-2 border-purple-600 text-purple-700 dark:text-purple-300">
                <span>1. Asistencia Biométrica</span>
                <span id="badge-cnt-presentes" class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">0/0</span>
            </button>
            <button type="button" onclick="cambiarPestanaSesion('anecdotario')" id="tab-btn-anecdotario" class="tab-sesion-btn py-4 px-2 border-b-2 font-bold text-base inline-flex items-center gap-2 border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                <span>2. Registro Anecdotario</span>
                <span id="badge-cnt-anecdotas" class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">0</span>
            </button>
            <button type="button" onclick="cambiarPestanaSesion('evaluacion')" id="tab-btn-evaluacion" class="tab-sesion-btn py-4 px-2 border-b-2 font-bold text-base inline-flex items-center gap-2 border-transparent text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                <span>3. Evaluación de la Lección (MEC)</span>
                <span id="badge-cnt-evaluados" class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">0 evaluados</span>
            </button>
        </nav>
    </div>

    <!-- CONTENIDO PESTAÑA 1: ASISTENCIA EN VIVO -->
    <div id="seccion-pestana-asistencia" class="space-y-4">
        <!-- Métricas de Asistencia -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Matriculados</span>
                <span id="metrica-total-matriculados" class="text-2xl font-black text-slate-900 dark:text-white block mt-1">0</span>
            </div>
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Presentes (Huella/Manual)</span>
                <span id="metrica-total-presentes" class="text-2xl font-black text-emerald-600 dark:text-emerald-400 block mt-1">0</span>
            </div>
            <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400">Ausentes</span>
                <span id="metrica-total-ausentes" class="text-2xl font-black text-rose-600 dark:text-rose-400 block mt-1">0</span>
            </div>
            <div class="p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800 shadow-xs">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400">Porcentaje</span>
                <span id="metrica-porcentaje-asist" class="text-2xl font-black text-indigo-600 dark:text-indigo-400 block mt-1">0%</span>
            </div>
        </div>

        <!-- Tabla de Asistencia de Estudiantes -->
        <div class="app-table-wrap rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-base">
                    <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800 text-sm font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-5 text-left">Estudiante</th>
                            <th class="py-3 px-5 text-left">Estado</th>
                            <th class="py-3 px-5 text-left">Hora & Método</th>
                            <th class="py-3 px-5 text-right">Acción Manual</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-asistencia-estudiantes-body" class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500 font-semibold">Cargando lista de asistencia...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- CONTENIDO PESTAÑA 2: REGISTRO ANECDOTARIO INDIVIDUAL -->
    <div id="seccion-pestana-anecdotario" class="space-y-4 hidden">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h4 class="text-lg font-black text-slate-900 dark:text-white">
                    Bitácora de Registros Anecdotarios de la Sesión
                </h4>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Registre incidentes significativos, logros sobresalientes o actitudes de los estudiantes durante la clase.
                </p>
            </div>
            <button type="button" onclick="abrirModalNuevoAnecdotario()" class="btn btn-primary text-base font-bold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2 shadow-xs">
                <span>+ Nuevo Registro Anecdotario</span>
            </button>
        </div>

        <div id="contenedor-registros-anecdotarios" class="space-y-3">
            <div class="p-8 text-center text-slate-500 font-semibold">Cargando registros anecdotarios...</div>
        </div>
    </div>

    <!-- CONTENIDO PESTAÑA 3: EVALUACIÓN DE LA LECCIÓN (MEC) -->
    <div id="seccion-pestana-evaluacion" class="space-y-6 hidden">
        <!-- Panel de Selección de Instrumento Evaluativo -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h4 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white">
                        Instrumento Evaluativo de la Lección
                    </h4>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">
                        Seleccione el instrumento preconfigurado o plantilla para asentar el puntaje individual de cada estudiante.
                    </p>
                </div>
                <div class="w-full md:w-80">
                    <select id="selector-plantilla-evaluativa" class="app-input w-full text-base font-bold py-2.5 px-3 rounded-xl min-h-[46px]" onchange="aplicarPlantillaEvaluativa(this.value)">
                        <option value="">Seleccione instrumento del MEC...</option>
                    </select>
                </div>
            </div>

            <!-- Criterios del Instrumento Seleccionado -->
            <div id="box-criterios-plantilla-activa" class="p-4 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-900/60 hidden">
                <div class="flex items-center justify-between mb-2">
                    <span id="plantilla-activa-titulo" class="font-extrabold text-base text-indigo-900 dark:text-indigo-200">
                        Título de la Plantilla
                    </span>
                    <span id="plantilla-activa-puntos" class="font-mono font-bold text-sm text-indigo-700 dark:text-indigo-300">
                        Total: 0.00 pts
                    </span>
                </div>
                <div id="plantilla-activa-criterios-lista" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 text-xs">
                    <!-- Criterios -->
                </div>
            </div>
        </div>

        <!-- Tabla de Calificación Individual de Estudiantes -->
        <div class="app-table-wrap rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-base">
                    <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800 text-sm font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-5 text-left">Estudiante</th>
                            <th class="py-3 px-5 text-center">Puntaje Obtenido</th>
                            <th class="py-3 px-5 text-left">Retroalimentación / Observación</th>
                            <th class="py-3 px-5 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-evaluacion-estudiantes-body" class="divide-y divide-slate-100 dark:divide-slate-800">
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500 font-semibold">Cargando lista para evaluación...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL: NUEVO REGISTRO ANECDOTARIO -->
    <div id="modal-nuevo-anecdotario" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="mna-titulo">
        <div class="app-modal-dialog max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-5">
            <div class="flex items-start justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                <h3 id="mna-titulo" class="text-xl font-black text-slate-900 dark:text-white">
                    Registrar Incidente o Hecho Anecdotario
                </h3>
                <button type="button" onclick="cerrarModalNuevoAnecdotario()" class="text-2xl font-bold text-slate-400 hover:text-slate-700 dark:hover:text-white">
                    ×
                </button>
            </div>

            <form id="form-nuevo-anecdotario" class="space-y-4">
                <div>
                    <label for="anecdota-estudiante" class="block text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Estudiante *
                    </label>
                    <select id="anecdota-estudiante" name="id_estudiante" required class="app-input w-full text-base font-semibold py-2.5 px-3 rounded-xl min-h-[46px]">
                        <!-- Estudiantes de la clase -->
                    </select>
                </div>

                <div>
                    <label for="anecdota-observacion" class="block text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Descripción del Hecho o Situación Observada *
                    </label>
                    <textarea id="anecdota-observacion" name="observacion" rows="4" required placeholder="Detalle la situación pedagógica, conducta o actitud observada de forma objetiva..." class="app-input w-full text-base py-2.5 px-3 rounded-xl"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" onclick="cerrarModalNuevoAnecdotario()" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px]">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-submit-anecdota" class="btn btn-primary text-base font-bold py-2.5 px-5 min-h-[44px]">
                        Asentar Registro
                    </button>
                </div>
            </form>
        </div>
    </div>

</main>

<script>
let sesionActual = null;
let catalogoPlantillasSesion = [];
let plantillaSeleccionadaActual = null;
let misCatedrasDocente = [];

function getBaseUrl() {
    return (window.BASE_URL || '').replace(/\/+$/, '');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Reloj continuo de Paraguay
function actualizarRelojParaguay() {
    const ahora = new Date();
    const formato = new Intl.DateTimeFormat('es-PY', {
        timeZone: 'America/Asuncion',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    }).format(ahora);

    const elem = document.getElementById('sesion-reloj-paraguay');
    if (elem) elem.textContent = `${formato} (Asunción)`;
}

// 1. Cargar catálogo de cátedras del docente
async function cargarCatedrasDocente() {
    try {
        const res = await fetch(`${getBaseUrl()}/src/api/PlanesAnuales/mis_asignaciones.php`);
        const json = await res.json();
        misCatedrasDocente = json.data || [];

        const sel = document.getElementById('selector-catedra-activa');
        if (!sel) return;

        if (misCatedrasDocente.length === 0) {
            sel.innerHTML = '<option value="">Sin cátedras asignadas</option>';
            return;
        }

        sel.innerHTML = misCatedrasDocente.map(c => `
            <option value="${c.id_grado}" data-asignacion="${c.id_asignacion}">
                ${escapeHtml(c.grado_nombre || 'Curso')} - ${escapeHtml(c.materia_nombre || 'Materia')} (${c.aula_nombre || 'Sin aula'})
            </option>
        `).join('');

        // Cargar sesión con la primera cátedra
        await cargarSesionClase(sel.value);
    } catch (e) {
        console.error("Error al cargar cátedras:", e);
    }
}

async function cambiarCatedraSesion() {
    const sel = document.getElementById('selector-catedra-activa');
    if (sel && sel.value) {
        await cargarSesionClase(sel.value);
    }
}

// 2. Cargar sesión completa con sus 3 componentes
async function cargarSesionClase(idGrado = null) {
    actualizarRelojParaguay();

    try {
        const qs = idGrado ? `?id_grado=${encodeURIComponent(idGrado)}` : '';
        const res = await fetch(`${getBaseUrl()}/src/api/Evaluaciones/leer_sesion_clase.php${qs}`);
        const json = await res.json();

        if (!res.ok || json.status !== 'success' || !json.data) {
            console.warn("No hay clase activa:", json.message);
            mostrarEstadoSinClaseActiva();
            return;
        }

        sesionActual = json.data;
        const clase = sesionActual.clase;

        // Metadatos
        document.getElementById('sesion-nombre-aula').textContent = clase.aula || 'Sin aula';
        document.getElementById('sesion-horario-aula').textContent = `${(clase.horario_inicio || '').substring(0, 5)} a ${(clase.horario_fin || '').substring(0, 5)}`;
        document.getElementById('leccion-unidad-nombre').textContent = clase.tema?.unidad || 'Unidad Temática';
        document.getElementById('leccion-tema-titulo').textContent = clase.tema?.titulo || 'Lección del Día';
        document.getElementById('leccion-capacidad-texto').textContent = clase.tema?.capacidad || 'Sin capacidad especificada.';
        document.getElementById('leccion-horas-catedra').textContent = String(clase.tema?.horas_catedra || 1);

        // Indicadores
        const divInd = document.getElementById('leccion-indicadores-lista');
        if (divInd) {
            const indList = clase.indicadores || [];
            divInd.innerHTML = indList.length > 0
                ? indList.map(i => `<div class="p-2 rounded-xl bg-white/80 dark:bg-slate-800/80 border border-purple-200 dark:border-purple-800 font-medium">📌 ${escapeHtml(i.descripcion || 'Indicador')}</div>`).join('')
                : '<div class="text-slate-400">Sin indicadores específicos para este tema.</div>';
        }

        // Renderizar los 3 componentes
        renderizarComponenteAsistencia();
        renderizarComponenteAnecdotario();
        renderizarComponenteEvaluacion();

    } catch (e) {
        console.error("Error al cargar sesión de clase:", e);
    }
}

function mostrarEstadoSinClaseActiva() {
    document.getElementById('sesion-nombre-aula').textContent = 'Fuera de horario';
    document.getElementById('leccion-tema-titulo').textContent = 'No hay sesión de clase activa en este momento';
    document.getElementById('leccion-capacidad-texto').textContent = 'Seleccione una cátedra en el selector superior para iniciar o revisar una sesión pedagógica.';
}

// -------------------------------------------------------------
// COMPONENTE 1: ASISTENCIA EN VIVO
// -------------------------------------------------------------
function renderizarComponenteAsistencia() {
    if (!sesionActual) return;
    const asist = sesionActual.asistencia;
    const metricas = asist.metricas || {};
    const estudiantes = asist.todos_estudiantes || [];

    document.getElementById('metrica-total-matriculados').textContent = String(metricas.total_matriculados || estudiantes.length);
    document.getElementById('metrica-total-presentes').textContent = String(metricas.presentes || 0);
    document.getElementById('metrica-total-ausentes').textContent = String(metricas.ausentes || 0);
    document.getElementById('metrica-porcentaje-asist').textContent = `${metricas.porcentaje_asistencia || 0}%`;

    document.getElementById('badge-cnt-presentes').textContent = `${metricas.presentes || 0}/${estudiantes.length}`;

    const tbody = document.getElementById('tabla-asistencia-estudiantes-body');
    if (!tbody) return;

    if (estudiantes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-slate-500">No hay estudiantes matriculados en este curso.</td></tr>';
        return;
    }

    tbody.innerHTML = estudiantes.map(e => {
        const esPresente = (parseInt(e.presente || 0) === 1 && e.estado_asistencia !== 'AUSENTE');
        const badgeEstado = esPresente
            ? `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-900 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                ✓ Presente
               </span>`
            : `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold bg-rose-100 text-rose-900 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-300 dark:border-rose-800">
                ✗ Ausente
               </span>`;

        const metodoTexto = esPresente
            ? `<span class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">${escapeHtml(e.hora_marcacion || '--:--')} (${e.tipo_registro || 'BIOMETRICO'})</span>`
            : `<span class="text-xs text-slate-400">Sin registro</span>`;

        return `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
                <td class="py-3 px-5 font-bold text-slate-900 dark:text-white">
                    <div>${escapeHtml(e.nombre_completo)}</div>
                    <div class="text-xs text-slate-500 font-mono">CI: ${escapeHtml(e.cedula_identidad)}</div>
                </td>
                <td class="py-3 px-5">
                    ${badgeEstado}
                </td>
                <td class="py-3 px-5">
                    ${metodoTexto}
                </td>
                <td class="py-3 px-5 text-right">
                    <button type="button" onclick="rectificarAsistenciaManual(${e.id_estudiante}, '${esPresente ? 'AUSENTE' : 'PRESENTE'}')" class="btn btn-secondary text-xs font-bold py-1.5 px-3 min-h-[36px]">
                        ${esPresente ? 'Marcar Ausente' : 'Marcar Presente'}
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    // Poblar selector de estudiantes en el modal anecdotario
    const selEstAnecdota = document.getElementById('anecdota-estudiante');
    if (selEstAnecdota) {
        selEstAnecdota.innerHTML = '<option value="">Seleccione estudiante...</option>' +
            estudiantes.map(e => `<option value="${e.id_estudiante}">${escapeHtml(e.nombre_completo)} (CI: ${escapeHtml(e.cedula_identidad)})</option>`).join('');
    }
}

async function rectificarAsistenciaManual(idEstudiante, nuevoEstado) {
    if (!sesionActual) return;
    try {
        const formData = new FormData();
        formData.append('id_clase', sesionActual.clase.id_clase);
        formData.append('id_estudiante', idEstudiante);
        formData.append('estado', nuevoEstado);
        formData.append('tipo_registro', 'MANUAL');

        const res = await fetch(`${getBaseUrl()}/src/api/Clases/marcar_asistencia.php`, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.status === 'success') {
            await cargarSesionClase(document.getElementById('selector-catedra-activa')?.value);
        } else {
            alert("Error al actualizar asistencia: " + json.message);
        }
    } catch (e) {
        alert("Error de comunicación: " + e.message);
    }
}

// -------------------------------------------------------------
// COMPONENTE 2: REGISTRO ANECDOTARIO INDIVIDUAL
// -------------------------------------------------------------
function renderizarComponenteAnecdotario() {
    if (!sesionActual) return;
    const anec = sesionActual.registro_anecdotario || {};
    const lista = anec.registros || [];

    document.getElementById('badge-cnt-anecdotas').textContent = String(lista.length);

    const contenedor = document.getElementById('contenedor-registros-anecdotarios');
    if (!contenedor) return;

    if (lista.length === 0) {
        contenedor.innerHTML = `
            <div class="p-10 text-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <span class="text-3xl">📝</span>
                <p class="font-bold text-base text-slate-700 dark:text-slate-300 mt-2">No se han registrado incidentes en esta sesión</p>
                <p class="text-sm text-slate-500 mt-1">Utilice el botón superior para documentar observaciones individuales.</p>
            </div>
        `;
        return;
    }

    contenedor.innerHTML = lista.map(r => `
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="font-black text-base text-slate-900 dark:text-white">${escapeHtml(r.estudiante_completo)}</span>
                    <span class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">CI: ${escapeHtml(r.estudiante_ci)}</span>
                </div>
                <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-medium">
                    "${escapeHtml(r.observacion)}"
                </p>
            </div>
            <div class="text-xs text-slate-400 font-mono shrink-0">
                ${escapeHtml((r.created_at || '').substring(0, 16))}
            </div>
        </div>
    `).join('');
}

function abrirModalNuevoAnecdotario() {
    const modal = document.getElementById('modal-nuevo-anecdotario');
    const form = document.getElementById('form-nuevo-anecdotario');
    if (form) form.reset();
    if (modal) modal.classList.remove('hidden');
}

function cerrarModalNuevoAnecdotario() {
    const modal = document.getElementById('modal-nuevo-anecdotario');
    if (modal) modal.classList.add('hidden');
}

// -------------------------------------------------------------
// COMPONENTE 3: EVALUACIÓN DE LA LECCIÓN DEL DÍA (MEC)
// -------------------------------------------------------------
function renderizarComponenteEvaluacion() {
    if (!sesionActual) return;
    const evData = sesionActual.evaluacion_leccion || {};
    catalogoPlantillasSesion = evData.plantillas_disponibles || [];
    const estudiantes = sesionActual.asistencia?.todos_estudiantes || [];

    document.getElementById('badge-cnt-evaluados').textContent = `${evData.total_evaluados || 0} evaluados`;

    // Selector de plantillas
    const selPlant = document.getElementById('selector-plantilla-evaluativa');
    if (selPlant) {
        selPlant.innerHTML = '<option value="">Seleccione instrumento del MEC...</option>' +
            catalogoPlantillasSesion.map(p => `
                <option value="${p.id_plantilla}" ${plantillaSeleccionadaActual?.id_plantilla === p.id_plantilla ? 'selected' : ''}>
                    ${escapeHtml(p.titulo)} (${p.puntaje_total} pts)
                </option>
            `).join('');

        if (!plantillaSeleccionadaActual && catalogoPlantillasSesion.length > 0) {
            aplicarPlantillaEvaluativa(String(catalogoPlantillasSesion[0].id_plantilla));
        }
    }

    renderizarTablaCalificacionEstudiantes(estudiantes);
}

function aplicarPlantillaEvaluativa(idPlantillaStr) {
    const idP = parseInt(idPlantillaStr);
    plantillaSeleccionadaActual = catalogoPlantillasSesion.find(p => p.id_plantilla === idP) || null;

    const box = document.getElementById('box-criterios-plantilla-activa');
    if (!box) return;

    if (!plantillaSeleccionadaActual) {
        box.classList.add('hidden');
        return;
    }

    box.classList.remove('hidden');
    document.getElementById('plantilla-activa-titulo').textContent = plantillaSeleccionadaActual.titulo;
    document.getElementById('plantilla-activa-puntos').textContent = `Puntaje Total: ${plantillaSeleccionadaActual.puntaje_total} pts`;

    const divCrit = document.getElementById('plantilla-activa-criterios-lista');
    if (divCrit) {
        const critList = Array.isArray(plantillaSeleccionadaActual.criterios) ? plantillaSeleccionadaActual.criterios : [];
        divCrit.innerHTML = critList.map(c => `
            <div class="p-2 rounded-xl bg-white/90 dark:bg-slate-900/90 border border-indigo-200 dark:border-indigo-800 font-medium">
                ${escapeHtml(c.indicador || c.criterio || 'Criterio')} <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">(${c.puntaje_max || 1}p)</span>
            </div>
        `).join('');
    }
}

function renderizarTablaCalificacionEstudiantes(estudiantes) {
    const tbody = document.getElementById('tabla-evaluacion-estudiantes-body');
    if (!tbody) return;

    if (estudiantes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-slate-500">No hay estudiantes para calificar.</td></tr>';
        return;
    }

    const puntajeMaxDefecto = plantillaSeleccionadaActual ? plantillaSeleccionadaActual.puntaje_total : 5.0;

    tbody.innerHTML = estudiantes.map(e => {
        const evalExistente = e.evaluacion || null;
        const ptsObt = evalExistente ? evalExistente.puntaje_obtenido : '';
        const ptsMax = evalExistente ? evalExistente.puntaje_maximo : puntajeMaxDefecto;
        const obs = evalExistente ? (evalExistente.observaciones || '') : '';

        return `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
                <td class="py-3 px-5 font-bold text-slate-900 dark:text-white">
                    <div>${escapeHtml(e.nombre_completo)}</div>
                    <div class="text-xs text-slate-500 font-mono">CI: ${escapeHtml(e.cedula_identidad)}</div>
                </td>
                <td class="py-3 px-5 text-center">
                    <div class="inline-flex items-center gap-1.5 justify-center">
                        <input type="number" min="0" max="${ptsMax}" step="0.5" id="eval-pts-${e.id_estudiante}" value="${ptsObt}" placeholder="0" class="app-input w-24 py-1.5 px-2 text-base font-black text-center rounded-xl">
                        <span class="text-sm font-bold text-slate-500">/ ${ptsMax} pts</span>
                    </div>
                </td>
                <td class="py-3 px-5">
                    <input type="text" id="eval-obs-${e.id_estudiante}" value="${escapeHtml(obs)}" placeholder="Retroalimentación cualitativa pedagógica..." class="app-input w-full py-1.5 px-3 text-sm rounded-xl">
                </td>
                <td class="py-3 px-5 text-right">
                    <button type="button" onclick="guardarCalificacionEstudiante(${e.id_estudiante}, ${ptsMax})" class="btn btn-primary text-xs font-bold py-1.5 px-3 min-h-[36px]">
                        Guardar
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

async function guardarCalificacionEstudiante(idEstudiante, puntajeMaximo) {
    if (!sesionActual) return;
    const inpPts = document.getElementById(`eval-pts-${idEstudiante}`);
    const inpObs = document.getElementById(`eval-obs-${idEstudiante}`);

    const ptsVal = parseFloat(inpPts?.value);
    if (isNaN(ptsVal) || ptsVal < 0) {
        alert("Ingrese un puntaje numérico válido mayor o igual a 0.");
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id_clase', sesionActual.clase.id_clase);
        formData.append('id_estudiante', idEstudiante);
        formData.append('id_plantilla', plantillaSeleccionadaActual ? plantillaSeleccionadaActual.id_plantilla : '');
        formData.append('id_instrumento', plantillaSeleccionadaActual ? plantillaSeleccionadaActual.id_instrumento : '');
        formData.append('puntaje_obtenido', ptsVal);
        formData.append('puntaje_maximo', puntajeMaximo);
        formData.append('observaciones', inpObs?.value || '');

        const res = await fetch(`${getBaseUrl()}/src/api/Evaluaciones/guardar_evaluacion_estudiante.php`, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.status === 'success') {
            inpPts.classList.add('border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-950/40');
            setTimeout(() => {
                inpPts.classList.remove('border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-950/40');
            }, 1500);
        } else {
            alert("Error: " + json.message);
        }
    } catch (e) {
        alert("Error al guardar evaluación: " + e.message);
    }
}

// Navegación de pestañas
function cambiarPestanaSesion(pestana) {
    ['asistencia', 'anecdotario', 'evaluacion'].forEach(p => {
        const sec = document.getElementById(`seccion-pestana-${p}`);
        const btn = document.getElementById(`tab-btn-${p}`);
        if (p === pestana) {
            sec?.classList.remove('hidden');
            btn?.classList.add('border-purple-600', 'text-purple-700', 'dark:text-purple-300', 'font-black');
            btn?.classList.remove('border-transparent', 'text-slate-500', 'font-bold');
        } else {
            sec?.classList.add('hidden');
            btn?.classList.remove('border-purple-600', 'text-purple-700', 'dark:text-purple-300', 'font-black');
            btn?.classList.add('border-transparent', 'text-slate-500', 'font-bold');
        }
    });
}

// Inicialización de la sesión
document.addEventListener('DOMContentLoaded', () => {
    cargarCatedrasDocente();
    setInterval(actualizarRelojParaguay, 1000);

    // Formulario Registro Anecdotario
    const formAnecdota = document.getElementById('form-nuevo-anecdotario');
    if (formAnecdota) {
        formAnecdota.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!sesionActual) return;

            const btn = document.getElementById('btn-submit-anecdota');
            if (btn) btn.disabled = true;

            try {
                const formData = new FormData(formAnecdota);
                formData.append('id_clase', sesionActual.clase.id_clase);

                const res = await fetch(`${getBaseUrl()}/src/api/Evaluaciones/guardar_registro_anecdotico.php`, {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (!res.ok || json.status !== 'success') {
                    throw new Error(json.message || "Error al asentar registro");
                }

                cerrarModalNuevoAnecdotario();
                await cargarSesionClase(document.getElementById('selector-catedra-activa')?.value);
            } catch (err) {
                alert("Error: " + err.message);
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
