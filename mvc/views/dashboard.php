<?php require_once 'layouts/header.php'; ?>
<?php require_once 'layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../../src/config/app.php'; ?>

<main class="flex-1 p-6 md:p-10">
    <div class="app-page-header p-6 mb-8">
        <h2 class="text-2xl font-bold">Bienvenido, <?= $_SESSION['usuario']; ?></h2>
        <p class="mt-1">Rol: <?= $_SESSION['rol']; ?></p>
    </div>

    <section class="mb-8" aria-labelledby="dashboard-resumen-titulo">
        <div class="flex items-end justify-between gap-4 mb-4">
            <div>
                <h3 id="dashboard-resumen-titulo" class="text-xl font-bold">Resumen del sistema</h3>
                <p class="text-sm mt-1" style="color: var(--color-muted);">Datos activos y marcas recibidas hoy.</p>
            </div>
        </div>
        <p id="dashboard-metricas-estado" class="text-sm mb-4">Cargando métricas...</p>
        <div id="dashboard-metricas" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4"></div>
    </section>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php
        $modulos = [
            ["Profesores", "Gestionar profesores", "profesores/index.php"],
            ["Estudiantes", "Gestionar estudiantes", "estudiantes/index.php"],
            ["Horarios", "Gestionar horarios", "horarios/index.php"],
            ["Planificaci&oacute;n pedag&oacute;gica", "Crear y publicar planes anuales", "planificacion/index.php"],
            ["Informes diarios", "Contenido, asistencia y registro anecd&oacute;tico", "informes/diario.php"],
            ["Asist. Estudiantes", "Ver asistencias de estudiantes", "asistencias_estudiantes/index.php"],
            ["Asist. Profesores", "Ver asistencias de profesores", "asistencias_profesores/index.php"],
            ["Administraci&oacute;n", "Gestionar usuarios, aulas, grados, materias y asignaciones.", "administracion/index.php", true],
        ];

        foreach ($modulos as $m):
        ?>
            <a href="<?= base_url('mvc/views/' . $m[2]) ?>" class="app-card <?= !empty($m[3]) ? 'app-card-accent' : '' ?> p-6">
                <h3 class="text-lg font-bold"><?= $m[0] ?></h3>
                <p class="text-sm mt-2"><?= $m[1] ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</main>

<script>
window.DASHBOARD_CONFIG = <?= json_encode([
    'apiResumen' => base_url('src/api/Dashboard/resumen.php')
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= base_url('public/js/dashboard.js') ?>"></script>

<?php require_once 'layouts/footer.php'; ?>
