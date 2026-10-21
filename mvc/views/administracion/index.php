<?php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/middleware/auth.php';

// RBAC: Solo SuperAdmin, Administrador y Administracion tienen acceso
verificarRol(['SuperAdmin', 'Administrador', 'Administracion']);

require_once __DIR__ . '/../../../src/config/db.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Carga optimizada de contadores en una sola consulta
$totales = [
    'total_turnos' => 0,
    'total_niveles' => 0,
    'total_bachilleratos' => 0,
    'total_grados' => 0,
    'total_materias' => 0,
    'total_asignaciones' => 0,
    'total_usuarios' => 0,
    'total_estudiantes' => 0,
    'total_tutores' => 0,
    'total_aulas' => 0
];

try {
    $db = (new Database())->getConnection();
    $stmt = $db->query("
        SELECT 
            (SELECT COUNT(*) FROM turnos WHERE activo = 1) AS total_turnos,
            (SELECT COUNT(*) FROM niveles_academicos WHERE activo = 1) AS total_niveles,
            (SELECT COUNT(*) FROM bachilleratos WHERE activo = 1) AS total_bachilleratos,
            (SELECT COUNT(*) FROM grados WHERE activo = 1) AS total_grados,
            (SELECT COUNT(*) FROM materias WHERE activo = 1) AS total_materias,
            (SELECT COUNT(*) FROM asignacion_docente WHERE activo = 1) AS total_asignaciones,
            (SELECT COUNT(*) FROM usuarios WHERE activo = 1) AS total_usuarios,
            (SELECT COUNT(*) FROM estudiantes WHERE activo = 1) AS total_estudiantes,
            (SELECT COUNT(*) FROM tutores WHERE activo = 1) AS total_tutores,
            (SELECT COUNT(*) FROM aulas WHERE activo = 1) AS total_aulas
    ");
    if ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $totales = array_merge($totales, $fila);
    }
} catch (Throwable $e) {
    error_log("Error cargando totales de administración: " . $e->getMessage());
}
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Breadcrumb de Navegación -->
        <nav class="flex items-center gap-2 text-sm text-slate-500">
            <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="hover:text-blue-600 transition flex items-center gap-1 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Inicio
            </a>
            <span>/</span>
            <span class="text-slate-900 font-semibold">Administración</span>
        </nav>

        <!-- Cabecera de Página -->
        <div class="app-page-header p-6 sm:p-8 rounded-2xl flex flex-col md:flex-row md:items-center md:justify-between gap-6 border border-slate-200 shadow-sm">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-3 py-1 text-xs font-bold rounded-full bg-blue-100 text-blue-800 tracking-wide uppercase">
                        Centro de Control
                    </span>
                    <span class="text-xs text-slate-500 font-medium hidden sm:inline">
                        Gestión Pedagógica e Institucional
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Administración del Sistema
                </h1>
                <p class="text-sm sm:text-base text-slate-600 mt-2 max-w-2xl leading-relaxed">
                    Estructurá los ciclos formativos, turnos, bachilleratos y materias. Esta jerarquía alimenta los filtros y vistas de profesores y coordinadores.
                </p>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="btn btn-muted text-sm inline-flex items-center gap-2 font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Volver al Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Pipeline Explicativo de la Jerarquía Académica -->
        <div class="p-5 bg-gradient-to-r from-blue-50/70 via-indigo-50/50 to-slate-50 rounded-2xl border border-blue-100/80">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                        ⚡
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Pirámide de Navegación y Filtros Pedagógicos</h4>
                        <p class="text-xs text-slate-600">La selección en cascada permite a los coordinadores y profesores acceder rápidamente a cualquier materia y curso:</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-1.5 text-xs font-medium text-slate-700">
                    <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 shadow-2xs font-semibold text-blue-700">1. Turno</span>
                    <span class="text-slate-400">➔</span>
                    <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 shadow-2xs font-semibold text-indigo-700">2. Nivel / Ciclo</span>
                    <span class="text-slate-400">➔</span>
                    <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 shadow-2xs font-semibold text-emerald-700">3. Bachillerato</span>
                    <span class="text-slate-400">➔</span>
                    <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 shadow-2xs font-semibold text-sky-700">4. Grado</span>
                    <span class="text-slate-400">➔</span>
                    <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 shadow-2xs font-semibold text-purple-700">5. Materia</span>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 1: ESTRUCTURA ACADÉMICA Y CURRICULAR -->
        <section class="space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600 inline-block"></span>
                        Estructura Académica y Curricular
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Componentes clave para el ordenamiento pedagógico y distribución horaria</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                    6 Módulos
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                <!-- 1. Turnos -->
                <a href="<?= base_url('mvc/views/turnos/index.php') ?>" class="app-card p-6 flex flex-col justify-between group hover:border-blue-300 hover:shadow-md transition duration-200">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100 group-hover:scale-105 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800">
                                <?= (int)$totales['total_turnos'] ?> activos
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition">Turnos Escolares</h3>
                        <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                            Gestión de jornadas lectivas (Mañana, Tarde, Noche) y sus franjas horarias de funcionamiento.
                        </p>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                        <span>Configurar turnos</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>

                <!-- 2. Ciclos y Niveles -->
                <a href="<?= base_url('mvc/views/niveles/index.php') ?>" class="app-card p-6 flex flex-col justify-between group hover:border-blue-300 hover:shadow-md transition duration-200">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 group-hover:scale-105 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-800">
                                <?= (int)$totales['total_niveles'] ?> etapas
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition">Ciclos y Niveles</h3>
                        <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                            Definición de etapas educativas: EEB (1°, 2°, 3° Ciclo) y Nivel Medio del sistema escolar.
                        </p>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                        <span>Configurar niveles</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>

                <!-- 3. Bachilleratos -->
                <a href="<?= base_url('mvc/views/bachilleratos/index.php') ?>" class="app-card p-6 flex flex-col justify-between group hover:border-blue-300 hover:shadow-md transition duration-200">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 group-hover:scale-105 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                                <?= (int)$totales['total_bachilleratos'] ?> especialidades
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition">Bachilleratos</h3>
                        <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                            Especialidades Técnicas (BTI, BATAN, BTC) y Científicas (BCB, BCS) del Nivel Medio.
                        </p>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                        <span>Configurar bachilleratos</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>

                <!-- 4. Grados y Cursos -->
                <a href="<?= base_url('mvc/views/grados/index.php') ?>" class="app-card p-6 flex flex-col justify-between group hover:border-blue-300 hover:shadow-md transition duration-200">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center border border-sky-100 group-hover:scale-105 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-sky-100 text-sky-800">
                                <?= (int)$totales['total_grados'] ?> cursos
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition">Grados y Cursos</h3>
                        <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                            Cursos y secciones vinculados con su turno, nivel académico, especialidad y aula asignada.
                        </p>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                        <span>Gestionar grados</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>

                <!-- 5. Materias -->
                <a href="<?= base_url('mvc/views/materias/index.php') ?>" class="app-card p-6 flex flex-col justify-between group hover:border-blue-300 hover:shadow-md transition duration-200">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center border border-purple-100 group-hover:scale-105 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800">
                                <?= (int)$totales['total_materias'] ?> materias
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition">Materias</h3>
                        <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                            Malla curricular institucional: asignaturas científicas, técnicas y de formación general.
                        </p>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                        <span>Gestionar materias</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>

                <!-- 6. Asignaciones Docentes -->
                <a href="<?= base_url('mvc/views/asignaciones/index.php') ?>" class="app-card p-6 flex flex-col justify-between group hover:border-blue-300 hover:shadow-md transition duration-200">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center border border-teal-100 group-hover:scale-105 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-teal-100 text-teal-800">
                                <?= (int)$totales['total_asignaciones'] ?> vínculos
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition">Asignaciones</h3>
                        <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                            Relacionamiento entre profesores, materias, grados y carga horaria para los planes y clases en vivo.
                        </p>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                        <span>Gestionar asignaciones</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>

            </div>
        </section>

        <!-- SECCIÓN 2: GESTIÓN INSTITUCIONAL Y OPERATIVA -->
        <section class="space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h2 class="text-lg sm:text-xl font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-700 inline-block"></span>
                        Gestión Institucional y Operativa
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Control de cuentas, espacios físicos y configuración de conectividad</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                    5 Módulos
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                <!-- Estudiantes -->
                <a href="<?= base_url('mvc/views/estudiantes/index.php') ?>" class="app-card p-6 flex flex-col justify-between group hover:border-blue-300 hover:shadow-md transition duration-200">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 group-hover:scale-105 transition-transform">
                                <span class="text-2xl">🎓</span>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-800">
                                <?= (int)$totales['total_estudiantes'] ?> alumnos
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition">Estudiantes y Matrículas</h3>
                        <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                            Nómina general, filtro en cascada por curso, importación y exportación masiva a Excel.
                        </p>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                        <span>Gestionar estudiantes</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>

                <!-- Usuarios -->
                <a href="<?= base_url('mvc/views/usuarios/index.php') ?>" class="app-card p-6 flex flex-col justify-between group hover:border-blue-300 hover:shadow-md transition duration-200">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100 group-hover:scale-105 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800">
                                <?= (int)$totales['total_usuarios'] ?> usuarios
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition">Usuarios y Roles</h3>
                        <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                            Cuentas del personal institucional, credenciales de acceso y asignación de roles.
                        </p>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                        <span>Gestionar usuarios</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>

                <!-- Tutores y Familias -->
                <a href="<?= base_url('mvc/views/tutores/index.php') ?>" class="app-card p-6 flex flex-col justify-between group hover:border-blue-300 hover:shadow-md transition duration-200">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 group-hover:scale-105 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                                <?= (int)$totales['total_tutores'] ?> tutores
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition">Tutores y Familias</h3>
                        <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                            Padrón de padres, madres y tutores legales vinculados con sus respectivos hijos a cargo.
                        </p>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                        <span>Gestionar familias</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>

                <!-- Aulas -->
                <a href="<?= base_url('mvc/views/aulas/index.php') ?>" class="app-card p-6 flex flex-col justify-between group hover:border-blue-300 hover:shadow-md transition duration-200">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-100 group-hover:scale-105 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-800">
                                <?= (int)$totales['total_aulas'] ?> salas
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition">Aulas y Espacios</h3>
                        <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                            Control de salas de clase, laboratorios de cómputo, talleres y capacidad física del colegio.
                        </p>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                        <span>Gestionar aulas</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>

                <!-- Configuración IoT -->
                <a href="<?= base_url('mvc/views/configuracion/index.php') ?>" class="app-card p-6 flex flex-col justify-between group hover:border-blue-300 hover:shadow-md transition duration-200">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center border border-cyan-100 group-hover:scale-105 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-cyan-100 text-cyan-800">
                                IoT & Nube
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition">Configuración de Red e IoT</h3>
                        <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                            Parámetros dinámicos de red, nodos ESP32 registradores, gateway LoRa y conectividad Supabase.
                        </p>
                    </div>
                    <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-semibold text-blue-600">
                        <span>Configurar parámetros</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>

            </div>
        </section>

    </div>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
