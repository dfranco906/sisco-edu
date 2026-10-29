<?php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Determinación segura de la URL de retorno
$rolSesion = $_SESSION['rol'] ?? '';
$esAdmin = in_array(strtolower($rolSesion), ['superadmin', 'administrador', 'administracion'], true);

if (!empty($_GET['ref'])) {
    $destinoVolver = $_GET['ref'];
} elseif (!$esAdmin && ($urlVolver ?? '') === 'mvc/views/administracion/index.php') {
    $destinoVolver = match ($rolSesion) {
        'Coordinador' => 'mvc/views/coordinacion/index.php',
        'Profesor' => 'mvc/views/profesores/mi_panel.php',
        'Estudiante' => 'mvc/views/estudiantes/mi_panel.php',
        'Tutor' => 'mvc/views/tutores/mi_panel.php',
        default => 'mvc/views/dashboard.php'
    };
} else {
    $destinoVolver = $urlVolver ?? 'mvc/views/administracion/index.php';
}
?>

<?php if (isset($formCrear)): ?>
<!-- Modal: Crear Registro Accesible -->
<div id="modal-crear" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-crear">
    <div class="app-modal-dialog bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-full max-w-xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <!-- Header -->
        <div class="p-5 sm:p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-800/90 flex items-start justify-between gap-4 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 flex items-center justify-center text-2xl font-bold shrink-0 border border-blue-200 dark:border-blue-800 shadow-2xs">
                    <?= $icono ?? '✍️' ?>
                </div>
                <div class="min-w-0">
                    <h3 id="titulo-modal-crear" class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white truncate">
                        Crear <?= htmlspecialchars($tituloBotonCrear ?? $titulo) ?>
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                        Completá los campos para registrar en el sistema.
                    </p>
                </div>
            </div>
            <button type="button" id="cerrar-modal" class="min-w-[44px] min-h-[44px] w-11 h-11 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer hover:bg-slate-200/70 dark:hover:bg-slate-800 transition-colors shrink-0 js-cerrar-modal-crear" aria-label="Cerrar modal">&times;</button>
        </div>

        <div id="mensaje-form" class="px-6 pt-4 empty:hidden"></div>

        <!-- Formulario con Scroll Interno -->
        <form id="form-crear" data-api="<?= base_url($formCrear['api']) ?>" class="flex flex-col flex-1 overflow-hidden">
            <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-4 text-base">
                <?php foreach ($formCrear['campos'] as $campo): ?>
                    <div>
                        <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                            <?= htmlspecialchars($campo['label']) ?> <?= ($campo['required'] ?? true) ? '<span class="text-rose-600">*</span>' : '' ?>
                        </label>

                        <?php if (($campo['type'] ?? '') === 'select'): ?>
                            <?php if (isset($campo['options'])): ?>
                                <select name="<?= htmlspecialchars($campo['name']) ?>" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all" <?= ($campo['required'] ?? true) ? 'required' : '' ?>>
                                    <option value="">Seleccione una opción</option>
                                    <?php foreach ($campo['options'] as $op): ?>
                                        <option value="<?= htmlspecialchars($op['value']) ?>"><?= htmlspecialchars($op['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <select
                                    name="<?= htmlspecialchars($campo['name']) ?>"
                                    data-api="<?= htmlspecialchars($campo['api']) ?>"
                                    data-value="<?= htmlspecialchars($campo['value']) ?>"
                                    data-label="<?= htmlspecialchars($campo['labelField']) ?>"
                                    class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all"
                                    <?= ($campo['required'] ?? true) ? 'required' : '' ?>>
                                    <option value="">Cargando opciones...</option>
                                </select>
                            <?php endif; ?>
                        <?php else: ?>
                            <input 
                                type="<?= htmlspecialchars($campo['type']) ?>"
                                name="<?= htmlspecialchars($campo['name']) ?>"
                                class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all"
                                <?= ($campo['required'] ?? true) ? 'required' : '' ?>
                            >
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Footer Fijo Accesible -->
            <div class="p-4 sm:p-6 bg-slate-50/90 dark:bg-slate-800/90 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3 shrink-0">
                <button type="button" class="btn btn-secondary min-h-[46px] px-5 py-2.5 rounded-xl text-base font-semibold js-cerrar-modal-crear cursor-pointer">
                    Cerrar
                </button>
                <button type="submit" class="btn btn-primary min-h-[46px] px-6 py-2.5 rounded-xl text-base font-bold shadow-sm flex items-center gap-2 cursor-pointer active:scale-95">
                    <span>💾</span> Guardar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Main Content -->
<main class="app-main flex-1 p-4 sm:p-6 lg:p-10 text-base">
    <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">

        <!-- Breadcrumb Accesible -->
        <nav class="flex items-center gap-2 text-base text-slate-600 dark:text-slate-400 font-medium" aria-label="Migas de pan">
            <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition flex items-center gap-1.5 focus:outline-none focus:underline">
                <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Inicio
            </a>
            <span class="text-slate-400 dark:text-slate-600">/</span>
            <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition focus:outline-none focus:underline">
                Administración
            </a>
            <span class="text-slate-400 dark:text-slate-600">/</span>
            <span class="text-slate-900 dark:text-white font-bold"><?= htmlspecialchars($titulo) ?></span>
        </nav>

        <!-- Cabecera de Página Accesible y Responsiva -->
        <div class="app-page-header p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 flex items-center justify-center text-3xl font-black shrink-0 border border-blue-200 dark:border-blue-800 shadow-xs">
                    <?= $icono ?? '📋' ?>
                </div>
                <div class="min-w-0">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight truncate">
                        <?= htmlspecialchars($titulo) ?>
                    </h1>
                    <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 mt-1 max-w-3xl leading-relaxed">
                        <?= htmlspecialchars($subtitulo ?? 'Listado general y configuración del catálogo institucional.') ?>
                    </p>
                </div>
            </div>

            <!-- Botonera de Acción Primaria -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <?= $toolbarExtraHtml ?? '' ?>
                <?php if (isset($formCrear)): ?>
                <button id="btn-crear" type="button" class="btn btn-primary min-h-[46px] px-6 py-2.5 text-base font-bold rounded-2xl shadow-sm flex items-center gap-2 cursor-pointer active:scale-95">
                    <span class="text-xl leading-none font-bold">+</span>
                    <span>Crear <?= htmlspecialchars($tituloBotonCrear ?? 'Registro') ?></span>
                </button>
                <?php endif; ?>

                <?php if (!empty($urlDesactivados)): ?>
                <a href="<?= base_url($urlDesactivados) ?>" class="btn btn-muted min-h-[46px] px-5 py-2.5 text-base font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 flex items-center gap-2">
                    <span>🗑️</span>
                    <span>Ver inactivos</span>
                </a>
                <?php endif; ?>

                <a href="<?= base_url($destinoVolver) ?>" class="btn btn-secondary min-h-[46px] px-5 py-2.5 text-base font-semibold rounded-2xl">
                    ← Volver
                </a>
            </div>
        </div>

        <!-- Panel de Filtros y Tabla Accesible -->
        <div class="app-panel p-5 sm:p-7 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm w-full space-y-6">

            <div id="pestanas-tabla" class="app-tabs empty:hidden"></div>
            <div id="filtros-tabla" class="app-filters empty:hidden flex flex-wrap items-center gap-3"></div>

            <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                <table class="w-full text-left border-collapse text-base">
                    <thead class="bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 uppercase tracking-wider text-xs sm:text-sm font-bold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <?php foreach ($columnas as $col): ?>
                                <th class="p-4"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $col))) ?></th>
                            <?php endforeach; ?>
                            <th class="p-4 text-center w-1 whitespace-nowrap">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-body" class="divide-y divide-slate-200 dark:divide-slate-800 text-slate-800 dark:text-slate-200"></tbody>
                </table>
            </div>

        </div>

    </div>

    <!-- Modal: Editar Registro Accesible -->
    <div id="modal-editar" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-editar">
        <div class="app-modal-dialog bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-full max-w-xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Header -->
            <div class="p-5 sm:p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-800/90 flex items-start justify-between gap-4 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 flex items-center justify-center text-2xl font-bold shrink-0 border border-amber-200 dark:border-amber-800 shadow-2xs">
                        ✏️
                    </div>
                    <div class="min-w-0">
                        <h3 id="titulo-modal-editar" class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white truncate">
                            Editar registro
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                            Actualizá los datos necesarios y guardá los cambios.
                        </p>
                    </div>
                </div>
                <button type="button" id="cerrar-modal-editar" class="min-w-[44px] min-h-[44px] w-11 h-11 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer hover:bg-slate-200/70 dark:hover:bg-slate-800 transition-colors shrink-0" aria-label="Cerrar modal">&times;</button>
            </div>

            <div id="mensaje-editar" class="px-6 pt-4 empty:hidden"></div>

            <!-- Formulario con Scroll Interno -->
            <form id="form-editar" class="flex flex-col flex-1 overflow-hidden">
                <div id="campos-editar" class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-4 text-base"></div>

                <!-- Footer Fijo Accesible -->
                <div class="p-4 sm:p-6 bg-slate-50/90 dark:bg-slate-800/90 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3 shrink-0">
                    <button type="button" id="cancelar-editar" class="btn btn-secondary min-h-[46px] px-5 py-2.5 rounded-xl text-base font-semibold cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary min-h-[46px] px-6 py-2.5 rounded-xl text-base font-bold shadow-sm flex items-center gap-2 cursor-pointer active:scale-95">
                        <span>💾</span> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?= $modalExtraHtml ?? '' ?>
