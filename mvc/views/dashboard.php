<?php require_once 'layouts/header.php'; ?>
<?php require_once 'layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../../src/config/app.php'; ?>

<main class="flex-1 p-6 md:p-10">
    <div class="bg-white rounded-2xl shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold">Bienvenido, <?= $_SESSION['usuario']; ?></h2>
        <p class="mt-1" style="color: var(--color-muted);">Rol: <?= $_SESSION['rol']; ?></p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php
        $modulos = [
            ["Profesores", "Gestionar profesores", "profesores/index.php"],
            ["Estudiantes", "Gestionar estudiantes", "estudiantes/index.php"],
            ["Materias", "Gestionar materias", "materias/index.php"],
            ["Horarios", "Gestionar horarios", "horarios/index.php"],
            ["Asignaciones", "Gestionar asignaciones", "asignaciones/index.php"],
            ["Asist. Estudiantes", "Ver asistencias de estudiantes", "asistencias_estudiantes/index.php"],
            ["Asist. Profesores", "Ver asistencias de profesores", "asistencias_profesores/index.php"],
            ["Usuarios", "Control de acceso", "usuarios/index.php"],
            ["Grados", "Gestionar grados y aulas asignadas", "grados/index.php"],
            ["Aulas", "Gestionar aulas", "aulas/index.php"],
        ];

        foreach ($modulos as $m):
        ?>
            <a href="<?= base_url('mvc/views/' . $m[2]) ?>" class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
                <h3 class="text-lg font-bold"><?= $m[0] ?></h3>
                <p class="text-sm mt-2" style="color: var(--color-muted);"><?= $m[1] ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once 'layouts/footer.php'; ?>