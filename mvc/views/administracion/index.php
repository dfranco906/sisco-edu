<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../../../src/config/app.php'; ?>

<main class="flex-1 p-6 md:p-10">
    <div class="app-page-header p-6 mb-8">
        <h2 class="text-2xl font-bold">Administraci&oacute;n</h2>
        <p class="mt-1">Gestion&aacute; los datos base del sistema.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-5">
        <?php
        $modulos = [
            ["Usuarios", "Control de acceso y roles del sistema.", "usuarios/index.php"],
            ["Aulas", "Gesti&oacute;n de aulas y c&oacute;digos de sala.", "aulas/index.php"],
            ["Grados", "Gesti&oacute;n de grados y aula asignada.", "grados/index.php"],
            ["Materias", "Gesti&oacute;n de materias acad&eacute;micas.", "materias/index.php"],
            ["Asignaciones", "Relacionar profesores, materias, grados y horarios.", "asignaciones/index.php"],
            ["Nodos ESP32", "Configurar aula, node ID y dirección LoRa.", "nodos/index.php"],
            ["Configuraci&oacute;n de informes", "Membrete institucional para impresi&oacute;n.", "configuracion/informes.php"],
        ];

        foreach ($modulos as $m):
        ?>
            <a href="<?= base_url('mvc/views/' . $m[2]) ?>" class="app-card p-6">
                <h3 class="text-lg font-bold"><?= $m[0] ?></h3>
                <p class="text-sm mt-2"><?= $m[1] ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
