<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$esProfesor = ($_SESSION['rol'] ?? '') === 'Profesor';
$rolUsuario = $_SESSION['rol'] ?? 'Usuario';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10 overflow-y-auto">
    <!-- Notificaciones Toast -->
    <div id="toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <!-- ========================================== -->
    <!-- VISTA 1: CATÁLOGO DE PLANES ANUALES (MASTER) -->
    <!-- ========================================== -->
    <section id="vista-catalogo" class="transition-opacity duration-200">
        <!-- Breadcrumb de Navegación -->
        <nav class="flex items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400 mb-3" aria-label="Ruta de navegación">
            <a href="<?= base_url('mvc/views/dashboard/index.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center gap-1.5">
                <span>🏠</span> Inicio
            </a>
            <span>/</span>
            <span class="text-slate-800 dark:text-slate-200 font-bold"><?= $esProfesor ? 'Mis Planes Anuales' : 'Gestión de Planes Anuales' ?></span>
        </nav>

        <!-- Encabezado de la Sección -->
        <header class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold text-2xl shadow-sm">
                            📚
                        </span>
                        <h1 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                            <?= $esProfesor ? 'Mis Planes Anuales Pedagógicos' : 'Gestión de Planes Anuales' ?>
                        </h1>
                    </div>
                    <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 mt-2 font-normal leading-relaxed">
                        Estructura curricular oficial MEC Paraguay · 1 Hora Cátedra (HC) = 40 minutos.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button id="btn-abrir-crear-plan" type="button" class="min-h-[48px] px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-base font-extrabold shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                        <span class="text-lg">➕</span>
                        <span>Crear Nuevo Plan Anual</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Barra de Estadísticas Rápidas -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 mb-6">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 p-6 flex items-center gap-4 shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-3xl font-bold shrink-0">
                    📚
                </div>
                <div>
                    <span class="text-sm uppercase tracking-wider text-slate-500 dark:text-slate-400 font-extrabold">Total de Planes</span>
                    <p id="stat-total-planes" class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white mt-1">0</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 p-6 flex items-center gap-4 shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center text-3xl font-bold shrink-0">
                    📝
                </div>
                <div>
                    <span class="text-sm uppercase tracking-wider text-slate-500 dark:text-slate-400 font-extrabold">Lecciones Planificadas</span>
                    <p id="stat-total-temas" class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white mt-1">0</p>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 p-6 flex items-center gap-4 shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 flex items-center justify-center text-3xl font-bold shrink-0">
                    ⏱️
                </div>
                <div>
                    <span class="text-sm uppercase tracking-wider text-slate-500 dark:text-slate-400 font-extrabold">Unidad de Tiempo</span>
                    <p class="text-base sm:text-lg font-black text-slate-800 dark:text-slate-200 mt-1">40 min / Hora Cátedra</p>
                </div>
            </div>
        </div>

        <!-- Buscador y Filtros en Cascada -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 p-6 sm:p-7 mb-6 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-indigo-600 inline-block"></span>
                        Filtros de Búsqueda y Navegación Curricular
                    </h3>
                    <p class="text-sm sm:text-base text-slate-500 dark:text-slate-400 mt-1">Seleccioná turno, ciclo, bachillerato y grado para filtrar los planes anuales al instante.</p>
                </div>
                <div class="w-full sm:w-88 relative">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 text-lg">🔍</span>
                    <input id="input-buscar-plan" type="text" placeholder="Buscar materia, docente o año..." class="w-full min-h-[48px] pl-11 pr-4 py-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 rounded-xl text-base text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-medium">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label for="filtro-plan-turno" class="block font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 text-sm mb-2">1. Turno</label>
                    <select id="filtro-plan-turno" class="w-full min-h-[48px] bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl px-4 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-indigo-500 font-semibold cursor-pointer">
                        <option value="">Todos los turnos</option>
                    </select>
                </div>

                <div>
                    <label for="filtro-plan-nivel" class="block font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 text-sm mb-2">2. Ciclo / Nivel</label>
                    <select id="filtro-plan-nivel" class="w-full min-h-[48px] bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl px-4 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-indigo-500 font-semibold cursor-pointer">
                        <option value="">Todos los ciclos</option>
                    </select>
                </div>

                <div>
                    <label for="filtro-plan-bach" class="block font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 text-sm mb-2">3. Bachillerato</label>
                    <select id="filtro-plan-bach" class="w-full min-h-[48px] bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl px-4 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-indigo-500 font-semibold cursor-pointer">
                        <option value="">Todos / General</option>
                    </select>
                </div>

                <div>
                    <label for="filtro-plan-grado" class="block font-extrabold uppercase tracking-wider text-slate-700 dark:text-slate-300 text-sm mb-2">4. Grado / Curso</label>
                    <select id="filtro-plan-grado" class="w-full min-h-[48px] bg-slate-50 dark:bg-slate-950 border-2 border-indigo-400 dark:border-indigo-500 text-slate-900 dark:text-white rounded-xl px-4 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-indigo-500 font-bold cursor-pointer">
                        <option value="">Todos los grados</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Grid de Tarjetas de Planes -->
        <div id="grid-planes" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            <div class="col-span-full py-16 text-center text-slate-400">
                <div class="animate-spin inline-block w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full mb-3"></div>
                <p class="text-base font-semibold">Cargando catálogo de planes anuales...</p>
            </div>
        </div>
    </section>

    <!-- ========================================== -->
    <!-- VISTA 2: DETALLE / EDITOR CURRICULAR (DETAIL) -->
    <!-- ========================================== -->
    <section id="vista-detalle" class="hidden transition-opacity duration-200 space-y-6">
        <!-- Breadcrumb de Navegación de la Vista Individual -->
        <nav class="flex items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400 mb-2" aria-label="Ruta de navegación detalle">
            <a href="<?= base_url('mvc/views/dashboard/index.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center gap-1.5">
                <span>🏠</span> Inicio
            </a>
            <span>/</span>
            <a href="#" id="breadcrumb-catalogo-link" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                <?= $esProfesor ? 'Mis Planes Anuales' : 'Planes Anuales' ?>
            </a>
            <span>/</span>
            <span id="breadcrumb-materia-actual" class="text-slate-800 dark:text-slate-200 font-bold">Detalle Curricular</span>
        </nav>

        <!-- Barra de Navegación Superior y Acciones Principales -->
        <div class="p-5 sm:p-6 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <button id="btn-volver-catalogo" type="button" class="min-h-[46px] px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-xl text-base font-bold border border-slate-200 dark:border-slate-700 transition-all flex items-center gap-2 cursor-pointer shadow-2xs">
                <span class="text-lg">←</span>
                <span>Volver al Catálogo de Planes</span>
            </button>

            <div class="flex flex-wrap items-center gap-3">
                <button id="btn-editar-plan-header" type="button" class="min-h-[46px] px-4 py-2.5 bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-xl text-sm sm:text-base font-bold shadow-2xs transition-colors flex items-center gap-2 cursor-pointer">
                    <span>✏️</span>
                    <span>Editar Encabezado</span>
                </button>
                <button id="btn-eliminar-plan-actual" type="button" class="min-h-[46px] px-4 py-2.5 bg-rose-50 dark:bg-rose-950/40 border-2 border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 rounded-xl text-sm sm:text-base font-bold shadow-2xs transition-colors flex items-center gap-2 cursor-pointer">
                    <span>🗑️</span>
                    <span>Eliminar Plan</span>
                </button>
                <button id="btn-nueva-unidad" type="button" class="min-h-[46px] px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm sm:text-base font-extrabold shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                    <span>➕</span>
                    <span>Nueva Unidad Temática</span>
                </button>
            </div>
        </div>

        <!-- Encabezado Institucional del Plan (Hero Card) -->
        <div id="plan-header-card" class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border-2 border-slate-200/90 dark:border-slate-800 p-6 sm:p-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <span class="text-xs sm:text-sm uppercase tracking-wider text-indigo-600 dark:text-indigo-400 font-extrabold">Planificación Anual Oficial MEC</span>
                    <h2 id="det-materia-curso" class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 dark:text-white mt-1 leading-tight">-</h2>
                </div>
                <div class="text-right">
                    <span id="det-carga-turno" class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-2 border-indigo-200 dark:border-indigo-800 shadow-2xs">
                        -
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 pt-2">
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Institución Educativa</span>
                    <p id="det-institucion" class="text-base sm:text-lg font-black text-slate-900 dark:text-white mt-1">-</p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Profesor / Docente Responsable</span>
                    <p id="det-profesor" class="text-base sm:text-lg font-black text-slate-900 dark:text-white mt-1">-</p>
                </div>
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Régimen y Carga Horaria</span>
                    <p class="text-base sm:text-lg font-black text-slate-900 dark:text-white mt-1">40 min / Hora Cátedra</p>
                </div>
            </div>

            <!-- Competencias Pedagógicas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div class="p-5 sm:p-6 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/30 border-2 border-indigo-100 dark:border-indigo-900/40">
                    <span class="text-xs sm:text-sm font-extrabold text-indigo-800 dark:text-indigo-300 uppercase tracking-wide flex items-center gap-2">
                        <span class="text-lg">🎯</span> Competencia General
                    </span>
                    <p id="det-comp-general" class="text-base text-slate-700 dark:text-slate-300 mt-2.5 leading-relaxed font-normal whitespace-pre-line">-</p>
                </div>
                <div class="p-5 sm:p-6 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/30 border-2 border-indigo-100 dark:border-indigo-900/40">
                    <span class="text-xs sm:text-sm font-extrabold text-indigo-800 dark:text-indigo-300 uppercase tracking-wide flex items-center gap-2">
                        <span class="text-lg">📌</span> Competencia Específica de la Disciplina
                    </span>
                    <p id="det-comp-especifica" class="text-base text-slate-700 dark:text-slate-300 mt-2.5 leading-relaxed font-normal whitespace-pre-line">-</p>
                </div>
            </div>

            <div id="det-obs-wrapper" class="pt-4 border-t border-slate-100 dark:border-slate-800 hidden">
                <span class="text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Observaciones Generales</span>
                <p id="det-observaciones" class="text-base text-slate-600 dark:text-slate-300 mt-1 italic leading-relaxed">-</p>
            </div>
        </div>

        <!-- Contenedor Dinámico de Unidades, Capacidades y Temas -->
        <div id="unidades-container" class="space-y-6">
            <!-- Animación de Carga Tranquilizadora -->
            <div class="rounded-3xl border-2 border-indigo-200 dark:border-indigo-900/50 bg-gradient-to-b from-indigo-50/60 to-white dark:from-slate-900/90 dark:to-slate-900 p-12 sm:p-16 text-center my-6 shadow-sm">
                <div class="relative w-20 h-20 mx-auto mb-5 flex items-center justify-center">
                    <div class="absolute inset-0 rounded-full bg-indigo-400/20 dark:bg-indigo-500/20 animate-ping"></div>
                    <div class="w-16 h-16 rounded-full border-4 border-indigo-200 dark:border-indigo-800 border-t-indigo-600 dark:border-t-indigo-400 animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-2xl">
                        📖
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    Cargando Estructura Curricular...
                </h3>
                <p class="text-base text-slate-600 dark:text-slate-400 max-w-md mx-auto mt-2 leading-relaxed font-medium">
                    Consultando unidades temáticas, capacidades pedagógicas del MEC e indicadores de logro.
                </p>
                <div class="max-w-xs mx-auto mt-5 bg-slate-200 dark:bg-slate-800 h-2 rounded-full overflow-hidden shadow-inner">
                    <div class="h-full bg-gradient-to-r from-indigo-500 via-sky-400 to-emerald-400 rounded-full animate-pulse w-3/4"></div>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- ======================================================== -->
