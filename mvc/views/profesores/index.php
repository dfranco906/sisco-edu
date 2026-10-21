<?php
declare(strict_types=1);

$titulo = "Gestión de Profesores";

require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full space-y-6">

    <!-- Encabezado Principal y Barra de Acciones -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 text-xl font-bold">
                    👨‍🏫
                </span>
                <div>
                    <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        Gestión de Profesores
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                        Nómina docente, asignación por curso con filtro en cascada, importación y exportación masiva.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <button type="button" onclick="abrirModalCrearProfesor()" class="btn btn-success text-xs font-bold py-2 px-3.5 inline-flex items-center gap-1.5 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Nuevo Profesor</span>
            </button>

            <button type="button" onclick="abrirModalImportarProfesores(document.getElementById('filtro-grado')?.value)" class="btn btn-secondary text-xs font-semibold py-2 px-3 inline-flex items-center gap-1.5" title="Importar profesores desde archivo CSV / Excel">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span>📥 Importar</span>
            </button>

            <button type="button" onclick="exportarNominaActual()" class="btn btn-secondary text-xs font-semibold py-2 px-3 inline-flex items-center gap-1.5" title="Exportar profesores a CSV / Excel">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>📤 Exportar</span>
            </button>

            <a href="<?= base_url('mvc/views/profesores/desactivados.php') ?>" class="btn btn-secondary text-xs py-2 px-3 inline-flex items-center gap-1" title="Ver profesores desactivados">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <span>Desactivados</span>
            </a>

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
            <label for="filtro-grado" class="block text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 mb-1.5">
                4. Grado o Curso *
            </label>
            <select id="filtro-grado" class="app-input w-full text-xs font-bold border-purple-400 dark:border-purple-500 focus:border-purple-600">
                <option value="">Seleccione grado o curso...</option>
                <option value="TODOS">-- Ver todos los profesores --</option>
            </select>
        </div>
    </div>

    <!-- Tira de Metadatos del Grado Seleccionado -->
    <div id="info-grado-seleccionado" class="hidden p-4 rounded-2xl bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200/60 dark:border-purple-900/40 flex flex-wrap items-center justify-between gap-4 text-xs">
        <div class="flex flex-wrap items-center gap-4">
            <span class="font-bold text-purple-800 dark:text-purple-300 text-sm flex items-center gap-1.5">
                <span id="meta-grado-nombre" class="font-extrabold text-base">3° BTI</span>
            </span>
            <span class="text-slate-300 dark:text-slate-700">•</span>
            <span class="text-slate-600 dark:text-slate-300">Aula: <strong id="meta-grado-aula" class="text-slate-800 dark:text-slate-100 font-bold">-</strong></span>
            <span class="text-slate-300 dark:text-slate-700">•</span>
            <span class="text-slate-600 dark:text-slate-300">Turno: <strong id="meta-grado-turno" class="text-slate-800 dark:text-slate-100 font-bold">-</strong></span>
        </div>

        <div class="flex items-center gap-3">
            <div class="px-3 py-1 rounded-lg bg-white/80 dark:bg-slate-900/80 border border-purple-200 dark:border-purple-800 font-semibold text-slate-700 dark:text-slate-300">
                Docentes: <span id="meta-total-profesores" class="font-bold text-purple-600 dark:text-purple-400">0</span>
            </div>
            <div class="px-3 py-1 rounded-lg bg-white/80 dark:bg-slate-900/80 border border-purple-200 dark:border-purple-800 font-semibold text-slate-700 dark:text-slate-300">
                Con Huella: <span id="meta-total-huellas" class="font-bold text-emerald-600 dark:text-emerald-400">0</span>
            </div>
            <div class="px-3 py-1 rounded-lg bg-white/80 dark:bg-slate-900/80 border border-purple-200 dark:border-purple-800 font-semibold text-slate-700 dark:text-slate-300">
                Con Cargas: <span id="meta-total-asignaciones" class="font-bold text-blue-600 dark:text-blue-400">0</span>
            </div>
        </div>
    </div>

    <!-- Estado Inicial / Prompt de Selección (cuando NO se ha elegido curso) -->
    <div id="prompt-seleccion-grado" class="p-12 text-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
        <div class="w-14 h-14 rounded-2xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center mx-auto text-2xl">
            🔍
        </div>
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">
            Seleccioná un grado o curso para consultar la nómina docente
        </h3>
        <p class="text-xs text-slate-500 max-w-md mx-auto">
            Para garantizar un rendimiento óptimo, filtrá por turno, ciclo o bachillerato en los selectores superiores para consultar los docentes de ese curso.
        </p>
        <button type="button" onclick="cargarProfesores('TODOS')" class="btn btn-secondary text-xs py-2 px-4 font-semibold inline-flex items-center gap-1.5 mt-2">
            <span>Ver todos los profesores del colegio</span>
        </button>
    </div>

    <!-- Contenedor Principal de la Tabla de Profesores -->
    <div id="contenedor-tabla-profesores" class="hidden space-y-4">
        
        <!-- Barra de búsqueda local y conteo -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" id="busqueda-profesores-filtro" placeholder="Filtrar por nombre, apellido, CI o materia..."
                       class="app-input w-full pl-9 pr-4 text-xs">
            </div>

            <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                Mostrando <span id="contador-filtrados" class="font-bold text-slate-800 dark:text-slate-100">0</span> de <span id="contador-total" class="font-bold">0</span> docentes
            </div>
        </div>

        <!-- Tabla -->
        <div class="app-table-wrap rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
            <table class="min-w-full text-xs">
                <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider">
                    <tr>
                        <th class="p-3.5 text-left">CI</th>
                        <th class="p-3.5 text-left">Profesor</th>
                        <th class="p-3.5 text-left">Materias y Cursos</th>
                        <th class="p-3.5 text-left">Contacto</th>
                        <th class="p-3.5 text-left">Huella Biométrica</th>
                        <th class="p-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-profesores-body" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <!-- Filas dinámicas -->
                </tbody>
            </table>
        </div>

    </div>

    <!-- Modal Crear Profesor -->
    <div id="modal-crear-profesor" class="app-modal hidden" role="dialog" aria-modal="true">
        <div class="app-modal-dialog max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-5 sm:p-6 space-y-4">
            <div class="flex items-start justify-between gap-4 pb-2 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Registrar Nuevo Profesor</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Crea la ficha del docente y genera su usuario de acceso al sistema.</p>
                </div>
                <button type="button" onclick="cerrarModalCrearProfesor()" class="app-modal-close text-xl font-bold">×</button>
            </div>

            <form id="form-crear-profesor" class="space-y-3 text-xs">
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
                    <input type="text" name="cedula_identidad" placeholder="Ej: 6767676" class="app-input w-full text-xs font-mono" required>
                    <p class="text-[10px] text-slate-400 mt-0.5">Servirá como usuario y contraseña inicial para el panel del profesor.</p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Email (Opcional)</label>
                        <input type="email" name="email" placeholder="docente@colegio.edu.py" class="app-input w-full text-xs">
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Celular (Opcional)</label>
                        <input type="text" name="celular" placeholder="0981xxxxxx" class="app-input w-full text-xs">
                    </div>
                </div>

                <div class="border-t border-slate-100 dark:border-slate-800 pt-2 space-y-2">
                    <p class="font-bold text-[11px] uppercase tracking-wider text-slate-500">Asignación Inicial (Opcional)</p>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Grado / Curso</label>
                            <select name="id_grado" id="modal-crear-grado-select" class="app-input w-full text-xs">
                                <option value="">Sin asignar aún</option>
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Materia</label>
                            <select name="id_materia" id="modal-crear-materia-select" class="app-input w-full text-xs">
                                <option value="">Sin asignar aún</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="cerrarModalCrearProfesor()" class="btn btn-muted text-xs py-2 px-4">Cancelar</button>
                    <button type="submit" id="btn-submit-crear-prof" class="btn btn-success text-xs py-2 px-5 font-bold">Guardar Profesor</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Profesor -->
    <div id="modal-editar-profesor" class="app-modal hidden" role="dialog" aria-modal="true">
        <div class="app-modal-dialog max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 p-5 sm:p-6 space-y-4">
            <div class="flex items-start justify-between gap-4 pb-2 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Editar Datos del Profesor</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Actualizá los datos personales y de contacto del docente.</p>
                </div>
                <button type="button" onclick="cerrarModalEditarProfesor()" class="app-modal-close text-xl font-bold">×</button>
            </div>

            <form id="form-editar-profesor" class="space-y-3 text-xs">
                <input type="hidden" name="id_profesor" id="edit-id-profesor">
                <input type="hidden" name="user_id_global" id="edit-user-id-global">

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

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Email</label>
                        <input type="email" name="email" id="edit-email" class="app-input w-full text-xs">
                    </div>
                    <div>
                        <label class="block mb-1 font-semibold text-slate-700 dark:text-slate-300">Celular</label>
                        <input type="text" name="celular" id="edit-celular" class="app-input w-full text-xs">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="cerrarModalEditarProfesor()" class="btn btn-muted text-xs py-2 px-4">Cancelar</button>
                    <button type="submit" id="btn-submit-editar-prof" class="btn btn-primary text-xs py-2 px-5 font-bold">Actualizar Datos</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Importar Profesores -->
    <?php require_once __DIR__ . '/modal_importar_profesores.php'; ?>

