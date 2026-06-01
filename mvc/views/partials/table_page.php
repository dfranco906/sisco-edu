<?php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>
<?php if (isset($formCrear)): ?>
<div id="modal-crear" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Crear <?= $titulo ?></h3>

        <form id="form-crear" data-api="<?= base_url($formCrear['api']) ?>">
            <?php foreach ($formCrear['campos'] as $campo): ?>
                <label class="block mb-2 font-semibold"><?= $campo['label'] ?></label>
                <input 
                    type="<?= $campo['type'] ?>"
                    name="<?= $campo['name'] ?>"
                    class="border rounded-xl px-4 py-2 w-full mb-4"
                    required
                >
            <?php endforeach; ?>

            <div class="flex justify-end gap-3">
                <button type="button" id="cerrar-modal" class="px-4 py-2 rounded-xl bg-gray-100">
                    Cancelar
                </button>
                <button class="px-4 py-2 rounded-xl bg-green-600 text-white">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<main class="flex-1 p-6 md:p-10">
    <div class="bg-white rounded-2xl shadow p-6 w-full">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold"><?= $titulo ?></h2>
                <p class="text-sm" style="color: var(--color-muted);">
                    Listado general del módulo
                </p>
            </div>

            <div class="flex gap-3">
                <button id="btn-crear"
                    class="px-5 py-3 rounded-xl text-white font-semibold bg-green-600 hover:bg-green-700">
                    + Crear
                </button>

                <a href="<?= base_url('mvc/views/dashboard.php') ?>"
                   class="px-5 py-3 rounded-xl text-white font-semibold"
                   style="background: var(--color-primary);">
                    Volver
                </a>
            </div>
        </div>

        <div id="filtros-tabla" class="flex flex-col md:flex-row gap-3 mb-6"></div>

        <div class="overflow-x-auto">
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
</main>

<script src="<?= base_url('public/js/table-loader.js') ?>"></script>
<script src="<?= base_url('public/js/crud.js') ?>"></script>
<script src="<?= base_url('public/js/huella.js') ?>"></script>
<script>
    cargarTabla(
        "<?= base_url($api) ?>",
        <?= json_encode($columnas) ?>,
        <?= json_encode($filtros ?? []) ?>
    );
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>