<?php
declare(strict_types=1);

$titulo = "Gestión de Estudiantes";

require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full space-y-6">

    <!-- Encabezado Principal y Barra de Acciones -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 text-xl font-bold">
                    🎓
                </span>
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        Gestión de Estudiantes
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Matriculación, nómina por curso con filtro en cascada, importación y exportación masiva.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" onclick="abrirModalCrearEstudiante()" class="btn btn-success text-xs font-bold py-2 px-3.5 inline-flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Nuevo Estudiante</span>
            </button>

            <button type="button" onclick="abrirModalImportarEstudiantes(document.getElementById('filtro-grado')?.value)" class="btn btn-secondary text-xs font-semibold py-2 px-3 inline-flex items-center gap-1.5" title="Importar estudiantes desde archivo CSV / Excel">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span>📥 Importar</span>
            </button>

            <button type="button" onclick="exportarNominaActual()" class="btn btn-secondary text-xs font-semibold py-2 px-3 inline-flex items-center gap-1.5" title="Exportar nómina a CSV / Excel">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>📤 Exportar</span>
            </button>

            <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="btn btn-primary text-xs py-2 px-3">
                Volver
            </a>
        </div>
    </div>

    <!-- Filtros en cascada: Turno -> Nivel -> Bachillerato -> Grado/Curso -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs">
        <div>
            <label for="filtro-turno" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                1. Turno
            </label>
            <select id="filtro-turno" class="app-input w-full text-xs font-semibold">
                <option value="">Todos los turnos</option>
            </select>
        </div>

        <div>
            <label for="filtro-nivel" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                2. Ciclo / Nivel
            </label>
            <select id="filtro-nivel" class="app-input w-full text-xs font-semibold">
                <option value="">Todos los ciclos</option>
            </select>
        </div>

        <div>
            <label for="filtro-bachillerato" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                3. Bachillerato
            </label>
            <select id="filtro-bachillerato" class="app-input w-full text-xs font-semibold">
                <option value="">Todos / Sin bachillerato</option>
            </select>
        </div>

        <div>
            <label for="filtro-grado" class="block text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-1.5">
                4. Grado o Curso *
            </label>
            <select id="filtro-grado" class="app-input w-full text-xs font-bold border-blue-400 dark:border-blue-500 focus:border-blue-600">
                <option value="">Seleccione grado o curso...</option>
                <option value="TODOS">-- Ver todos los cursos --</option>
            </select>
        </div>
    </div>

    <!-- Tira de Metadatos del Grado Seleccionado -->
    <div id="info-grado-seleccionado" class="hidden p-4 rounded-2xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200/60 dark:border-blue-900/40 flex flex-wrap items-center justify-between gap-4 text-xs">
        <div class="flex flex-wrap items-center gap-4">
            <span class="font-bold text-blue-800 dark:text-blue-300 text-sm flex items-center gap-1.5">
                <span id="meta-grado-nombre" class="font-extrabold text-base">3° BTI</span>
            </span>
            <span class="text-slate-300 dark:text-slate-700">•</span>
            <span class="text-slate-600 dark:text-slate-300">Aula: <strong id="meta-grado-aula" class="text-slate-800 dark:text-slate-100 font-bold">-</strong></span>
            <span class="text-slate-300 dark:text-slate-700">•</span>
            <span class="text-slate-600 dark:text-slate-300">Turno: <strong id="meta-grado-turno" class="text-slate-800 dark:text-slate-100 font-bold">-</strong></span>
        </div>

        <div class="flex items-center gap-3">
            <div class="px-3 py-1 rounded-lg bg-white/80 dark:bg-slate-900/80 border border-blue-200 dark:border-blue-800 font-semibold text-slate-700 dark:text-slate-300">
                Matriculados: <span id="meta-total-estudiantes" class="font-bold text-blue-600 dark:text-blue-400">0</span>
            </div>
            <div class="px-3 py-1 rounded-lg bg-white/80 dark:bg-slate-900/80 border border-blue-200 dark:border-blue-800 font-semibold text-slate-700 dark:text-slate-300">
                Con Huella: <span id="meta-total-huellas" class="font-bold text-emerald-600 dark:text-emerald-400">0</span>
            </div>
            <div class="px-3 py-1 rounded-lg bg-white/80 dark:bg-slate-900/80 border border-blue-200 dark:border-blue-800 font-semibold text-slate-700 dark:text-slate-300">
                Con Tutor: <span id="meta-total-tutores" class="font-bold text-indigo-600 dark:text-indigo-400">0</span>
            </div>
        </div>
    </div>

    <!-- Estado Inicial / Prompt de Selección (cuando NO se ha elegido curso) -->
    <div id="prompt-seleccion-grado" class="p-12 text-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto text-2xl">
            🔍
        </div>
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">
            Seleccioná un grado o curso para consultar la nómina
        </h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto">
            Para garantizar la máxima velocidad del sistema, filtrá por turno, ciclo o bachillerato en los selectores superiores para consultar el curso deseado.
        </p>
        <button type="button" onclick="cargarEstudiantes('TODOS')" class="btn btn-secondary text-xs py-2 px-4 font-semibold inline-flex items-center gap-1.5 mt-2">
            <span>Ver todos los estudiantes del colegio</span>
        </button>
    </div>

    <!-- Contenedor Principal de la Tabla de Estudiantes -->
    <div id="contenedor-tabla-estudiantes" class="hidden space-y-4">
        
        <!-- Barra de búsqueda local y conteo -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" id="busqueda-estudiantes-filtro" placeholder="Filtrar por nombre, apellido o CI en esta nómina..."
                       class="app-input w-full pl-9 pr-4 text-xs">
            </div>

            <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                Mostrando <span id="contador-filtrados" class="font-bold text-slate-800 dark:text-slate-100">0</span> de <span id="contador-total" class="font-bold">0</span> alumnos
            </div>
        </div>

        <!-- Tabla -->
        <div class="app-table-wrap rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
            <table class="min-w-full text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider">
                    <tr>
                        <th class="p-3.5 text-left">CI</th>
                        <th class="p-3.5 text-left">Estudiante</th>
                        <th class="p-3.5 text-left">Grado / Curso</th>
                        <th class="p-3.5 text-center">Materias</th>
                        <th class="p-3.5 text-left">Huella Biométrica</th>
                        <th class="p-3.5 text-left">Tutores / Familia</th>
                        <th class="p-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-estudiantes-body" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <!-- Filas dinámicas -->
                </tbody>
            </table>
        </div>

    </div>

    <!-- Modal Crear Estudiante -->
    <div id="modal-crear-estudiante" class="app-modal hidden" role="dialog" aria-modal="true">
        <div class="app-modal-dialog max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-5 sm:p-6 space-y-4">
            <div class="flex items-start justify-between gap-4 pb-2 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Registrar Nuevo Estudiante</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Crea la ficha del alumno y matricula automáticamente en sus materias.</p>
                </div>
                <button type="button" onclick="cerrarModalCrearEstudiante()" class="app-modal-close text-xl font-bold">×</button>
            </div>

            <form id="form-crear-estudiante" class="space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Nombre *</label>
                        <input type="text" name="nombre" class="app-input w-full text-xs" required>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Apellido *</label>
                        <input type="text" name="apellido" class="app-input w-full text-xs" required>
                    </div>
                </div>

                <div>
                    <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Cédula de Identidad *</label>
                    <input type="text" name="cedula_identidad" placeholder="Ej: 6306661" class="app-input w-full text-xs font-mono" required>
                    <p class="text-[10px] text-slate-400 mt-0.5">Servirá como usuario y contraseña inicial para el acceso del alumno.</p>
                </div>

                <div>
                    <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Grado / Curso *</label>
                    <select name="id_grado" id="modal-crear-grado-select" class="app-input w-full text-xs font-semibold" required>
                        <option value="">Seleccione grado o curso...</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="cerrarModalCrearEstudiante()" class="btn btn-muted text-xs py-2 px-4">Cancelar</button>
                    <button type="submit" id="btn-submit-crear-est" class="btn btn-success text-xs py-2 px-5 font-bold">Guardar Estudiante</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Estudiante -->
    <div id="modal-editar-estudiante" class="app-modal hidden" role="dialog" aria-modal="true">
        <div class="app-modal-dialog max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-5 sm:p-6 space-y-4">
            <div class="flex items-start justify-between gap-4 pb-2 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Editar Datos del Estudiante</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Actualizá los datos de filiación o cambiá el grado asignado.</p>
                </div>
                <button type="button" onclick="cerrarModalEditarEstudiante()" class="app-modal-close text-xl font-bold">×</button>
            </div>

            <form id="form-editar-estudiante" class="space-y-3 text-xs">
                <input type="hidden" name="id_estudiante" id="edit-id-estudiante">

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Nombre *</label>
                        <input type="text" name="nombre" id="edit-nombre" class="app-input w-full text-xs" required>
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Apellido *</label>
                        <input type="text" name="apellido" id="edit-apellido" class="app-input w-full text-xs" required>
                    </div>
                </div>

                <div>
                    <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Cédula de Identidad *</label>
                    <input type="text" name="cedula_identidad" id="edit-cedula" class="app-input w-full text-xs font-mono" required>
                </div>

                <div>
                    <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Grado / Curso</label>
                    <select name="id_grado" id="edit-grado" class="app-input w-full text-xs font-semibold">
                        <option value="">Seleccione grado o curso...</option>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-0.5">Al cambiar de curso, se actualizarán las matrículas correspondientes.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="cerrarModalEditarEstudiante()" class="btn btn-muted text-xs py-2 px-4">Cancelar</button>
                    <button type="submit" id="btn-submit-editar-est" class="btn btn-primary text-xs py-2 px-5 font-bold">Actualizar Datos</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modales Importar y Gestor de Familias -->
    <?php require_once __DIR__ . '/modal_importar_estudiantes.php'; ?>
    <?php require_once __DIR__ . '/../tutores/modal_vincular_familia.php'; ?>

