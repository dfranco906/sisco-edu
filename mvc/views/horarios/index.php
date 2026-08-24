<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
require_once __DIR__ . '/../../../src/config/app.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10">
    <header class="app-page-header p-5 sm:p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-2xl sm:text-3xl font-bold">Horarios por grado</h2>
                <p class="mt-1">Organizá la semana por horas cátedra y agregá extensiones cuando el curso las necesite.</p>
            </div>

            <div class="app-toolbar-actions flex flex-wrap gap-3">
                <button id="btn-crear-horario" type="button"
                        class="btn btn-success">
                    + Crear horario
                </button>
                <a href="<?= base_url('mvc/views/dashboard.php') ?>"
                   class="btn btn-primary">
                    Volver
                </a>
            </div>
        </div>
    </header>

    <section class="app-panel p-4 sm:p-6 mb-6" aria-labelledby="titulo-vista-semanal">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-4">
            <div>
                <h3 id="titulo-vista-semanal" class="text-xl font-bold">Horario semanal</h3>
                <p class="text-sm mt-1" style="color: var(--color-muted);">
                    Las horas se muestran una sola vez; cada bloque contiene únicamente la clase.
                </p>
            </div>

            <div class="schedule-view-controls">
                <label class="block w-full font-semibold">
                    Grado
                    <select id="selector-grado-semanal" class="app-input mt-2 w-full">
                        <option value="">Cargando grados...</option>
                    </select>
                </label>
                <label class="schedule-extended-toggle">
                    <input id="mostrar-horas-extra" type="checkbox">
                    <span>
                        <strong>Mostrar turno tarde</strong>
                        <small>13:30 a 16:00</small>
                    </span>
                </label>
            </div>
        </div>

        <div id="estado-horario-semanal" class="text-sm mb-4" aria-live="polite"></div>
        <div id="horario-semanal-desktop" class="schedule-week-table-wrap" aria-label="Grilla semanal"></div>
        <div id="horario-semanal-mobile" class="schedule-mobile-days" aria-label="Horario agrupado por día"></div>
    </section>

    <details class="app-panel schedule-admin-details" aria-labelledby="titulo-lista-horarios">
        <summary class="schedule-admin-summary">
            <div>
                <h3 id="titulo-lista-horarios" class="text-xl font-bold">Lista completa</h3>
                <p class="text-sm mt-1" style="color: var(--color-muted);">
                    Abrí esta vista únicamente para buscar, editar o desactivar horarios.
                </p>
            </div>
            <span class="schedule-admin-summary-action" aria-hidden="true">Abrir lista</span>
        </summary>

        <div class="schedule-admin-content">
        <div class="flex justify-end mb-5">
            <a href="<?= base_url('mvc/views/horarios/desactivados.php') ?>"
               class="btn btn-dark">
                Ver desactivados
            </a>
        </div>

        <div class="app-filters mb-5">
            <input id="filtro-horario-buscar" type="search" class="app-input"
                   placeholder="Buscar materia o profesor...">
            <select id="filtro-horario-grado" class="app-input">
                <option value="">Todos los grados</option>
            </select>
            <select id="filtro-horario-dia" class="app-input">
                <option value="">Todos los días</option>
            </select>
            <button id="limpiar-filtros-horario" type="button"
                    class="btn btn-muted">
                Limpiar filtros
            </button>
        </div>

        <div class="app-table-wrap">
            <table class="app-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th>Grado</th>
                        <th>Día</th>
                        <th>Hora inicio</th>
                        <th>Hora fin</th>
                        <th>Materia</th>
                        <th>Profesor</th>
                        <th>Aula</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-horarios-body">
                    <tr><td colspan="8" class="text-center">Cargando horarios...</td></tr>
                </tbody>
            </table>
        </div>
        </div>
    </details>
</main>

