<?php
declare(strict_types=1);

$titulo = "Gestión de Estudiantes";

require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full space-y-6 text-base">

    <!-- Encabezado Principal y Barra de Acciones Accesibles -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-3">
                <span class="p-2.5 rounded-2xl bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 text-2xl font-bold shrink-0 border border-blue-200 dark:border-blue-800">
                    🎓
                </span>
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Gestión de Estudiantes
                    </h2>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1">
                        Matriculación escolar, nómina por curso, vinculación familiar y control biométrico.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button type="button" onclick="abrirModalCrearEstudiante()" class="btn btn-success text-base font-bold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2 shadow-sm" title="Registrar un nuevo estudiante">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Nuevo Estudiante</span>
            </button>

            <button type="button" onclick="abrirModalImportarEstudiantes(document.getElementById('filtro-grado')?.value)" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2" title="Importar estudiantes desde archivo CSV / Excel">
                <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span>Importar</span>
            </button>

            <button type="button" onclick="exportarNominaActual()" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2" title="Exportar nómina a CSV / Excel">
                <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Exportar</span>
            </button>

            <a href="<?= base_url('mvc/views/estudiantes/desactivados.php') ?>" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2" title="Consultar o restaurar estudiantes dados de baja">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <span>Desactivados</span>
            </a>

            <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="btn btn-primary text-base font-semibold py-2.5 px-4 min-h-[44px]">
                Volver
            </a>
        </div>
    </div>

    <!-- Filtros en cascada: Turno -> Nivel -> Bachillerato -> Grado/Curso (Tipografía Grande) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
        <div>
            <label for="filtro-turno" class="block text-sm sm:text-base font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                1. Turno
            </label>
            <select id="filtro-turno" class="app-input w-full text-base font-semibold py-2.5 px-3 rounded-xl min-h-[46px]">
                <option value="">Todos los turnos</option>
            </select>
        </div>

        <div>
            <label for="filtro-nivel" class="block text-sm sm:text-base font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                2. Ciclo / Nivel
            </label>
            <select id="filtro-nivel" class="app-input w-full text-base font-semibold py-2.5 px-3 rounded-xl min-h-[46px]">
                <option value="">Todos los ciclos</option>
            </select>
        </div>

        <div>
            <label for="filtro-bachillerato" class="block text-sm sm:text-base font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                3. Bachillerato
            </label>
            <select id="filtro-bachillerato" class="app-input w-full text-base font-semibold py-2.5 px-3 rounded-xl min-h-[46px]">
                <option value="">Todos / Sin bachillerato</option>
            </select>
        </div>

        <div>
            <label for="filtro-grado" class="block text-sm sm:text-base font-extrabold uppercase tracking-wider text-blue-700 dark:text-blue-300 mb-2">
                4. Grado o Curso *
            </label>
            <select id="filtro-grado" class="app-input w-full text-base font-bold py-2.5 px-3 rounded-xl min-h-[46px] border-blue-400 dark:border-blue-500 focus:border-blue-600">
                <option value="">Seleccione grado o curso...</option>
                <option value="TODOS">-- Ver todos los cursos --</option>
            </select>
        </div>
    </div>

    <!-- Tira de Metadatos del Grado Seleccionado -->
    <div id="info-grado-seleccionado" class="hidden p-5 rounded-3xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/60 flex flex-wrap items-center justify-between gap-4 text-base">
        <div class="flex flex-wrap items-center gap-3">
            <span class="font-bold text-blue-900 dark:text-blue-200 text-lg flex items-center gap-2">
                <span id="meta-grado-nombre" class="font-extrabold text-xl text-blue-900 dark:text-blue-100">3° BTI</span>
            </span>
            <span class="text-slate-300 dark:text-slate-700">•</span>
            <span class="text-slate-700 dark:text-slate-300 font-medium">Aula: <strong id="meta-grado-aula" class="text-slate-900 dark:text-white font-bold">-</strong></span>
            <span class="text-slate-300 dark:text-slate-700">•</span>
            <span class="text-slate-700 dark:text-slate-300 font-medium">Turno: <strong id="meta-grado-turno" class="text-slate-900 dark:text-white font-bold">-</strong></span>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <div class="px-3.5 py-1.5 rounded-xl bg-white/90 dark:bg-slate-900/90 border border-blue-200 dark:border-blue-800 font-semibold text-slate-800 dark:text-slate-200 text-base">
                Matriculados: <span id="meta-total-estudiantes" class="font-extrabold text-blue-700 dark:text-blue-300">0</span>
            </div>
            <div class="px-3.5 py-1.5 rounded-xl bg-white/90 dark:bg-slate-900/90 border border-blue-200 dark:border-blue-800 font-semibold text-slate-800 dark:text-slate-200 text-base">
                Con Huella: <span id="meta-total-huellas" class="font-extrabold text-emerald-600 dark:text-emerald-400">0</span>
            </div>
            <div class="px-3.5 py-1.5 rounded-xl bg-white/90 dark:bg-slate-900/90 border border-blue-200 dark:border-blue-800 font-semibold text-slate-800 dark:text-slate-200 text-base">
                Con Tutor: <span id="meta-total-tutores" class="font-extrabold text-indigo-600 dark:text-indigo-400">0</span>
            </div>
        </div>
    </div>

    <!-- Estado Inicial / Prompt de Selección (cuando NO se ha elegido curso) -->
    <div id="prompt-seleccion-grado" class="p-10 sm:p-14 text-center rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        <div class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 flex items-center justify-center mx-auto text-3xl font-bold border border-blue-200 dark:border-blue-800">
            🔍
        </div>
        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white">
            Seleccioná un grado o curso para consultar la nómina
        </h3>
        <p class="text-base text-slate-600 dark:text-slate-400 max-w-xl mx-auto leading-relaxed">
            Filtrá por turno, ciclo o bachillerato en los selectores superiores para consultar la nómina completa de alumnos matriculados.
        </p>
        <div class="pt-2">
            <button type="button" onclick="cargarEstudiantes('TODOS')" class="btn btn-secondary text-base py-3 px-6 font-bold inline-flex items-center gap-2 min-h-[46px] shadow-sm">
                <span>Ver todos los estudiantes del colegio</span>
            </button>
        </div>
    </div>

    <!-- Contenedor Principal de la Tabla / Lista de Estudiantes -->
    <div id="contenedor-tabla-estudiantes" class="hidden space-y-4">
        
        <!-- Barra de búsqueda local y conteo accesible -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" id="busqueda-estudiantes-filtro" placeholder="Buscar por nombre, apellido o cédula..."
                       class="app-input w-full pl-11 pr-4 py-3 text-base rounded-2xl min-h-[46px]">
            </div>

            <div class="text-base text-slate-700 dark:text-slate-300 font-semibold">
                Mostrando <span id="contador-filtrados" class="font-extrabold text-blue-700 dark:text-blue-300">0</span> de <span id="contador-total" class="font-extrabold text-slate-900 dark:text-white">0</span> alumnos
            </div>
        </div>

        <!-- Tabla Rediseñada: Fuentes Grandes (>= 16px / 12 pt) y Revelación Progresiva -->
        <div class="app-table-wrap rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-base">
                    <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800 text-sm sm:text-base font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-4 px-5 text-left">Estudiante & Cédula</th>
                            <th class="py-4 px-5 text-left">Curso & Matrícula</th>
                            <th class="py-4 px-5 text-left">Estado Biométrico</th>
                            <th class="py-4 px-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-estudiantes-body" class="divide-y divide-slate-100 dark:divide-slate-800">
                        <!-- Filas dinámicas -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modales Modulares Incluidos -->
    <?php require_once __DIR__ . '/modal_detalle_estudiante.php'; ?>
    <?php require_once __DIR__ . '/modal_biometria_estudiante.php'; ?>
    <?php require_once __DIR__ . '/modal_crear_estudiante.php'; ?>
    <?php require_once __DIR__ . '/modal_editar_estudiante.php'; ?>
    <?php require_once __DIR__ . '/modal_importar_estudiantes.php'; ?>
    <?php require_once __DIR__ . '/../tutores/modal_vincular_familia.php'; ?>