</main>

<script src="<?= base_url('public/js/obtener_template.js') ?>"></script>

<script>
let catalogoGrados = [];
let estudiantesCargados = [];

function getBaseUrl() {
    return (window.BASE_URL || '').replace(/\/+$/, '');
}

// 1. Cargar catálogo de grados para alimentar los filtros en cascada
async function cargarEstructuraCascada() {
    try {
        const res = await fetch(`${getBaseUrl()}/src/api/Grado/leer_grados.php`);
        const json = await res.json();
        catalogoGrados = json.data || [];
        window.todosLosGradosCatalogo = catalogoGrados;

        inicializarSelectoresCascada();
        poblarSelectoresModales();
    } catch (e) {
        console.error("Error cargando estructura de grados:", e);
    }
}

function inicializarSelectoresCascada() {
    const fTurno = document.getElementById('filtro-turno');
    const fNivel = document.getElementById('filtro-nivel');
    const fBachi = document.getElementById('filtro-bachillerato');
    const fGrado = document.getElementById('filtro-grado');

    if (!fTurno || !fNivel || !fBachi || !fGrado) return;

    // Poblar Turnos únicos
    const turnos = [...new Map(catalogoGrados.filter(g => g.id_turno).map(g => [g.id_turno, g.turno])).entries()];
    fTurno.innerHTML = '<option value="">Todos los turnos</option>' +
        turnos.map(([id, nom]) => `<option value="${id}">${nom}</option>`).join('');

    // Listeners
    fTurno.addEventListener('change', actualizarNivelesCascada);
    fNivel.addEventListener('change', actualizarBachilleratosCascada);
    fBachi.addEventListener('change', actualizarGradosCascada);
    fGrado.addEventListener('change', (e) => {
        const idG = e.target.value;
        if (idG) {
            cargarEstudiantes(idG);
        } else {
            mostrarPromptVacio();
        }
    });

    actualizarNivelesCascada();
}

