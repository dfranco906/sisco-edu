<?php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>
<?php if (isset($formCrear)): ?>
<div id="modal-crear" class="app-modal hidden">
    <div class="app-modal-dialog max-w-md">
        <h3 class="text-xl font-bold mb-4">Crear <?= $titulo ?></h3>
            <div id="mensaje-form" class="mb-3"></div>
        <form id="form-crear" data-api="<?= base_url($formCrear['api']) ?>">
            <?php foreach ($formCrear['campos'] as $campo): ?>
                <label class="block mb-2 font-semibold"><?= $campo['label'] ?></label>

<?php if (($campo['type'] ?? '') === 'select'): ?>

    <?php if (isset($campo['options'])): ?>
        <select name="<?= $campo['name'] ?>" class="border rounded-xl px-4 py-2 w-full mb-4" required>
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
            class="border rounded-xl px-4 py-2 w-full mb-4"
            required>
            <option value="">Cargando...</option>
        </select>
    <?php endif; ?>

<?php else: ?>
    <input 
        type="<?= $campo['type'] ?>"
        name="<?= $campo['name'] ?>"
        class="border rounded-xl px-4 py-2 w-full mb-4"
        required
    >
<?php endif; ?>
            <?php endforeach; ?>

            <div class="app-modal-actions">
    <button type="button" id="cerrar-modal" class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200">
        Cerrar
    </button>

    <button type="submit" class="px-4 py-2 rounded-xl bg-green-600 text-white hover:bg-green-700">
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
                    Listado general del módulo
                </p>
            </div>

            <div class="app-toolbar-actions flex flex-wrap gap-3">
                <?php if (isset($formCrear)): ?>
                <button id="btn-crear"
                    class="px-5 py-3 rounded-xl text-white font-semibold bg-green-600 hover:bg-green-700">
                    + Crear
                </button>
                <?php endif; ?>

                <a href="<?= base_url($urlVolver ?? 'mvc/views/dashboard.php') ?>"
                   class="px-5 py-3 rounded-xl text-white font-semibold"
                   style="background: var(--color-primary);">
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
                            <th class="p-3 text-left"><?= $col ?></th>
                        <?php endforeach; ?>

                        <th class="p-3 text-left">acciones</th>
                    </tr>
                </thead>

                <tbody id="tabla-body"></tbody>
            </table>
        </div>

    </div>
    <div id="modal-editar" class="app-modal hidden">
    <div class="app-modal-dialog max-w-md">
        <h3 class="text-xl font-bold mb-4">Editar registro</h3>

        <div id="mensaje-editar" class="mb-3"></div>

        <form id="form-editar">
            <div id="campos-editar"></div>

            <div class="app-modal-actions mt-4">
                <button type="button" id="cancelar-editar" class="px-4 py-2 rounded-xl bg-gray-100">
                    Cancelar
                </button>

                <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white">
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
    window.CAMPOS_EDITAR = <?= json_encode($camposEditar ?? []) ?>;
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
