<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../../../src/config/app.php';

$rol = $_SESSION['rol'] ?? '';
$esDocente = ($rol === 'Profesor');
$esAdmin = in_array(strtolower($rol), ['superadmin', 'administrador', 'administracion'], true);

if (!empty($_GET['ref'])) {
    $urlVolver = base_url($_GET['ref']);
} elseif ($esAdmin) {
    $urlVolver = base_url('mvc/views/administracion/index.php');
} elseif ($rol === 'Coordinador') {
    $urlVolver = base_url('mvc/views/coordinacion/index.php');
} else {
    $urlVolver = base_url('mvc/views/dashboard.php');
}
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10 overflow-y-auto">
    <!-- Breadcrumb de Navegación -->
    <nav class="flex items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400 mb-3" aria-label="Ruta de navegación">
        <a href="<?= base_url('mvc/views/dashboard/index.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center gap-1.5">
            <span>🏠</span> Inicio
        </a>
        <span>/</span>
        <span class="text-slate-800 dark:text-slate-200"><?= $esDocente ? 'Mis Horarios de Clase' : 'Gestión de Horarios' ?></span>
    </nav>

    <!-- Header Principal -->
    <header class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold text-2xl shadow-sm">
                        📅
                    </span>
                    <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                        <?= $esDocente ? 'Mis Horarios de Clase' : 'Gestión de Horarios Semanales' ?>
                    </h1>
                </div>
                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 mt-2 font-normal leading-relaxed">
                    <?= $esDocente ? 'Consultá tus materias y clases semanales en los cursos donde estás designado.' : 'Visualizá la grilla semanal por horas cátedras filtrando por turno, ciclo y grado.' ?>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <?php if (!$esDocente): ?>
                <button id="btn-crear-horario" type="button" class="min-h-[48px] px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm sm:text-base font-extrabold shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Crear horario</span>
                </button>
                <a href="<?= base_url('mvc/views/horarios/desactivados.php') ?>" class="min-h-[48px] px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-sm sm:text-base font-bold border-2 border-slate-200 dark:border-slate-700 transition-all flex items-center gap-2">
                    <span>🗑️</span>
                    <span>Ver desactivados</span>
                </a>
                <?php endif; ?>
                <a href="<?= $urlVolver ?>" class="min-h-[48px] px-5 py-2.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 rounded-xl text-sm sm:text-base font-bold border-2 border-indigo-200 dark:border-indigo-800 transition-all flex items-center gap-2">
                    <span>⬅️</span>
                    <span>Volver</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Panel de Consulta y Grilla Semanal -->
    <section class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm mb-6" aria-labelledby="titulo-modulo-horarios">
        <!-- Sub-encabezado con estado en vivo -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-slate-100 dark:border-slate-800 mb-6">
            <div>
                <h2 id="titulo-modulo-horarios" class="text-xl sm:text-2xl font-black flex items-center gap-2.5 text-slate-900 dark:text-white">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span><?= $esDocente ? 'Mi Horario Semanal' : 'Horario Semanal por Grado' ?></span>
                </h2>
                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 mt-1">
                    <?= $esDocente ? 'Seleccioná el grado para consultar tu cronograma.' : 'Filtrá turno, ciclo y bachillerato para extraer el horario oficial del grado.' ?>
                </p>
            </div>

            <!-- Indicador de clase en vivo en este momento -->
            <div id="indicador-en-vivo" class="flex items-center gap-2.5 min-h-[44px] px-4 py-2 rounded-xl text-sm font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-2 border-slate-200 dark:border-slate-700 shadow-2xs shrink-0 transition-all">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                <span id="texto-en-vivo">Cargando estado...</span>
            </div>
        </div>

        <!-- Filtros en cascada: Turno -> Nivel -> Bachillerato -> Grado -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-5 sm:p-6 rounded-2xl bg-slate-50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800 mb-6">
            <div>
                <label for="filtro-turno" class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                    1. Turno
                </label>
                <select id="filtro-turno" class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
                    <option value="">Todos los turnos</option>
                </select>
            </div>

            <div>
                <label for="filtro-nivel" class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                    2. Ciclo / Nivel
                </label>
                <select id="filtro-nivel" class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
                    <option value="">Todos los ciclos</option>
                </select>
            </div>

            <div>
                <label for="filtro-bachillerato" class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                    3. Bachillerato
                </label>
                <select id="filtro-bachillerato" class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
                    <option value="">Todos / No aplica</option>
                </select>
            </div>

            <div>
                <label for="filtro-grado" class="block text-sm sm:text-base font-extrabold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-2">
                    4. Grado / Curso *
                </label>
                <select id="filtro-grado" class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border-2 border-indigo-400 dark:border-indigo-500 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-bold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all shadow-2xs">
                    <option value="">Seleccione un grado...</option>
                </select>
            </div>
        </div>

        <!-- Tira de Metadatos del Grado Seleccionado -->
        <div id="info-grado-seleccionado" class="hidden flex flex-wrap items-center justify-between gap-4 p-4 sm:p-5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border-2 border-indigo-200/70 dark:border-indigo-800/60 mb-6 text-sm sm:text-base">
            <div class="flex flex-wrap items-center gap-3">
                <span class="font-black text-indigo-900 dark:text-indigo-200 text-lg sm:text-xl flex items-center gap-2">
                    <span>🏫</span>
                    <span id="meta-grado-nombre"></span>
                </span>
                <span class="text-slate-400 dark:text-slate-600 font-bold">•</span>
                <span class="text-slate-600 dark:text-slate-300">Aula: <strong id="meta-grado-aula" class="text-slate-900 dark:text-white font-black"></strong></span>
                <span class="text-slate-400 dark:text-slate-600 font-bold">•</span>
                <span class="text-slate-600 dark:text-slate-300">Ubicación: <strong id="meta-grado-ubicacion" class="text-slate-900 dark:text-white font-black"></strong></span>
                <span class="text-slate-400 dark:text-slate-600 font-bold">•</span>
                <span class="text-slate-600 dark:text-slate-300">Estudiantes: <strong id="meta-grado-estudiantes" class="text-slate-900 dark:text-white font-black"></strong></span>
            </div>

            <div class="flex items-center gap-2 bg-white/80 dark:bg-slate-900/80 p-1.5 rounded-xl border border-indigo-200/60 dark:border-indigo-800/40">
                <button id="btn-vista-grilla" type="button" class="min-h-[38px] px-4 py-1.5 rounded-lg text-sm font-extrabold bg-indigo-600 text-white shadow-xs cursor-pointer transition-all">
                    Grilla Semanal
                </button>
                <button id="btn-vista-lista" type="button" class="min-h-[38px] px-4 py-1.5 rounded-lg text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer transition-all">
                    Lista Detallada
                </button>
            </div>
        </div>

        <!-- Contenedor Principal de la Grilla / Lista -->
        <div id="contenedor-grilla-horario" class="relative">
            <!-- Estado de Carga Tranquilizador -->
            <div id="grilla-loading" class="hidden rounded-3xl border-2 border-indigo-200 dark:border-indigo-900/50 bg-gradient-to-b from-indigo-50/60 to-white dark:from-slate-900/90 dark:to-slate-900 p-12 sm:p-16 text-center my-6 shadow-sm">
                <div class="relative w-20 h-20 mx-auto mb-5 flex items-center justify-center">
                    <div class="absolute inset-0 rounded-full bg-indigo-400/20 dark:bg-indigo-500/20 animate-ping"></div>
                    <div class="w-16 h-16 rounded-full border-4 border-indigo-200 dark:border-indigo-800 border-t-indigo-600 dark:border-t-indigo-400 animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-2xl">
                        📅
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    Consultando Horario del Curso...
                </h3>
                <p class="text-base text-slate-600 dark:text-slate-400 max-w-md mx-auto mt-2 leading-relaxed font-medium">
                    Cargando la matriz semanal de materias, profesores asignados y horas cátedra.
                </p>
                <div class="max-w-xs mx-auto mt-5 bg-slate-200 dark:bg-slate-800 h-2 rounded-full overflow-hidden shadow-inner">
                    <div class="h-full bg-gradient-to-r from-indigo-500 via-sky-400 to-emerald-400 rounded-full animate-pulse w-3/4"></div>
                </div>
            </div>

            <!-- Prompt vacío cuando no hay grado seleccionado -->
            <div id="grilla-vacia" class="text-center py-16 px-4 border-2 border-dashed border-slate-300 dark:border-slate-800 rounded-3xl bg-slate-50/50 dark:bg-slate-900/40">
                <div class="w-20 h-20 mx-auto mb-4 rounded-3xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-4xl shadow-sm">
                    📅
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Seleccioná un grado o curso</h3>
                <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 mt-2 max-w-md mx-auto leading-relaxed font-normal">
                    Elegí un turno, ciclo y grado en los selectores para visualizar la matriz semanal de horarios y materias.
                </p>
            </div>

            <!-- Matriz Semanal (Tabla con horas cátedras y días) -->
            <div id="grilla-tabla-wrap" class="hidden schedule-matrix-wrap shadow-sm">
                <table id="tabla-matriz-horarios" class="schedule-matrix-table">
                    <thead>
                        <tr id="matriz-thead-row">
                            <!-- Inyectado por JS: Hora Cátedra, Lunes, Martes, Miércoles, Jueves, Viernes -->
                        </tr>
                    </thead>
                    <tbody id="matriz-tbody">
                        <!-- Inyectado dinámicamente con horas cátedras y rowspans -->
                    </tbody>
                </table>
            </div>

            <!-- Vista Lista alternativa para el grado seleccionado -->
            <div id="lista-tabla-wrap" class="hidden">
                <div class="overflow-x-auto rounded-2xl border-2 border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 border-b-2 border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="p-4 text-sm font-extrabold uppercase text-slate-700 dark:text-slate-300">Día</th>
                                <th class="p-4 text-sm font-extrabold uppercase text-slate-700 dark:text-slate-300">Horario</th>
                                <th class="p-4 text-sm font-extrabold uppercase text-slate-700 dark:text-slate-300">Materia</th>
                                <th class="p-4 text-sm font-extrabold uppercase text-slate-700 dark:text-slate-300">Profesor</th>
                                <th class="p-4 text-sm font-extrabold uppercase text-slate-700 dark:text-slate-300">Aula / Ubicación</th>
                                <th class="p-4 text-sm font-extrabold uppercase text-slate-700 dark:text-slate-300">Estudiantes</th>
                                <?php if (!$esDocente): ?>
                                <th class="p-4 text-sm font-extrabold uppercase text-slate-700 dark:text-slate-300 text-center">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="tabla-lista-grado-body" class="divide-y divide-slate-100 dark:divide-slate-800 text-base">
                            <!-- Inyectado dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Modal: Detalle / Metadatos de Materia -->