function actualizarNivelesCascada() {
    const fTurno = document.getElementById('filtro-turno');
    const fNivel = document.getElementById('filtro-nivel');
    const turnoVal = fTurno?.value || '';

    let filtrados = catalogoGrados;
    if (turnoVal) {
        filtrados = filtrados.filter(g => String(g.id_turno) === String(turnoVal));
    }

    const niveles = [...new Map(filtrados.filter(g => g.id_nivel).map(g => [g.id_nivel, g.nivel])).entries()];
    if (fNivel) {
        fNivel.innerHTML = '<option value="">Todos los ciclos</option>' +
            niveles.map(([id, nom]) => `<option value="${id}">${nom}</option>`).join('');
    }

    actualizarBachilleratosCascada();
}

function actualizarBachilleratosCascada() {
    const fTurno = document.getElementById('filtro-turno');
    const fNivel = document.getElementById('filtro-nivel');
    const fBachi = document.getElementById('filtro-bachillerato');

    const turnoVal = fTurno?.value || '';
    const nivelVal = fNivel?.value || '';

    let filtrados = catalogoGrados;
    if (turnoVal) filtrados = filtrados.filter(g => String(g.id_turno) === String(turnoVal));
    if (nivelVal) filtrados = filtrados.filter(g => String(g.id_nivel) === String(nivelVal));

    const bachis = [...new Map(filtrados.filter(g => g.id_bachillerato && g.bachillerato !== 'N/A').map(g => [g.id_bachillerato, g.bachillerato])).entries()];
    if (fBachi) {
        fBachi.innerHTML = '<option value="">Todos / Sin bachillerato</option>' +
            bachis.map(([id, nom]) => `<option value="${id}">${nom}</option>`).join('');
    }

    actualizarGradosCascada();
}

