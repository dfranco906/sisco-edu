<?php
declare(strict_types=1);

$titulo = "Gestión de Profesores";

require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full space-y-6 text-base">

    <!-- Encabezado Principal y Barra de Acciones Accesibles -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-3">
                <span class="p-2.5 rounded-2xl bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 text-2xl font-bold shrink-0 border border-purple-200 dark:border-purple-800">
                    👨‍🏫
                </span>
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Gestión de Profesores
                    </h2>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1">
                        Nómina docente institucional, asignación curricular y control biométrico.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button type="button" onclick="abrirModalCrearProfesor()" class="btn btn-success text-base font-bold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2 shadow-sm" title="Registrar un nuevo docente en la nómina">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Nuevo Profesor</span>
            </button>

            <button type="button" onclick="abrirModalImportarProfesores(document.getElementById('filtro-grado')?.value)" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2" title="Importar profesores desde archivo CSV / Excel">
                <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span>Importar</span>
            </button>

            <button type="button" onclick="exportarNominaActual()" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2" title="Exportar nómina a CSV / Excel">
                <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Exportar</span>
            </button>

            <a href="<?= base_url('mvc/views/profesores/desactivados.php') ?>" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2" title="Consultar o restaurar profesores dados de baja">
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
            <label for="filtro-grado" class="block text-sm sm:text-base font-extrabold uppercase tracking-wider text-purple-700 dark:text-purple-300 mb-2">
                4. Grado o Curso *
            </label>
            <select id="filtro-grado" class="app-input w-full text-base font-bold py-2.5 px-3 rounded-xl min-h-[46px] border-purple-400 dark:border-purple-500 focus:border-purple-600">
                <option value="">Seleccione grado o curso...</option>
                <option value="TODOS">-- Ver todos los profesores --</option>
            </select>
        </div>
    </div>

    <!-- Tira de Metadatos del Grado Seleccionado -->
    <div id="info-grado-seleccionado" class="hidden p-5 rounded-3xl bg-purple-50/80 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-900/60 flex flex-wrap items-center justify-between gap-4 text-base">
        <div class="flex flex-wrap items-center gap-3">
            <span class="font-bold text-purple-900 dark:text-purple-200 text-lg flex items-center gap-2">
                <span id="meta-grado-nombre" class="font-extrabold text-xl text-purple-900 dark:text-purple-100">3° BTI</span>
            </span>
            <span class="text-slate-300 dark:text-slate-700">•</span>
            <span class="text-slate-700 dark:text-slate-300 font-medium">Aula: <strong id="meta-grado-aula" class="text-slate-900 dark:text-white font-bold">-</strong></span>
            <span class="text-slate-300 dark:text-slate-700">•</span>
            <span class="text-slate-700 dark:text-slate-300 font-medium">Turno: <strong id="meta-grado-turno" class="text-slate-900 dark:text-white font-bold">-</strong></span>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <div class="px-3.5 py-1.5 rounded-xl bg-white/90 dark:bg-slate-900/90 border border-purple-200 dark:border-purple-800 font-semibold text-slate-800 dark:text-slate-200 text-base">
                Docentes: <span id="meta-total-profesores" class="font-extrabold text-purple-700 dark:text-purple-300">0</span>
            </div>
            <div class="px-3.5 py-1.5 rounded-xl bg-white/90 dark:bg-slate-900/90 border border-purple-200 dark:border-purple-800 font-semibold text-slate-800 dark:text-slate-200 text-base">
                Con Huella: <span id="meta-total-huellas" class="font-extrabold text-emerald-600 dark:text-emerald-400">0</span>
            </div>
            <div class="px-3.5 py-1.5 rounded-xl bg-white/90 dark:bg-slate-900/90 border border-purple-200 dark:border-purple-800 font-semibold text-slate-800 dark:text-slate-200 text-base">
                Con Asignación: <span id="meta-total-asignaciones" class="font-extrabold text-blue-600 dark:text-blue-400">0</span>
            </div>
        </div>
    </div>

    <!-- Estado Inicial / Prompt de Selección (cuando NO se ha elegido curso) -->
    <div id="prompt-seleccion-grado" class="p-10 sm:p-14 text-center rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        <div class="w-16 h-16 rounded-2xl bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 flex items-center justify-center mx-auto text-3xl font-bold border border-purple-200 dark:border-purple-800">
            🔍
        </div>
        <h3 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white">
            Seleccioná un grado o curso para consultar la nómina docente
        </h3>
        <p class="text-base text-slate-600 dark:text-slate-400 max-w-xl mx-auto leading-relaxed">
            Filtrá por turno, ciclo o bachillerato en los selectores superiores para consultar el equipo de profesores de ese curso específico.
        </p>
        <div class="pt-2">
            <button type="button" onclick="cargarProfesores('TODOS')" class="btn btn-secondary text-base py-3 px-6 font-bold inline-flex items-center gap-2 min-h-[46px] shadow-sm">
                <span>Ver todos los profesores del colegio</span>
            </button>
        </div>
    </div>

    <!-- Contenedor Principal de la Tabla / Lista de Profesores -->
    <div id="contenedor-tabla-profesores" class="hidden space-y-4">
        
        <!-- Barra de búsqueda local y conteo accesible -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" id="busqueda-profesores-filtro" placeholder="Buscar por nombre, apellido o cédula..."
                       class="app-input w-full pl-11 pr-4 py-3 text-base rounded-2xl min-h-[46px]">
            </div>

            <div class="text-base text-slate-700 dark:text-slate-300 font-semibold">
                Mostrando <span id="contador-filtrados" class="font-extrabold text-purple-700 dark:text-purple-300">0</span> de <span id="contador-total" class="font-extrabold text-slate-900 dark:text-white">0</span> docentes
            </div>
        </div>

        <!-- Tabla Rediseñada: Fuentes Grandes (>= 16px / 12 pt) y Revelación Progresiva -->
        <div class="app-table-wrap rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-base">
                    <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800 text-sm sm:text-base font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-4 px-5 text-left">Docente & Cédula</th>
                            <th class="py-4 px-5 text-left">Carga Académica</th>
                            <th class="py-4 px-5 text-left">Estado Biométrico</th>
                            <th class="py-4 px-5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-profesores-body" class="divide-y divide-slate-100 dark:divide-slate-800">
                        <!-- Filas dinámicas -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Modales Modulares Incluidos -->
    <?php require_once __DIR__ . '/modal_detalle_profesor.php'; ?>
    <?php require_once __DIR__ . '/modal_biometria_profesor.php'; ?>
    <?php require_once __DIR__ . '/modal_crear_profesor.php'; ?>
    <?php require_once __DIR__ . '/modal_editar_profesor.php'; ?>
    <?php require_once __DIR__ . '/modal_importar_profesores.php'; ?>

