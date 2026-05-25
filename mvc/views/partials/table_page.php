<?php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="flex-1 p-6 md:p-10">
    <div class="bg-white rounded-2xl shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold"><?= $titulo ?></h2>
                <p class="text-sm" style="color: var(--color-muted);">
                    Listado general del módulo
                </p>
            </div>

            <a href="<?= base_url('mvc/views/dashboard.php') ?>" 
               class="px-4 py-2 rounded-xl text-white"
               style="background: var(--color-primary);">
                Volver
            </a>
        </div>

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
    cargarTabla(
        "<?= base_url($api) ?>",
        <?= json_encode($columnas) ?>
    );
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>