</main>

<script src="<?= base_url('public/js/obtener_template.js') ?>"></script>

<script>
let catalogoGrados = [];
let estudiantesCargados = [];
let estudianteSeleccionadoActivo = null;

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

function obtenerIniciales(nombre, apellido) {
    const n = (nombre || '').trim().charAt(0).toUpperCase();
    const a = (apellido || '').trim().charAt(0).toUpperCase();
    return (n + a) || 'ES';
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
    const fGrado = document.getElementById('filtro-grado');

    // Si no se especifica idGrado, conservar el curso o filtro actualmente seleccionado
    if (!idGrado) {
        idGrado = fGrado?.value || '';
    }

    if (!idGrado) {
        mostrarPromptVacio();
        return;
    }

    if (fGrado && idGrado && fGrado.value !== idGrado) {
        fGrado.value = idGrado;
    }

    if (promptBox) promptBox.classList.add('hidden');
    if (metaBox) metaBox.classList.remove('hidden');
    if (tableContainer) tableContainer.classList.remove('hidden');

    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-base text-slate-500 font-semibold">Cargando nómina de estudiantes...</td></tr>';
    }

    // Actualizar metadatos del encabezado
    if (idGrado === 'TODOS') {
        document.getElementById('meta-grado-nombre').textContent = 'Todos los Cursos del Colegio';
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
        let params = [];
        if (idGrado !== 'TODOS') {
            params.push(`id_grado=${encodeURIComponent(idGrado)}`);
        } else {
            const fTurno = document.getElementById('filtro-turno')?.value;
            const fNivel = document.getElementById('filtro-nivel')?.value;
            const fBachi = document.getElementById('filtro-bachillerato')?.value;
            if (fTurno) params.push(`id_turno=${encodeURIComponent(fTurno)}`);
            if (fNivel) params.push(`id_nivel=${encodeURIComponent(fNivel)}`);
            if (fBachi) params.push(`id_bachillerato=${encodeURIComponent(fBachi)}`);
        }

        const qs = params.length > 0 ? `?${params.join('&')}` : '';
        const res = await fetch(`${getBaseUrl()}/src/api/Estudiante/leer_estudiantes.php${qs}`);
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
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-base text-rose-600 font-bold">Error al cargar la nómina de estudiantes.</td></tr>';
        }
    }
}

