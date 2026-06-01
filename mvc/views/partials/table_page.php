<?php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="flex-1 p-6 md:p-10">
    <div class="bg-white rounded-2xl shadow p-6 w-full">

        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold"><?= $titulo ?></h2>
                <p class="text-sm" style="color: var(--color-muted);">
                    Listado general del módulo
                </p>
            </div>

            <a href="<?= base_url('mvc/views/dashboard.php') ?>"
               class="px-5 py-3 rounded-xl text-white font-semibold"
               style="background: var(--color-primary);">
                Volver
            </a>
        </div>

        <?php if ($titulo === "Horarios por Grado"): ?>
            <div class="mb-6">
                <h3 class="font-semibold mb-3">Seleccionar grado</h3>
                <div id="filtro-grado-botones" class="flex flex-wrap gap-3">
                    <button class="px-4 py-2 rounded-xl bg-blue-600 text-white">
                        Cargando...
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <?php foreach ($columnas as $col): ?>
                            <th class="p-3 text-left"><?= $col ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody id="tabla-body"></tbody>
            </table>
        </div>

    </div>
</main>

<script src="<?= base_url('public/js/table-loader.js') ?>"></script>
<script>
    cargarTabla("<?= base_url($api) ?>", <?= json_encode($columnas) ?>);
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>