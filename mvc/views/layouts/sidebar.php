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
$inicialUsuario = strtoupper(mb_substr($nombreUsuario, 0, 1, 'UTF-8') ?: 'U');

// Mapeo de SVG descriptivos por clave semántica
function obtenerIconoNav(string $clave): string {
    switch ($clave) {
        case 'dashboard':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10-4a1 1 0 011-1h4a1 1 0 011 1v8a1 1 0 01-1 1h-4a1 1 0 01-1-1v-8z" />
            </svg>';

        case 'coordinacion':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>';

        case 'en_vivo':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
            </svg>';

        case 'planes':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>';

        case 'profesores':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5" />
            </svg>';

        case 'estudiantes':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>';

        case 'horarios':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>';

        case 'asist_profesores':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>';

        case 'administracion':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>';

        case 'mi_panel':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>';

        case 'avance':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>';

        case 'hijos':
        case 'tutores':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>';

        default:
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
            </svg>';
    }
}

// Configuración de navegación por rol
$navItems = [];

switch ($rolActual) {
    case 'SuperAdmin':
    case 'Administrador':
    case 'Administracion':
        $navItems = [
            ['Dashboard', 'mvc/views/dashboard.php', 'dashboard'],
            ['Coordinación', 'mvc/views/coordinacion/index.php', 'coordinacion'],
            ['Clase en Vivo', 'mvc/views/clases/panel.php', 'en_vivo'],
            ['Planes Anuales', 'mvc/views/planes_anuales/index.php', 'planes'],
            ['Profesores', 'mvc/views/profesores/index.php', 'profesores'],
            ['Estudiantes', 'mvc/views/estudiantes/index.php', 'estudiantes'],
            ['Tutores y Familias', 'mvc/views/tutores/index.php', 'tutores'],
            ['Horarios', 'mvc/views/horarios/index.php', 'horarios'],
            ['Administración', 'mvc/views/administracion/index.php', 'administracion'],
        ];
        break;

    case 'Coordinador':
        $navItems = [
            ['Coordinación', 'mvc/views/coordinacion/index.php', 'coordinacion'],
            ['Clase en Vivo', 'mvc/views/clases/panel.php', 'en_vivo'],
            ['Planes Anuales', 'mvc/views/planes_anuales/index.php', 'planes'],
            ['Profesores', 'mvc/views/profesores/index.php', 'profesores'],
            ['Estudiantes', 'mvc/views/estudiantes/index.php', 'estudiantes'],
            ['Tutores y Familias', 'mvc/views/tutores/index.php', 'tutores'],
            ['Horarios', 'mvc/views/horarios/index.php', 'horarios'],
        ];
        break;

    case 'Profesor':
        $navItems = [
            ['Mi Panel', 'mvc/views/profesores/mi_panel.php', 'mi_panel'],
            ['Clase en Vivo', 'mvc/views/clases/panel.php', 'en_vivo'],
            ['Mis Planes Anuales', 'mvc/views/planes_anuales/index.php', 'planes'],
            ['Horarios', 'mvc/views/horarios/index.php', 'horarios'],
        ];
        break;

    case 'Estudiante':
        $navItems = [
            ['Mi Avance Académico', 'mvc/views/estudiantes/mi_panel.php', 'avance'],
        ];
        break;

    case 'Tutor':
        $navItems = [
            ['Mis Hijos', 'mvc/views/tutores/mi_panel.php', 'hijos'],
        ];
        break;

    default:
        $navItems = [
            ['Inicio', 'mvc/views/dashboard.php', 'dashboard'],
        ];
        break;
}

