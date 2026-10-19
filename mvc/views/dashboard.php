<?php
require_once __DIR__ . '/../../src/middleware/auth.php';
verificarRol(['SuperAdmin', 'Administrador', 'Administracion']);

require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';
require_once __DIR__ . '/../../src/config/app.php';
?>

<main class="flex-1 p-6 md:p-10">
    <div class="app-page-header p-6 mb-8">
        <h2 class="text-2xl font-bold">Bienvenido, <?= htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <p class="mt-1 text-slate-300">Rol: <span class="font-semibold text-emerald-400"><?= htmlspecialchars($_SESSION['rol'], ENT_QUOTES, 'UTF-8'); ?></span></p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php
        $modulos = [
            ["🏫 Coordinación Académica", "Supervisión métrica de asistencia, aulas, materias, estudiantes y tutores", "coordinacion/index.php", true],
            ["🔴 Clase en Vivo", "Monitor biométrico de aula en tiempo real y lección del día", "clases/panel.php", true],
            ["📖 Planes Anuales", "Estructura pedagógica MEC y programación de clases", "planes_anuales/index.php"],
            ["Profesores", "Gestionar profesores", "profesores/index.php"],
            ["Estudiantes", "Gestionar estudiantes", "estudiantes/index.php"],
            ["Horarios", "Gestionar horarios", "horarios/index.php"],
            ["Asist. Estudiantes", "Ver asistencias de estudiantes", "asistencias_estudiantes/index.php"],
            ["Asist. Profesores", "Ver asistencias de profesores", "asistencias_profesores/index.php"],
            ["Administración", "Gestionar usuarios, aulas, grados, materias y asignaciones.", "administracion/index.php", true],
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

<?php require_once 'layouts/footer.php'; ?>