<div id="modal-crear-horario" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="titulo-crear-horario">
    <div class="app-modal-dialog max-w-2xl">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h3 id="titulo-crear-horario" class="text-xl font-bold">Crear horario</h3>
                <p class="text-sm mt-1" style="color: var(--color-muted);">Seleccioná una asignación y sus datos se completarán automáticamente.</p>
            </div>
            <button type="button" class="app-modal-close" data-close-modal="modal-crear-horario" aria-label="Cerrar">×</button>
        </div>

        <div id="mensaje-crear-horario" class="mb-4" aria-live="polite"></div>

        <form id="form-crear-horario" data-api="<?= base_url('src/api/Horario/crear_horario.php') ?>">
            <div class="app-form-grid">
                <label class="font-semibold sm:col-span-2">
                    Asignación
                    <input id="crear-buscar-asignacion" type="search" class="app-input app-select-search mt-2 w-full"
                           placeholder="Buscar por grado, materia, profesor o año..." autocomplete="off">
                    <div id="crear-asignacion-sugerencias" class="app-select-suggestions"
                         role="listbox" aria-label="Asignaciones disponibles" hidden></div>
                    <select name="id_asignacion" id="crear-id-asignacion" class="app-input app-select-source-hidden w-full" required>
                        <option value="">Cargando asignaciones...</option>
                    </select>
                    <span class="app-help">Elegí una combinación válida. El grado, la materia, el profesor y el año lectivo aparecen juntos.</span>
                </label>

                <label class="font-semibold">
                    Profesor
                    <input id="crear-profesor" type="text" class="app-input app-input-readonly mt-2 w-full"
                           placeholder="Seleccione una asignación" readonly aria-readonly="true">
                </label>

                <label class="font-semibold">
                    Materia
                    <input id="crear-materia" type="text" class="app-input app-input-readonly mt-2 w-full"
                           placeholder="Seleccione una asignación" readonly aria-readonly="true">
                </label>

                <label class="font-semibold">
                    Grado
                    <input id="crear-grado" type="text" class="app-input app-input-readonly mt-2 w-full"
                           placeholder="Seleccione una asignación" readonly aria-readonly="true">
                </label>

                <label class="font-semibold">
                    Aula asignada
                    <input id="crear-id-aula" type="text" class="app-input app-input-readonly mt-2 w-full"
                           placeholder="Seleccione una asignación" readonly aria-readonly="true">
                    <span class="app-help">Se obtiene desde la asignación y no puede modificarse manualmente.</span>
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

                <label class="schedule-extra-option sm:col-span-2">
                    <input id="crear-horario-extra" type="checkbox">
                    <span>
                        <strong>Agregar turno tarde</strong>
                        <small>Habilita las horas posteriores a las 13:30 para los cursos que continúan por la tarde.</small>
                    </span>
                </label>

                <label class="font-semibold sm:col-span-2">
                    Hora cátedra sugerida
                    <select id="crear-bloque-horario" class="app-input mt-2 w-full"></select>
                    <span class="app-help">Completa una hora cátedra. Podés ajustar el final si la clase ocupa bloques consecutivos.</span>
                </label>

                <label class="font-semibold">
                    Hora inicio
                    <input name="hora_inicio" id="crear-hora-inicio" type="time" class="app-input mt-2 w-full" required>
                </label>

                <label class="font-semibold">
                    Hora fin
                    <input name="hora_fin" id="crear-hora-fin" type="time" class="app-input mt-2 w-full" required>
                </label>

                <label class="schedule-exception-option sm:col-span-2">
                    <input name="permite_superposicion" id="crear-permite-superposicion"
                           type="checkbox" value="1">
                    <span>
                        <strong>Clase conjunta (excepción controlada)</strong>
                        <small>Vincula esta clase con el curso paralelo y toma automáticamente su día y horario.</small>
                    </span>
                </label>

                <div id="crear-clase-conjunta-panel" class="schedule-joint-link sm:col-span-2" hidden>
                    <label class="font-semibold">
                        Cursos correspondientes
                        <select name="id_asignaciones_conjuntas" id="crear-id-asignacion-conjunta" class="app-input mt-2 w-full"></select>
                    </label>
                    <p id="crear-clase-conjunta-ayuda" class="app-help" aria-live="polite"></p>
                </div>
            </div>

            <div class="app-modal-actions mt-6">
                <button type="button" data-close-modal="modal-crear-horario"
                        class="btn btn-muted">Cancelar</button>
                <button id="guardar-horario" type="submit"
                        class="btn btn-success">Guardar horario</button>
            </div>
        </form>
    </div>
</div>

