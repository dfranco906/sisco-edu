<?php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
$etiquetasColumnasDefault = [
    'anio_lectivo' => 'Año lectivo',
    'cedula_identidad' => 'Cédula',
    'codigo_aula' => 'Código aula',
    'carga_horaria' => 'Carga horaria semanal',
    'dia_semana' => 'Día',
    'hora_inicio' => 'Hora inicio',
    'hora_fin' => 'Hora fin'
];
?>
<?php if (isset($formCrear)): ?>
<div id="modal-crear" class="app-modal hidden">
    <div class="app-modal-dialog max-w-md">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h3 class="text-xl font-bold">Crear <?= $titulo ?></h3>
                <p class="text-sm mt-1" style="color: var(--color-muted);">Completá los datos obligatorios para guardar el registro.</p>
            </div>
            <button type="button" id="cerrar-modal" class="app-modal-close" aria-label="Cerrar">×</button>
        </div>
        <div id="mensaje-form" class="mb-3"></div>
        <form id="form-crear" data-api="<?= base_url($formCrear['api']) ?>">
            <?php foreach ($formCrear['campos'] as $campo): ?>
                <label class="block mb-2 font-semibold"><?= $campo['label'] ?></label>

<?php if (($campo['type'] ?? '') === 'checkboxes'): ?>
    <div class="app-checkbox-group mb-4"
         data-checkbox-group
         data-api="<?= htmlspecialchars(base_url($campo['api']), ENT_QUOTES, 'UTF-8') ?>"
         data-value="<?= htmlspecialchars($campo['value'], ENT_QUOTES, 'UTF-8') ?>"
         data-label="<?= htmlspecialchars($campo['labelField'], ENT_QUOTES, 'UTF-8') ?>"
         data-name="<?= htmlspecialchars($campo['name'], ENT_QUOTES, 'UTF-8') ?>"
         data-required="<?= ($campo['required'] ?? true) !== false ? '1' : '0' ?>">
        <input type="search"
               class="app-input app-checkbox-search w-full"
               data-checkbox-search
               placeholder="<?= htmlspecialchars($campo['searchPlaceholder'] ?? 'Buscar opciones...', ENT_QUOTES, 'UTF-8') ?>"
               autocomplete="off">
        <div class="app-checkbox-toolbar">
            <span data-checkbox-count>0 seleccionados</span>
            <button type="button" class="app-link-button" data-checkbox-select-visible>Seleccionar visibles</button>
            <button type="button" class="app-link-button" data-checkbox-clear>Limpiar</button>
        </div>
        <div class="app-checkbox-options" data-checkbox-options role="group"
             aria-label="<?= htmlspecialchars($campo['label'], ENT_QUOTES, 'UTF-8') ?>">
            <p class="app-checkbox-empty">Cargando opciones...</p>
        </div>
        <?php if (!empty($campo['help'])): ?>
            <small class="app-help"><?= htmlspecialchars($campo['help'], ENT_QUOTES, 'UTF-8') ?></small>
        <?php endif; ?>
    </div>

<?php elseif (($campo['type'] ?? '') === 'select'): ?>

    <?php if (!empty($campo['searchable'])): ?>
        <input
            type="search"
            data-select-search="<?= htmlspecialchars($campo['name'], ENT_QUOTES, 'UTF-8') ?>"
            class="app-input app-select-search w-full"
            placeholder="<?= htmlspecialchars($campo['searchPlaceholder'] ?? 'Buscar opción...', ENT_QUOTES, 'UTF-8') ?>"
            autocomplete="off"
            aria-label="Buscar <?= htmlspecialchars($campo['label'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <?php if (isset($campo['options'])): ?>
        <select name="<?= $campo['name'] ?>" class="app-input w-full mb-4<?= !empty($campo['searchable']) ? ' app-select-source-hidden' : '' ?>"
                <?php if (!empty($campo['searchable'])): ?>data-searchable="1"<?php endif; ?> required>
            <option value="">Seleccione una opción</option>
            <?php foreach ($campo['options'] as $op): ?>
                <option value="<?= $op['value'] ?>"><?= $op['label'] ?></option>
            <?php endforeach; ?>
        </select>
    <?php else: ?>
        <select
            name="<?= $campo['name'] ?>"
            data-api="<?= $campo['api'] ?>"
            data-value="<?= $campo['value'] ?>"
            data-label="<?= $campo['labelField'] ?>"
            <?php if (!empty($campo['labelFields'])): ?>data-label-fields="<?= implode(',', $campo['labelFields']) ?>"<?php endif; ?>
            <?php if (!empty($campo['searchable'])): ?>data-searchable="1"<?php endif; ?>
            class="app-input w-full mb-4<?= !empty($campo['searchable']) ? ' app-select-source-hidden' : '' ?>"
            required>
            <option value="">Cargando...</option>
        </select>
    <?php endif; ?>