function actualizarGradosCascada() {
    const fTurno = document.getElementById('filtro-turno');
    const fNivel = document.getElementById('filtro-nivel');
    const fBachi = document.getElementById('filtro-bachillerato');
    const fGrado = document.getElementById('filtro-grado');

    const turnoVal = fTurno?.value || '';
    const nivelVal = fNivel?.value || '';
    const bachiVal = fBachi?.value || '';

    let filtrados = catalogoGrados;
    if (turnoVal) filtrados = filtrados.filter(g => String(g.id_turno) === String(turnoVal));
    if (nivelVal) filtrados = filtrados.filter(g => String(g.id_nivel) === String(nivelVal));
    if (bachiVal) filtrados = filtrados.filter(g => String(g.id_bachillerato) === String(bachiVal));

    if (fGrado) {
        const valorPrevio = fGrado.value;
        fGrado.innerHTML = '<option value="">Seleccione grado o curso...</option>' +
            '<option value="TODOS">-- Ver todos los cursos --</option>' +
            filtrados.map(g => `<option value="${g.id_grado}">${g.nombre} (${g.turno || 'Sin turno'})</option>`).join('');

        if (valorPrevio && Array.from(fGrado.options).some(o => o.value === valorPrevio)) {
            fGrado.value = valorPrevio;
        }
    }
}

function poblarSelectoresModales() {
    const opts = '<option value="">Seleccione grado o curso...</option>' +
        catalogoGrados.map(g => `<option value="${g.id_grado}">${g.nombre} - ${g.aula || 'Sin aula'} (${g.turno || ''})</option>`).join('');

    const selCrear = document.getElementById('modal-crear-grado-select');
    const selEdit = document.getElementById('edit-grado');
    if (selCrear) selCrear.innerHTML = opts;
    if (selEdit) selEdit.innerHTML = opts;
}