<div id="modal-editar-horario" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="titulo-editar-horario">
    <div class="app-modal-dialog max-w-2xl">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h3 id="titulo-editar-horario" class="text-xl font-bold">Editar horario</h3>
                <p class="text-sm mt-1" style="color: var(--color-muted);">Al cambiar la asignación también se actualizan el grado y el aula.</p>
            </div>
            <button type="button" class="app-modal-close" data-close-modal="modal-editar-horario" aria-label="Cerrar">×</button>
        </div>

        <div id="mensaje-editar-horario" class="mb-4" aria-live="polite"></div>

        <form id="form-editar-horario" data-api="<?= base_url('src/api/Horario/actualizar_horario.php') ?>">
            <input type="hidden" name="id_horario" id="editar-id-horario">
            <div class="app-form-grid">
                <label class="font-semibold sm:col-span-2">
                    Asignación
                    <input id="editar-buscar-asignacion" type="search" class="app-input app-select-search mt-2 w-full"
                           placeholder="Buscar por grado, materia, profesor o año..." autocomplete="off">
                    <div id="editar-asignacion-sugerencias" class="app-select-suggestions"
                         role="listbox" aria-label="Asignaciones disponibles" hidden></div>
                    <select name="id_asignacion" id="editar-id-asignacion" class="app-input app-select-source-hidden w-full" required></select>
                    <span class="app-help">Solo aparecen asignaciones activas y válidas.</span>
                </label>

                <label class="font-semibold">
                    Profesor
                    <input id="editar-profesor" type="text" class="app-input app-input-readonly mt-2 w-full" readonly aria-readonly="true">
                </label>

                <label class="font-semibold">
                    Materia
                    <input id="editar-materia" type="text" class="app-input app-input-readonly mt-2 w-full" readonly aria-readonly="true">
                </label>

                <label class="font-semibold">
                    Grado
                    <input id="editar-grado" type="text" class="app-input app-input-readonly mt-2 w-full" readonly aria-readonly="true">
                </label>

                <label class="font-semibold">
                    Aula asignada
                    <input id="editar-id-aula" type="text" class="app-input app-input-readonly mt-2 w-full" readonly aria-readonly="true">
                    <span class="app-help">El aula se asigna automáticamente según la asignación seleccionada.</span>
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

                <label class="schedule-extra-option sm:col-span-2">
                    <input id="editar-horario-extra" type="checkbox">
                    <span>
                        <strong>Turno tarde</strong>
                        <small>Usá esta opción cuando la jornada continúe después de las 13:30.</small>
                    </span>
                </label>

                <label class="font-semibold sm:col-span-2">
                    Hora cátedra sugerida
                    <select id="editar-bloque-horario" class="app-input mt-2 w-full"></select>
                    <span class="app-help">Elegir un bloque actualiza las horas; los intervalos existentes siguen siendo editables.</span>
                </label>

                <label class="font-semibold">
                    Hora inicio
                    <input name="hora_inicio" id="editar-hora-inicio" type="time" class="app-input mt-2 w-full" required>
                </label>

                <label class="font-semibold">
                    Hora fin
                    <input name="hora_fin" id="editar-hora-fin" type="time" class="app-input mt-2 w-full" required>
                </label>

                <label class="schedule-exception-option sm:col-span-2">
                    <input name="permite_superposicion" id="editar-permite-superposicion"
                           type="checkbox" value="1">
                    <span>
                        <strong>Clase conjunta (excepción controlada)</strong>
                        <small>Vincula esta clase con el curso paralelo y mantiene la misma franja en ambos.</small>
                    </span>
                </label>

                <div id="editar-clase-conjunta-panel" class="schedule-joint-link sm:col-span-2" hidden>
                    <label class="font-semibold">
                        Cursos correspondientes
                        <select name="id_asignaciones_conjuntas" id="editar-id-asignacion-conjunta" class="app-input mt-2 w-full"></select>
                    </label>
                    <p id="editar-clase-conjunta-ayuda" class="app-help" aria-live="polite"></p>
                </div>
            </div>

            <div class="app-modal-actions mt-6">
                <button type="button" data-close-modal="modal-editar-horario"
                        class="btn btn-muted">Cancelar</button>
                <button id="actualizar-horario" type="submit"
                        class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
window.HORARIOS_CONFIG = <?= json_encode([
    'apiHorarios' => base_url('src/api/Horario/leer_horarios.php'),
    'apiGrados' => base_url('src/api/Grado/leer_grados.php?opciones=1'),
    'apiAsignaciones' => base_url('src/api/Asignaciones/leer_asignaciones.php?opciones=1'),
    'apiDesactivar' => base_url('src/api/Horario/desactivar_horario.php')
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= base_url('public/js/horarios.js') ?>?v=<?= urlencode((string) @filemtime(__DIR__ . '/../../../public/js/horarios.js')) ?>"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
