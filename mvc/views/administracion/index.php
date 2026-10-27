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
    'total_aulas' => 0,
    'total_sync_pendientes' => 0
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
            (SELECT COUNT(*) FROM aulas WHERE activo = 1) AS total_aulas,
            (SELECT COUNT(*) FROM sync_biometrica WHERE estado = 'PENDIENTE') AS total_sync_pendientes
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

        <!-- Breadcrumb de Navegación Accesible -->
        <nav class="flex items-center gap-2 text-base text-slate-600 dark:text-slate-400 font-medium" aria-label="Migas de pan">
            <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition flex items-center gap-1.5 focus:outline-none focus:underline">
                <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Inicio
            </a>
            <span class="text-slate-400 dark:text-slate-600">/</span>
            <span class="text-slate-900 dark:text-white font-bold">Administración</span>
        </nav>

        <!-- Cabecera de Página con Letras Grandes y Acciones Claras -->
        <div class="app-page-header p-6 sm:p-8 rounded-3xl flex flex-col md:flex-row md:items-center md:justify-between gap-6 border-2 border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
            <div>
                <div class="flex items-center gap-3 mb-3 flex-wrap">
                    <span class="px-4 py-1.5 text-sm font-bold rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-900 dark:text-blue-300 tracking-wide uppercase border border-blue-200 dark:border-blue-800">
                        Centro de Control Institucional
                    </span>
                    <span class="text-base text-slate-600 dark:text-slate-400 font-medium hidden sm:inline">
                        Gestión Pedagógica y Operativa
                    </span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Administración del Sistema
                </h1>
                <p class="text-base sm:text-lg text-slate-700 dark:text-slate-300 mt-2 max-w-3xl leading-relaxed">
                    Estructurá los ciclos formativos, turnos, bachilleratos y materias. Esta jerarquía alimenta las listas de profesores, coordinadores y estudiantes.
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-3 flex-shrink-0">
                <button type="button" onclick="abrirModalResumen()" class="min-h-[46px] px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-base font-bold rounded-2xl shadow-sm transition inline-flex items-center gap-2.5 focus:outline-none focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900">
                    <span class="text-xl">📊</span>
                    <span>Resumen Institucional</span>
                </button>
                <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="btn btn-muted min-h-[46px] px-5 py-2.5 text-base font-semibold inline-flex items-center gap-2 rounded-2xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Volver</span>
                </a>
            </div>
        </div>

        <!-- Buscador Rápido de Módulos (Accesible para personas mayores) -->
        <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-2xl border-2 border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="relative w-full md:max-w-xl">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" id="input-filtro-modulos" placeholder="Buscar módulo (ej: materias, alumnos, turnos, usuarios, aulas)..."
                       class="w-full pl-12 pr-10 py-3 text-base text-slate-900 dark:text-white font-medium bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-slate-800 transition placeholder:text-slate-400 dark:placeholder:text-slate-500"
                       aria-label="Buscar módulos administrativos">
                <button type="button" id="btn-limpiar-busqueda-modulos" onclick="limpiarFiltroModulos()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hidden text-lg font-bold" aria-label="Limpiar búsqueda">
                    ✕
                </button>
            </div>
            <div class="flex items-center gap-2 self-start md:self-auto text-base text-slate-600 dark:text-slate-400 font-medium">
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                <span id="contador-modulos-visibles">12 módulos disponibles</span>
            </div>
        </div>

        <!-- SECCIÓN 1: ESTRUCTURA ACADÉMICA Y CURRICULAR -->
        <section class="space-y-5 seccion-admin" id="seccion-curricular">
            <div class="flex items-center justify-between border-b-2 border-slate-200 dark:border-slate-800 pb-3">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white flex items-center gap-3">
                        <span class="w-3.5 h-3.5 rounded-full bg-blue-600 inline-block"></span>
                        Estructura Académica y Curricular
                    </h2>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1">Configuración curricular, niveles, especialidades y franjas horarias</p>
                </div>
                <span class="text-sm font-bold px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    6 Módulos
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- 1. Turnos -->
                <div class="card-modulo app-card p-6 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 flex flex-col justify-between group hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition duration-200 bg-white dark:bg-slate-900"
                     data-titulo="turnos escolares jornadas" data-desc="gestion de jornadas lectivas manana tarde noche franjas horarias">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 flex items-center justify-center border border-amber-200 dark:border-amber-800 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                <?= (int)$totales['total_turnos'] ?> activos
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition">Turnos Escolares</h3>
                        <p class="text-base text-slate-700 dark:text-slate-300 mt-2 leading-relaxed">
                            Gestión de jornadas lectivas (Mañana, Tarde, Noche) y sus franjas horarias de funcionamiento.
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="<?= base_url('mvc/views/turnos/index.php') ?>" class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-blue-50/90 dark:bg-blue-950/60 hover:bg-blue-600 dark:hover:bg-blue-600 text-blue-700 dark:text-blue-300 hover:text-white dark:hover:text-white border border-blue-200 dark:border-blue-800/60 hover:border-blue-600 flex items-center justify-between text-base font-bold transition">
                            <span>Configurar turnos</span>
                            <span class="text-lg group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

                <!-- 2. Ciclos y Niveles -->
                <div class="card-modulo app-card p-6 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 flex flex-col justify-between group hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition duration-200 bg-white dark:bg-slate-900"
                     data-titulo="ciclos y niveles educativos etapas eeb medio" data-desc="definicion de etapas educativas eeb 1 2 3 ciclo nivel medio">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center border border-indigo-200 dark:border-indigo-800 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-900 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                <?= (int)$totales['total_niveles'] ?> etapas
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition">Ciclos y Niveles</h3>
                        <p class="text-base text-slate-700 dark:text-slate-300 mt-2 leading-relaxed">
                            Definición de etapas educativas: EEB (1°, 2°, 3° Ciclo) y Nivel Medio del colegio.
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="<?= base_url('mvc/views/niveles/index.php') ?>" class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-blue-50/90 dark:bg-blue-950/60 hover:bg-blue-600 dark:hover:bg-blue-600 text-blue-700 dark:text-blue-300 hover:text-white dark:hover:text-white border border-blue-200 dark:border-blue-800/60 hover:border-blue-600 flex items-center justify-between text-base font-bold transition">
                            <span>Configurar niveles</span>
                            <span class="text-lg group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

                <!-- 3. Bachilleratos -->
                <div class="card-modulo app-card p-6 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 flex flex-col justify-between group hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition duration-200 bg-white dark:bg-slate-900"
                     data-titulo="bachilleratos tecnicos cientificos bti batan btc bcb bcs" data-desc="especialidades tecnicas bti batan btc y cientificas nivel medio">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center border border-emerald-200 dark:border-emerald-800 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <?= (int)$totales['total_bachilleratos'] ?> especialidades
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition">Bachilleratos</h3>
                        <p class="text-base text-slate-700 dark:text-slate-300 mt-2 leading-relaxed">
                            Especialidades Técnicas (BTI, BATAN, BTC) y Científicas (BCB, BCS) del Nivel Medio.
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="<?= base_url('mvc/views/bachilleratos/index.php') ?>" class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-blue-50/90 dark:bg-blue-950/60 hover:bg-blue-600 dark:hover:bg-blue-600 text-blue-700 dark:text-blue-300 hover:text-white dark:hover:text-white border border-blue-200 dark:border-blue-800/60 hover:border-blue-600 flex items-center justify-between text-base font-bold transition">
                            <span>Configurar bachilleratos</span>
                            <span class="text-lg group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

                <!-- 4. Grados y Cursos -->
                <div class="card-modulo app-card p-6 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 flex flex-col justify-between group hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition duration-200 bg-white dark:bg-slate-900"
                     data-titulo="grados y cursos secciones aula asignada" data-desc="cursos y secciones vinculados con turno nivel academico especialidad y aula">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 flex items-center justify-center border border-sky-200 dark:border-sky-800 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-full bg-sky-100 dark:bg-sky-950/60 text-sky-900 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                <?= (int)$totales['total_grados'] ?> cursos
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition">Grados y Cursos</h3>
                        <p class="text-base text-slate-700 dark:text-slate-300 mt-2 leading-relaxed">
                            Cursos y secciones vinculados con su turno, nivel académico, especialidad y aula asignada.
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="<?= base_url('mvc/views/grados/index.php') ?>" class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-blue-50/90 dark:bg-blue-950/60 hover:bg-blue-600 dark:hover:bg-blue-600 text-blue-700 dark:text-blue-300 hover:text-white dark:hover:text-white border border-blue-200 dark:border-blue-800/60 hover:border-blue-600 flex items-center justify-between text-base font-bold transition">
                            <span>Gestionar grados</span>
                            <span class="text-lg group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

                <!-- 5. Materias -->
                <div class="card-modulo app-card p-6 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 flex flex-col justify-between group hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition duration-200 bg-white dark:bg-slate-900"
                     data-titulo="materias malla curricular asignaturas cientificas tecnicas" data-desc="malla curricular institucional asignaturas cientificas tecnicas y formacion general">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 flex items-center justify-center border border-purple-200 dark:border-purple-800 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-full bg-purple-100 dark:bg-purple-950/60 text-purple-900 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                <?= (int)$totales['total_materias'] ?> materias
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition">Materias</h3>
                        <p class="text-base text-slate-700 dark:text-slate-300 mt-2 leading-relaxed">
                            Malla curricular institucional: asignaturas científicas, técnicas y de formación general.
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="<?= base_url('mvc/views/materias/index.php') ?>" class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-blue-50/90 dark:bg-blue-950/60 hover:bg-blue-600 dark:hover:bg-blue-600 text-blue-700 dark:text-blue-300 hover:text-white dark:hover:text-white border border-blue-200 dark:border-blue-800/60 hover:border-blue-600 flex items-center justify-between text-base font-bold transition">
                            <span>Gestionar materias</span>
                            <span class="text-lg group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

                <!-- 6. Asignaciones Docentes -->
                <div class="card-modulo app-card p-6 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 flex flex-col justify-between group hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition duration-200 bg-white dark:bg-slate-900"
                     data-titulo="asignaciones docentes profesores carga horaria vinculos" data-desc="relacionamiento entre profesores materias grados y carga horaria para planes y clases">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 flex items-center justify-center border border-teal-200 dark:border-teal-800 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-full bg-teal-100 dark:bg-teal-950/60 text-teal-900 dark:text-teal-300 border border-teal-200 dark:border-teal-800">
                                <?= (int)$totales['total_asignaciones'] ?> vínculos
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition">Asignaciones Docentes</h3>
                        <p class="text-base text-slate-700 dark:text-slate-300 mt-2 leading-relaxed">
                            Relacionamiento entre profesores, materias, grados y carga horaria para los planes y clases en vivo.
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="<?= base_url('mvc/views/asignaciones/index.php') ?>" class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-blue-50/90 dark:bg-blue-950/60 hover:bg-blue-600 dark:hover:bg-blue-600 text-blue-700 dark:text-blue-300 hover:text-white dark:hover:text-white border border-blue-200 dark:border-blue-800/60 hover:border-blue-600 flex items-center justify-between text-base font-bold transition">
                            <span>Gestionar asignaciones</span>
                            <span class="text-lg group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

            </div>
        </section>

        <!-- SECCIÓN 2: GESTIÓN INSTITUCIONAL Y OPERATIVA -->
        <section class="space-y-5 seccion-admin" id="seccion-operativa">
            <div class="flex items-center justify-between border-b-2 border-slate-200 dark:border-slate-800 pb-3">
                <div>
                    <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white flex items-center gap-3">
                        <span class="w-3.5 h-3.5 rounded-full bg-slate-800 dark:bg-slate-400 inline-block"></span>
                        Gestión Institucional y Operativa
                    </h2>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1">Nóminas de personas, espacios físicos e infraestructura técnica IoT</p>
                </div>
                <span class="text-sm font-bold px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    6 Módulos
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <!-- 7. Estudiantes -->
                <div class="card-modulo app-card p-6 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 flex flex-col justify-between group hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition duration-200 bg-white dark:bg-slate-900"
                     data-titulo="estudiantes y matriculas alumnos nomina general" data-desc="nomina general filtro en cascada por curso importacion exportacion a excel biometria">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center border border-indigo-200 dark:border-indigo-800 group-hover:scale-105 transition-transform text-2xl font-bold">
                                🎓
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-900 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                <?= (int)$totales['total_estudiantes'] ?> alumnos
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition">Estudiantes y Matrículas</h3>
                        <p class="text-base text-slate-700 dark:text-slate-300 mt-2 leading-relaxed">
                            Nómina general, filtro en cascada por curso, importación masiva a Excel y registro biométrico DY50.
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="<?= base_url('mvc/views/estudiantes/index.php') ?>" class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-blue-50/90 dark:bg-blue-950/60 hover:bg-blue-600 dark:hover:bg-blue-600 text-blue-700 dark:text-blue-300 hover:text-white dark:hover:text-white border border-blue-200 dark:border-blue-800/60 hover:border-blue-600 flex items-center justify-between text-base font-bold transition">
                            <span>Gestionar estudiantes</span>
                            <span class="text-lg group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

                <!-- 8. Usuarios -->
                <div class="card-modulo app-card p-6 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 flex flex-col justify-between group hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition duration-200 bg-white dark:bg-slate-900"
                     data-titulo="usuarios y roles cuentas credenciales personal docentes coordinadores" data-desc="cuentas del personal institucional credenciales de acceso asignacion de roles y permisos">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 flex items-center justify-center border border-blue-200 dark:border-blue-800 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                <?= (int)$totales['total_usuarios'] ?> usuarios
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition">Usuarios y Roles</h3>
                        <p class="text-base text-slate-700 dark:text-slate-300 mt-2 leading-relaxed">
                            Cuentas del personal institucional, credenciales de acceso y asignación de roles.
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="<?= base_url('mvc/views/usuarios/index.php') ?>" class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-blue-50/90 dark:bg-blue-950/60 hover:bg-blue-600 dark:hover:bg-blue-600 text-blue-700 dark:text-blue-300 hover:text-white dark:hover:text-white border border-blue-200 dark:border-blue-800/60 hover:border-blue-600 flex items-center justify-between text-base font-bold transition">
                            <span>Gestionar usuarios</span>
                            <span class="text-lg group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

                <!-- 9. Tutores y Familias -->
                <div class="card-modulo app-card p-6 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 flex flex-col justify-between group hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition duration-200 bg-white dark:bg-slate-900"
                     data-titulo="tutores y familias padres madres tutores legales hijos vinculados" data-desc="padron de padres madres y tutores legales vinculados con sus respectivos hijos a cargo">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center border border-emerald-200 dark:border-emerald-800 group-hover:scale-105 transition-transform text-2xl font-bold">
                                👨‍👩‍👧‍👦
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                <?= (int)$totales['total_tutores'] ?> tutores
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition">Tutores y Familias</h3>
                        <p class="text-base text-slate-700 dark:text-slate-300 mt-2 leading-relaxed">
                            Padrón de padres, madres y tutores legales vinculados con sus respectivos hijos a cargo.
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="<?= base_url('mvc/views/tutores/index.php') ?>" class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-blue-50/90 dark:bg-blue-950/60 hover:bg-blue-600 dark:hover:bg-blue-600 text-blue-700 dark:text-blue-300 hover:text-white dark:hover:text-white border border-blue-200 dark:border-blue-800/60 hover:border-blue-600 flex items-center justify-between text-base font-bold transition">
                            <span>Gestionar familias</span>
                            <span class="text-lg group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

                <!-- 10. Aulas y Espacios -->
                <div class="card-modulo app-card p-6 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 flex flex-col justify-between group hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition duration-200 bg-white dark:bg-slate-900"
                     data-titulo="aulas y espacios salas laboratorios computo talleres capacidad fisica" data-desc="control de salas de clase laboratorios de computo talleres y capacidad fisica">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 flex items-center justify-center border border-rose-200 dark:border-rose-800 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-900 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                <?= (int)$totales['total_aulas'] ?> salas
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition">Aulas y Espacios</h3>
                        <p class="text-base text-slate-700 dark:text-slate-300 mt-2 leading-relaxed">
                            Control de salas de clase, laboratorios de cómputo, talleres y capacidad física del colegio.
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="<?= base_url('mvc/views/aulas/index.php') ?>" class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-blue-50/90 dark:bg-blue-950/60 hover:bg-blue-600 dark:hover:bg-blue-600 text-blue-700 dark:text-blue-300 hover:text-white dark:hover:text-white border border-blue-200 dark:border-blue-800/60 hover:border-blue-600 flex items-center justify-between text-base font-bold transition">
                            <span>Gestionar aulas</span>
                            <span class="text-lg group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

                <!-- 11. Configuración IoT & Red -->
                <div class="card-modulo app-card p-6 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 flex flex-col justify-between group hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-lg transition duration-200 bg-white dark:bg-slate-900"
                     data-titulo="configuracion red iot esp32 registrador lora gateway nube" data-desc="parametros dinamicos de red nodos esp32 registradores gateway lora y conectividad">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 flex items-center justify-center border border-cyan-200 dark:border-cyan-800 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-full bg-cyan-100 dark:bg-cyan-950/60 text-cyan-900 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800">
                                IoT & Nube
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-blue-700 dark:group-hover:text-blue-400 transition">Configuración de Red e IoT</h3>
                        <p class="text-base text-slate-700 dark:text-slate-300 mt-2 leading-relaxed">
                            Parámetros dinámicos de red, nodos ESP32 registradores, gateway LoRa y conectividad Supabase.
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="<?= base_url('mvc/views/configuracion/index.php') ?>" class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-blue-50/90 dark:bg-blue-950/60 hover:bg-blue-600 dark:hover:bg-blue-600 text-blue-700 dark:text-blue-300 hover:text-white dark:hover:text-white border border-blue-200 dark:border-blue-800/60 hover:border-blue-600 flex items-center justify-between text-base font-bold transition">
                            <span>Configurar parámetros</span>
                            <span class="text-lg group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

                <!-- 12. Sincronización Biométrica LoRa -->
                <div class="card-modulo app-card p-6 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 flex flex-col justify-between group hover:border-amber-400 dark:hover:border-amber-500 hover:shadow-lg transition duration-200 bg-white dark:bg-slate-900"
                     data-titulo="sincronizacion biometrica lora cola huellas sensores aulas transmision pendiente" data-desc="cola de envio y distribucion de huellas dactilares a las aulas via radiofrecuencia lora">
                    <div>
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 flex items-center justify-center border border-amber-200 dark:border-amber-800 group-hover:scale-105 transition-transform text-2xl font-bold">
                                📡
                            </div>
                            <span class="text-sm font-bold px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300 border border-amber-200 dark:border-amber-800 flex items-center gap-1.5">
                                <?php if ((int)$totales['total_sync_pendientes'] > 0): ?>
                                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                <?php endif; ?>
                                <?= (int)$totales['total_sync_pendientes'] ?> pendientes
                            </span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition">Sincronización Biométrica</h3>
                        <p class="text-base text-slate-700 dark:text-slate-300 mt-2 leading-relaxed">
                            Cola de distribución y transmisión de huellas por LoRa hacia los sensores de asistencia en cada aula física.
                        </p>
                    </div>
                    <div class="mt-6">
                        <a href="<?= base_url('mvc/views/administracion/sync_biometrica.php') ?>" class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-amber-50/90 dark:bg-amber-950/60 hover:bg-amber-600 dark:hover:bg-amber-600 text-amber-800 dark:text-amber-300 hover:text-white dark:hover:text-white border border-amber-200 dark:border-amber-800/60 hover:border-amber-600 flex items-center justify-between text-base font-bold transition">
                            <span>Supervisar cola LoRa</span>
                            <span class="text-lg group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>
                </div>

            </div>
        </section>

        <!-- Estado Vacío cuando el filtro de búsqueda no encuentra coincidencias -->
        <div id="empty-busqueda-modulos" class="hidden p-12 text-center rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <span class="text-5xl block">🔍</span>
            <h3 class="text-2xl font-bold text-slate-900 dark:text-white">No encontramos ningún módulo con ese nombre</h3>
            <p class="text-base text-slate-600 dark:text-slate-400 max-w-md mx-auto">
                Probá escribiendo otra palabra clave como "estudiantes", "tutores", "materias" o "horarios".
            </p>
            <button type="button" onclick="limpiarFiltroModulos()" class="btn btn-primary min-h-[46px] px-6 text-base font-bold rounded-2xl">
                Restablecer lista de módulos
            </button>
        </div>

    </div>