// 2. Cargar nómina de estudiantes (On-Demand)
async function cargarEstudiantes(idGrado) {
    const promptBox = document.getElementById('prompt-seleccion-grado');
    const metaBox = document.getElementById('info-grado-seleccionado');
    const tableContainer = document.getElementById('contenedor-tabla-estudiantes');
    const tbody = document.getElementById('tabla-estudiantes-body');

    if (!idGrado) {
        mostrarPromptVacio();
        return;
    }

    if (promptBox) promptBox.classList.add('hidden');
    if (metaBox) metaBox.classList.remove('hidden');
    if (tableContainer) tableContainer.classList.remove('hidden');

    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="7" class="p-6 text-center text-slate-400">Cargando nómina de estudiantes...</td></tr>';
    }

    // Actualizar metadatos del encabezado
    if (idGrado === 'TODOS') {
        document.getElementById('meta-grado-nombre').textContent = 'Todos los Cursos';
        document.getElementById('meta-grado-aula').textContent = 'Todas las aulas';
        document.getElementById('meta-grado-turno').textContent = 'Todos los turnos';
    } else {
        const gradoInfo = catalogoGrados.find(g => String(g.id_grado) === String(idGrado));
        if (gradoInfo) {
            document.getElementById('meta-grado-nombre').textContent = gradoInfo.nombre;
            document.getElementById('meta-grado-aula').textContent = gradoInfo.aula || 'Sin aula';
            document.getElementById('meta-grado-turno').textContent = gradoInfo.turno || 'Sin turno';
        }
    }

    try {
        const param = idGrado === 'TODOS' ? '' : `?id_grado=${idGrado}`;
        const res = await fetch(`${getBaseUrl()}/src/api/Estudiante/leer_estudiantes.php${param}`);
        const json = await res.json();
        estudiantesCargados = json.data || [];

        // Actualizar contadores
        const total = estudiantesCargados.length;
        const huellas = estudiantesCargados.filter(e => parseInt(e.huella_registrada || 0) > 0 || parseInt(e.huella_id || 0) > 0).length;
        const tutores = estudiantesCargados.filter(e => parseInt(e.total_tutores || 0) > 0).length;

        document.getElementById('meta-total-estudiantes').textContent = String(total);
        document.getElementById('meta-total-huellas').textContent = String(huellas);
        document.getElementById('meta-total-tutores').textContent = String(tutores);

        renderizarTablaEstudiantes(estudiantesCargados);
    } catch (e) {
        console.error("Error al cargar estudiantes:", e);
        if (tbody) tbody.innerHTML = '<tr><td colspan="7" class="p-6 text-center text-rose-500 font-medium">Error al cargar la nómina de estudiantes</td></tr>';
    }
}

function mostrarPromptVacio() {
    document.getElementById('prompt-seleccion-grado')?.classList.remove('hidden');
    document.getElementById('info-grado-seleccionado')?.classList.add('hidden');
    document.getElementById('contenedor-tabla-estudiantes')?.classList.add('hidden');
    estudiantesCargados = [];
}