<!-- MODAL 1: CREAR / EDITAR ENCABEZADO DEL PLAN ANUAL        -->
<!-- ======================================================== -->
<div id="modal-plan" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-full max-w-2xl my-8 overflow-hidden animate-in fade-in zoom-in duration-150">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 id="modal-plan-title" class="font-black text-slate-900 dark:text-white text-xl">Crear Nuevo Plan Anual</h3>
            <button type="button" class="btn-close-modal w-11 h-11 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">&times;</button>
        </div>

        <form id="form-plan" class="p-6 sm:p-8 space-y-5">
            <input type="hidden" id="plan-id" name="id_plan">

            <div>
                <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Materia y Grado / Curso Asignado *</label>
                <select id="plan-id-asignacion" required class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
                    <option value="">Cargando asignaciones...</option>
                </select>
                <span class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 block">Solo se muestran materias asignadas activas en la institución.</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Año Lectivo *</label>
                    <input type="number" id="plan-anio" required min="2020" max="2035" value="2026" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Turno *</label>
                    <select id="plan-turno" required class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
                        <option value="MAÑANA">Mañana</option>
                        <option value="TARDE">Tarde</option>
                        <option value="NOCHE">Noche</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Institución Educativa</label>
                <input type="text" id="plan-institucion" value="COLEGIO PRIV. PRESB. SAN PABLO" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
            </div>

            <div>
                <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Competencia General (MEC)</label>
                <textarea id="plan-comp-general" rows="3" placeholder="Formular e implementar soluciones técnicas y éticas acordes al perfil de egreso..." class="w-full p-4 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-medium focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all"></textarea>
            </div>

            <div>
                <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Competencia Específica de la Disciplina</label>
                <textarea id="plan-comp-especifica" rows="3" placeholder="Aplica las normativas, métodos de análisis y herramientas prácticas del área..." class="w-full p-4 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-medium focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all"></textarea>
            </div>

            <div>
                <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Observaciones</label>
                <input type="text" id="plan-observaciones" placeholder="Planificación ajustada a cronograma institucional" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
            </div>

            <div class="pt-5 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" class="btn-close-modal min-h-[48px] px-5 py-2.5 rounded-xl text-sm sm:text-base font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">Cancelar</button>
                <button type="submit" class="min-h-[48px] px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm sm:text-base font-extrabold shadow-sm transition-all cursor-pointer active:scale-95">Guardar Plan</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 2: CREAR / EDITAR UNIDAD TEMÁTICA                  -->
<!-- ======================================================== -->
<div id="modal-unidad" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-150">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 id="modal-unidad-title" class="font-black text-slate-900 dark:text-white text-xl">Nueva Unidad Temática</h3>
            <button type="button" class="btn-close-modal w-11 h-11 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">&times;</button>
        </div>

        <form id="form-unidad" class="p-6 sm:p-8 space-y-5">
            <input type="hidden" id="unidad-id" name="id_unidad">

            <div>
                <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Título de la Unidad *</label>
                <input type="text" id="unidad-nombre" required placeholder="Ej: Unidad I: Fundamentos y Conceptos Clave" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Período / Tiempo Estimado</label>
                    <input type="text" id="unidad-tiempo" placeholder="Ej: Febrero a Abril" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Orden Secuencial</label>
                    <input type="number" id="unidad-orden" min="1" max="50" value="1" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
                </div>
            </div>

            <div class="pt-5 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" class="btn-close-modal min-h-[48px] px-5 py-2.5 rounded-xl text-sm sm:text-base font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">Cancelar</button>
                <button type="submit" class="min-h-[48px] px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm sm:text-base font-extrabold shadow-sm transition-all cursor-pointer active:scale-95">Guardar Unidad</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 3: CREAR / EDITAR CAPACIDAD MEC                    -->
