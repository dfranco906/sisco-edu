<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../../../src/config/app.php';

$rol = $_SESSION['rol'] ?? '';
$esDocente = ($rol === 'Profesor');
$urlVolver = $esDocente ? base_url('mvc/views/dashboard.php') : base_url('mvc/views/administracion/index.php');
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10">
    <!-- Header Principal -->
    <header class="app-page-header p-5 sm:p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
                    <?= $esDocente ? 'Mis Horarios de Clase' : 'Gestión de Horarios' ?>
                </h2>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                    <?= $esDocente ? 'Consultá tus materias y clases semanales en los cursos donde estás designado.' : 'Visualizá la grilla semanal por horas cátedras filtrando por turno, ciclo y grado.' ?>
                </p>
            </div>

            <div class="app-toolbar-actions flex flex-wrap items-center gap-3">
                <?php if (!$esDocente): ?>
                <button id="btn-crear-horario" type="button" class="btn btn-success">
                    <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Crear horario
                </button>
                <a href="<?= base_url('mvc/views/horarios/desactivados.php') ?>" class="btn btn-dark">
                    Ver desactivados
                </a>
                <?php endif; ?>
                <a href="<?= $urlVolver ?>" class="btn btn-primary">
                    Volver
                </a>
            </div>
        </div>
    </header>

    <!-- Panel de Consulta y Grilla Semanal -->
    <section class="app-panel p-4 sm:p-6 mb-6" aria-labelledby="titulo-modulo-horarios">
        <!-- Sub-encabezado con estado en vivo -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-border mb-5">
            <div>
                <h3 id="titulo-modulo-horarios" class="text-lg sm:text-xl font-bold flex items-center gap-2 text-slate-900 dark:text-white">
                    <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span><?= $esDocente ? 'Mi Horario Semanal' : 'Horario Semanal por Grado' ?></span>
                </h3>
                <p class="text-xs sm:text-sm mt-0.5" style="color: var(--color-muted);">
                    <?= $esDocente ? 'Seleccioná el grado para consultar tu cronograma.' : 'Filtrá turno, ciclo y bachillerato para extraer el horario oficial del grado.' ?>
                </p>
            </div>

            <!-- Indicador de clase en vivo en este momento -->
            <div id="indicador-en-vivo" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/80 shadow-sm shrink-0">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                <span id="texto-en-vivo" class="text-slate-700 dark:text-slate-300">Cargando estado...</span>
            </div>
        </div>

        <!-- Filtros en cascada: Turno -> Nivel -> Bachillerato -> Grado -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 p-4 rounded-xl bg-slate-50 dark:bg-slate-900/50 border border-slate-200/80 dark:border-slate-800 mb-5">
            <div>
                <label for="filtro-turno" class="block text-xs font-bold uppercase tracking-wider text-muted mb-1.5">
                    1. Turno
                </label>
                <select id="filtro-turno" class="app-input w-full text-sm">
                    <option value="">Todos los turnos</option>
                </select>
            </div>

            <div>
                <label for="filtro-nivel" class="block text-xs font-bold uppercase tracking-wider text-muted mb-1.5">
                    2. Ciclo / Nivel
                </label>
                <select id="filtro-nivel" class="app-input w-full text-sm">
                    <option value="">Todos los ciclos</option>
                </select>
            </div>

            <div>
                <label for="filtro-bachillerato" class="block text-xs font-bold uppercase tracking-wider text-muted mb-1.5">
                    3. Bachillerato
                </label>
                <select id="filtro-bachillerato" class="app-input w-full text-sm">
                    <option value="">Todos / No aplica</option>
                </select>
            </div>

            <div>
                <label for="filtro-grado" class="block text-xs font-bold uppercase tracking-wider text-primary mb-1.5">
                    4. Grado / Curso *
                </label>
                <select id="filtro-grado" class="app-input w-full text-sm font-semibold border-primary/50 focus:border-primary">
                    <option value="">Seleccione un grado...</option>
                </select>
            </div>
        </div>

        <!-- Tira de Metadatos del Grado Seleccionado -->
        <div id="info-grado-seleccionado" class="hidden flex flex-wrap items-center justify-between gap-3 p-3.5 rounded-xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200/60 dark:border-blue-900/40 text-xs sm:text-sm mb-5">
            <div class="flex flex-wrap items-center gap-3">
                <span class="font-bold text-primary flex items-center gap-1.5">
                    <span id="meta-grado-nombre" class="text-base font-extrabold text-blue-700 dark:text-blue-400"></span>
                </span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-muted">Aula: <strong id="meta-grado-aula" class="text-slate-800 dark:text-slate-200 font-bold"></strong></span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-muted">Ubicación: <strong id="meta-grado-ubicacion" class="text-slate-800 dark:text-slate-200 font-bold"></strong></span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-muted">Estudiantes: <strong id="meta-grado-estudiantes" class="text-slate-800 dark:text-slate-200 font-bold"></strong></span>
            </div>

            <div class="flex items-center gap-1.5 bg-slate-200/70 dark:bg-slate-800/80 p-1 rounded-lg">
                <button id="btn-vista-grilla" type="button" class="btn btn-sm btn-primary text-xs py-1 px-3 shadow-none">
                    Grilla Semanal
                </button>
                <button id="btn-vista-lista" type="button" class="btn btn-sm btn-muted text-xs py-1 px-3 shadow-none">
                    Lista Detallada
                </button>
            </div>
        </div>

        <!-- Contenedor Principal de la Grilla / Lista -->
        <div id="contenedor-grilla-horario" class="relative">
            <!-- Spinner de carga -->
            <div id="grilla-loading" class="hidden flex flex-col items-center justify-center p-12 text-center text-muted">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-solid border-primary border-r-transparent mb-3"></div>
                <p class="font-medium text-sm">Consultando horario del grado...</p>
            </div>

            <!-- Prompt vacío cuando no hay grado seleccionado -->
            <div id="grilla-vacia" class="text-center py-16 px-4 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-blue-50 dark:bg-blue-950/40 text-primary flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h4 class="text-base font-bold text-slate-800 dark:text-slate-100">Seleccioná un grado</h4>
                <p class="text-xs sm:text-sm text-muted mt-1 max-w-md mx-auto">
                    Elegí un turno y grado en los selectores para visualizar la matriz semanal de horarios y materias.
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
                <div class="app-table-wrap">
                    <table class="app-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th>Día</th>
                                <th>Hora Inicio</th>
                                <th>Hora Fin</th>
                                <th>Materia</th>
                                <th>Profesor</th>
                                <th>Aula / Ubicación</th>
                                <th>Estudiantes</th>
                                <?php if (!$esDocente): ?>
                                <th>Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="tabla-lista-grado-body">
                            <!-- Inyectado dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Modal: Detalle / Metadatos de Materia -->