<?php else: ?>
    <input 
        type="<?= $campo['type'] ?>"
        name="<?= $campo['name'] ?>"
        class="app-input w-full mb-4"
        <?php if (($campo['required'] ?? true) !== false): ?>required<?php endif; ?>
        <?php if (isset($campo['min'])): ?>min="<?= $campo['min'] ?>"<?php endif; ?>
        <?php if (isset($campo['max'])): ?>max="<?= $campo['max'] ?>"<?php endif; ?>
        <?php if (isset($campo['step'])): ?>step="<?= $campo['step'] ?>"<?php endif; ?>
        <?php if (isset($campo['minlength'])): ?>minlength="<?= $campo['minlength'] ?>"<?php endif; ?>
        <?php if (isset($campo['maxlength'])): ?>maxlength="<?= $campo['maxlength'] ?>"<?php endif; ?>
        <?php if (isset($campo['value'])): ?>value="<?= htmlspecialchars((string) $campo['value'], ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
        <?php if (isset($campo['placeholder'])): ?>placeholder="<?= htmlspecialchars($campo['placeholder'], ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
    >
<?php endif; ?>
            <?php endforeach; ?>

            <div class="app-modal-actions">
    <button type="button" class="btn btn-muted js-cerrar-modal-crear">
        Cerrar
    </button>

    <button type="submit" class="btn btn-success">
        Guardar
    </button>
</div>
        </form>
    </div>
</div>
<?php endif; ?>
<main class="app-main flex-1 p-4 sm:p-6 lg:p-10">
    <div class="app-panel p-4 sm:p-6 w-full">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold"><?= $titulo ?></h2>
                <p class="text-sm" style="color: var(--color-muted);">
                    <?= htmlspecialchars($subtitulo ?? 'Listado general del módulo', ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>

            <div class="app-toolbar-actions flex flex-wrap gap-3">
                <?php if (isset($formCrear)): ?>
                <button id="btn-crear"
                    class="btn btn-success">
                    + Crear
                </button>
                <?php endif; ?>

                <a href="<?= base_url($urlVolver ?? 'mvc/views/dashboard.php') ?>"
                   class="btn btn-primary">
                    Volver
                </a>
            </div>
        </div>

        <div id="filtros-tabla" class="app-filters mb-6"></div>

        <div class="app-table-wrap">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <?php foreach ($columnas as $col): ?>
                            <th class="p-3 text-left"><?= $etiquetasColumnas[$col] ?? $etiquetasColumnasDefault[$col] ?? ucfirst(str_replace('_', ' ', $col)) ?></th>
                        <?php endforeach; ?>

                        <th class="p-3">Acciones</th>
                    </tr>
                </thead>

                <tbody id="tabla-body"></tbody>
            </table>
        </div>

    </div>
    <div id="modal-editar" class="app-modal hidden">
    <div class="app-modal-dialog max-w-md">
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h3 class="text-xl font-bold">Editar registro</h3>
                <p class="text-sm mt-1" style="color: var(--color-muted);">Actualizá los datos necesarios y guardá los cambios.</p>
            </div>
            <button type="button" id="cerrar-modal-editar" class="app-modal-close" aria-label="Cerrar">×</button>
        </div>

        <div id="mensaje-editar" class="mb-3"></div>

        <form id="form-editar">
            <div id="campos-editar"></div>

            <div class="app-modal-actions mt-4">
                <button type="button" id="cancelar-editar" class="btn btn-muted">
                    Cancelar
                </button>

                <button type="submit" class="btn btn-primary">
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
</main>
<script>
    window.BASE_URL = "<?= base_url('') ?>";
    window.API_ACTUALIZAR = "<?= isset($apiActualizar) ? base_url($apiActualizar) : '' ?>";
    window.API_DESACTIVAR = "<?= isset($apiDesactivar) ? base_url($apiDesactivar) : '' ?>";
    window.API_RESTAURAR = "<?= isset($apiRestaurar) ? base_url($apiRestaurar) : '' ?>";
    window.API_ELIMINAR = "<?= isset($apiEliminar) ? base_url($apiEliminar) : '' ?>";
    window.URL_DESACTIVADOS = "<?= isset($urlDesactivados) ? base_url($urlDesactivados) : '' ?>";
    window.ID_CAMPO = "<?= $idCampo ?? '' ?>";
    window.CAMPO_CONTEXTO_CREAR = <?= json_encode($campoContextoCrear ?? '', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.CAMPOS_EDITAR = <?= json_encode($camposEditar ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= base_url('public/js/table-loader.js') ?>?v=<?= urlencode((string) @filemtime(__DIR__ . '/../../../public/js/table-loader.js')) ?>"></script>
<script src="<?= base_url('public/js/crud.js') ?>?v=<?= urlencode((string) @filemtime(__DIR__ . '/../../../public/js/crud.js')) ?>"></script>
<script src="<?= base_url('public/js/obtener_template.js') ?>"></script>

<script>
    cargarTabla(
        "<?= base_url($api) ?>",
        <?= json_encode($columnas) ?>,
        <?= json_encode($filtros ?? []) ?>
    );
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