<!-- ======================================================== -->
<div id="modal-capacidad" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-150">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 id="modal-capacidad-title" class="font-black text-slate-900 dark:text-white text-xl">Capacidad Pedagógica MEC</h3>
            <button type="button" class="btn-close-modal w-11 h-11 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">&times;</button>
        </div>

        <form id="form-capacidad" class="p-6 sm:p-8 space-y-5">
            <input type="hidden" id="capacidad-id" name="id_capacidad">
            <input type="hidden" id="capacidad-id-unidad" name="id_unidad">

            <div>
                <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Descripción de la Capacidad (MEC) *</label>
                <textarea id="capacidad-desc" required rows="4" placeholder="Comprende la importancia de los estados financieros en la gestión moderna..." class="w-full p-4 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-medium focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all"></textarea>
            </div>

            <div>
                <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Orden Secuencial</label>
                <input type="number" id="capacidad-orden" min="1" max="50" value="1" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
            </div>

            <div class="pt-5 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" class="btn-close-modal min-h-[48px] px-5 py-2.5 rounded-xl text-sm sm:text-base font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">Cancelar</button>
                <button type="submit" class="min-h-[48px] px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm sm:text-base font-extrabold shadow-sm transition-all cursor-pointer active:scale-95">Guardar Capacidad</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 4: CREAR / EDITAR TEMA / LECCIÓN DETALLADA         -->
<!-- ======================================================== -->
<div id="modal-tema" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-full max-w-2xl my-8 overflow-hidden animate-in fade-in zoom-in duration-150">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h3 id="modal-tema-title" class="font-black text-slate-900 dark:text-white text-xl">Planificar Lección / Tema</h3>
            <button type="button" class="btn-close-modal w-11 h-11 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">&times;</button>
        </div>

        <form id="form-tema" class="p-6 sm:p-8 space-y-5">
            <input type="hidden" id="tema-id" name="id_tema">

            <div>
                <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Capacidad MEC Asociada *</label>
                <select id="tema-id-capacidad" required class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
                    <option value="">Seleccione capacidad...</option>
                </select>
            </div>

            <div>
                <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Tema / Contenido de la Lección *</label>
                <input type="text" id="tema-titulo" required placeholder="Ej: 1.1 El rol del administrador financiero en la era digital" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Horas Cátedra (HC)
                        <span id="label-duracion-minutos" class="text-indigo-600 dark:text-indigo-400 font-extrabold normal-case">(= 80 min)</span>
                    </label>
                    <input type="number" id="tema-horas" min="1" max="10" step="1" value="2" required class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
                </div>

                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Fecha Programada de la Clase</label>
                    <input type="date" id="tema-fecha" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all">
                </div>
            </div>

            <div>
                <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">
                    Indicadores de Logro
                    <span class="text-slate-500 dark:text-slate-400 font-normal lowercase">(escribe uno por línea)</span>
                </label>
                <textarea id="tema-indicadores" rows="3" placeholder="- Reconoce las variables financieras&#10;- Calcula el presupuesto operativo&#10;- Participa activamente en clase" class="w-full p-4 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm sm:text-base font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none transition-all"></textarea>
            </div>

            <!-- Procedimientos e Instrumentos de Evaluación -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                <div>
                    <span class="block text-sm font-extrabold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-3">Procedimientos Evaluativos</span>
                    <div id="container-procedimientos" class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <span class="text-sm text-slate-400">Cargando procedimientos...</span>
                    </div>
                </div>

                <div>
                    <span class="block text-sm font-extrabold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-3">Instrumentos Evaluativos</span>
                    <div id="container-instrumentos" class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <span class="text-sm text-slate-400">Cargando instrumentos...</span>
                    </div>
                </div>
            </div>

            <div class="pt-5 flex justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                <button type="button" class="btn-close-modal min-h-[48px] px-5 py-2.5 rounded-xl text-sm sm:text-base font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer">Cancelar</button>
                <button type="submit" class="min-h-[48px] px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm sm:text-base font-extrabold shadow-sm transition-all cursor-pointer active:scale-95">Guardar Lección</button>
            </div>
        </form>
    </div>
</div>