$adminPaths = [
    'mvc/views/administracion/',
    'mvc/views/usuarios/',
    'mvc/views/tutores/',
    'mvc/views/turnos/',
    'mvc/views/niveles/',
    'mvc/views/bachilleratos/',
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

<aside id="app-sidebar" class="app-sidebar flex w-64 flex-col p-4 md:p-5" aria-label="Navegación principal">
    <!-- Encabezado del Sidebar (Logo + Botones de Control) -->
    <div class="sidebar-header flex items-center justify-between gap-2 mb-5">
        <!-- Logo / Marca Expandida -->
        <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="sidebar-brand-text flex-1 min-w-0 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-cyan-500 p-0.5 shadow-md shadow-indigo-500/25 shrink-0 flex items-center justify-center">
                <img src="<?= base_url('public/img/logo-badge.svg') ?>" alt="SISCO-EDU Logo" class="w-full h-full rounded-[10px] object-cover">
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="app-logo text-lg font-black tracking-tight leading-none text-white truncate">SISCO-EDU</h1>
                <p class="text-[11px] text-slate-400 font-medium truncate mt-1">Gestión Académica & IoT</p>
            </div>
        </a>

        <!-- Logo Compacto (Visible solo al colapsar, ocupando el mismo espacio que los iconos del menú) -->
        <a href="<?= base_url('mvc/views/dashboard.php') ?>"
           id="sidebar-brand-mini"
           class="sidebar-brand-mini hidden group relative items-center justify-center w-full rounded-xl transition-all"
           title="SISCO-EDU">
            <span class="sidebar-logo-badge w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-cyan-500 p-0.5 flex items-center justify-center shadow-md shadow-blue-500/25">
                <img src="<?= base_url('public/img/logo-badge.svg') ?>" alt="SISCO-EDU" class="w-full h-full rounded-[9px] object-cover">
            </span>
        </a>

        <div class="sidebar-header-actions flex items-center gap-1">
            <!-- Botón Colapsar / Expandir (Solo Desktop) -->
            <button id="app-sidebar-collapse-btn" type="button"
                    class="sidebar-collapse-btn hidden md:flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/80 transition-colors group relative"
                    title="Colapsar o expandir menú" aria-label="Colapsar o expandir menú lateral">
                <svg class="sidebar-chevron w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>

            <!-- Botón Cerrar (Solo Móvil) -->
            <button id="app-menu-close" class="app-menu-close md:hidden text-slate-400 hover:text-white" type="button" aria-label="Cerrar menú">×</button>
        </div>
    </div>

    <!-- Perfil del usuario (Versión Expandida y Mini) -->
    <div class="sidebar-profile-box mb-5">
        <!-- Versión Expandida -->
        <div class="sidebar-profile-full p-3 rounded-xl bg-slate-800/70 border border-slate-700/60 shadow-xs">
            <div class="text-xs font-semibold text-slate-200 truncate" title="<?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="flex items-center gap-1.5 mt-1">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider"><?= htmlspecialchars($rolActual, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>

        <!-- Versión Mini (Avatar de Inicial) -->
        <div class="sidebar-profile-mini hidden items-center justify-center p-1" title="<?= htmlspecialchars($nombreUsuario . ' (' . $rolActual . ')', ENT_QUOTES, 'UTF-8') ?>">
            <div class="relative w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-200 font-bold text-xs shadow-xs">
                <?= htmlspecialchars($inicialUsuario, ENT_QUOTES, 'UTF-8') ?>
                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-500 border-2 border-slate-900"></span>
            </div>
        </div>
    </div>

    <!-- Navegación Principal -->
    <nav class="app-sidebar-nav flex-1 space-y-1.5">
        <?php foreach ($navItems as $item): ?>
            <?php
            $label = $item[0];
            $path = $item[1];
            $iconoClave = $item[2] ?? 'default';
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
            <a href="<?= base_url($path) ?>"
               class="app-nav-link group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-150 <?= $isActive ? 'is-active' : '' ?>"
               title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
                <?= obtenerIconoNav($iconoClave) ?>
                <span class="app-nav-text flex-1 truncate"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Alternar Tema, Selector de Escala Visual y Botón de Cerrar Sesión -->
    <div class="sidebar-footer pt-3 mt-auto border-t border-slate-800/80 space-y-2">
        <button type="button" data-action="toggle-theme"
           class="app-theme-btn w-full group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors cursor-pointer"
           title="Alternar Modo Oscuro / Claro">
            <svg class="theme-icon-sun w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg class="theme-icon-moon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            <span class="app-nav-text flex-1 truncate text-left">Modo Claro / Oscuro</span>
        </button>

        <!-- Selector Triple de Tamaño de Elementos / Tipografía (11pt | 12pt | 13pt) -->
        <div class="sidebar-font-scale-container px-2 py-2 rounded-xl bg-slate-900/70 border border-slate-800" role="group" aria-label="Selector de escala de texto">
            <div class="flex items-center justify-between px-1.5 pb-1.5 app-nav-text">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h6" />
                    </svg>
                    <span>Tamaño de vista</span>
                </span>
                <span id="label-current-font-size" class="text-xs font-black text-blue-400">12pt</span>
            </div>

            <!-- Segmented Control Triple para Modo Expandido y Móvil -->
            <div class="sidebar-font-scale-control flex items-center p-1 rounded-lg bg-slate-950/80 border border-slate-800 gap-1">
                <button type="button"
                        data-font-size="11pt"
                        class="btn-font-scale flex-1 py-1.5 px-2 text-xs font-bold rounded-md transition-all text-slate-400 hover:text-white hover:bg-slate-800/70 text-center cursor-pointer"
                        title="Tamaño 11pt - Vista Compacta con mayor información en pantalla"
                        aria-label="Tamaño compacto 11 puntos">
                    11pt
                </button>
                <button type="button"
                        data-font-size="12pt"
                        class="btn-font-scale flex-1 py-1.5 px-2 text-xs font-bold rounded-md transition-all text-slate-400 hover:text-white hover:bg-slate-800/70 text-center cursor-pointer"
                        title="Tamaño 12pt - Vista Estándar Equilibrada"
                        aria-label="Tamaño estándar 12 puntos">
                    12pt
                </button>
                <button type="button"
                        data-font-size="13pt"
                        class="btn-font-scale flex-1 py-1.5 px-2 text-xs font-bold rounded-md transition-all text-slate-400 hover:text-white hover:bg-slate-800/70 text-center cursor-pointer"
                        title="Tamaño 13pt - Vista Grande y Clara para lectura descansada"
                        aria-label="Tamaño grande 13 puntos">
                    13pt
                </button>
            </div>

            <!-- Botón Compacto para Sidebar Colapsado (Desktop Mini) -->
            <button type="button"
                    id="btn-font-scale-cycle"
                    class="sidebar-font-scale-mini hidden w-full py-2 px-1 text-center font-extrabold text-xs text-blue-400 hover:text-white hover:bg-slate-800/80 rounded-lg transition-colors cursor-pointer"
                    title="Alternar tamaño de vista (11pt / 12pt / 13pt)"
                    aria-label="Alternar escala de vista">
                <span class="block text-[10px] text-slate-400 font-semibold leading-none">Aa</span>
                <span id="mini-font-size-badge" class="block text-xs font-black mt-0.5 leading-none">12</span>
            </button>
        </div>

        <a href="<?= base_url('src/api/logout.php') ?>"
           class="app-logout group relative flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-rose-400 hover:text-rose-200 hover:bg-rose-950/30 transition-colors"
           title="Cerrar sesión">
            <svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span class="app-nav-text flex-1 truncate">Cerrar sesión</span>
        </a>
    </div>
</aside>