</main>

<!-- Inclusión del Submodal de Resumen Institucional -->
<?php require_once __DIR__ . '/modal_resumen_institucional.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputFiltro = document.getElementById('input-filtro-modulos');
    const btnLimpiar = document.getElementById('btn-limpiar-busqueda-modulos');
    const labelContador = document.getElementById('contador-modulos-visibles');
    const emptyBox = document.getElementById('empty-busqueda-modulos');
    const cards = document.querySelectorAll('.card-modulo');
    const secciones = document.querySelectorAll('.seccion-admin');

    function filtrarModulos() {
        const query = (inputFiltro ? inputFiltro.value : '').trim().toLowerCase();
        
        if (btnLimpiar) {
            if (query.length > 0) {
                btnLimpiar.classList.remove('hidden');
            } else {
                btnLimpiar.classList.add('hidden');
            }
        }

        let totalVisibles = 0;

        cards.forEach(card => {
            const titulo = (card.dataset.titulo || '').toLowerCase();
            const desc = (card.dataset.desc || '').toLowerCase();
            const coincide = !query || titulo.includes(query) || desc.includes(query);

            if (coincide) {
                card.classList.remove('hidden');
                totalVisibles++;
            } else {
                card.classList.add('hidden');
            }
        });

        // Ocultar sección completa si no tiene cards visibles
        secciones.forEach(sec => {
            const cardsEnSec = sec.querySelectorAll('.card-modulo:not(.hidden)');
            if (cardsEnSec.length === 0) {
                sec.classList.add('hidden');
            } else {
                sec.classList.remove('hidden');
            }
        });

        // Actualizar contador
        if (labelContador) {
            labelContador.textContent = `${totalVisibles} de ${cards.length} módulos disponibles`;
        }

        // Estado vacío
        if (emptyBox) {
            if (totalVisibles === 0) {
                emptyBox.classList.remove('hidden');
            } else {
                emptyBox.classList.add('hidden');
            }
        }
    }

    if (inputFiltro) {
        inputFiltro.addEventListener('input', filtrarModulos);
    }

    window.limpiarFiltroModulos = function() {
        if (inputFiltro) {
            inputFiltro.value = '';
            filtrarModulos();
            inputFiltro.focus();
        }
    };
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