</main>

<script src="<?= base_url('public/js/obtener_template.js') ?>"></script>

<script>
let catalogoGrados = [];
let catalogoMaterias = [];
let profesoresCargados = [];
let profesorSeleccionadoActivo = null;

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
    return (n + a) || 'PR';
}

// 1. Cargar catálogo de grados y materias para filtros y selectores
async function cargarEstructuraCascada() {
    try {
        const [resG, resM] = await Promise.all([
            fetch(`${getBaseUrl()}/src/api/Grado/leer_grados.php`),
            fetch(`${getBaseUrl()}/src/api/Materia/leer_materias.php`)
        ]);

        const jsonG = await resG.json();
        const jsonM = await resM.json();

        catalogoGrados = jsonG.data || [];
        catalogoMaterias = jsonM.data || [];

        inicializarSelectoresCascada();
        poblarSelectoresModales();
    } catch (e) {
        console.error("Error cargando estructuras de grados o materias:", e);
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
            cargarProfesores(idG);
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
            '<option value="TODOS">-- Ver todos los profesores --</option>' +
            filtrados.map(g => `<option value="${g.id_grado}">${g.nombre} (${g.turno || 'Sin turno'})</option>`).join('');

        if (valorPrevio && Array.from(fGrado.options).some(o => o.value === valorPrevio)) {
            fGrado.value = valorPrevio;
        }
    }
}