<div id="modal-detalle-horario" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="detalle-materia-nombre">
    <div class="app-modal-dialog max-w-lg">
        <div class="flex items-start justify-between gap-4 mb-4 pb-3 border-b border-border">
            <div>
                <span id="detalle-badge-dia" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary/10 text-primary mb-1">
                    Lunes
                </span>
                <h3 id="detalle-materia-nombre" class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white">
                    Materia
                </h3>
                <p id="detalle-horario-tiempo" class="text-xs sm:text-sm font-semibold text-muted mt-0.5">
                    07:00 - 08:20
                </p>
            </div>
            <button type="button" class="app-modal-close" data-close-modal="modal-detalle-horario" aria-label="Cerrar">×</button>
        </div>

        <div class="space-y-3 mb-6 text-sm">
            <!-- Profesor -->
            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs uppercase font-bold text-muted">Profesor asignado</p>
                    <p id="detalle-profesor-nombre" class="font-bold text-slate-900 dark:text-slate-100 truncate">Docente</p>
                    <p id="detalle-profesor-ci" class="text-xs text-muted">C.I.: -</p>
                </div>
            </div>

            <!-- Aula y Ubicación -->
            <div class="grid grid-cols-2 gap-3">
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                    <p class="text-xs uppercase font-bold text-muted">Aula</p>
                    <p id="detalle-aula-nombre" class="font-bold text-slate-900 dark:text-slate-100 mt-0.5 truncate">Aula</p>
                    <p id="detalle-aula-codigo" class="text-xs text-muted truncate"></p>
                </div>
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                    <p class="text-xs uppercase font-bold text-muted">Ubicación</p>
                    <p id="detalle-aula-ubicacion" class="font-bold text-slate-900 dark:text-slate-100 mt-0.5 truncate">Ubicación</p>
                </div>
            </div>

            <!-- Estudiantes Matriculados -->
            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs uppercase font-bold text-muted">Estudiantes Matriculados</p>
                        <p id="detalle-total-estudiantes" class="font-extrabold text-sm text-slate-900 dark:text-slate-100">Sin estudiantes</p>
                    </div>
                </div>
                <span id="detalle-badge-estudiantes" class="text-xs font-bold px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                    Activos
                </span>
            </div>

            <!-- Grado / Curso -->
            <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase font-bold text-muted">Grado / Curso</p>
                    <p id="detalle-grado-nombre" class="font-bold text-slate-900 dark:text-slate-100">1° BTI</p>
                </div>
                <span id="detalle-turno-nombre" class="text-xs px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 font-semibold text-slate-700 dark:text-slate-300">
                    Mañana
                </span>
            </div>
        </div>

        <div class="app-modal-actions pt-3 border-t border-border flex items-center justify-between">
            <div>
                <?php if (!$esDocente): ?>
                <button id="btn-detalle-desactivar" type="button" class="btn btn-danger text-xs">
                    Desactivar horario
                </button>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" data-close-modal="modal-detalle-horario" class="btn btn-muted">
                    Cerrar
                </button>
                <?php if (!$esDocente): ?>
                <button id="btn-detalle-editar" type="button" class="btn btn-primary">
                    Editar horario
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Crear Horario -->
<div id="modal-crear-horario" class="app-modal hidden" <?= $esDocente ? 'style="display:none;" aria-hidden="true"' : '' ?> role="dialog" aria-modal="true" aria-labelledby="titulo-crear-horario">
    <div class="app-modal-dialog max-w-2xl">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h3 id="titulo-crear-horario" class="text-xl font-bold">Crear horario</h3>
                <p class="text-sm mt-1" style="color: var(--color-muted);">El aula se asigna automáticamente según el grado seleccionado.</p>
            </div>
            <button type="button" class="app-modal-close" data-close-modal="modal-crear-horario" aria-label="Cerrar">×</button>
        </div>

        <div id="mensaje-crear-horario" class="mb-4" aria-live="polite"></div>

        <form id="form-crear-horario" data-api="<?= base_url('src/api/Horario/crear_horario.php') ?>">
            <div class="app-form-grid">
                <label class="font-semibold sm:col-span-2">
                    Asignación (Materia / Docente)
                    <select name="id_asignacion" id="crear-id-asignacion" class="app-input mt-2 w-full" required>
                        <option value="">Cargando asignaciones...</option>
                    </select>
                </label>

                <label class="font-semibold">
                    Grado
                    <select name="id_grado" id="crear-id-grado" class="app-input mt-2 w-full" required>
                        <option value="">Cargando grados...</option>
                    </select>
                </label>

                <label class="font-semibold">
                    Aula asignada
                    <input id="crear-id-aula" type="text" class="app-input app-input-readonly mt-2 w-full"
                           placeholder="Seleccione un grado" readonly aria-readonly="true">
                    <span class="app-help">Se obtiene automáticamente desde el grado.</span>
                </label>

                <label class="font-semibold">
                    Día
                    <select name="dia_semana" class="app-input mt-2 w-full" required>
                        <option value="">Seleccione un día</option>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miércoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sábado">Sábado</option>
                    </select>
                </label>

                <label class="font-semibold">
                    Hora inicio
                    <input name="hora_inicio" type="time" class="app-input mt-2 w-full" required>
                </label>

                <label class="font-semibold">
                    Hora fin
                    <input name="hora_fin" type="time" class="app-input mt-2 w-full" required>
                </label>
            </div>

            <div class="app-modal-actions mt-6">
                <button type="button" data-close-modal="modal-crear-horario" class="btn btn-muted">Cancelar</button>
                <button id="guardar-horario" type="submit" class="btn btn-success">Guardar horario</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Editar Horario -->
