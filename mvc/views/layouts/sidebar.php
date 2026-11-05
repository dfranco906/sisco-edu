<?php
require_once __DIR__ . '/../../../src/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$rolActual = $_SESSION['rol'] ?? 'Invitado';
$rolNormalized = strtolower(trim($rolActual));
$usuarioActual = $_SESSION['usuario'] ?? 'Usuario';
$nombreUsuario = trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellido'] ?? ''));
if (empty($nombreUsuario)) {
    $nombreUsuario = $usuarioActual;
}
$inicialUsuario = strtoupper(mb_substr($nombreUsuario, 0, 1, 'UTF-8') ?: 'U');

// Mapeo exhaustivo de SVG semanticos de alta calidad (sin emojis)
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

        case 'profesores':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5" />
            </svg>';

        case 'estudiantes':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>';

        case 'tutores':
        case 'hijos':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>';

        case 'horarios':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>';

        case 'planes':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>';

        case 'evaluacion':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>';

        case 'administracion':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>';

        // Submodulos de Administracion
        case 'resumen_admin':
            return '<svg class="w-4 h-4 flex-shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>';

        case 'grados':
            return '<svg class="w-4 h-4 flex-shrink-0 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>';

        case 'materias':
            return '<svg class="w-4 h-4 flex-shrink-0 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>';

        case 'asignaciones':
            return '<svg class="w-4 h-4 flex-shrink-0 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>';

        case 'aulas':
            return '<svg class="w-4 h-4 flex-shrink-0 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>';

        case 'turnos':
            return '<svg class="w-4 h-4 flex-shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>';

        case 'niveles':
            return '<svg class="w-4 h-4 flex-shrink-0 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
            </svg>';

        case 'bachilleratos':
            return '<svg class="w-4 h-4 flex-shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
            </svg>';

        case 'usuarios':
            return '<svg class="w-4 h-4 flex-shrink-0 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>';

        case 'sync':
            return '<svg class="w-4 h-4 flex-shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
            </svg>';

        case 'promociones':
            return '<svg class="w-4 h-4 flex-shrink-0 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>';

        case 'config_red':
            return '<svg class="w-4 h-4 flex-shrink-0 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
            </svg>';

        case 'mi_panel':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>';

        case 'sesion_clase':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>';

        case 'avance':
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>';

        default:
            return '<svg class="app-nav-icon w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
            </svg>';
    }
}

// Subitems estructurados de Administracion
$subitemsAdmin = [
    [
        'label' => 'Panel General',
        'path' => 'mvc/views/administracion/index.php',
        'icon' => 'resumen_admin',
        'exact' => true,
    ],
    [
        'label' => 'Grados y Cursos',
        'path' => 'mvc/views/grados/index.php',
        'icon' => 'grados',
        'match' => 'mvc/views/grados/',
    ],
    [
        'label' => 'Materias y Malla',
        'path' => 'mvc/views/materias/index.php',
        'icon' => 'materias',
        'match' => 'mvc/views/materias/',
    ],
    [
        'label' => 'Asignaciones Docentes',
        'path' => 'mvc/views/asignaciones/index.php',
        'icon' => 'asignaciones',
        'match' => 'mvc/views/asignaciones/',
    ],
    [
        'label' => 'Aulas y Espacios',
        'path' => 'mvc/views/aulas/index.php',
        'icon' => 'aulas',
        'match' => 'mvc/views/aulas/',
    ],
    [
        'label' => 'Turnos Escolares',
        'path' => 'mvc/views/turnos/index.php',
        'icon' => 'turnos',
        'match' => 'mvc/views/turnos/',
    ],
    [
        'label' => 'Ciclos y Niveles',
        'path' => 'mvc/views/niveles/index.php',
        'icon' => 'niveles',
        'match' => 'mvc/views/niveles/',
    ],
    [
        'label' => 'Bachilleratos',
        'path' => 'mvc/views/bachilleratos/index.php',
        'icon' => 'bachilleratos',
        'match' => 'mvc/views/bachilleratos/',
    ],
    [
        'label' => 'Usuarios y Roles',
        'path' => 'mvc/views/usuarios/index.php',
        'icon' => 'usuarios',
        'match' => 'mvc/views/usuarios/',
    ],
    [
        'label' => 'Sincronizacion LoRa',
        'path' => 'mvc/views/administracion/sync_biometrica.php',
        'icon' => 'sync',
        'match' => 'mvc/views/administracion/sync_biometrica.php',
    ],
    [
        'label' => 'Promocion de Cursos',
        'path' => 'mvc/views/administracion/promociones.php',
        'icon' => 'promociones',
        'match' => 'mvc/views/administracion/promociones.php',
    ],
];