function poblarSelectoresModales() {
    // Grados
    const optsGrados = '<option value="">Seleccionar curso...</option>' +
        catalogoGrados.map(g => `<option value="${g.id_grado}">${g.nombre} - ${g.aula || 'Sin aula'} (${g.turno || ''})</option>`).join('');

    const selCrearGrado = document.getElementById('modal-crear-grado-select');
    const selImportGrado = document.getElementById('import-prof-grado-defecto');
    if (selCrearGrado) selCrearGrado.innerHTML = optsGrados;
    if (selImportGrado) selImportGrado.innerHTML = '<option value="">Detectar desde el archivo</option>' + optsGrados.replace('<option value="">Seleccionar curso...</option>', '');

    // Materias
    const optsMaterias = '<option value="">Seleccionar materia...</option>' +
        catalogoMaterias.map(m => `<option value="${m.id_materia}">${m.nombre} ${m.codigo ? '(' + m.codigo + ')' : ''}</option>`).join('');

    const selCrearMateria = document.getElementById('modal-crear-materia-select');
    const selImportMateria = document.getElementById('import-prof-materia-defecto');
    if (selCrearMateria) selCrearMateria.innerHTML = optsMaterias;
    if (selImportMateria) selImportMateria.innerHTML = '<option value="">Detectar desde el archivo</option>' + optsMaterias.replace('<option value="">Seleccionar materia...</option>', '');
}

// 2. Cargar nómina de profesores (On-Demand)
async function cargarProfesores(idGrado) {
    const promptBox = document.getElementById('prompt-seleccion-grado');
    const metaBox = document.getElementById('info-grado-seleccionado');
    const tableContainer = document.getElementById('contenedor-tabla-profesores');
    const tbody = document.getElementById('tabla-profesores-body');

    if (!idGrado) {
        mostrarPromptVacio();
        return;
    }

    if (promptBox) promptBox.classList.add('hidden');
    if (metaBox) metaBox.classList.remove('hidden');
    if (tableContainer) tableContainer.classList.remove('hidden');

    if (tbody) {
        tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-base text-slate-500 font-semibold">Cargando nómina de profesores...</td></tr>';
    }

    // Actualizar metadatos del encabezado
    if (idGrado === 'TODOS') {
        document.getElementById('meta-grado-nombre').textContent = 'Todos los Docentes del Colegio';
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
        const res = await fetch(`${getBaseUrl()}/src/api/Profesor/leer_profesores.php${qs}`);
        const json = await res.json();
        profesoresCargados = json.data || [];

        // Actualizar contadores
        const total = profesoresCargados.length;
        const huellas = profesoresCargados.filter(p => parseInt(p.huella_registrada || 0) > 0 || parseInt(p.huella_id || 0) > 0).length;
        const asignados = profesoresCargados.filter(p => parseInt(p.total_asignaciones || 0) > 0).length;

        document.getElementById('meta-total-profesores').textContent = String(total);
        document.getElementById('meta-total-huellas').textContent = String(huellas);
        document.getElementById('meta-total-asignaciones').textContent = String(asignados);

        renderizarTablaProfesores(profesoresCargados);
    } catch (err) {
        console.error("Error al cargar profesores:", err);
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-base text-rose-600 font-bold">Error al cargar la nómina de profesores.</td></tr>';
        }
    }
}