<div id="modal-detalle-horario" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden" role="dialog" aria-modal="true" aria-labelledby="detalle-materia-nombre">
    <div class="app-modal-dialog bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-150">
        <!-- Header -->
        <div class="p-6 sm:p-7 border-b border-slate-100 dark:border-slate-800 flex items-start justify-between gap-4">
            <div>
                <span id="detalle-badge-dia" class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-black bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 uppercase tracking-wider mb-2">
                    Lunes
                </span>
                <h3 id="detalle-materia-nombre" class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white leading-tight">
                    Materia
                </h3>
                <p id="detalle-horario-tiempo" class="text-sm sm:text-base font-bold text-slate-500 dark:text-slate-400 font-mono mt-1">
                    07:00 - 08:20
                </p>
            </div>
            <button type="button" class="w-11 h-11 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" data-close-modal="modal-detalle-horario" aria-label="Cerrar modal">&times;</button>
        </div>

        <!-- Body -->
        <div class="p-6 sm:p-7 space-y-4 text-base">
            <!-- Profesor -->
            <div class="flex items-center gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-2xl shrink-0 shadow-sm">
                    👨‍🏫
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Profesor asignado</p>
                    <p id="detalle-profesor-nombre" class="font-black text-slate-900 dark:text-white text-base sm:text-lg truncate mt-0.5">Docente</p>
                    <p id="detalle-profesor-ci" class="text-sm font-mono text-slate-500 dark:text-slate-400 mt-0.5">C.I.: -</p>
                </div>
            </div>

            <!-- Aula y Ubicación -->
            <div class="grid grid-cols-2 gap-3">
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                    <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Aula</p>
                    <p id="detalle-aula-nombre" class="font-black text-slate-900 dark:text-white text-base sm:text-lg mt-0.5 truncate">Aula</p>
                    <p id="detalle-aula-codigo" class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5 truncate"></p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                    <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ubicación</p>
                    <p id="detalle-aula-ubicacion" class="font-black text-slate-900 dark:text-white text-base sm:text-lg mt-0.5 truncate">Ubicación</p>
                </div>
            </div>

            <!-- Estudiantes Matriculados -->
            <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-xl shrink-0">
                        👥
                    </div>
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Estudiantes Matriculados</p>
                        <p id="detalle-total-estudiantes" class="font-black text-base text-slate-900 dark:text-white mt-0.5">Sin estudiantes</p>
                    </div>
                </div>
                <span id="detalle-badge-estudiantes" class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                    Activos
                </span>
            </div>

            <!-- Grado / Curso -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Grado / Curso</p>
                    <p id="detalle-grado-nombre" class="font-black text-slate-900 dark:text-white text-base sm:text-lg mt-0.5">1° BTI</p>
                </div>
                <span id="detalle-turno-nombre" class="text-xs font-bold px-3 py-1 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                    Mañana
                </span>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3">
            <div>
                <?php if (!$esDocente): ?>
                <button id="btn-detalle-desactivar" type="button" class="min-h-[44px] px-4 py-2 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60 rounded-xl text-sm font-bold border border-rose-200 dark:border-rose-800 transition-colors cursor-pointer">
                    Desactivar horario
                </button>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" data-close-modal="modal-detalle-horario" class="min-h-[44px] px-5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-sm font-bold transition-colors cursor-pointer">
                    Cerrar
                </button>
                <?php if (!$esDocente): ?>
                <button id="btn-detalle-editar" type="button" class="min-h-[44px] px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-extrabold shadow-sm transition-all cursor-pointer active:scale-95">
                    Editar horario
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Crear Horario -->
<div id="modal-crear-horario" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden" <?= $esDocente ? 'style="display:none;" aria-hidden="true"' : '' ?> role="dialog" aria-modal="true" aria-labelledby="titulo-crear-horario">
    <div class="app-modal-dialog bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-150 p-6 sm:p-8">
        <div class="flex items-start justify-between gap-4 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 id="titulo-crear-horario" class="text-2xl font-black text-slate-900 dark:text-white">Crear horario de clase</h3>
                <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1">El aula se asigna automáticamente según el grado seleccionado.</p>
            </div>
            <button type="button" class="w-11 h-11 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" data-close-modal="modal-crear-horario" aria-label="Cerrar modal">&times;</button>
        </div>

        <div id="mensaje-crear-horario" class="mb-4" aria-live="polite"></div>

        <form id="form-crear-horario" data-api="<?= base_url('src/api/Horario/crear_horario.php') ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Grado / Curso *
                    </label>
                    <select name="id_grado" id="crear-id-grado" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all" required>
                        <option value="">Cargando grados...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Aula asignada
                    </label>
                    <input id="crear-id-aula" type="text" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 text-base font-semibold cursor-not-allowed"
                           placeholder="Seleccione un grado" readonly aria-readonly="true">
                    <span class="text-xs text-slate-500 dark:text-slate-400 mt-1 block">Se obtiene automáticamente desde el grado.</span>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Materia y Docente Asignado *
                    </label>
                    <select name="id_asignacion" id="crear-id-asignacion" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all" required>
                        <option value="">Seleccione primero un grado</option>
                    </select>
                    <span class="text-xs text-slate-500 dark:text-slate-400 mt-1 block">Muestra exclusivamente las asignaciones docentes del grado seleccionado.</span>
                </div>

                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Día de la Semana *
                    </label>
                    <select name="dia_semana" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all" required>
                        <option value="">Seleccione un día</option>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miércoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sábado">Sábado</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Hora Inicio *
                        </label>
                        <input name="hora_inicio" type="time" class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all" required>
                    </div>

                    <div>
                        <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Hora Fin *
                        </label>
                        <input name="hora_fin" type="time" class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all" required>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-5 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                <button type="button" data-close-modal="modal-crear-horario" class="min-h-[48px] px-5 py-2.5 rounded-xl text-sm sm:text-base font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancelar
                </button>
                <button id="guardar-horario" type="submit" class="min-h-[48px] px-6 py-2.5 rounded-xl text-sm sm:text-base font-extrabold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                    <span>💾</span> Guardar horario
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Horario -->
<div id="modal-editar-horario" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden" <?= $esDocente ? 'style="display:none;" aria-hidden="true"' : '' ?> role="dialog" aria-modal="true" aria-labelledby="titulo-editar-horario">
    <div class="app-modal-dialog bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-150 p-6 sm:p-8">
        <div class="flex items-start justify-between gap-4 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
            <div>
                <h3 id="titulo-editar-horario" class="text-2xl font-black text-slate-900 dark:text-white">Editar horario de clase</h3>
                <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1">Al cambiar el grado también se actualiza su aula.</p>
            </div>
            <button type="button" class="w-11 h-11 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" data-close-modal="modal-editar-horario" aria-label="Cerrar modal">&times;</button>
        </div>

        <div id="mensaje-editar-horario" class="mb-4" aria-live="polite"></div>

        <form id="form-editar-horario" data-api="<?= base_url('src/api/Horario/actualizar_horario.php') ?>">
            <input type="hidden" name="id_horario" id="editar-id-horario">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-5">
                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Grado / Curso *
                    </label>
                    <select name="id_grado" id="editar-id-grado" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all" required></select>
                </div>

                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Aula asignada
                    </label>
                    <input id="editar-id-aula" type="text" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 text-base font-semibold cursor-not-allowed" readonly aria-readonly="true">
                    <span class="text-xs text-slate-500 dark:text-slate-400 mt-1 block">El aula se actualiza según el grado seleccionado.</span>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Materia y Docente Asignado *
                    </label>
                    <select name="id_asignacion" id="editar-id-asignacion" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all" required></select>
                    <span class="text-xs text-slate-500 dark:text-slate-400 mt-1 block">Muestra exclusivamente las asignaciones docentes del grado seleccionado.</span>
                </div>

                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Día de la Semana *
                    </label>
                    <select name="dia_semana" id="editar-dia-semana" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all" required>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miércoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sábado">Sábado</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Hora Inicio *
                        </label>
                        <input name="hora_inicio" id="editar-hora-inicio" type="time" class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all" required>
                    </div>

                    <div>
                        <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            Hora Fin *
                        </label>
                        <input name="hora_fin" id="editar-hora-fin" type="time" class="w-full min-h-[48px] px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all" required>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-5 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                <button type="button" data-close-modal="modal-editar-horario" class="min-h-[48px] px-5 py-2.5 rounded-xl text-sm sm:text-base font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">
                    Cancelar
                </button>
                <button id="actualizar-horario" type="submit" class="min-h-[48px] px-6 py-2.5 rounded-xl text-sm sm:text-base font-extrabold bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                    <span>💾</span> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
window.HORARIOS_CONFIG = <?= json_encode([
    'apiHorarios' => base_url('src/api/Horario/leer_horarios.php'),
    'apiGrados' => base_url('src/api/Grado/leer_grados.php'),
    'apiAsignaciones' => base_url('src/api/Asignaciones/leer_asignaciones.php'),
    'apiDesactivar' => base_url('src/api/Horario/desactivar_horario.php'),
    'esDocente' => $esDocente
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= base_url('public/js/horarios.js?v=' . filemtime(__DIR__ . '/../../../public/js/horarios.js')) ?>"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