if ($rolNormalized === 'superadmin') {
    $subitemsAdmin[] = [
        'label' => 'Configuracion IoT & Red',
        'path' => 'mvc/views/configuracion/index.php',
        'icon' => 'config_red',
        'match' => 'mvc/views/configuracion/',
    ];
}

// Secciones categorizadas segun el rol del usuario (Mental Model Hierarchy)
$navSections = [];

if (in_array($rolNormalized, ['superadmin', 'administrador', 'administracion'], true)) {
    $navSections = [
        [
            'titulo' => 'Principal',
            'items' => [
                ['label' => 'Dashboard', 'path' => 'mvc/views/dashboard.php', 'icon' => 'dashboard', 'exact' => true],
                ['label' => 'Coordinacion', 'path' => 'mvc/views/coordinacion/index.php', 'icon' => 'coordinacion', 'match' => 'mvc/views/coordinacion/'],
                ['label' => 'Clase en Vivo', 'path' => 'mvc/views/clases/panel.php', 'icon' => 'en_vivo', 'badge' => ['text' => 'En Vivo', 'live' => true]],
            ],
        ],
        [
            'titulo' => 'Comunidad Educativa',
            'items' => [
                ['label' => 'Profesores', 'path' => 'mvc/views/profesores/index.php', 'icon' => 'profesores', 'exact' => true],
                ['label' => 'Estudiantes', 'path' => 'mvc/views/estudiantes/index.php', 'icon' => 'estudiantes', 'exact' => true],
                ['label' => 'Tutores y Familias', 'path' => 'mvc/views/tutores/index.php', 'icon' => 'tutores', 'exact' => true],
            ],
        ],
        [
            'titulo' => 'Planificacion Academica',
            'items' => [
                ['label' => 'Horarios', 'path' => 'mvc/views/horarios/index.php', 'icon' => 'horarios', 'match' => 'mvc/views/horarios/'],
                ['label' => 'Planes Anuales', 'path' => 'mvc/views/planes_anuales/index.php', 'icon' => 'planes', 'match' => 'mvc/views/planes_anuales/'],
                ['label' => 'Instrumentos de Evaluacion', 'path' => 'mvc/views/evaluaciones/instrumentos.php', 'icon' => 'evaluacion', 'match' => 'mvc/views/evaluaciones/'],
            ],
        ],
        [
            'titulo' => 'Gestion Institucional',
            'items' => [
                [
                    'label' => 'Administracion',
                    'path' => 'mvc/views/administracion/index.php',
                    'icon' => 'administracion',
                    'hasSubmenu' => true,
                    'subitems' => $subitemsAdmin,
                ],
            ],
        ],
    ];
} elseif ($rolNormalized === 'coordinador') {
    $navSections = [
        [
            'titulo' => 'Principal',
            'items' => [
                ['label' => 'Coordinacion', 'path' => 'mvc/views/coordinacion/index.php', 'icon' => 'coordinacion', 'match' => 'mvc/views/coordinacion/'],
                ['label' => 'Clase en Vivo', 'path' => 'mvc/views/clases/panel.php', 'icon' => 'en_vivo', 'badge' => ['text' => 'En Vivo', 'live' => true]],
            ],
        ],
        [
            'titulo' => 'Comunidad Educativa',
            'items' => [
                ['label' => 'Profesores', 'path' => 'mvc/views/profesores/index.php', 'icon' => 'profesores', 'exact' => true],
                ['label' => 'Estudiantes', 'path' => 'mvc/views/estudiantes/index.php', 'icon' => 'estudiantes', 'exact' => true],
                ['label' => 'Tutores y Familias', 'path' => 'mvc/views/tutores/index.php', 'icon' => 'tutores', 'exact' => true],
            ],
        ],
        [
            'titulo' => 'Planificacion Academica',
            'items' => [
                ['label' => 'Horarios', 'path' => 'mvc/views/horarios/index.php', 'icon' => 'horarios', 'match' => 'mvc/views/horarios/'],
                ['label' => 'Planes Anuales', 'path' => 'mvc/views/planes_anuales/index.php', 'icon' => 'planes', 'match' => 'mvc/views/planes_anuales/'],
                ['label' => 'Instrumentos de Evaluacion', 'path' => 'mvc/views/evaluaciones/instrumentos.php', 'icon' => 'evaluacion', 'match' => 'mvc/views/evaluaciones/'],
            ],
        ],
    ];
} elseif ($rolNormalized === 'profesor') {
    $navSections = [
        [
            'titulo' => 'Mi Espacio Docente',
            'items' => [
                ['label' => 'Mi Panel', 'path' => 'mvc/views/profesores/mi_panel.php', 'icon' => 'mi_panel'],
                ['label' => 'Mi Sesion de Clase', 'path' => 'mvc/views/profesores/sesion_clase.php', 'icon' => 'sesion_clase', 'badge' => ['text' => 'En Aula', 'live' => true]],
            ],
        ],
        [
            'titulo' => 'Planificacion & Aula',
            'items' => [
                ['label' => 'Horarios de Clases', 'path' => 'mvc/views/horarios/index.php', 'icon' => 'horarios', 'match' => 'mvc/views/horarios/'],
                ['label' => 'Mis Planes Anuales', 'path' => 'mvc/views/planes_anuales/index.php', 'icon' => 'planes', 'match' => 'mvc/views/planes_anuales/'],
                ['label' => 'Instrumentos Evaluativos (MEC)', 'path' => 'mvc/views/evaluaciones/instrumentos.php', 'icon' => 'evaluacion', 'match' => 'mvc/views/evaluaciones/'],
            ],
        ],
    ];
} elseif ($rolNormalized === 'estudiante') {
    $navSections = [
        [
            'titulo' => 'Mi Portal',
            'items' => [
                ['label' => 'Mi Avance Academico', 'path' => 'mvc/views/estudiantes/mi_panel.php', 'icon' => 'avance'],
            ],
        ],
    ];
} elseif ($rolNormalized === 'tutor') {
    $navSections = [
        [
            'titulo' => 'Portal Familiar',
            'items' => [
                ['label' => 'Mis Hijos a Cargo', 'path' => 'mvc/views/tutores/mi_panel.php', 'icon' => 'hijos'],
            ],
        ],
    ];
} else {
    $navSections = [
        [
            'titulo' => 'General',
            'items' => [
                ['label' => 'Inicio', 'path' => 'mvc/views/dashboard.php', 'icon' => 'dashboard'],
            ],
        ],
    ];
}
?>

