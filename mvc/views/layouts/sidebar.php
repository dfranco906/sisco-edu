<?php
require_once __DIR__ . '/../../../src/config/app.php';

$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$navItems = [
    ['Dashboard', 'mvc/views/dashboard.php'],
    ['Profesores', 'mvc/views/profesores/index.php'],
    ['Estudiantes', 'mvc/views/estudiantes/index.php'],
    ['Horarios', 'mvc/views/horarios/index.php'],
    ['Asist. Estudiantes', 'mvc/views/asistencias_estudiantes/index.php'],
    ['Asist. Profesores', 'mvc/views/asistencias_profesores/index.php'],
    ['Administraci&oacute;n', 'mvc/views/administracion/index.php'],
];

$adminPaths = [
    'mvc/views/administracion/',
    'mvc/views/usuarios/',
    'mvc/views/aulas/',
    'mvc/views/grados/',
    'mvc/views/materias/',
    'mvc/views/asignaciones/',
];
?>

<aside class="app-sidebar hidden md:flex w-64 flex-col p-6">
    <h1 class="app-logo text-2xl font-bold mb-8">
        SISCO-EDU
    </h1>

    <nav class="space-y-3">
        <?php foreach ($navItems as $item): ?>
            <?php
            $label = $item[0];
            $path = $item[1];
            $isActive = strpos($currentPath, '/' . $path) !== false;

            if ($path === 'mvc/views/administracion/index.php') {
                foreach ($adminPaths as $adminPath) {
                    if (strpos($currentPath, '/' . $adminPath) !== false) {
                        $isActive = true;
                        break;
                    }
                }
            }
            ?>
            <a href="<?= base_url($path) ?>" class="app-nav-link<?= $isActive ? ' is-active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>

    <a href="<?= base_url('src/api/logout.php') ?>" class="app-logout mt-auto">
        Cerrar sesi&oacute;n
    </a>
</aside>