function mostrarPromptVacio() {
    const promptBox = document.getElementById('prompt-seleccion-grado');
    const metaBox = document.getElementById('info-grado-seleccionado');
    const tableContainer = document.getElementById('contenedor-tabla-profesores');

    if (promptBox) promptBox.classList.remove('hidden');
    if (metaBox) metaBox.classList.add('hidden');
    if (tableContainer) tableContainer.classList.add('hidden');
}

// 3. Renderizar Tabla de Profesores (Rediseñada para Máxima Legibilidad)
function renderizarTablaProfesores(lista) {
    const tbody = document.getElementById('tabla-profesores-body');
    const cntFilt = document.getElementById('contador-filtrados');
    const cntTot = document.getElementById('contador-total');

    if (!tbody) return;

    if (cntTot) cntTot.textContent = String(profesoresCargados.length);
    if (cntFilt) cntFilt.textContent = String(lista.length);

    if (lista.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="p-10 text-center text-slate-500">
                    <p class="font-extrabold text-lg text-slate-800 dark:text-slate-200">No se encontraron docentes</p>
                    <p class="text-base mt-1">Pruebe ajustando los filtros superiores o registre un nuevo profesor.</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = lista.map(p => {
        const tieneHuella = (parseInt(p.huella_registrada || 0) > 0) || (parseInt(p.huella_id || 0) > 0);
        
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

        // Resumen sintético de asignaciones (Primer Plano)
        const totalAsig = parseInt(p.total_asignaciones || 0);
        let asignacionesResumenHtml = '';
        if (totalAsig > 0) {
            asignacionesResumenHtml = `
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-100/80 text-purple-900 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800 font-bold text-sm sm:text-base">
                        📚 ${totalAsig} ${totalAsig === 1 ? 'materia asignada' : 'materias asignadas'}
                    </span>
                    <button type="button" onclick="abrirModalDetalleProfesor(${p.id_profesor})" class="block text-sm font-bold text-purple-700 dark:text-purple-400 hover:underline">
                        Ver cursos y horarios →
                    </button>
                </div>
            `;
        } else {
            asignacionesResumenHtml = `
                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 text-sm font-semibold">
                    Sin materias asignadas
                </span>
            `;
        }

        const iniciales = obtenerIniciales(p.nombre, p.apellido);

        return `
            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                <!-- Columna 1: Docente e Identificación -->
                <td class="py-4 px-5">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-200 flex items-center justify-center font-black text-lg border border-purple-200 dark:border-purple-800 shrink-0">
                            ${iniciales}
                        </div>
                        <div>
                            <div class="font-extrabold text-base sm:text-lg text-slate-900 dark:text-white leading-tight">
                                ${escapeHtml(p.nombre)} ${escapeHtml(p.apellido)}
                            </div>
                            <div class="text-base text-slate-700 dark:text-slate-300 font-mono mt-0.5">
                                CI: <strong class="text-purple-700 dark:text-purple-300 font-bold">${escapeHtml(p.cedula_identidad)}</strong>
                            </div>
                        </div>
                    </div>
                </td>

                <!-- Columna 2: Carga Académica Resumida -->
                <td class="py-4 px-5">
                    ${asignacionesResumenHtml}
                </td>

                <!-- Columna 3: Estado Biométrico -->
                <td class="py-4 px-5">
                    <div class="flex items-center gap-2 flex-wrap">
                        ${huellaBadge}
                        <button type="button" onclick="abrirModalBiometriaProfesor(${p.id_profesor})"
                                class="btn btn-secondary text-sm font-bold py-1.5 px-3 min-h-[38px] inline-flex items-center gap-1.5"
                                title="Gestionar registro biométrico en el sensor ESP32">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.99 7.132"></path></svg>
                            <span>Biometría</span>
                        </button>
                    </div>
                </td>

                <!-- Columna 4: Acciones Principales con Objetivos Táctiles Grandes -->
                <td class="py-4 px-5 text-right whitespace-nowrap">
                    <div class="inline-flex items-center gap-2 justify-end">
                        <button type="button" onclick="abrirModalDetalleProfesor(${p.id_profesor})"
                                class="btn btn-primary text-base font-bold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-1.5 shadow-xs"
                                title="Ver ficha integral, contactos y materias completas">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span>Ver Ficha</span>
                        </button>

                        <button type="button" onclick="abrirModalEditarProfesor(${JSON.stringify(p).replace(/"/g, '&quot;')})"
                                class="btn btn-secondary text-base font-semibold py-2.5 px-3.5 min-h-[44px] inline-flex items-center gap-1"
                                title="Editar datos del docente">
                            <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            <span>Editar</span>
                        </button>

                        <button type="button" onclick="desactivarProfesor(${p.id_profesor}, '${escapeHtml(p.nombre + ' ' + p.apellido)}')"
                                class="btn btn-danger text-base font-semibold py-2.5 px-3 min-h-[44px] inline-flex items-center justify-center"
                                title="Desactivar profesor">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// 4. Búsqueda local predictiva
document.addEventListener('DOMContentLoaded', () => {
    cargarEstructuraCascada();

    const inputBusqueda = document.getElementById('busqueda-profesores-filtro');
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', (e) => {
            const q = (e.target.value || '').trim().toLowerCase();
            if (!q) {
                renderizarTablaProfesores(profesoresCargados);
                return;
            }

            const filtrados = profesoresCargados.filter(p => {
                const nom = (p.nombre || '').toLowerCase();
                const ape = (p.apellido || '').toLowerCase();
                const ci = (p.cedula_identidad || '').toLowerCase();
                const email = (p.email || '').toLowerCase();
                const mat = (p.asignaciones_resumen || '').toLowerCase();
                return nom.includes(q) || ape.includes(q) || ci.includes(q) || email.includes(q) || mat.includes(q);
            });

            renderizarTablaProfesores(filtrados);
        });
    }

    // Configurar Formulario Crear Profesor
    const formCrear = document.getElementById('form-crear-profesor');
    if (formCrear) {
        formCrear.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-crear-prof');
            if (btn) btn.disabled = true;

            try {
                const formData = new FormData(formCrear);
                const res = await fetch(`${getBaseUrl()}/src/api/Profesor/crear_profesor.php`, {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (!res.ok || json.status !== 'success') {
                    throw new Error(json.message || "Error al registrar profesor.");
                }

                cerrarModalCrearProfesor();
                formCrear.reset();

                const selG = document.getElementById('filtro-grado');
                const val = selG ? selG.value : 'TODOS';
                cargarProfesores(val || 'TODOS');
            } catch (err) {
                alert("Error al registrar profesor: " + err.message);
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    }

    // Configurar Formulario Editar Profesor
    const formEditar = document.getElementById('form-editar-profesor');
    if (formEditar) {
        formEditar.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-editar-prof');
            if (btn) btn.disabled = true;

            try {
                const formData = new FormData(formEditar);
                const res = await fetch(`${getBaseUrl()}/src/api/Profesor/actualizar_profesor.php`, {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (!res.ok || json.status !== 'success') {
                    throw new Error(json.message || "Error al actualizar profesor.");
                }

                cerrarModalEditarProfesor();

                const selG = document.getElementById('filtro-grado');
                const val = selG ? selG.value : 'TODOS';
                cargarProfesores(val || 'TODOS');
            } catch (err) {
                alert("Error al actualizar profesor: " + err.message);
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    }
});

// ==========================================
// CONTROLADORES DE SUBMODALES
// ==========================================

// Submodal 1: Ficha Integral del Docente
async function abrirModalDetalleProfesor(idProfesor) {
    const prof = profesoresCargados.find(p => parseInt(p.id_profesor) === parseInt(idProfesor));
    if (!prof) return;

    profesorSeleccionadoActivo = prof;
    const modal = document.getElementById('modal-detalle-profesor');
    if (!modal) return;

    // Encabezado
    document.getElementById('mdp-nombre-docente').textContent = `${prof.nombre} ${prof.apellido}`;
    document.getElementById('mdp-cedula-docente').textContent = prof.cedula_identidad || 'Sin CI';
    document.getElementById('mdp-avatar').textContent = obtenerIniciales(prof.nombre, prof.apellido);
    document.getElementById('mdp-user-id-global').textContent = prof.user_id_global || 'Pendiente de asignación';

    // Estado biométrico
    const tieneHuella = (parseInt(prof.huella_registrada || 0) > 0) || (parseInt(prof.huella_id || 0) > 0);
    const badgeHuella = document.getElementById('mdp-badge-huella-resumen');
    if (badgeHuella) {
        badgeHuella.innerHTML = tieneHuella
            ? '<span class="text-emerald-700 dark:text-emerald-300 font-bold">✓ Huella Registrada en Base de Datos</span>'
            : '<span class="text-amber-700 dark:text-amber-400 font-bold">⚠️ Pendiente de Captura en Sensor</span>';
    }

    // Contacto: Celular
    const celularTexto = document.getElementById('mdp-celular-texto');
    const celularAcciones = document.getElementById('mdp-celular-acciones');
    const linkWhatsApp = document.getElementById('mdp-link-whatsapp');
    const linkTel = document.getElementById('mdp-link-tel');

    if (prof.celular && prof.celular.trim()) {
        celularTexto.textContent = prof.celular;
        const limpio = prof.celular.replace(/\D/g, '');
        if (linkWhatsApp) linkWhatsApp.href = `https://wa.me/595${limpio.startsWith('0') ? limpio.substring(1) : limpio}`;
        if (linkTel) linkTel.href = `tel:${prof.celular}`;
        celularAcciones.classList.remove('hidden');
    } else {
        celularTexto.textContent = 'Sin teléfono registrado';
        celularAcciones.classList.add('hidden');
    }

    // Contacto: Email
    const emailLink = document.getElementById('mdp-email-link');
    if (emailLink) {
        if (prof.email && prof.email.trim()) {
            emailLink.textContent = prof.email;
            emailLink.href = `mailto:${prof.email}`;
            emailLink.classList.remove('text-slate-400', 'pointer-events-none');
        } else {
            emailLink.textContent = 'Sin correo registrado';
            emailLink.removeAttribute('href');
            emailLink.classList.add('text-slate-400', 'pointer-events-none');
        }
    }

    // Usuario del sistema
    const usuarioTexto = document.getElementById('mdp-usuario-texto');
    if (usuarioTexto) {
        usuarioTexto.textContent = prof.usuario || prof.cedula_identidad || '---';
    }

    // Botones de acción dentro de la ficha
    const btnEditar = document.getElementById('mdp-btn-editar-desde-ficha');
    if (btnEditar) {
        btnEditar.onclick = () => {
            cerrarModalDetalleProfesor();
            abrirModalEditarProfesor(prof);
        };
    }

    const btnBio = document.getElementById('mdp-btn-abrir-biometria');
    if (btnBio) {
        btnBio.onclick = () => {
            cerrarModalDetalleProfesor();
            abrirModalBiometriaProfesor(prof.id_profesor);
        };
    }

    modal.classList.remove('hidden');

    // Cargar materias detalladas vía API
    await cargarMateriasFichaDocente(prof.id_profesor, prof.asignaciones_resumen);
}

function cerrarModalDetalleProfesor() {
    const modal = document.getElementById('modal-detalle-profesor');
    if (modal) modal.classList.add('hidden');
}

async function cargarMateriasFichaDocente(idProfesor, resumenFallback) {
    const tbody = document.getElementById('mdp-tabla-materias-body');
    const cntBadge = document.getElementById('mdp-total-materias-cnt');
    if (!tbody) return;

    tbody.innerHTML = `
        <tr>
            <td colspan="4" class="p-6 text-center text-slate-500 font-medium">
                Consultando materias y horarios asignados...
            </td>
        </tr>
    `;

    try {
        const res = await fetch(`${getBaseUrl()}/src/api/Asignaciones/leer_asignaciones.php?id_profesor=${encodeURIComponent(idProfesor)}`);
        const json = await res.json();
        const asignaciones = json.data || [];

        if (cntBadge) cntBadge.textContent = String(asignaciones.length);

        if (asignaciones.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="p-8 text-center text-slate-500">
                        <p class="font-bold text-base text-slate-700 dark:text-slate-300">Este docente no tiene materias asignadas actualmente.</p>
                        <p class="text-sm mt-1 text-slate-500">Podés asignarle cursos y cátedras desde el módulo de Asignaciones o Coordinación.</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = asignaciones.map(a => `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
                <td class="py-3.5 px-4 font-bold text-base text-slate-900 dark:text-white">
                    ${escapeHtml(a.grado || 'Sin curso')}
                </td>
                <td class="py-3.5 px-4 text-base text-slate-800 dark:text-slate-200 font-medium">
                    ${escapeHtml(a.materia || 'Sin materia')}
                </td>
                <td class="py-3.5 px-4 text-center font-mono font-bold text-base text-purple-700 dark:text-purple-300">
                    ${a.carga_horaria ? `${a.carga_horaria} hs/sem` : 'Cátedra estándar'}
                </td>
                <td class="py-3.5 px-4 text-center font-semibold text-base text-slate-700 dark:text-slate-300">
                    ${escapeHtml(String(a.anho_lectivo || a['año_lectivo'] || '2026'))}
                </td>
            </tr>
        `).join('');

    } catch (e) {
        console.warn("Fallo al obtener detalle de asignaciones vía API, usando resumen:", e);
        if (resumenFallback && resumenFallback.trim()) {
            const mats = resumenFallback.split(',').map(m => m.trim());
            if (cntBadge) cntBadge.textContent = String(mats.length);
            tbody.innerHTML = mats.map(m => `
                <tr>
                    <td colspan="4" class="py-3.5 px-4 text-base text-slate-800 dark:text-slate-200 font-medium">
                        📌 ${escapeHtml(m)}
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="p-6 text-center text-rose-500 font-bold">
                        No se pudieron cargar las materias asignadas.
                    </td>
                </tr>
            `;
        }
    }
}

// Submodal 2: Asistente de Huella Biométrica
function abrirModalBiometriaProfesor(idProfesor) {
    const prof = profesoresCargados.find(p => parseInt(p.id_profesor) === parseInt(idProfesor));
    if (!prof) return;

    profesorSeleccionadoActivo = prof;
    const modal = document.getElementById('modal-biometria-profesor');
    if (!modal) return;

    document.getElementById('mbp-nombre-docente').textContent = `${prof.nombre} ${prof.apellido}`;
    document.getElementById('mbp-cedula-docente').textContent = prof.cedula_identidad || 'Sin CI';

    // IP del registrador desde configuración centralizada
    const ipESP32 = (window.CONFIG_RED && window.CONFIG_RED.ipRegistrador) ? window.CONFIG_RED.ipRegistrador : "";
    const ipElem = document.getElementById('mbp-ip-nodo');
    if (ipElem) ipElem.textContent = ipESP32 || 'Configurado en el servidor';

    const tieneHuella = (parseInt(prof.huella_registrada || 0) > 0) || (parseInt(prof.huella_id || 0) > 0);
    const badgeWrap = document.getElementById('mbp-badge-estado');
    if (badgeWrap) {
        badgeWrap.className = tieneHuella
            ? 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-base font-bold bg-emerald-100 text-emerald-900 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800'
            : 'inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-base font-bold bg-amber-100 text-amber-900 dark:bg-amber-950/70 dark:text-amber-300 border border-amber-300 dark:border-amber-800';
        badgeWrap.innerHTML = tieneHuella ? '✓ Huella Registrada' : '⚠️ Sin Huella Registrada';
    }

    // Limpiar feedback anterior
    const feedbackBox = document.getElementById('mbp-feedback-box');
    if (feedbackBox) feedbackBox.classList.add('hidden');

    const btnCaptura = document.getElementById('mbp-btn-iniciar-captura');
    if (btnCaptura) {
        btnCaptura.disabled = false;
        btnCaptura.onclick = async () => {
            btnCaptura.disabled = true;
            if (feedbackBox) {
                feedbackBox.className = 'p-4 rounded-2xl border border-purple-300 dark:border-purple-800 bg-purple-50 dark:bg-purple-950/40 text-center space-y-2 block';
                feedbackBox.classList.remove('hidden');
                document.getElementById('mbp-feedback-spinner').classList.remove('hidden');
                document.getElementById('mbp-feedback-msg').textContent = 'Conectando con sensor registrador y esperando huella...';
            }

            try {
                await descargarYGuardarTemplate(prof.user_id_global, 'profesor');
            } catch (err) {
                if (feedbackBox) {
                    feedbackBox.className = 'p-4 rounded-2xl border border-rose-300 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/40 text-center space-y-2 block';
                    document.getElementById('mbp-feedback-spinner').classList.add('hidden');
                    document.getElementById('mbp-feedback-msg').textContent = 'Error: ' + err.message;
                }
            } finally {
                btnCaptura.disabled = false;
            }
        };
    }

    modal.classList.remove('hidden');
}

function cerrarModalBiometriaProfesor() {
    const modal = document.getElementById('modal-biometria-profesor');
    if (modal) modal.classList.add('hidden');
}

// Modales Crear y Editar
function abrirModalCrearProfesor() {
    const modal = document.getElementById('modal-crear-profesor');
    const fGrado = document.getElementById('filtro-grado');
    const selModal = document.getElementById('modal-crear-grado-select');

    if (selModal && fGrado && fGrado.value && fGrado.value !== 'TODOS') {
        selModal.value = fGrado.value;
    }

    if (modal) modal.classList.remove('hidden');
}

function cerrarModalCrearProfesor() {
    const modal = document.getElementById('modal-crear-profesor');
    if (modal) modal.classList.add('hidden');
}

function abrirModalEditarProfesor(p) {
    const modal = document.getElementById('modal-editar-profesor');
    if (!modal) return;

    document.getElementById('edit-id-profesor').value = p.id_profesor || '';
    document.getElementById('edit-user-id-global').value = p.user_id_global || '';
    document.getElementById('edit-nombre').value = p.nombre || '';
    document.getElementById('edit-apellido').value = p.apellido || '';
    document.getElementById('edit-cedula').value = p.cedula_identidad || '';
    document.getElementById('edit-email').value = p.email || '';
    document.getElementById('edit-celular').value = p.celular || '';

    modal.classList.remove('hidden');
}

function cerrarModalEditarProfesor() {
    const modal = document.getElementById('modal-editar-profesor');
    if (modal) modal.classList.add('hidden');
}

// Desactivar Profesor
async function desactivarProfesor(idProfesor, nombre) {
    if (!confirm(`¿Estás seguro de que deseás desactivar al profesor ${nombre}?`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id_profesor', idProfesor);

        const res = await fetch(`${getBaseUrl()}/src/api/Profesor/desactivar_profesor.php`, {
            method: 'POST',
            body: formData
        });

        const json = await res.json();
        if (!res.ok || json.status !== 'success') {
            throw new Error(json.message || "Error al desactivar");
        }

        const selG = document.getElementById('filtro-grado');
        const val = selG ? selG.value : 'TODOS';
        cargarProfesores(val || 'TODOS');
    } catch (err) {
        alert("Error al desactivar profesor: " + err.message);
    }
}

// Exportar nómina actual con filtros activos
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
    window.location.href = `${getBaseUrl()}/src/api/Profesor/exportar_profesores.php${qs}`;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