function mostrarPromptVacio() {
    document.getElementById('prompt-seleccion-grado')?.classList.remove('hidden');
    document.getElementById('info-grado-seleccionado')?.classList.add('hidden');
    document.getElementById('contenedor-tabla-estudiantes')?.classList.add('hidden');
    estudiantesCargados = [];
}

// 3. Renderizar Tabla de Estudiantes (Rediseñada para Máxima Legibilidad)
function renderizarTablaEstudiantes(lista) {
    const tbody = document.getElementById('tabla-estudiantes-body');
    const cntFiltrados = document.getElementById('contador-filtrados');
    const cntTotal = document.getElementById('contador-total');

    if (cntFiltrados) cntFiltrados.textContent = String(lista.length);
    if (cntTotal) cntTotal.textContent = String(estudiantesCargados.length);

    if (!tbody) return;

    if (lista.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="p-10 text-center text-slate-500">
                    <p class="font-extrabold text-lg text-slate-800 dark:text-slate-200">No se encontraron estudiantes</p>
                    <p class="text-base mt-1">Pruebe ajustando los filtros superiores o registre un nuevo estudiante.</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = lista.map(est => {
        const tieneHuella = parseInt(est.huella_registrada || 0) > 0 || parseInt(est.huella_id || 0) > 0;
        const totalTut = parseInt(est.total_tutores || 0);

        // Badge Biométrico de Alto Contraste (>= 15px)
        const huellaBadge = tieneHuella
            ? `<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-bold bg-emerald-100 text-emerald-900 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                <span>Huella Vinculada</span>
               </span>`
            : `<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-bold bg-amber-100 text-amber-900 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>Sin registrar</span>
               </span>`;

        const iniciales = obtenerIniciales(est.nombre, est.apellido);
        const jsonEst = JSON.stringify(est).replace(/"/g, '&quot;');

        return `
            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                <!-- Columna 1: Estudiante & Cédula -->
                <td class="py-4 px-5">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200 flex items-center justify-center font-black text-lg border border-blue-200 dark:border-blue-800 shrink-0">
                            ${iniciales}
                        </div>
                        <div>
                            <div class="font-extrabold text-base sm:text-lg text-slate-900 dark:text-white leading-tight">
                                ${escapeHtml(est.apellido)}, ${escapeHtml(est.nombre)}
                            </div>
                            <div class="text-base text-slate-700 dark:text-slate-300 font-mono mt-0.5">
                                CI: <strong class="text-blue-700 dark:text-blue-300 font-bold">${escapeHtml(est.cedula_identidad)}</strong>
                            </div>
                        </div>
                    </div>
                </td>

                <!-- Columna 2: Curso & Matrícula -->
                <td class="py-4 px-5">
                    <div class="space-y-1">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-100/80 text-blue-900 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800 font-bold text-sm sm:text-base">
                            🏫 ${escapeHtml(est.grado || 'Sin curso asignado')}
                        </span>
                        <div class="text-sm font-semibold text-slate-600 dark:text-slate-400">
                            ${est.materias_matriculadas || 0} materias matriculadas
                        </div>
                    </div>
                </td>

                <!-- Columna 3: Estado Biométrico -->
                <td class="py-4 px-5">
                    <div class="flex items-center gap-2 flex-wrap">
                        ${huellaBadge}
                        <button type="button" onclick="abrirModalBiometriaEstudiante(${est.id_estudiante})"
                                class="btn btn-secondary text-sm font-bold py-1.5 px-3 min-h-[38px] inline-flex items-center gap-1.5"
                                title="Gestionar registro biométrico en el sensor ESP32">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.99 7.132"></path></svg>
                            <span>Biometría</span>
                        </button>
                        ${tieneHuella ? `
                        <button type="button" onclick="enviarHuellaEstudianteDirecto(${est.id_estudiante})"
                                class="btn bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-sm font-bold py-1.5 px-3 min-h-[38px] inline-flex items-center gap-1.5"
                                title="Enviar huella al aula asignada vía LoRa">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                            <span>Enviar a Aula</span>
                        </button>` : ''}
                    </div>
                </td>

                <!-- Columna 4: Acciones Principales con Objetivos Táctiles Grandes -->
                <td class="py-4 px-5 text-right whitespace-nowrap">
                    <div class="inline-flex items-center gap-2 justify-end">
                        <button type="button" onclick="abrirModalDetalleEstudiante(${est.id_estudiante})"
                                class="btn btn-primary text-base font-bold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-1.5 shadow-xs"
                                title="Ver ficha integral, tutores y expediente">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span>Ver Ficha</span>
                        </button>

                        <button type="button" onclick="abrirModalEditarEstudiante(${jsonEst})"
                                class="btn btn-secondary text-base font-semibold py-2.5 px-3.5 min-h-[44px] inline-flex items-center gap-1"
                                title="Editar datos del estudiante">
                            <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            <span>Editar</span>
                        </button>

                        <a href="${getBaseUrl()}/mvc/views/tutores/expediente_estudiante.php?id_estudiante=${est.id_estudiante}"
                           class="btn btn-secondary text-base font-semibold py-2.5 px-3.5 min-h-[44px] inline-flex items-center gap-1"
                           title="Consultar historial de faltas, calificaciones y citaciones">
                            <span>Expediente</span>
                        </a>

                        <button type="button" onclick="desactivarEstudiante(${est.id_estudiante}, '${escaparJs(est.nombre)} ${escaparJs(est.apellido)}')"
                                class="btn btn-danger text-base font-semibold py-2.5 px-3 min-h-[44px] inline-flex items-center justify-center"
                                title="Desactivar estudiante">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
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

// 4. Búsqueda local predictiva
document.addEventListener('DOMContentLoaded', () => {
    cargarEstructuraCascada();

    // Actualización reactiva sin recargar página ante eventos biométricos
    window.addEventListener('huellaRegistrada', () => {
        const selG = document.getElementById('filtro-grado');
        const val = selG ? selG.value : 'TODOS';
        cargarEstudiantes(val || 'TODOS');
    });

    const inputBusqueda = document.getElementById('busqueda-estudiantes-filtro');
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', (e) => {
            const q = (e.target.value || '').trim().toLowerCase();
            if (!q) {
                renderizarTablaEstudiantes(estudiantesCargados);
                return;
            }

            const filtrados = estudiantesCargados.filter(est => {
                const nom = (est.nombre || '').toLowerCase();
                const ape = (est.apellido || '').toLowerCase();
                const ci = (est.cedula_identidad || '').toLowerCase();
                const gra = (est.grado || '').toLowerCase();
                const tut = (est.tutores_resumen || '').toLowerCase();
                return nom.includes(q) || ape.includes(q) || ci.includes(q) || gra.includes(q) || tut.includes(q);
            });

            renderizarTablaEstudiantes(filtrados);
        });
    }

    // Configurar Formulario Crear Estudiante
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

                if (!res.ok || json.status !== 'success') {
                    throw new Error(json.message || "Error al registrar estudiante.");
                }

                cerrarModalCrearEstudiante();
                formCrear.reset();

                const selG = document.getElementById('filtro-grado');
                const val = selG ? selG.value : 'TODOS';
                cargarEstudiantes(val || 'TODOS');
            } catch (err) {
                alert("Error al registrar estudiante: " + err.message);
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    }

    // Configurar Formulario Editar Estudiante
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

                if (!res.ok || json.status !== 'success') {
                    throw new Error(json.message || "Error al actualizar estudiante.");
                }

                cerrarModalEditarEstudiante();

                const selG = document.getElementById('filtro-grado');
                const val = selG ? selG.value : 'TODOS';
                cargarEstudiantes(val || 'TODOS');
            } catch (err) {
                alert("Error al actualizar estudiante: " + err.message);
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    }
});

// ==========================================
// CONTROLADORES DE SUBMODALES
// ==========================================

// Submodal 1: Ficha Integral del Estudiante
function abrirModalDetalleEstudiante(idEstudiante) {
    const est = estudiantesCargados.find(e => parseInt(e.id_estudiante) === parseInt(idEstudiante));
    if (!est) return;

    estudianteSeleccionadoActivo = est;
    const modal = document.getElementById('modal-detalle-estudiante');
    if (!modal) return;

    // Encabezado
    document.getElementById('mde-nombre-estudiante').textContent = `${est.nombre} ${est.apellido}`;
    document.getElementById('mde-cedula-estudiante').textContent = est.cedula_identidad || 'Sin CI';
    document.getElementById('mde-avatar').textContent = obtenerIniciales(est.nombre, est.apellido);
    document.getElementById('mde-user-id-global').textContent = est.user_id_global || 'Pendiente de asignación';

    // Situación Académica
    document.getElementById('mde-grado-texto').textContent = est.grado || 'Sin curso asignado';
    document.getElementById('mde-materias-texto').textContent = `${est.materias_matriculadas || 0} asignaturas activas`;

    // Estado biométrico
    const tieneHuella = (parseInt(est.huella_registrada || 0) > 0) || (parseInt(est.huella_id || 0) > 0);
    const badgeHuella = document.getElementById('mde-badge-huella-resumen');
    if (badgeHuella) {
        badgeHuella.innerHTML = tieneHuella
            ? '<span class="text-emerald-700 dark:text-emerald-300 font-bold">✓ Huella Registrada en Base de Datos</span>'
            : '<span class="text-amber-700 dark:text-amber-400 font-bold">⚠️ Pendiente de Captura en Sensor</span>';
    }

    // Tutores
    const contTutores = document.getElementById('mde-tutores-texto');
    if (contTutores) {
        if (est.tutores_resumen && est.tutores_resumen.trim()) {
            contTutores.innerHTML = `<span class="font-bold text-indigo-900 dark:text-indigo-200">👨‍👩‍👧 Responsables registrados:</span><br>${escapeHtml(est.tutores_resumen)}`;
        } else {
            contTutores.textContent = 'Este alumno no cuenta con tutores o familiares vinculados aún.';
        }
    }

    // Botón Vincular Tutor
    const btnVincular = document.getElementById('mde-btn-vincular-tutor');
    if (btnVincular) {
        btnVincular.onclick = () => {
            cerrarModalDetalleEstudiante();
            abrirModalVincular(est.id_estudiante, est.nombre, est.apellido, est.cedula_identidad, est.grado || '');
        };
    }

    // Botón Expediente
    const linkExp = document.getElementById('mde-link-expediente');
    if (linkExp) {
        linkExp.href = `${getBaseUrl()}/mvc/views/tutores/expediente_estudiante.php?id_estudiante=${est.id_estudiante}`;
    }

    // Botón Editar
    const btnEditar = document.getElementById('mde-btn-editar-desde-ficha');
    if (btnEditar) {
        btnEditar.onclick = () => {
            cerrarModalDetalleEstudiante();
            abrirModalEditarEstudiante(est);
        };
    }

    // Botón Biometría
    const btnBio = document.getElementById('mde-btn-abrir-biometria');
    if (btnBio) {
        btnBio.onclick = () => {
            cerrarModalDetalleEstudiante();
            abrirModalBiometriaEstudiante(est.id_estudiante);
        };
    }

    modal.classList.remove('hidden');
}

function cerrarModalDetalleEstudiante() {
    const modal = document.getElementById('modal-detalle-estudiante');
    if (modal) modal.classList.add('hidden');
}

// Submodal 2: Asistente de Huella Biométrica Estudiantil
function abrirModalBiometriaEstudiante(idEstudiante) {
    const est = estudiantesCargados.find(e => parseInt(e.id_estudiante) === parseInt(idEstudiante));
    if (!est) return;

    estudianteSeleccionadoActivo = est;
    const modal = document.getElementById('modal-biometria-estudiante');
    if (!modal) return;

    document.getElementById('mbe-nombre-estudiante').textContent = `${est.nombre} ${est.apellido}`;
    document.getElementById('mbe-cedula-estudiante').textContent = est.cedula_identidad || 'Sin CI';
    document.getElementById('mbe-grado-estudiante').textContent = est.grado || 'Sin curso';

    // IP del registrador desde configuración centralizada
    const ipESP32 = (window.CONFIG_RED && window.CONFIG_RED.ipRegistrador) ? window.CONFIG_RED.ipRegistrador : "";
    const ipElem = document.getElementById('mbe-ip-nodo');
    if (ipElem) ipElem.textContent = ipESP32 || 'Configurado en el servidor';

    const tieneHuella = (parseInt(est.huella_registrada || 0) > 0) || (parseInt(est.huella_id || 0) > 0);
    const badgeWrap = document.getElementById('mbe-badge-estado');
    const btnCaptura = document.getElementById('mbe-btn-iniciar-captura');
    const btnEnviarAulas = document.getElementById('mbe-btn-enviar-aulas');

    const actualizarEstadoModal = (registrada) => {
        if (badgeWrap) {
            badgeWrap.className = registrada
                ? 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-base font-bold bg-emerald-100 text-emerald-900 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800'
                : 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-base font-bold bg-amber-100 text-amber-900 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300 dark:border-amber-800';
            badgeWrap.innerHTML = registrada ? '✓ Huella Registrada' : '⚠️ Sin Huella Registrada';
        }
        if (btnEnviarAulas) {
            btnEnviarAulas.disabled = !registrada;
        }
    };

    actualizarEstadoModal(tieneHuella);

    // Limpiar feedback anterior
    const feedbackBox = document.getElementById('mbe-feedback-box');
    if (feedbackBox) feedbackBox.classList.add('hidden');

    if (btnCaptura) {
        btnCaptura.disabled = false;
        btnCaptura.onclick = async () => {
            btnCaptura.disabled = true;
            if (btnEnviarAulas) btnEnviarAulas.disabled = true;
            if (feedbackBox) {
                feedbackBox.className = 'p-4 rounded-2xl border border-blue-300 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/40 text-center space-y-2 block';
                feedbackBox.classList.remove('hidden');
                document.getElementById('mbe-feedback-spinner').classList.remove('hidden');
                document.getElementById('mbe-feedback-msg').textContent = 'Conectando con sensor registrador y esperando huella...';
            }

            try {
                const res = await descargarYGuardarTemplate(est.user_id_global, 'estudiante');
                if (res && res.ok) {
                    est.huella_id = res.data?.id_huella || 1;
                    est.huella_registrada = 1;
                    actualizarEstadoModal(true);
                    if (feedbackBox) {
                        feedbackBox.className = 'p-4 rounded-2xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-center space-y-2 block';
                        document.getElementById('mbe-feedback-spinner').classList.add('hidden');
                        document.getElementById('mbe-feedback-msg').textContent = '✓ ¡Huella guardada exitosamente en el servidor!\nAhora puede enviarla al aula usando el botón "2. Enviar al Aula".';
                    }
                    const gradoActualReg = document.getElementById('filtro-grado')?.value || 'TODOS';
                    await cargarEstudiantes(gradoActualReg);
                } else if (res && !res.ok) {
                    if (feedbackBox) {
                        feedbackBox.className = 'p-4 rounded-2xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 text-center space-y-2 block';
                        document.getElementById('mbe-feedback-spinner').classList.add('hidden');
                        document.getElementById('mbe-feedback-msg').textContent = 'Error: ' + (res?.message || 'No se pudo guardar la huella');
                    }
                }
            } catch (err) {
                if (feedbackBox) {
                    feedbackBox.className = 'p-4 rounded-2xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 text-center space-y-2 block';
                    document.getElementById('mbe-feedback-spinner').classList.add('hidden');
                    document.getElementById('mbe-feedback-msg').textContent = 'Error: ' + err.message;
                }
            } finally {
                btnCaptura.disabled = false;
                const tieneHuellaAhora = (parseInt(est.huella_registrada || 0) > 0) || (parseInt(est.huella_id || 0) > 0);
                if (btnEnviarAulas) btnEnviarAulas.disabled = !tieneHuellaAhora;
            }
        };
    }

    if (btnEnviarAulas) {
        btnEnviarAulas.onclick = async () => {
            btnEnviarAulas.disabled = true;
            if (btnCaptura) btnCaptura.disabled = true;
            if (feedbackBox) {
                feedbackBox.className = 'p-4 rounded-2xl border border-indigo-300 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-950/40 text-center space-y-2 block';
                feedbackBox.classList.remove('hidden');
                document.getElementById('mbe-feedback-spinner').classList.remove('hidden');
                document.getElementById('mbe-feedback-msg').textContent = 'Encolando huella hacia el nodo del aula vía LoRa...';
            }

            try {
                const res = await enviarHuellaAulas({
                    idPersona: est.id_estudiante,
                    userIdGlobal: est.user_id_global,
                    idHuella: est.huella_id || null,
                    idAula: est.id_aula || null,
                    tipoUsuario: 'estudiante'
                });

                if (res && res.ok) {
                    if (feedbackBox) {
                        feedbackBox.className = 'p-4 rounded-2xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-center space-y-2 block';
                        document.getElementById('mbe-feedback-spinner').classList.add('hidden');
                        document.getElementById('mbe-feedback-msg').textContent = res.data?.message || '✓ ¡Huella encolada exitosamente para el aula!';
                    }
                    const gradoActualEnv = document.getElementById('filtro-grado')?.value || 'TODOS';
                    await cargarEstudiantes(gradoActualEnv);
                } else {
                    if (feedbackBox) {
                        feedbackBox.className = 'p-4 rounded-2xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 text-center space-y-2 block';
                        document.getElementById('mbe-feedback-spinner').classList.add('hidden');
                        document.getElementById('mbe-feedback-msg').textContent = 'Error al enviar al aula: ' + (res?.message || 'Error desconocido');
                    }
                }
            } catch (err) {
                if (feedbackBox) {
                    feedbackBox.className = 'p-4 rounded-2xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 text-center space-y-2 block';
                    document.getElementById('mbe-feedback-spinner').classList.add('hidden');
                    document.getElementById('mbe-feedback-msg').textContent = 'Error: ' + err.message;
                }
            } finally {
                btnEnviarAulas.disabled = false;
                btnCaptura.disabled = false;
            }
        };
    }

    modal.classList.remove('hidden');
}

async function enviarHuellaEstudianteDirecto(idEstudiante) {
    const est = estudiantesCargados.find(e => parseInt(e.id_estudiante) === parseInt(idEstudiante));
    if (!est) return;

    if (!confirm(`¿Desea enviar la huella de ${est.nombre} ${est.apellido} al aula correspondiente?`)) {
        return;
    }

    try {
        const res = await enviarHuellaAulas({
            idPersona: est.id_estudiante,
            userIdGlobal: est.user_id_global,
            idHuella: est.huella_id || null,
            idAula: est.id_aula || null,
            tipoUsuario: 'estudiante'
        });

        if (res && res.ok) {
            alert(res.data?.message || '✓ Huella encolada exitosamente para el aula.');
            const gradoActualDir = document.getElementById('filtro-grado')?.value || 'TODOS';
            await cargarEstudiantes(gradoActualDir);
        } else {
            alert('Error al enviar huella: ' + (res?.message || 'Compruebe el estado del servidor.'));
        }
    } catch (e) {
        alert('Error: ' + e.message);
    }
}

function cerrarModalBiometriaEstudiante() {
    const modal = document.getElementById('modal-biometria-estudiante');
    if (modal) modal.classList.add('hidden');
}

// Modales Crear y Editar Estudiante
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
}

function cerrarModalCrearEstudiante() {
    const modal = document.getElementById('modal-crear-estudiante');
    if (modal) modal.classList.add('hidden');
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
}

function cerrarModalEditarEstudiante() {
    const modal = document.getElementById('modal-editar-estudiante');
    if (modal) modal.classList.add('hidden');
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
            const fGrado = document.getElementById('filtro-grado');
            const val = fGrado ? fGrado.value : 'TODOS';
            cargarEstudiantes(val || 'TODOS');
        } else {
            alert(json.message || "Error al dar de baja");
        }
    } catch (err) {
        console.error("Error al desactivar estudiante:", err);
        alert("Error de red al desactivar estudiante");
    }
}

// 5. Exportación dinámica
function exportarNominaActual() {
    const fGrado = document.getElementById('filtro-grado')?.value || '';
    const fTurno = document.getElementById('filtro-turno')?.value || '';
    const fNivel = document.getElementById('filtro-nivel')?.value || '';
    const fBachi = document.getElementById('filtro-bachillerato')?.value || '';

    let params = [];
    if (fGrado && fGrado !== 'TODOS') params.push(`id_grado=${encodeURIComponent(fGrado)}`);
    if (fTurno) params.push(`id_turno=${encodeURIComponent(fTurno)}`);
    if (fNivel) params.push(`id_nivel=${encodeURIComponent(fNivel)}`);
    if (fBachi) params.push(`id_bachillerato=${encodeURIComponent(fBachi)}`);

    const qs = params.length > 0 ? `?${params.join('&')}` : '';
    window.location.href = `${getBaseUrl()}/src/api/Estudiante/exportar_estudiantes.php${qs}`;
}

window.recargarListaEstudiantesActual = function() {
    const fGrado = document.getElementById('filtro-grado');
    const val = fGrado ? fGrado.value : 'TODOS';
    if (val) {
        cargarEstudiantes(val);
    }
};
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