<div id="modal-editar-horario" class="app-modal hidden" <?= $esDocente ? 'style="display:none;" aria-hidden="true"' : '' ?> role="dialog" aria-modal="true" aria-labelledby="titulo-editar-horario">
    <div class="app-modal-dialog max-w-2xl">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h3 id="titulo-editar-horario" class="text-xl font-bold">Editar horario</h3>
                <p class="text-sm mt-1" style="color: var(--color-muted);">Al cambiar el grado también se actualiza su aula.</p>
            </div>
            <button type="button" class="app-modal-close" data-close-modal="modal-editar-horario" aria-label="Cerrar">×</button>
        </div>

        <div id="mensaje-editar-horario" class="mb-4" aria-live="polite"></div>

        <form id="form-editar-horario" data-api="<?= base_url('src/api/Horario/actualizar_horario.php') ?>">
            <input type="hidden" name="id_horario" id="editar-id-horario">
            <div class="app-form-grid">
                <label class="font-semibold sm:col-span-2">
                    Asignación (Materia / Docente)
                    <select name="id_asignacion" id="editar-id-asignacion" class="app-input mt-2 w-full" required></select>
                </label>

                <label class="font-semibold">
                    Grado
                    <select name="id_grado" id="editar-id-grado" class="app-input mt-2 w-full" required></select>
                </label>

                <label class="font-semibold">
                    Aula asignada
                    <input id="editar-id-aula" type="text" class="app-input app-input-readonly mt-2 w-full" readonly aria-readonly="true">
                    <span class="app-help">El aula se actualiza según el grado seleccionado.</span>
                </label>

                <label class="font-semibold">
                    Día
                    <select name="dia_semana" id="editar-dia-semana" class="app-input mt-2 w-full" required>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miércoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sábado">Sábado</option>
                    </select>
                </label>

                <label class="font-semibold">
                    Hora inicio
                    <input name="hora_inicio" id="editar-hora-inicio" type="time" class="app-input mt-2 w-full" required>
                </label>

                <label class="font-semibold">
                    Hora fin
                    <input name="hora_fin" id="editar-hora-fin" type="time" class="app-input mt-2 w-full" required>
                </label>
            </div>

            <div class="app-modal-actions mt-6">
                <button type="button" data-close-modal="modal-editar-horario" class="btn btn-muted">Cancelar</button>
                <button id="actualizar-horario" type="submit" class="btn btn-primary">Guardar cambios</button>
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
