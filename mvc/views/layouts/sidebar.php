<?php
require_once __DIR__ . '/../../../src/config/app.php';

$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$navItems = [
    ['Dashboard', 'mvc/views/dashboard.php'],
    ['Profesores', 'mvc/views/profesores/index.php'],
    ['Estudiantes', 'mvc/views/estudiantes/index.php'],
    ['Horarios', 'mvc/views/horarios/index.php'],
    ['Planificaci&oacute;n', 'mvc/views/planificacion/index.php'],
    ['Informes diarios', 'mvc/views/informes/diario.php'],
    ['Asist. Estudiantes', 'mvc/views/asistencias_estudiantes/index.php'],
    ['Asist. Profesores', 'mvc/views/asistencias_profesores/index.php'],
    ['Administraci&oacute;n', 'mvc/views/administracion/index.php'],
];
if (in_array($_SESSION['rol'] ?? '', ['SuperAdmin', 'Administracion'], true)) {
    $navItems[] = ['Config. informes', 'mvc/views/configuracion/informes.php'];
}

$adminPaths = [
    'mvc/views/administracion/',
    'mvc/views/usuarios/',
    'mvc/views/aulas/',
    'mvc/views/grados/',
    'mvc/views/materias/',
    'mvc/views/asignaciones/',
    'mvc/views/configuracion/',
];
?>

<button id="app-menu-toggle" class="app-menu-toggle md:hidden" type="button"
        aria-controls="app-sidebar" aria-expanded="false" aria-label="Abrir menú principal">
    <span></span><span></span><span></span>
</button>
<div id="app-sidebar-overlay" class="app-sidebar-overlay md:hidden" aria-hidden="true"></div>

<aside id="app-sidebar" class="app-sidebar flex w-64 flex-col p-6" aria-label="Navegación principal">
    <div class="flex items-center justify-between gap-3 mb-8">
        <h1 class="app-logo text-2xl font-bold">SISCO-EDU</h1>
        <button id="app-menu-close" class="app-menu-close md:hidden" type="button" aria-label="Cerrar menú">×</button>
    </div>

    <nav class="app-sidebar-nav space-y-3">
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