</main>

<script>
    window.BASE_URL = "<?= base_url('') ?>";
    window.API_ACTUALIZAR = "<?= isset($apiActualizar) ? base_url($apiActualizar) : '' ?>";
    window.API_DESACTIVAR = "<?= isset($apiDesactivar) ? base_url($apiDesactivar) : '' ?>";
    window.API_RESTAURAR = "<?= isset($apiRestaurar) ? base_url($apiRestaurar) : '' ?>";
    window.API_ELIMINAR = "<?= isset($apiEliminar) ? base_url($apiEliminar) : '' ?>";
    window.URL_DESACTIVADOS = "<?= isset($urlDesactivados) ? base_url($urlDesactivados) : '' ?>";
    window.ID_CAMPO = "<?= $idCampo ?? '' ?>";
    window.CAMPOS_EDITAR = <?= json_encode($camposEditar ?? []) ?>;
    window.FORM_CAMPOS = <?= json_encode($formCrear['campos'] ?? []) ?>;
</script>
<script src="<?= base_url('public/js/table-loader.js') ?>"></script>
<script src="<?= base_url('public/js/crud.js') ?>"></script>
<script src="<?= base_url('public/js/obtener_template.js') ?>"></script>

<script>
    cargarTabla(
        "<?= base_url($api) ?>",
        <?= json_encode($columnas) ?>,
        <?= json_encode($filtros ?? []) ?>
    );
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
