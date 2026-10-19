<?php
require_once __DIR__ . '/../../../src/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$rolActual = $_SESSION['rol'] ?? 'Invitado';
$usuarioActual = $_SESSION['usuario'] ?? 'Usuario';
$nombreUsuario = trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''));
if (empty($nombreUsuario)) {
    $nombreUsuario = $usuarioActual;
}

// Configuración de navegación por rol
$navItems = [];

switch ($rolActual) {
    case 'SuperAdmin':
    case 'Administrador':
    case 'Administracion':
        $navItems = [
            ['Dashboard', 'mvc/views/dashboard.php'],
            ['Coordinación', 'mvc/views/coordinacion/index.php'],
            ['Clase en Vivo', 'mvc/views/clases/panel.php'],
            ['Planes Anuales', 'mvc/views/planes_anuales/index.php'],
            ['Profesores', 'mvc/views/profesores/index.php'],
            ['Estudiantes', 'mvc/views/estudiantes/index.php'],
            ['Horarios', 'mvc/views/horarios/index.php'],
            ['Asist. Estudiantes', 'mvc/views/asistencias_estudiantes/index.php'],
            ['Asist. Profesores', 'mvc/views/asistencias_profesores/index.php'],
            ['Administración', 'mvc/views/administracion/index.php'],
        ];
        break;

    case 'Coordinador':
        $navItems = [
            ['Coordinación', 'mvc/views/coordinacion/index.php'],
            ['Clase en Vivo', 'mvc/views/clases/panel.php'],
            ['Planes Anuales', 'mvc/views/planes_anuales/index.php'],
            ['Profesores', 'mvc/views/profesores/index.php'],
            ['Estudiantes', 'mvc/views/estudiantes/index.php'],
            ['Horarios', 'mvc/views/horarios/index.php'],
            ['Asist. Estudiantes', 'mvc/views/asistencias_estudiantes/index.php'],
            ['Asist. Profesores', 'mvc/views/asistencias_profesores/index.php'],
        ];
        break;

    case 'Profesor':
        $navItems = [
            ['Mi Panel', 'mvc/views/profesores/mi_panel.php'],
            ['Clase en Vivo', 'mvc/views/clases/panel.php'],
            ['Mis Planes Anuales', 'mvc/views/planes_anuales/index.php'],
            ['Horarios', 'mvc/views/horarios/index.php'],
            ['Asist. Estudiantes', 'mvc/views/asistencias_estudiantes/index.php'],
        ];
        break;

    case 'Estudiante':
        $navItems = [
            ['Mi Avance Académico', 'mvc/views/estudiantes/mi_panel.php'],
        ];
        break;

    case 'Tutor':
        $navItems = [
            ['Mis Hijos', 'mvc/views/tutores/mi_panel.php'],
        ];
        break;

    default:
        $navItems = [
            ['Inicio', 'mvc/views/dashboard.php'],
        ];
        break;
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
    <div class="flex items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="app-logo text-2xl font-bold tracking-tight">SISCO-EDU</h1>
            <p class="text-xs text-slate-400 font-medium">Gestión Académica & IoT</p>
        </div>
        <button id="app-menu-close" class="app-menu-close md:hidden text-slate-400 hover:text-white" type="button" aria-label="Cerrar menú">×</button>
    </div>

    <!-- Perfil rápido en sidebar -->
    <div class="mb-6 p-3 rounded-lg bg-slate-800/60 border border-slate-700/60">
        <div class="text-sm font-semibold text-slate-200 truncate"><?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="flex items-center gap-1.5 mt-1">
            <span class="inline-block w-2 h-2 rounded-full bg-emerald-400"></span>
            <span class="text-xs font-medium text-emerald-400 uppercase tracking-wider"><?= htmlspecialchars($rolActual, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <nav class="app-sidebar-nav space-y-2">
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
            if ($path === 'mvc/views/coordinacion/index.php') {
                if (strpos($currentPath, '/mvc/views/coordinacion/') !== false) {
                    $isActive = true;
                }
            }
            ?>
            <a href="<?= base_url($path) ?>" class="app-nav-link<?= $isActive ? ' is-active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>

    <a href="<?= base_url('src/api/logout.php') ?>" class="app-logout mt-auto flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-rose-300 hover:text-rose-200 hover:bg-rose-950/40 rounded-lg transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        Cerrar sesión
    </a>
</aside>