</main>

<script src="<?= base_url('public/js/obtener_template.js') ?>"></script>

<script>
let catalogoGrados = [];
let catalogoMaterias = [];
let profesoresCargados = [];

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

// 1. Cargar catálogo de grados y materias para filtros y modales
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
        tbody.innerHTML = '<tr><td colspan="6" class="p-6 text-center text-slate-400">Cargando nómina de profesores...</td></tr>';
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
            tbody.innerHTML = '<tr><td colspan="6" class="p-6 text-center text-rose-500 font-bold">Error al cargar la nómina de profesores.</td></tr>';
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

// 3. Renderizar Tabla de Profesores
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
                <td colspan="6" class="p-8 text-center text-slate-400">
                    <p class="font-bold text-sm">No se encontraron docentes</p>
                    <p class="text-xs mt-1">Probá cambiando el filtro o registrando un nuevo profesor.</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = lista.map(p => {
        const tieneHuella = (parseInt(p.huella_registrada || 0) > 0) || (parseInt(p.huella_id || 0) > 0);
        const huellaBadge = tieneHuella
            ? '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">✓ Activa</span>'
            : '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">Sin huella</span>';

        // Badges de materias y asignaciones
        let asignacionesHtml = '';
        if (p.asignaciones_resumen && p.asignaciones_resumen.trim().length > 0) {
            const materias = p.asignaciones_resumen.split(',').map(m => m.trim());
            const primerLote = materias.slice(0, 3);
            const restantes = materias.length - 3;

            asignacionesHtml = '<div class="flex flex-wrap gap-1">' +
                primerLote.map(mat => `<span class="px-2 py-0.5 rounded-md bg-purple-50 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border border-purple-200/50 dark:border-purple-800/40 text-[10px] font-medium">${escapeHtml(mat)}</span>`).join('') +
                (restantes > 0 ? `<span class="px-1.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-500 text-[10px] font-bold" title="${escapeHtml(p.asignaciones_resumen)}">+${restantes} más</span>` : '') +
                '</div>';
        } else {
            asignacionesHtml = '<span class="text-slate-400 italic text-[11px]">Sin materias asignadas</span>';
        }

        const emailTxt = p.email ? `<div class="text-[11px] text-slate-500 truncate max-w-[180px]">${escapeHtml(p.email)}</div>` : '';
        const celularTxt = p.celular ? `<div class="text-[11px] font-mono text-slate-600 dark:text-slate-300">📱 ${escapeHtml(p.celular)}</div>` : '';

        return `
            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                <td class="p-3.5 font-mono font-bold text-slate-900 dark:text-white">
                    ${escapeHtml(p.cedula_identidad)}
                </td>
                <td class="p-3.5 font-semibold text-slate-900 dark:text-white">
                    <div class="font-bold text-xs">${escapeHtml(p.nombre)} ${escapeHtml(p.apellido)}</div>
                    ${emailTxt}
                </td>
                <td class="p-3.5 max-w-xs">
                    ${asignacionesHtml}
                </td>
                <td class="p-3.5">
                    ${celularTxt || '<span class="text-slate-400 text-[11px]">Sin teléfono</span>'}
                </td>
                <td class="p-3.5">
                    <div class="flex items-center gap-2">
                        ${huellaBadge}
                        <button type="button" onclick="descargarYGuardarTemplate('${escapeHtml(p.user_id_global)}', 'profesor')"
                                class="btn btn-secondary text-[10px] py-1 px-2 font-semibold inline-flex items-center gap-1"
                                title="Registrar o actualizar huella biométrica en el sensor ESP32">
                            <span>Capturar</span>
                        </button>
                    </div>
                </td>
                <td class="p-3.5 text-right whitespace-nowrap">
                    <div class="inline-flex items-center gap-1">
                        <button type="button" onclick="abrirModalEditarProfesor(${JSON.stringify(p).replace(/"/g, '&quot;')})"
                                class="btn btn-secondary text-[11px] py-1 px-2.5 font-medium" title="Editar profesor">
                            ✏️ Editar
                        </button>
                        <button type="button" onclick="desactivarProfesor(${p.id_profesor}, '${escapeHtml(p.nombre + ' ' + p.apellido)}')"
                                class="btn btn-danger text-[11px] py-1 px-2 font-medium" title="Desactivar profesor">
                            🗑️
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