<!-- ======================================================== -->
<!-- LÓGICA JAVASCRIPT: MASTER-DETAIL LAZY LOADING Y CRUD     -->
<!-- ======================================================== -->
<script>
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', () => {
    // Endpoints
    const API_LISTAR = `${window.BASE_URL}src/api/PlanesAnuales/listar.php`;
    const API_OBTENER = `${window.BASE_URL}src/api/PlanesAnuales/obtener.php`;
    const API_GUARDAR_PLAN = `${window.BASE_URL}src/api/PlanesAnuales/guardar_plan.php`;
    const API_ELIMINAR_PLAN = `${window.BASE_URL}src/api/PlanesAnuales/eliminar_plan.php`;
    const API_GUARDAR_UNIDAD = `${window.BASE_URL}src/api/PlanesAnuales/guardar_unidad.php`;
    const API_ELIMINAR_UNIDAD = `${window.BASE_URL}src/api/PlanesAnuales/eliminar_unidad.php`;
    const API_GUARDAR_CAPACIDAD = `${window.BASE_URL}src/api/PlanesAnuales/guardar_capacidad.php`;
    const API_ELIMINAR_CAPACIDAD = `${window.BASE_URL}src/api/PlanesAnuales/eliminar_capacidad.php`;
    const API_GUARDAR_TEMA = `${window.BASE_URL}src/api/PlanesAnuales/guardar_tema.php`;
    const API_ELIMINAR_TEMA = `${window.BASE_URL}src/api/PlanesAnuales/eliminar_tema.php`;
    const API_MIS_ASIGNACIONES = `${window.BASE_URL}src/api/PlanesAnuales/mis_asignaciones.php`;
    const API_RECURSOS_EVALUACION = `${window.BASE_URL}src/api/PlanesAnuales/recursos_evaluacion.php`;

    // Vistas
    const vistaCatalogo = document.getElementById('vista-catalogo');
    const vistaDetalle = document.getElementById('vista-detalle');
    const gridPlanes = document.getElementById('grid-planes');
    const unidadesContainer = document.getElementById('unidades-container');
    const inputBuscar = document.getElementById('input-buscar-plan');

    // Botones maestros
    const btnVolverCatalogo = document.getElementById('btn-volver-catalogo');
    const breadcrumbCatalogoLink = document.getElementById('breadcrumb-catalogo-link');
    const breadcrumbMateriaActual = document.getElementById('breadcrumb-materia-actual');
    const btnAbrirCrearPlan = document.getElementById('btn-abrir-crear-plan');
    const btnEditarPlanHeader = document.getElementById('btn-editar-plan-header');
    const btnEliminarPlanActual = document.getElementById('btn-eliminar-plan-actual');
    const btnNuevaUnidad = document.getElementById('btn-nueva-unidad');

    // Modales
    const modalPlan = document.getElementById('modal-plan');
    const modalUnidad = document.getElementById('modal-unidad');
    const modalCapacidad = document.getElementById('modal-capacidad');
    const modalTema = document.getElementById('modal-tema');

    // Formularios
    const formPlan = document.getElementById('form-plan');
    const formUnidad = document.getElementById('form-unidad');
    const formCapacidad = document.getElementById('form-capacidad');
    const formTema = document.getElementById('form-tema');

    // Selects dinámicos
    const selectAsignacion = document.getElementById('plan-id-asignacion');
    const selectTemaCapacidad = document.getElementById('tema-id-capacidad');
    const containerProcedimientos = document.getElementById('container-procedimientos');
    const containerInstrumentos = document.getElementById('container-instrumentos');

    // Horas Cátedra live badge
    const inputHoras = document.getElementById('tema-horas');
    const labelMinutos = document.getElementById('label-duracion-minutos');

    inputHoras.addEventListener('input', () => {
        const val = parseFloat(inputHoras.value) || 1;
        labelMinutos.textContent = `(= ${val * 40} min)`;
    });

    // Estados en memoria
    let planesCache = [];
    let planActual = null;
    let recursosCache = null;

    // Toast feedback
    function mostrarToast(mensaje, tipo = 'success') {
        if (window.Toast) {
            if (tipo === 'error') return Toast.error(mensaje);
            if (tipo === 'warning') return Toast.warning(mensaje);
            return Toast.success(mensaje);
        }
        console.log(`[${tipo}] ${mensaje}`);
    }

    // Modal close listeners
    document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            modalPlan.classList.add('hidden');
            modalUnidad.classList.add('hidden');
            modalCapacidad.classList.add('hidden');
            modalTema.classList.add('hidden');
        });
    });

    // Cargar Recursos de Evaluación (procedimientos e instrumentos)
    function cargarRecursosEvaluacion() {
        if (recursosCache) return Promise.resolve(recursosCache);
        return fetch(API_RECURSOS_EVALUACION)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    recursosCache = res.data;
                    renderizarCheckboxesRecursos(res.data);
                    return res.data;
                }
            })
            .catch(e => console.error("Error al cargar recursos de evaluación", e));
    }

    function renderizarCheckboxesRecursos(data) {
        if (data.procedimientos) {
            containerProcedimientos.innerHTML = data.procedimientos.map(p => `
                <label class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-white dark:hover:bg-slate-800 text-sm font-semibold text-slate-800 dark:text-slate-200 cursor-pointer select-none transition-colors">
                    <input type="checkbox" name="procedimientos[]" value="${p.id_procedimiento}" class="w-4 h-4 rounded text-indigo-600 focus:ring-0 border-slate-300 dark:border-slate-600 cursor-pointer">
                    <span>${p.nombre}</span>
                </label>
            `).join('');
        }
        if (data.instrumentos) {
            containerInstrumentos.innerHTML = data.instrumentos.map(i => `
                <label class="flex items-center gap-2.5 p-2 rounded-xl hover:bg-white dark:hover:bg-slate-800 text-sm font-semibold text-slate-800 dark:text-slate-200 cursor-pointer select-none transition-colors">
                    <input type="checkbox" name="instrumentos[]" value="${i.id_instrumento}" class="w-4 h-4 rounded text-indigo-600 focus:ring-0 border-slate-300 dark:border-slate-600 cursor-pointer">
                    <span>${i.nombre}</span>
                </label>
            `).join('');
        }
    }

    // Cargar Asignaciones del Docente para el select del plan
    function cargarAsignacionesDocente(selectedId = null) {
        return fetch(API_MIS_ASIGNACIONES)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    selectAsignacion.innerHTML = '<option value="">Seleccione materia y curso...</option>' +
                        res.data.map(a => `
                            <option value="${a.id_asignacion}" ${selectedId && a.id_asignacion == selectedId ? 'selected' : ''}>
                                ${a.materia_nombre} — ${a.grado_nombre || 'Sin Grado'} (${a.seccion || 'Única'}) [${a.carga_horaria || 2} HC]
                            </option>
                        `).join('');
                } else {
                    selectAsignacion.innerHTML = '<option value="">No hay asignaciones disponibles</option>';
                }
            })
            .catch(e => console.error("Error al cargar asignaciones", e));
    }

    // ==========================================
    // 1. CARGA INICIAL DEL CATÁLOGO DE PLANES
    // ==========================================
    function cargarCatalogoPlanes() {
        gridPlanes.innerHTML = `
            <div class="col-span-full py-16 text-center text-slate-400">
                <div class="animate-spin inline-block w-10 h-10 border-4 border-indigo-500 border-t-transparent rounded-full mb-3"></div>
                <p class="text-base font-semibold">Cargando catálogo de planes anuales...</p>
            </div>
        `;

        fetch(API_LISTAR)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    planesCache = res.data || [];
                    document.getElementById('stat-total-planes').textContent = planesCache.length;
                    
                    const totalTemas = planesCache.reduce((acc, p) => acc + parseInt(p.total_temas || 0), 0);
                    document.getElementById('stat-total-temas').textContent = totalTemas;

                    inicializarFiltrosCascada(planesCache);
                    renderizarCatalogo(planesCache);
                } else {
                    gridPlanes.innerHTML = `<div class="col-span-full p-8 text-center text-rose-500 font-semibold">${res.message}</div>`;
                }
            })
            .catch(err => {
                gridPlanes.innerHTML = `<div class="col-span-full p-8 text-center text-rose-500 font-semibold">Error de conexión al cargar planes: ${err.message}</div>`;
            });
    }

    const selPlanTurno = document.getElementById('filtro-plan-turno');
    const selPlanNivel = document.getElementById('filtro-plan-nivel');
    const selPlanBach = document.getElementById('filtro-plan-bach');
    const selPlanGrado = document.getElementById('filtro-plan-grado');

    function inicializarFiltrosCascada(planes) {
        if (!selPlanTurno || !selPlanNivel || !selPlanBach || !selPlanGrado) return;

        const turnos = [...new Set(planes.map(p => p.turno_nombre || p.turno || p.turno_fuente || '').filter(Boolean))].sort();
        selPlanTurno.innerHTML = '<option value="">Todos los turnos</option>' +
            turnos.map(t => `<option value="${t}">${t}</option>`).join('');

        const niveles = [...new Set(planes.map(p => p.nivel_nombre || '').filter(Boolean))].sort();
        selPlanNivel.innerHTML = '<option value="">Todos los ciclos</option>' +
            niveles.map(n => `<option value="${n}">${n}</option>`).join('');

        const bachilleratos = [...new Set(planes.map(p => p.bachillerato_nombre || '').filter(Boolean))].sort();
        selPlanBach.innerHTML = '<option value="">Todos / General</option>' +
            bachilleratos.map(b => `<option value="${b}">${b}</option>`).join('');

        const grados = [...new Set(planes.map(p => p.grado_nombre || p.curso_fuente || '').filter(Boolean))].sort();
        selPlanGrado.innerHTML = '<option value="">Todos los grados</option>' +
            grados.map(g => `<option value="${g}">${g}</option>`).join('');
    }

    function aplicarFiltrosCatalogo() {
        const turnoVal = (selPlanTurno ? selPlanTurno.value : '').toLowerCase().trim();
        const nivelVal = (selPlanNivel ? selPlanNivel.value : '').toLowerCase().trim();
        const bachVal = (selPlanBach ? selPlanBach.value : '').toLowerCase().trim();
        const gradoVal = (selPlanGrado ? selPlanGrado.value : '').toLowerCase().trim();
        const queryVal = (inputBuscar ? inputBuscar.value : '').toLowerCase().trim();

        const filtrados = planesCache.filter(p => {
            const pTurno = (p.turno_nombre || p.turno || p.turno_fuente || '').toLowerCase();
            const pNivel = (p.nivel_nombre || '').toLowerCase();
            const pBach = (p.bachillerato_nombre || '').toLowerCase();
            const pGrado = (p.grado_nombre || p.curso_fuente || '').toLowerCase();
            const pMateria = (p.materia_nombre || p.materia_fuente || '').toLowerCase();
            const pProf = (p.profesor_nombre ? `${p.profesor_nombre} ${p.profesor_apellido}` : (p.profesor_fuente || '')).toLowerCase();
            const pAnio = (p.anio || '').toString();

            const matchTurno = !turnoVal || pTurno === turnoVal;
            const matchNivel = !nivelVal || pNivel === nivelVal;
            const matchBach = !bachVal || pBach === bachVal;
            const matchGrado = !gradoVal || pGrado === gradoVal;
            const matchQuery = !queryVal || pMateria.includes(queryVal) || pGrado.includes(queryVal) || pProf.includes(queryVal) || pAnio.includes(queryVal);

            return matchTurno && matchNivel && matchBach && matchGrado && matchQuery;
        });

        renderizarCatalogo(filtrados);
    }

    if (selPlanTurno) selPlanTurno.addEventListener('change', aplicarFiltrosCatalogo);
    if (selPlanNivel) selPlanNivel.addEventListener('change', aplicarFiltrosCatalogo);
    if (selPlanBach) selPlanBach.addEventListener('change', aplicarFiltrosCatalogo);
    if (selPlanGrado) selPlanGrado.addEventListener('change', aplicarFiltrosCatalogo);

    function renderizarCatalogo(planes) {
        if (!planes || planes.length === 0) {
            gridPlanes.innerHTML = `
                <div class="col-span-full py-16 px-6 text-center bg-white dark:bg-slate-900 rounded-3xl border-2 border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-5xl block mb-4">📋</span>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-800 dark:text-white">No hay planes anuales que coincidan con la búsqueda</h3>
                    <p class="text-base text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto">
                        Verificá los filtros seleccionados o registrá un nuevo plan curricular para tu materia.
                    </p>
                    <button type="button" onclick="document.getElementById('btn-abrir-crear-plan').click()" class="mt-6 min-h-[48px] px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-base font-extrabold shadow-sm transition-all hover:shadow-md inline-flex items-center gap-2 cursor-pointer active:scale-95">
                        <span>➕</span>
                        <span>Crear Plan Anual</span>
                    </button>
                </div>
            `;
            return;
        }

        gridPlanes.innerHTML = planes.map(p => {
            const materia = p.materia_nombre || p.materia_fuente || 'Materia sin definir';
            const curso = p.grado_nombre || p.curso_fuente || 'Curso no especificado';
            const profesor = p.profesor_nombre ? `${p.profesor_nombre} ${p.profesor_apellido}` : (p.profesor_fuente || 'Docente asignado');
            const turnoRaw = p.turno_nombre || p.turno_fuente || p.turno || 'Sin turno';
            const turno = turnoRaw.charAt(0).toUpperCase() + turnoRaw.slice(1).toLowerCase();
            const temas = parseInt(p.total_temas || 0);

            return `
                <div class="bg-white dark:bg-slate-900 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 shadow-sm hover:shadow-xl hover:border-indigo-400 dark:hover:border-indigo-500 transition-all p-6 sm:p-7 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <!-- Nombre de la Materia -->
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white leading-snug hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors cursor-pointer" onclick="window.abrirPlanDetalle(${p.id_plan})" title="Clic para abrir el plan">
                            ${escapeHtml(materia)}
                        </h3>

                        <!-- Grilla 2x2: Grado/Curso (1:1), Turno (1:2), Profesor (2:1), Cantidad de contenidos (2:2) -->
                        <div class="grid grid-cols-2 gap-x-4 gap-y-3.5 pt-3 border-t border-slate-100 dark:border-slate-800 text-base">
                            <!-- 1:1 Grado / Curso -->
                            <div class="flex items-center gap-2.5 font-bold text-indigo-700 dark:text-indigo-300 min-w-0">
                                <span class="text-xl shrink-0" aria-hidden="true">🎓</span>
                                <span class="truncate" title="${escapeHtml(curso)}">${escapeHtml(curso)}</span>
                            </div>

                            <!-- 1:2 Turno -->
                            <div class="flex items-center gap-2.5 font-semibold text-slate-700 dark:text-slate-300 min-w-0">
                                <span class="text-xl shrink-0" aria-hidden="true">⏰</span>
                                <span class="truncate" title="${escapeHtml(turno)}">${escapeHtml(turno)}</span>
                            </div>

                            <!-- 2:1 Profesor -->
                            <div class="flex items-center gap-2.5 font-semibold text-slate-700 dark:text-slate-300 min-w-0">
                                <span class="text-xl shrink-0" aria-hidden="true">👨‍🏫</span>
                                <span class="truncate" title="${escapeHtml(profesor)}">${escapeHtml(profesor)}</span>
                            </div>

                            <!-- 2:2 Cantidad de Contenidos -->
                            <div class="flex items-center gap-2.5 font-bold text-slate-800 dark:text-slate-200 min-w-0">
                                <span class="text-xl shrink-0" aria-hidden="true">📚</span>
                                <span class="truncate">${temas} ${temas === 1 ? 'lección' : 'lecciones'}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción: Abrir plan al 100% de ancho, Editar y Eliminar al 50% debajo -->
                    <div class="pt-5 border-t border-slate-100 dark:border-slate-800 flex flex-col gap-3">
                        <button type="button" onclick="window.abrirPlanDetalle(${p.id_plan})" class="w-full min-h-[46px] py-2.5 px-5 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white text-base font-extrabold rounded-xl shadow-sm transition-all hover:shadow-md text-center inline-flex items-center justify-center gap-2 cursor-pointer">
                            <span>📖</span>
                            <span>Abrir plan</span>
                        </button>
                        <div class="grid grid-cols-2 gap-3 w-full">
                            <button type="button" onclick="window.abrirEditarPlanHeader(${p.id_plan})" class="w-full min-h-[44px] py-2 px-3 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 border-2 border-slate-200 dark:border-slate-700 text-sm sm:text-base font-bold rounded-xl transition-all inline-flex items-center justify-center gap-2 cursor-pointer" title="Editar encabezado del plan">
                                <span>✏️</span>
                                <span>Editar</span>
                            </button>
                            <button type="button" onclick="window.confirmarEliminarPlan(${p.id_plan})" class="w-full min-h-[44px] py-2 px-3 bg-rose-50 hover:bg-rose-100 active:bg-rose-200 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border-2 border-rose-200 dark:border-rose-800 text-sm sm:text-base font-bold rounded-xl transition-all inline-flex items-center justify-center gap-2 cursor-pointer" title="Eliminar plan anual">
                                <span>🗑️</span>
                                <span>Eliminar</span>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Buscador interactivo
    inputBuscar.addEventListener('input', aplicarFiltrosCatalogo);

    // ==========================================
    // 2. VISTA DE DETALLE / LAZY LOADING PEDAGÓGICO
    // ==========================================
    window.abrirPlanDetalle = function(idPlan) {
        vistaCatalogo.classList.add('hidden');
        vistaDetalle.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });

        unidadesContainer.innerHTML = `
            <div class="rounded-3xl border-2 border-indigo-200 dark:border-indigo-900/50 bg-gradient-to-b from-indigo-50/60 to-white dark:from-slate-900/90 dark:to-slate-900 p-12 sm:p-16 text-center my-6 shadow-sm">
                <div class="relative w-20 h-20 mx-auto mb-5 flex items-center justify-center">
                    <div class="absolute inset-0 rounded-full bg-indigo-400/20 dark:bg-indigo-500/20 animate-ping"></div>
                    <div class="w-16 h-16 rounded-full border-4 border-indigo-200 dark:border-indigo-800 border-t-indigo-600 dark:border-t-indigo-400 animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-2xl">
                        📖
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                    Cargando Árbol Curricular Completo...
                </h3>
                <p class="text-base text-slate-600 dark:text-slate-400 max-w-md mx-auto mt-2 leading-relaxed font-medium">
                    Sincronizando unidades temáticas, capacidades pedagógicas del MEC e indicadores de evaluación.
                </p>
                <div class="max-w-xs mx-auto mt-5 bg-slate-200 dark:bg-slate-800 h-2 rounded-full overflow-hidden shadow-inner">
                    <div class="h-full bg-gradient-to-r from-indigo-500 via-sky-400 to-emerald-400 rounded-full animate-pulse w-3/4"></div>
                </div>
            </div>
        `;

        fetch(`${API_OBTENER}?id_plan=${idPlan}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    planActual = res.data;
                    renderizarDetallePlan(res.data);
                } else {
                    unidadesContainer.innerHTML = `<div class="p-8 text-center text-rose-500 font-semibold text-base">${res.message}</div>`;
                }
            })
            .catch(err => {
                unidadesContainer.innerHTML = `<div class="p-8 text-center text-rose-500 font-semibold text-base">Error al obtener detalle del plan: ${err.message}</div>`;
            });
    };

    function renderizarDetallePlan(plan) {
        const materiaCurso = `${plan.materia_nombre || plan.materia_fuente || 'Materia'} — ${plan.grado_nombre || plan.curso_fuente || 'Curso'}`;
        
        // Actualizar breadcrumb
        if (breadcrumbMateriaActual) {
            breadcrumbMateriaActual.textContent = plan.materia_nombre || 'Detalle Curricular';
        }

        // Cabecera institucional
        document.getElementById('det-institucion').textContent = plan.institucion_fuente || 'COLEGIO PRIV. PRESB. SAN PABLO';
        document.getElementById('det-materia-curso').textContent = materiaCurso;
        document.getElementById('det-profesor').textContent = plan.profesor_nombre ? `${plan.profesor_nombre} ${plan.profesor_apellido}` : (plan.profesor_fuente || '-');

        const cargaHC = plan.carga_horaria || 2;
        const duracionMin = cargaHC * 40;
        document.getElementById('det-carga-turno').textContent = `${cargaHC} HC (${duracionMin} min) • Turno ${plan.turno_fuente || 'MAÑANA'}`;

        document.getElementById('det-comp-general').textContent = plan.competencia_general || 'Competencia general orientada a la formación integral técnica y académica según el perfil de egreso del MEC.';
        document.getElementById('det-comp-especifica').textContent = plan.competencia_especifica || 'Aplica los conceptos, procedimientos e instrumentos correspondientes al ámbito de estudio y especialidad.';

        const obsWrapper = document.getElementById('det-obs-wrapper');
        if (plan.observaciones) {
            obsWrapper.classList.remove('hidden');
            document.getElementById('det-observaciones').textContent = plan.observaciones;
        } else {
            obsWrapper.classList.add('hidden');
        }

        // Llenar selector de capacidades para el modal de nuevo tema
        selectTemaCapacidad.innerHTML = '<option value="">Seleccione capacidad...</option>';
        if (plan.unidades) {
            plan.unidades.forEach(u => {
                if (u.capacidades) {
                    u.capacidades.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id_capacidad;
                        opt.textContent = `${u.nombre ? u.nombre + ': ' : ''}${c.descripcion.substring(0, 75)}...`;
                        selectTemaCapacidad.appendChild(opt);
                    });
                }
            });
        }

        // Renderizar Unidades
        if (!plan.unidades || plan.unidades.length === 0) {
            unidadesContainer.innerHTML = `
                <div class="p-12 sm:p-16 text-center text-slate-500 bg-white dark:bg-slate-900 rounded-3xl border-2 border-dashed border-slate-300 dark:border-slate-800 shadow-sm">
                    <span class="text-5xl block mb-4">📖</span>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">El plan no tiene unidades temáticas registradas</h3>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-2 max-w-md mx-auto leading-relaxed">
                        Comienza agregando la primera unidad temática del programa curricular (ej: Unidad I: Fundamentos y Conceptos).
                    </p>
                    <button type="button" onclick="window.abrirModalNuevaUnidad()" class="mt-6 min-h-[48px] px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-base font-extrabold shadow-sm transition-all hover:shadow-md inline-flex items-center gap-2 cursor-pointer active:scale-95">
                        <span>➕</span>
                        <span>Agregar Unidad Temática</span>
                    </button>
                </div>
            `;
            return;
        }

        let html = '';
        plan.unidades.forEach(u => {
            html += `
                <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-sm border-2 border-slate-200/90 dark:border-slate-800 overflow-hidden mb-6">
                    <!-- Cabecera de la Unidad -->
                    <div class="bg-gradient-to-r from-slate-50 via-indigo-50/30 to-white dark:from-slate-800/80 dark:to-slate-900 p-5 sm:p-6 border-b-2 border-slate-200/90 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <span class="text-xs font-black text-indigo-700 dark:text-indigo-400 uppercase tracking-wider block mb-1">Unidad Temática</span>
                            <h3 class="text-lg sm:text-xl font-black text-slate-900 dark:text-white leading-tight">${escapeHtml(u.nombre || 'Unidad de Aprendizaje')}</h3>
                            ${u.tiempo_texto ? `<span class="inline-flex items-center gap-1.5 text-sm text-slate-600 dark:text-slate-300 font-bold mt-1.5"><span>📅</span> Período estimado: ${escapeHtml(u.tiempo_texto)}</span>` : ''}
                        </div>

                        <div class="flex items-center gap-2.5 self-start sm:self-center shrink-0">
                            <button type="button" onclick="window.abrirModalNuevaCapacidad(${u.id_unidad})" class="min-h-[40px] px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-extrabold rounded-xl shadow-2xs transition-all flex items-center gap-1.5 cursor-pointer active:scale-95">
                                <span>➕</span> Capacidad MEC
                            </button>
                            <button type="button" onclick="window.abrirModalEditarUnidad(${u.id_unidad})" title="Editar Unidad" class="min-h-[40px] px-3.5 py-2 bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-sm font-bold transition-colors cursor-pointer flex items-center gap-1">
                                <span>✏️</span> Editar
                            </button>
                            <button type="button" onclick="window.confirmarEliminarUnidad(${u.id_unidad})" title="Eliminar Unidad" class="min-h-[40px] px-3.5 py-2 bg-rose-50 dark:bg-rose-950/40 border-2 border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 rounded-xl text-sm font-bold transition-colors cursor-pointer flex items-center gap-1">
                                <span>🗑️</span>
                            </button>
                        </div>
                    </div>

                    <!-- Capacidades dentro de la Unidad -->
                    <div class="p-6 sm:p-7 space-y-6">
            `;

            if (u.capacidades && u.capacidades.length > 0) {
                u.capacidades.forEach(c => {
                    html += `
                        <div class="border-l-4 border-indigo-600 dark:border-indigo-400 pl-5 space-y-4 bg-slate-50/70 dark:bg-slate-800/40 p-5 sm:p-6 rounded-r-3xl border-y border-r border-slate-200/80 dark:border-slate-700/80">
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                <div class="max-w-2xl">
                                    <span class="text-xs font-black text-indigo-700 dark:text-indigo-400 uppercase tracking-wider block mb-1">Capacidad Pedagógica MEC</span>
                                    <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white leading-relaxed">${escapeHtml(c.descripcion)}</h4>
                                </div>
                                <div class="flex items-center gap-2 self-start sm:self-center shrink-0">
                                    <button type="button" onclick="window.abrirModalNuevoTema(${c.id_capacidad})" class="min-h-[40px] px-4 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-extrabold rounded-xl shadow-2xs transition-all flex items-center gap-1.5 cursor-pointer active:scale-95">
                                        <span>➕</span> Lección
                                    </button>
                                    <button type="button" onclick="window.abrirModalEditarCapacidad(${c.id_capacidad})" title="Editar Capacidad" class="min-h-[40px] px-3 py-1.5 bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-sm font-bold transition-colors cursor-pointer flex items-center gap-1">
                                        <span>✏️</span>
                                    </button>
                                    <button type="button" onclick="window.confirmarEliminarCapacidad(${c.id_capacidad})" title="Eliminar Capacidad" class="min-h-[40px] px-3 py-1.5 bg-rose-50 dark:bg-rose-950/40 border-2 border-rose-200 dark:border-rose-800 hover:bg-rose-100 text-rose-700 dark:text-rose-300 rounded-xl text-sm font-bold transition-colors cursor-pointer flex items-center gap-1">
                                        <span>🗑️</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Tabla de temas/lecciones -->
                            <div class="overflow-x-auto rounded-2xl border-2 border-slate-200/90 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
                                <table class="w-full text-left">
                                    <thead class="bg-slate-100/70 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 border-b-2 border-slate-200 dark:border-slate-700">
                                        <tr>
                                            <th class="py-3.5 px-4 font-black text-sm uppercase tracking-wider">Tema / Contenido</th>
                                            <th class="py-3.5 px-4 font-black text-sm uppercase tracking-wider w-36">Duración</th>
                                            <th class="py-3.5 px-4 font-black text-sm uppercase tracking-wider w-40">Fecha Clase</th>
                                            <th class="py-3.5 px-4 font-black text-sm uppercase tracking-wider">Indicadores de Logro</th>
                                            <th class="py-3.5 px-4 font-black text-sm uppercase tracking-wider w-44">Evaluación</th>
                                            <th class="py-3.5 px-4 font-black text-sm uppercase tracking-wider w-28 text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-base">
                    `;

                    if (c.temas && c.temas.length > 0) {
                        c.temas.forEach(t => {
                            const hc = parseFloat(t.horas_catedra || 1);
                            const min = hc * 40;
                            const fecha = t.fecha_inicio ? t.fecha_inicio : 'Por definir';

                            const indHtml = t.indicadores && t.indicadores.length > 0
                                ? `<ul class="list-disc pl-5 space-y-1 text-slate-700 dark:text-slate-300 font-medium">${t.indicadores.map(i => `<li>${escapeHtml(i.descripcion || i)}</li>`).join('')}</ul>`
                                : '<span class="text-slate-400 italic text-sm">Sin indicadores cargados</span>';

                            const evalHtml = [
                                ...(t.procedimientos || []),
                                ...(t.instrumentos || [])
                            ].map(item => `<span class="inline-block px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-lg text-xs sm:text-sm font-bold mr-1.5 mb-1.5 shadow-2xs">${escapeHtml(item)}</span>`).join('') || '<span class="text-slate-400 italic text-sm">Por definir</span>';

                            html += `
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="p-4 font-bold text-slate-900 dark:text-white leading-snug">${escapeHtml(t.titulo)}</td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-sm font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                            ${hc} HC (${min}m)
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-700 dark:text-slate-300 font-mono font-bold text-sm sm:text-base">
                                        📅 ${escapeHtml(fecha)}
                                    </td>
                                    <td class="p-4">${indHtml}</td>
                                    <td class="p-4">${evalHtml}</td>
                                    <td class="p-4 text-center">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" onclick="window.abrirModalEditarTema(${t.id_tema})" title="Editar Lección" class="min-h-[38px] px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold transition-colors cursor-pointer flex items-center gap-1">
                                                <span>✏️</span>
                                            </button>
                                            <button type="button" onclick="window.confirmarEliminarTema(${t.id_tema})" title="Eliminar Lección" class="min-h-[38px] px-3 py-1.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 rounded-xl text-sm font-bold transition-colors cursor-pointer flex items-center gap-1">
                                                <span>🗑️</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        html += `<tr><td colspan="6" class="p-6 text-center text-slate-400 italic text-base">No hay temas programados para esta capacidad aún. Haz clic en "+ Lección" para programar.</td></tr>`;
                    }

                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                });
            } else {
                html += `
                    <div class="p-8 text-center text-slate-500 bg-slate-50/70 dark:bg-slate-800/40 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700">
                        <p class="text-base font-semibold">No hay capacidades pedagógicas registradas en esta unidad temática.</p>
                        <button type="button" onclick="window.abrirModalNuevaCapacidad(${u.id_unidad})" class="mt-3 min-h-[44px] px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-sm font-extrabold shadow-sm transition-all inline-flex items-center gap-1.5 cursor-pointer">
                            <span>➕</span> Agregar Capacidad MEC
                        </button>
                    </div>
                `;
            }

            html += `
                    </div>
                </div>
            `;
        });

        unidadesContainer.innerHTML = html;
    }

    // Volver al Catálogo
    btnVolverCatalogo.addEventListener('click', () => {
        vistaDetalle.classList.add('hidden');
        vistaCatalogo.classList.remove('hidden');
        cargarCatalogoPlanes();
    });

    if (breadcrumbCatalogoLink) {
        breadcrumbCatalogoLink.addEventListener('click', (e) => {
            e.preventDefault();
            btnVolverCatalogo.click();
        });
    }

    // ==========================================
    // 3. CRUD DE PLANES ANUALES (CABECERA)
    // ==========================================
    btnAbrirCrearPlan.addEventListener('click', () => {
        document.getElementById('modal-plan-title').textContent = 'Crear Nuevo Plan Anual';
        formPlan.reset();
        document.getElementById('plan-id').value = '';
        document.getElementById('plan-anio').value = '2026';
        cargarAsignacionesDocente().then(() => {
            modalPlan.classList.remove('hidden');
        });
    });

    window.abrirEditarPlanHeader = function(idPlan) {
        const plan = (planActual && planActual.id_plan == idPlan) ? planActual : planesCache.find(p => p.id_plan == idPlan);
        if (!plan) return;

        document.getElementById('modal-plan-title').textContent = 'Editar Encabezado del Plan';
        document.getElementById('plan-id').value = plan.id_plan;
        document.getElementById('plan-anio').value = plan.anio || 2026;
        document.getElementById('plan-turno').value = plan.turno_fuente || 'MAÑANA';
        document.getElementById('plan-institucion').value = plan.institucion_fuente || 'COLEGIO PRIV. PRESB. SAN PABLO';
        document.getElementById('plan-comp-general').value = plan.competencia_general || '';
        document.getElementById('plan-comp-especifica').value = plan.competencia_especifica || '';
        document.getElementById('plan-observaciones').value = plan.observaciones || '';

        cargarAsignacionesDocente(plan.id_asignacion).then(() => {
            modalPlan.classList.remove('hidden');
        });
    };

    btnEditarPlanHeader.addEventListener('click', () => {
        if (planActual) window.abrirEditarPlanHeader(planActual.id_plan);
    });

    formPlan.addEventListener('submit', (e) => {
        e.preventDefault();
        const payload = {
            id_plan: document.getElementById('plan-id').value || null,
            id_asignacion: parseInt(selectAsignacion.value),
            anio: parseInt(document.getElementById('plan-anio').value),
            turno_fuente: document.getElementById('plan-turno').value,
            institucion_fuente: document.getElementById('plan-institucion').value.trim(),
            competencia_general: document.getElementById('plan-comp-general').value.trim(),
            competencia_especifica: document.getElementById('plan-comp-especifica').value.trim(),
            observaciones: document.getElementById('plan-observaciones').value.trim()
        };

        fetch(API_GUARDAR_PLAN, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                modalPlan.classList.add('hidden');
                mostrarToast(res.message);
                if (planActual && planActual.id_plan == payload.id_plan) {
                    window.abrirPlanDetalle(planActual.id_plan);
                } else {
                    cargarCatalogoPlanes();
                }
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    });

    window.confirmarEliminarPlan = async function(idPlan) {
        const confirmado = await SISCO.confirm({
            title: '¿Eliminar Plan Anual?',
            message: '¿Estás seguro de eliminar este plan anual completo junto con todas sus unidades, capacidades y temas? Esta acción no se puede deshacer.',
            confirmText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger'
        });
        if (!confirmado) return;

        fetch(API_ELIMINAR_PLAN, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_plan: idPlan })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                mostrarToast(res.message);
                if (!vistaDetalle.classList.contains('hidden')) {
                    btnVolverCatalogo.click();
                } else {
                    cargarCatalogoPlanes();
                }
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    };

    btnEliminarPlanActual.addEventListener('click', () => {
        if (planActual) window.confirmarEliminarPlan(planActual.id_plan);
    });

    // ==========================================
    // 4. CRUD DE UNIDADES TEMÁTICAS
    // ==========================================
    window.abrirModalNuevaUnidad = function() {
        if (!planActual) return;
        document.getElementById('modal-unidad-title').textContent = 'Nueva Unidad Temática';
        formUnidad.reset();
        document.getElementById('unidad-id').value = '';
        const siguienteOrden = (planActual.unidades ? planActual.unidades.length : 0) + 1;
        document.getElementById('unidad-orden').value = siguienteOrden;
        document.getElementById('unidad-nombre').value = `Unidad ${siguienteOrden}: `;
        modalUnidad.classList.remove('hidden');
    };

    btnNuevaUnidad.addEventListener('click', () => {
        window.abrirModalNuevaUnidad();
    });

    window.abrirModalEditarUnidad = function(idUnidad) {
        if (!planActual || !planActual.unidades) return;
        const uni = planActual.unidades.find(u => u.id_unidad == idUnidad);
        if (!uni) return;

        document.getElementById('modal-unidad-title').textContent = 'Editar Unidad Temática';
        document.getElementById('unidad-id').value = uni.id_unidad;
        document.getElementById('unidad-nombre').value = uni.nombre || '';
        document.getElementById('unidad-tiempo').value = uni.tiempo_texto || '';
        document.getElementById('unidad-orden').value = uni.orden || 1;
        modalUnidad.classList.remove('hidden');
    };

    formUnidad.addEventListener('submit', (e) => {
        e.preventDefault();
        const payload = {
            id_plan: planActual.id_plan,
            id_unidad: document.getElementById('unidad-id').value || null,
            nombre: document.getElementById('unidad-nombre').value.trim(),
            tiempo_texto: document.getElementById('unidad-tiempo').value.trim(),
            orden: parseInt(document.getElementById('unidad-orden').value) || 1
        };

        fetch(API_GUARDAR_UNIDAD, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                modalUnidad.classList.add('hidden');
                mostrarToast(res.message);
                window.abrirPlanDetalle(planActual.id_plan);
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    });

    window.confirmarEliminarUnidad = async function(idUnidad) {
        const confirmado = await SISCO.confirm({
            title: '¿Eliminar Unidad Temática?',
            message: '¿Eliminar esta unidad temática y todas sus capacidades y temas asociados?',
            confirmText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger'
        });
        if (!confirmado) return;
        fetch(API_ELIMINAR_UNIDAD, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_unidad: idUnidad })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                mostrarToast(res.message);
                window.abrirPlanDetalle(planActual.id_plan);
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    };

    // ==========================================
    // 5. CRUD DE CAPACIDADES MEC
    // ==========================================
    window.abrirModalNuevaCapacidad = function(idUnidad) {
        formCapacidad.reset();
        document.getElementById('modal-capacidad-title').textContent = 'Nueva Capacidad MEC';
        document.getElementById('capacidad-id').value = '';
        document.getElementById('capacidad-id-unidad').value = idUnidad;
        modalCapacidad.classList.remove('hidden');
    };

    window.abrirModalEditarCapacidad = function(idCapacidad) {
        if (!planActual || !planActual.unidades) return;
        let capEncontrada = null;
        let unidadPadre = null;
        for (const u of planActual.unidades) {
            const c = (u.capacidades || []).find(x => x.id_capacidad == idCapacidad);
            if (c) {
                capEncontrada = c;
                unidadPadre = u;
                break;
            }
        }
        if (!capEncontrada) return;

        document.getElementById('modal-capacidad-title').textContent = 'Editar Capacidad MEC';
        document.getElementById('capacidad-id').value = capEncontrada.id_capacidad;
        document.getElementById('capacidad-id-unidad').value = unidadPadre.id_unidad;
        document.getElementById('capacidad-desc').value = capEncontrada.descripcion || '';
        document.getElementById('capacidad-orden').value = capEncontrada.orden || 1;
        modalCapacidad.classList.remove('hidden');
    };

    formCapacidad.addEventListener('submit', (e) => {
        e.preventDefault();
        const payload = {
            id_unidad: parseInt(document.getElementById('capacidad-id-unidad').value),
            id_capacidad: document.getElementById('capacidad-id').value || null,
            descripcion: document.getElementById('capacidad-desc').value.trim(),
            orden: parseInt(document.getElementById('capacidad-orden').value) || 1
        };

        fetch(API_GUARDAR_CAPACIDAD, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                modalCapacidad.classList.add('hidden');
                mostrarToast(res.message);
                window.abrirPlanDetalle(planActual.id_plan);
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    });

    window.confirmarEliminarCapacidad = async function(idCapacidad) {
        const confirmado = await SISCO.confirm({
            title: '¿Eliminar Capacidad?',
            message: '¿Eliminar esta capacidad MEC y todas sus lecciones programadas?',
            confirmText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger'
        });
        if (!confirmado) return;
        fetch(API_ELIMINAR_CAPACIDAD, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_capacidad: idCapacidad })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                mostrarToast(res.message);
                window.abrirPlanDetalle(planActual.id_plan);
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    };

    // ==========================================
    // 6. CRUD DE TEMAS / LECCIONES Y EVALUACIÓN
    // ==========================================
    window.abrirModalNuevoTema = function(idCapacidadPreseleccionada = null) {
        formTema.reset();
        document.getElementById('modal-tema-title').textContent = 'Planificar Nueva Lección / Tema';
        document.getElementById('tema-id').value = '';
        document.getElementById('tema-horas').value = '2';
        labelMinutos.textContent = '(= 80 min)';
        document.getElementById('tema-fecha').value = new Date().toISOString().substring(0, 10);

        if (idCapacidadPreseleccionada) {
            selectTemaCapacidad.value = idCapacidadPreseleccionada;
        }

        // Desmarcar todos los checkboxes de procedimientos e instrumentos
        containerProcedimientos.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);
        containerInstrumentos.querySelectorAll('input[type="checkbox"]').forEach(c => c.checked = false);

        modalTema.classList.remove('hidden');
    };

    window.abrirModalEditarTema = function(idTema) {
        if (!planActual || !planActual.unidades) return;
        let temaEncontrado = null;
        for (const u of planActual.unidades) {
            for (const c of (u.capacidades || [])) {
                const t = (c.temas || []).find(x => x.id_tema == idTema);
                if (t) {
                    temaEncontrado = t;
                    break;
                }
            }
            if (temaEncontrado) break;
        }
        if (!temaEncontrado) return;

        document.getElementById('modal-tema-title').textContent = 'Editar Lección / Tema Pedagógico';
        document.getElementById('tema-id').value = temaEncontrado.id_tema;
        selectTemaCapacidad.value = temaEncontrado.id_capacidad;
        document.getElementById('tema-titulo').value = temaEncontrado.titulo || '';

        const hc = parseFloat(temaEncontrado.horas_catedra || 1);
        document.getElementById('tema-horas').value = hc;
        labelMinutos.textContent = `(= ${hc * 40} min)`;

        document.getElementById('tema-fecha').value = temaEncontrado.fecha_inicio || '';

        // Indicadores
        const indList = (temaEncontrado.indicadores || []).map(i => i.descripcion || i).join('\n');
        document.getElementById('tema-indicadores').value = indList;

        // Marcar checkboxes de procedimientos
        const procIds = (temaEncontrado.procedimientos_ids || []).map(String);
        containerProcedimientos.querySelectorAll('input[type="checkbox"]').forEach(c => {
            c.checked = procIds.includes(c.value);
        });

        // Marcar checkboxes de instrumentos
        const instIds = (temaEncontrado.instrumentos_ids || []).map(String);
        containerInstrumentos.querySelectorAll('input[type="checkbox"]').forEach(c => {
            c.checked = instIds.includes(c.value);
        });

        modalTema.classList.remove('hidden');
    };

    formTema.addEventListener('submit', (e) => {
        e.preventDefault();
        const indRaw = document.getElementById('tema-indicadores').value;
        const indicadores = indRaw.split('\n').map(s => s.replace(/^[-*•]\s*/, '').trim()).filter(s => s.length > 0);

        const procsSeleccionados = Array.from(containerProcedimientos.querySelectorAll('input[type="checkbox"]:checked')).map(c => parseInt(c.value));
        const instsSeleccionados = Array.from(containerInstrumentos.querySelectorAll('input[type="checkbox"]:checked')).map(c => parseInt(c.value));

        const payload = {
            id_capacidad: parseInt(selectTemaCapacidad.value),
            id_tema: document.getElementById('tema-id').value || null,
            titulo: document.getElementById('tema-titulo').value.trim(),
            horas_catedra: parseFloat(document.getElementById('tema-horas').value) || 1,
            fecha_inicio: document.getElementById('tema-fecha').value || null,
            fecha_fin: document.getElementById('tema-fecha').value || null,
            indicadores: indicadores,
            procedimientos: procsSeleccionados,
            instrumentos: instsSeleccionados
        };

        fetch(API_GUARDAR_TEMA, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                modalTema.classList.add('hidden');
                mostrarToast(res.message);
                window.abrirPlanDetalle(planActual.id_plan);
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    });

    window.confirmarEliminarTema = async function(idTema) {
        const confirmado = await SISCO.confirm({
            title: '¿Eliminar Lección?',
            message: '¿Eliminar este tema pedagógico y sus indicadores programados?',
            confirmText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger'
        });
        if (!confirmado) return;
        fetch(API_ELIMINAR_TEMA, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_tema: idTema })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                mostrarToast(res.message);
                window.abrirPlanDetalle(planActual.id_plan);
            } else {
                mostrarToast(res.message, 'error');
            }
        })
        .catch(e => mostrarToast(e.message, 'error'));
    };

    // Inicialización: cargar recursos y catálogo
    cargarRecursosEvaluacion();
    cargarCatalogoPlanes();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