<button id="app-menu-toggle" class="app-menu-toggle md:hidden" type="button"
        aria-controls="app-sidebar" aria-expanded="false" aria-label="Abrir menu principal">
    <span></span><span></span><span></span>
</button>
<div id="app-sidebar-overlay" class="app-sidebar-overlay md:hidden" aria-hidden="true"></div>

<aside id="app-sidebar" class="app-sidebar flex w-64 flex-col p-3 md:p-3.5" aria-label="Navegacion principal">
    <!-- Encabezado del Sidebar (Logo + IoT Micro-strip + Colapso) -->
    <div class="sidebar-header-wrapper mb-2 shrink-0">
        <div class="sidebar-header flex items-center justify-between gap-2">
            <!-- Logo / Marca Expandida -->
            <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="sidebar-brand-text flex-1 min-w-0 flex items-center gap-2.5 group">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-cyan-500 p-0.5 shadow-md shadow-indigo-500/20 shrink-0 flex items-center justify-center transition-transform group-hover:scale-105">
                    <img src="<?= base_url('public/img/logo-badge.svg') ?>" alt="SISCO-EDU Logo" class="w-full h-full rounded-[9px] object-cover">
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="app-logo text-base font-black tracking-tight leading-none text-white truncate">SISCO-EDU</h1>
                    <p class="text-[10px] text-slate-400 font-medium truncate mt-0.5">Gestion Academica &amp; IoT</p>
                </div>
            </a>

            <!-- Logo Compacto (Visible solo al colapsar) -->
            <a href="<?= base_url('mvc/views/dashboard.php') ?>"
               id="sidebar-brand-mini"
               class="sidebar-brand-mini hidden group relative items-center justify-center w-full rounded-xl transition-all"
               title="SISCO-EDU - Click para expandir menu">
                <span class="sidebar-logo-badge w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-cyan-500 p-0.5 flex items-center justify-center shadow-md shadow-blue-500/25 group-hover:scale-105 transition-transform">
                    <img src="<?= base_url('public/img/logo-badge.svg') ?>" alt="SISCO-EDU" class="w-full h-full rounded-[9px] object-cover">
                </span>
            </a>

            <div class="sidebar-header-actions flex items-center gap-1">
                <!-- Boton Colapsar / Expandir (Desktop) -->
                <button id="app-sidebar-collapse-btn" type="button"
                        class="sidebar-collapse-btn hidden md:flex items-center justify-center w-7 h-7 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800/80 transition-colors group relative cursor-pointer"
                        title="Colapsar o expandir menu lateral" aria-label="Colapsar o expandir menu lateral">
                    <svg class="sidebar-chevron w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Boton Cerrar (Movil) -->
                <button id="app-menu-close" class="app-menu-close md:hidden text-slate-400 hover:text-white cursor-pointer" type="button" aria-label="Cerrar menu">&times;</button>
            </div>
        </div>

        <?php if (in_array($rolNormalized, ['superadmin', 'administrador', 'administracion', 'coordinador'], true)): ?>
        <!-- Micro-Strip Monitoreo Hardware IoT (USB DY50 y LoRa Hub) -->
        <div class="sidebar-iot-strip mt-2 px-2 py-1 rounded-lg bg-slate-900/80 border border-slate-800/80 text-[10px] flex items-center justify-between" title="Estado de Hardware IoT">
            <div class="flex items-center gap-2">
                <span id="chip-iot-registrador" class="flex items-center gap-1 font-semibold text-slate-400 hover:text-slate-200 cursor-help" title="Lector USB Biometrico: Consultando...">
                    <span id="dot-iot-registrador" class="w-1.5 h-1.5 rounded-full bg-slate-500 shrink-0"></span>
                    <span class="tracking-tight">USB</span>
                </span>
                <span class="text-slate-700 font-light">|</span>
                <span id="chip-iot-gateway" class="flex items-center gap-1 font-semibold text-slate-400 hover:text-slate-200 cursor-help" title="Gateway LoRa: Consultando...">
                    <span id="dot-iot-gateway" class="w-1.5 h-1.5 rounded-full bg-slate-500 shrink-0"></span>
                    <span class="tracking-tight">LoRa</span>
                </span>
            </div>
            <button type="button" id="btn-ping-iot-sidebar" class="text-slate-500 hover:text-white transition-colors p-0.5 cursor-pointer" title="Verificar enlace de hardware (USB y LoRa)" aria-label="Actualizar estado IoT">
                <svg id="icon-ping-iot-sidebar" class="w-3 h-3 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
        </div>

        <!-- Version Mini para modo colapsado -->
        <div class="sidebar-iot-mini hidden flex-col items-center gap-1.5 p-1 rounded-lg bg-slate-900/60 border border-slate-800 my-1" title="Hardware IoT: Lector USB y Gateway LoRa">
            <span id="dot-iot-mini-registrador" class="w-2 h-2 rounded-full bg-slate-500" title="Lector USB DY50"></span>
            <span id="dot-iot-mini-gateway" class="w-2 h-2 rounded-full bg-slate-500" title="Gateway LoRa"></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Navegacion Principal con Categorias Jerarquicas (Mental Model) -->
    <nav class="app-sidebar-nav flex-1 space-y-2 pr-0.5" aria-label="Menu principal del sistema">
        <?php foreach ($navSections as $seccion): ?>
            <div class="app-nav-section">
                <!-- Titulo de Categoria / Separador en modo compacto -->
                <span class="app-nav-section-title"><?= htmlspecialchars($seccion['titulo'], ENT_QUOTES, 'UTF-8') ?></span>
                <div class="app-nav-section-divider" aria-hidden="true"></div>

                <div class="space-y-0.5">
                    <?php foreach ($seccion['items'] as $item): ?>
                        <?php
                        $label = $item['label'];
                        $path = $item['path'];
                        $iconoClave = $item['icon'] ?? 'default';
                        $hasSubmenu = !empty($item['hasSubmenu']) && !empty($item['subitems']);

                        // Evaluacion de estado activo
                        $isActive = false;
                        $hasActiveChild = false;

                        if ($hasSubmenu) {
                            foreach ($item['subitems'] as $sub) {
                                if (!empty($sub['exact'])) {
                                    if (strpos($currentPath, '/' . $sub['path']) !== false) {
                                        $hasActiveChild = true;
                                        break;
                                    }
                                } elseif (!empty($sub['match'])) {
                                    if (strpos($currentPath, '/' . $sub['match']) !== false) {
                                        $hasActiveChild = true;
                                        break;
                                    }
                                }
                            }
                            $isExactHub = (strpos($currentPath, '/' . $path) !== false);
                            $isActive = $isExactHub;
                        } else {
                            if (!empty($item['exact'])) {
                                $isActive = (strpos($currentPath, '/' . $path) !== false);
                            } elseif (!empty($item['match'])) {
                                $isActive = (strpos($currentPath, '/' . $item['match']) !== false);
                            } else {
                                $isActive = (strpos($currentPath, '/' . $path) !== false);
                            }
                        }
                        ?>

                        <?php if ($hasSubmenu): ?>
                            <!-- Item con Submenu Desplegable Accesible y Flyout en Hover -->
                            <?php $isSubmenuOpen = $hasActiveChild || $isActive; ?>
                            <div class="app-nav-group app-nav-item-has-flyout relative <?= $hasActiveChild ? 'has-active-child' : '' ?>" id="group-admin-nav">
                                <div class="flex items-center gap-1">
                                    <a href="<?= base_url($path) ?>"
                                       class="app-nav-link flex-1 group relative flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl font-semibold text-xs md:text-sm transition-all duration-150 <?= $isActive ? 'is-active' : ($hasActiveChild ? 'is-parent-active' : '') ?>"
                                       title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= obtenerIconoNav($iconoClave) ?>
                                        <span class="app-nav-text flex-1 truncate"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                        <svg class="app-nav-flyout-arrow app-nav-text w-3.5 h-3.5 text-slate-500 transition-transform group-hover:translate-x-0.5 group-hover:text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                    <!-- Boton toggle para movil (acordeon touch) -->
                                    <button type="button"
                                            class="app-submenu-toggle md:hidden app-nav-text w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer shrink-0"
                                            aria-expanded="<?= $isSubmenuOpen ? 'true' : 'false' ?>"
                                            aria-controls="submenu-admin-items"
                                            aria-label="Alternar submodulos de administracion"
                                            title="Desplegar submodulos">
                                        <svg class="w-3.5 h-3.5 transition-transform duration-200 <?= $isSubmenuOpen ? 'rotate-180' : '' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Submenu: Flyout flotante en Desktop (hover) / Acordeon en Movil -->
                                <div id="submenu-admin-items"
                                     class="app-nav-flyout app-submenu <?= $isSubmenuOpen ? '' : 'hidden md:block' ?>"
                                     role="region" aria-label="Submodulos de <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
                                    <!-- Cabecera del Flyout (Visible solo en desktop) -->
                                    <div class="app-flyout-header hidden md:flex items-center justify-between pb-2 mb-2 border-b border-slate-700/60">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                            <span class="text-xs font-bold text-white tracking-wide"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="text-[10px] text-slate-400 font-medium">Submodulos</span>
                                        </div>
                                        <a href="<?= base_url($path) ?>" class="text-[10px] font-extrabold text-blue-400 hover:text-blue-300 uppercase tracking-wider px-1.5 py-0.5 rounded hover:bg-blue-500/10 transition-colors">
                                            Ver panel &rarr;
                                        </a>
                                    </div>

                                    <!-- Grilla de Submodulos (2 columnas en desktop) -->
                                    <div class="app-flyout-grid grid grid-cols-1 md:grid-cols-2 gap-1">
                                        <?php foreach ($item['subitems'] as $sub): ?>
                                            <?php
                                            $subActive = false;
                                            if (!empty($sub['exact'])) {
                                                $subActive = (strpos($currentPath, '/' . $sub['path']) !== false);
                                            } elseif (!empty($sub['match'])) {
                                                $subActive = (strpos($currentPath, '/' . $sub['match']) !== false);
                                            }
                                            ?>
                                            <a href="<?= base_url($sub['path']) ?>"
                                               class="app-submenu-link app-flyout-item group flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/90 transition-all <?= $subActive ? 'is-active text-blue-300 bg-blue-600/20 border-l-2 border-blue-500 font-bold' : '' ?>"
                                               title="<?= htmlspecialchars($sub['label'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= obtenerIconoNav($sub['icon']) ?>
                                                <span class="truncate"><?= htmlspecialchars($sub['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Item Estandar de Navegacion -->
                            <a href="<?= base_url($path) ?>"
                               class="app-nav-link group relative flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl font-semibold text-xs md:text-sm transition-all duration-150 <?= $isActive ? 'is-active' : '' ?>"
                               title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
                                <?= obtenerIconoNav($iconoClave) ?>
                                <span class="app-nav-text flex-1 truncate"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($item['badge'])): ?>
                                    <span class="app-nav-badge ml-auto px-1.5 py-0.5 rounded-md bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 text-[10px] font-extrabold uppercase tracking-wider flex items-center gap-1 shrink-0">
                                        <?php if (!empty($item['badge']['live'])): ?>
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        <?php endif; ?>
                                        <span><?= htmlspecialchars($item['badge']['text'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>

                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- Pie del Sidebar: Barra de Utilidades Compacta (Perfil, Escala Tipografica, Tema y Logout) -->
    <div class="sidebar-footer pt-2 mt-auto border-t border-slate-800/80 shrink-0">
        <!-- Barra de Utilidades Unificada (Altura ultra-optimizada) -->
        <div class="sidebar-utility-bar flex items-center justify-between gap-1 p-1 rounded-xl bg-slate-900/70 border border-slate-800/80">
            <!-- Perfil de usuario (Avatar + Tooltip/Nombre) -->
            <div class="sidebar-profile-toolbar flex items-center gap-2 min-w-0 flex-1 px-1" title="<?= htmlspecialchars($nombreUsuario . ' (' . $rolActual . ')', ENT_QUOTES, 'UTF-8') ?>">
                <div class="relative w-7 h-7 rounded-lg bg-gradient-to-tr from-indigo-600 to-blue-500 flex items-center justify-center text-white font-black text-xs shrink-0 shadow-2xs">
                    <?= htmlspecialchars($inicialUsuario, ENT_QUOTES, 'UTF-8') ?>
                    <span class="absolute -bottom-0.5 -right-0.5 w-2 h-2 rounded-full bg-emerald-400 border-2 border-slate-900"></span>
                </div>
                <div class="min-w-0 flex-1 app-nav-text">
                    <div class="text-xs font-bold text-slate-200 truncate leading-tight"><?= htmlspecialchars($nombreUsuario, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="text-[9px] font-extrabold text-blue-400 uppercase tracking-wider truncate"><?= htmlspecialchars($rolActual, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>

            <!-- Acciones Rapidas: Escala Visual, Tema y Logout -->
            <div class="sidebar-quick-actions flex items-center gap-0.5 shrink-0">
                <!-- Ciclar Tamano de Vista (11pt / 12pt / 13pt) -->
                <button type="button"
                        id="btn-font-scale-cycle"
                        class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer"
                        title="Alternar tamano de vista (11pt / 12pt / 13pt)"
                        aria-label="Alternar escala de vista">
                    <span class="text-[10px] font-black tracking-tight text-blue-400 flex items-center leading-none">
                        <span class="text-[9px] opacity-75">Aa</span>
                        <span id="mini-font-size-badge" class="ml-0.5">12</span>
                    </span>
                    <span id="label-current-font-size" class="hidden">12pt</span>
                </button>

                <!-- Alternar Modo Oscuro / Claro -->
                <button type="button" data-action="toggle-theme"
                        class="app-theme-btn w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer"
                        title="Alternar Modo Oscuro / Claro" aria-label="Alternar Modo Oscuro / Claro">
                    <svg class="theme-icon-sun w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="theme-icon-moon w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <!-- Cerrar Sesion -->
                <a href="<?= base_url('src/api/logout.php') ?>"
                   class="app-logout w-7 h-7 flex items-center justify-center rounded-lg text-rose-400 hover:text-rose-200 hover:bg-rose-950/40 transition-colors"
                   title="Cerrar sesion" aria-label="Cerrar sesion">
                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Botones triples ocultos para compatibilidad total con scripts existentes -->
        <div class="hidden" aria-hidden="true">
            <button type="button" data-font-size="11pt" class="btn-font-scale">11pt</button>
            <button type="button" data-font-size="12pt" class="btn-font-scale">12pt</button>
            <button type="button" data-font-size="13pt" class="btn-font-scale">13pt</button>
        </div>
    </div>
</aside>