function renderizarTablaEstudiantes(lista) {
    const tbody = document.getElementById('tabla-estudiantes-body');
    const cntFiltrados = document.getElementById('contador-filtrados');
    const cntTotal = document.getElementById('contador-total');

    if (cntFiltrados) cntFiltrados.textContent = String(lista.length);
    if (cntTotal) cntTotal.textContent = String(estudiantesCargados.length);

    if (!tbody) return;

    if (lista.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="p-8 text-center text-slate-400 font-medium">No se encontraron estudiantes para mostrar</td></tr>';
        return;
    }

    tbody.innerHTML = lista.map(est => {
        const tieneHuella = parseInt(est.huella_registrada || 0) > 0 || parseInt(est.huella_id || 0) > 0;
        const totalTut = parseInt(est.total_tutores || 0);

        let badgeTutores = '';
        if (totalTut > 0) {
            badgeTutores = `
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 font-semibold text-[11px] truncate max-w-[230px]" title="${est.tutores_resumen || ''}">
                        👤 ${est.tutores_resumen || totalTut + ' tutor(es)'}
                    </span>
                    <button type="button" onclick="abrirModalVincular(${est.id_estudiante}, '${escaparJs(est.nombre)}', '${escaparJs(est.apellido)}', '${est.cedula_identidad}', '${escaparJs(est.grado || '')}')"
                            class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-bold">
                        + Otro
                    </button>
                </div>`;
        } else {
            badgeTutores = `
                <div class="flex items-center gap-2">
                    <span class="text-slate-400 text-[11px]">Sin tutor</span>
                    <button type="button" onclick="abrirModalVincular(${est.id_estudiante}, '${escaparJs(est.nombre)}', '${escaparJs(est.apellido)}', '${est.cedula_identidad}', '${escaparJs(est.grado || '')}')"
                            class="px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-bold text-[10px] hover:bg-indigo-100 transition">
                        + Vincular
                    </button>
                </div>`;
        }

        const jsonEst = JSON.stringify(est).replace(/"/g, '&quot;');

        return `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                <td class="p-3 font-mono font-bold text-slate-700 dark:text-slate-200">${est.cedula_identidad}</td>
                <td class="p-3">
                    <span class="font-bold text-slate-900 dark:text-white text-xs">${est.apellido}, ${est.nombre}</span>
                </td>
                <td class="p-3">
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 font-semibold text-slate-700 dark:text-slate-300 text-[11px]">
                        ${est.grado || 'Sin curso'}
                    </span>
                </td>
                <td class="p-3 text-center">
                    <span class="px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-bold text-[10px]">
                        ${est.materias_matriculadas || 0} mat.
                    </span>
                </td>
                <td class="p-3">
                    ${tieneHuella 
                        ? `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold border border-emerald-200 dark:border-emerald-500/20">✓ Huella Activa</span>`
                        : `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 text-[10px] font-medium border border-amber-200 dark:border-amber-500/20">⚠ Sin huella</span>`
                    }
                </td>
                <td class="p-3">
                    ${badgeTutores}
                </td>
                <td class="p-3 text-right">
                    <div class="flex items-center justify-end gap-1.5 flex-wrap">
                        <button type="button" onclick="abrirModalEditarEstudiante(${jsonEst})"
                                class="px-2 py-1 rounded bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 font-semibold text-[11px] text-slate-700 dark:text-slate-200 transition">
                            Editar
                        </button>
                        <a href="${getBaseUrl()}/mvc/views/tutores/expediente_estudiante.php?id_estudiante=${est.id_estudiante}"
                           class="px-2 py-1 rounded bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-300 font-semibold text-[11px] transition">
                            Expediente
                        </a>
                        ${est.user_id_global ? `
                        <button type="button" onclick="descargarYGuardarTemplate('${est.user_id_global}', 'estudiante')"
                                class="px-2 py-1 rounded bg-emerald-50 dark:bg-emerald-900/30 hover:bg-emerald-100 text-emerald-700 dark:text-emerald-300 font-semibold text-[11px] transition" title="Registrar huella biométrica">
                            Huella
                        </button>` : ''}
                        <button type="button" onclick="desactivarEstudiante(${est.id_estudiante}, '${escaparJs(est.nombre)} ${escaparJs(est.apellido)}')"
                                class="px-2 py-1 rounded hover:bg-rose-50 text-rose-600 text-[11px] font-medium transition" title="Desactivar estudiante">
                            ✕
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function escaparJs(str) {
    if (!str) return '';
    return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

// 3. Exportación dinámica
function exportarNominaActual() {
    const fGrado = document.getElementById('filtro-grado');
    const fTurno = document.getElementById('filtro-turno');
    const fNivel = document.getElementById('filtro-nivel');
    const fBachi = document.getElementById('filtro-bachillerato');

    const params = new URLSearchParams();
    if (fGrado && fGrado.value && fGrado.value !== 'TODOS') params.append('id_grado', fGrado.value);
    if (fTurno && fTurno.value) params.append('id_turno', fTurno.value);
    if (fNivel && fNivel.value) params.append('id_nivel', fNivel.value);
    if (fBachi && fBachi.value) params.append('id_bachillerato', fBachi.value);

    const qs = params.toString() ? `?${params.toString()}` : '';
    window.location.href = `${getBaseUrl()}/src/api/Estudiante/exportar_estudiantes.php${qs}`;
}

// Función callback para recargar la nómina actual tras importar
window.recargarListaEstudiantesActual = function() {
    const fGrado = document.getElementById('filtro-grado');
    if (fGrado && fGrado.value) {
        cargarEstudiantes(fGrado.value);
    }
};

// 4. Modales Crear y Editar Estudiante
function abrirModalCrearEstudiante() {
    const modal = document.getElementById('modal-crear-estudiante');
    const form = document.getElementById('form-crear-estudiante');
    const selGrado = document.getElementById('modal-crear-grado-select');
    const fGrado = document.getElementById('filtro-grado');

    if (!modal) return;
    if (form) form.reset();

    if (selGrado && fGrado && fGrado.value && fGrado.value !== 'TODOS') {
        selGrado.value = fGrado.value;
    }

    modal.classList.remove('hidden');
    document.body.classList.add('modal-open');
}

function cerrarModalCrearEstudiante() {
    const modal = document.getElementById('modal-crear-estudiante');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('modal-open');
    }
}

function abrirModalEditarEstudiante(est) {
    const modal = document.getElementById('modal-editar-estudiante');
    if (!modal || !est) return;

    document.getElementById('edit-id-estudiante').value = est.id_estudiante;
    document.getElementById('edit-nombre').value = est.nombre || '';
    document.getElementById('edit-apellido').value = est.apellido || '';
    document.getElementById('edit-cedula').value = est.cedula_identidad || '';
    document.getElementById('edit-grado').value = est.id_grado || '';

    modal.classList.remove('hidden');
    document.body.classList.add('modal-open');
}

function cerrarModalEditarEstudiante() {
    const modal = document.getElementById('modal-editar-estudiante');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('modal-open');
    }
}

async function desactivarEstudiante(idEstudiante, nombreCompleto) {
    if (!confirm(`¿Desea dar de baja al estudiante "${nombreCompleto}"?`)) return;

    try {
        const formData = new FormData();
        formData.append('id_estudiante', idEstudiante);

        const res = await fetch(`${getBaseUrl()}/src/api/Estudiante/desactivar_estudiante.php`, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.status === 'success') {
            if (window.Toast) Toast.success("Estudiante dado de baja correctamente");
            window.recargarListaEstudiantesActual();
        } else {
            if (window.Toast) Toast.error(json.message || "Error al desactivar estudiante");
        }
    } catch (e) {
        console.error(e);
        if (window.Toast) Toast.error("Error al procesar la baja del estudiante");
    }
}

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    cargarEstructuraCascada();

    // Filtro de búsqueda en tiempo real dentro de la lista actual
    const inputFiltro = document.getElementById('busqueda-estudiantes-filtro');
    if (inputFiltro) {
        inputFiltro.addEventListener('input', (e) => {
            const q = e.target.value.toLowerCase().trim();
            if (!q) {
                renderizarTablaEstudiantes(estudiantesCargados);
                return;
            }
            const filtrados = estudiantesCargados.filter(est => 
                (est.nombre && est.nombre.toLowerCase().includes(q)) ||
                (est.apellido && est.apellido.toLowerCase().includes(q)) ||
                (est.cedula_identidad && est.cedula_identidad.includes(q)) ||
                (est.grado && est.grado.toLowerCase().includes(q))
            );
            renderizarTablaEstudiantes(filtrados);
        });
    }

    // Submit Crear Estudiante
    const formCrear = document.getElementById('form-crear-estudiante');
    if (formCrear) {
        formCrear.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-crear-est');
            if (btn) btn.disabled = true;

            try {
                const formData = new FormData(formCrear);
                const res = await fetch(`${getBaseUrl()}/src/api/Estudiante/crear_estudiante.php`, {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (json.status === 'success') {
                    if (window.Toast) Toast.success(json.message || "Estudiante registrado correctamente");
                    cerrarModalCrearEstudiante();
                    const selG = document.getElementById('filtro-grado');
                    const gradoCreado = formData.get('id_grado');
                    if (selG && gradoCreado) {
                        selG.value = String(gradoCreado);
                        cargarEstudiantes(gradoCreado);
                    } else {
                        window.recargarListaEstudiantesActual();
                    }
                } else {
                    if (window.Toast) Toast.error(json.message || "Error al crear estudiante");
                    else alert(json.message);
                }
            } catch (err) {
                console.error(err);
                if (window.Toast) Toast.error("Error de comunicación al registrar estudiante");
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    }

    // Submit Editar Estudiante
    const formEditar = document.getElementById('form-editar-estudiante');
    if (formEditar) {
        formEditar.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-editar-est');
            if (btn) btn.disabled = true;

            try {
                const formData = new FormData(formEditar);
                const res = await fetch(`${getBaseUrl()}/src/api/Estudiante/actualizar_estudiante.php`, {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (json.status === 'success') {
                    if (window.Toast) Toast.success(json.message || "Estudiante actualizado correctamente");
                    cerrarModalEditarEstudiante();
                    window.recargarListaEstudiantesActual();
                } else {
                    if (window.Toast) Toast.error(json.message || "Error al actualizar");
                    else alert(json.message);
                }
            } catch (err) {
                console.error(err);
                if (window.Toast) Toast.error("Error al actualizar estudiante");
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
