<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$rolUsuario = $_SESSION['rol'] ?? '';
$rolesAdmin = ['SuperAdmin', 'Administrador', 'Administracion', 'admin'];
$rolesOperativos = ['Profesor', 'Coordinador'];

$esAdmin = in_array($rolUsuario, $rolesAdmin, true) || in_array(strtolower($rolUsuario), ['superadmin', 'administrador', 'administracion', 'admin'], true);
$esOperativo = in_array($rolUsuario, $rolesOperativos, true) || in_array(strtolower($rolUsuario), ['profesor', 'coordinador'], true);
$puedeMarcarManual = $esAdmin || $esOperativo;
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10 overflow-y-auto">
    <!-- Barra Superior -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40 animate-pulse">
                    ● EN VIVO
                </span>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Panel de Clase y Asistencia Biométrica</h2>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Monitoreo en tiempo real de huellas dactilares, lección pedagógica del día y lista de presentes/ausentes.
            </p>
            <div class="flex items-center gap-2 mt-2">
                <?php if ($esAdmin): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/40 shadow-2xs">
                        <span>🛡️</span> Modo Administrador: Marcación Manual y Rectificación Habilitada
                    </span>
                <?php elseif ($puedeMarcarManual): ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40 shadow-2xs">
                        <span>✍️</span> Modo Docente / Coordinador: Marcación Manual Habilitada (Modificaciones reservadas a Administradores)
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40 shadow-2xs">
                        <span>👁️</span> Modo Observador: Solo lectura
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Controles de Estado y Hora Institucional -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 bg-white dark:bg-slate-900 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm text-xs font-mono text-slate-700 dark:text-slate-300">
                <span>📅</span>
                <span id="label-servidor-tiempo">Sincronizando hora...</span>
            </div>

            <!-- Indicador Reactivo Supabase Realtime (WebSockets) -->
            <div id="badge-realtime-status" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shadow-2xs transition-all">
                <span id="dot-realtime" class="w-2 h-2 rounded-full bg-slate-400"></span>
                <span id="text-realtime">⚡ Realtime: Inicializando...</span>
            </div>

            <!-- Botón de Actualización Manual bajo demanda (Cero consultas fantasma) -->
            <button id="btn-refrescar-manual" type="button" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 rounded-lg text-xs font-semibold border border-indigo-200 dark:border-indigo-800/40 shadow-2xs transition-all flex items-center gap-1.5" title="Actualizar datos de la clase bajo demanda">
                <span id="icon-refrescar" class="transition-transform duration-300">🔄</span>
                <span>Actualizar</span>
            </button>
        </div>
    </div>

    <!-- Filtros Jerárquicos en Cascada: Turno -> Ciclo/Nivel -> Bachillerato -> Grado/Curso -->
    <div class="p-4 sm:p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm mb-6">
        <div class="flex items-center justify-between gap-3 mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2">
                <span>🧭</span> Explorador Jerárquico de Cursos
            </span>
            <span id="label-estado-filtro" class="text-[11px] text-slate-400 font-medium"></span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div>
                <label for="filtro-turno" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    1. Turno
                </label>
                <select id="filtro-turno" class="app-input w-full text-xs font-semibold">
                    <option value="">Todos los turnos</option>
                </select>
            </div>

            <div>
                <label for="filtro-nivel" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    2. Ciclo / Nivel
                </label>
                <select id="filtro-nivel" class="app-input w-full text-xs font-semibold">
                    <option value="">Todos los ciclos</option>
                </select>
            </div>

            <div>
                <label for="filtro-bachillerato" class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5">
                    3. Bachillerato
                </label>
                <select id="filtro-bachillerato" class="app-input w-full text-xs font-semibold">
                    <option value="">Todos / No aplica</option>
                </select>
            </div>

            <div>
                <label for="filtro-grado" class="block text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 mb-1.5">
                    4. Grado / Curso a Observar
                </label>
                <select id="filtro-grado" class="app-input w-full text-xs font-bold border-indigo-300 dark:border-indigo-500/50">
                    <option value="">Seleccione grado o curso...</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Estado Inicial: Seleccionar Grado o Curso (Prompt Limpio) -->
    <div id="prompt-seleccion-grado" class="rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-slate-900/40 p-12 text-center my-6">
        <div class="mx-auto w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-800/50 flex items-center justify-center text-3xl mb-4 shadow-sm">
            📡
        </div>
        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Seleccionar grado o curso para monitorear</h3>
        <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1.5 leading-relaxed">
            Utilice los filtros superiores para seleccionar el Turno, Ciclo y Grado a observar en tiempo real.
        </p>
        <div class="mt-4 inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
            Esperando selección de curso
        </div>
    </div>

    <!-- Si no hay clase activa para el curso seleccionado -->
    <div id="no-clase-banner" class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 rounded-2xl p-6 mb-6 hidden">
        <div class="flex items-start gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <h3 class="font-bold text-amber-900 dark:text-amber-300 text-sm">No se detectó una clase en curso para este curso</h3>
                <p id="no-clase-detalle" class="text-xs text-amber-800 dark:text-amber-400/90 mt-1 leading-relaxed">
                    Verifique que el día y la hora actual correspondan al horario programado para este grado o seleccione otro curso en el explorador.
                </p>
            </div>
        </div>
    </div>

    <!-- Contenedor Principal de la Clase Activa -->
    <div id="clase-activa-container" class="space-y-6 hidden">

        <!-- 1. Encabezado de la Clase y Docente -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Datos de la Materia y Horario -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="text-[11px] uppercase tracking-wider text-indigo-600 dark:text-indigo-400 font-bold">Materia Activa</span>
                        <h3 id="clase-materia" class="text-xl font-bold text-slate-900 dark:text-white mt-0.5">-</h3>
                    </div>
                    <div class="text-right">
                        <span id="clase-grado-badge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-500/30">
                            -
                        </span>
                        <p id="clase-horario" class="text-xs font-mono text-slate-500 dark:text-slate-400 mt-1 font-semibold">-</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 text-xs">
                    <div>
                        <span class="text-slate-400 font-medium">Aula Física</span>
                        <p id="clase-aula" class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">-</p>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium">Horas Cátedra</span>
                        <p id="clase-hc" class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">-</p>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium">Duración Total</span>
                        <p id="clase-duracion" class="font-bold text-slate-800 dark:text-slate-200 mt-0.5">-</p>
                    </div>
                    <div>
                        <span class="text-slate-400 font-medium">Fecha de la Clase</span>
                        <p id="clase-fecha" class="font-bold text-slate-800 dark:text-slate-200 mt-0.5 font-mono">-</p>
                    </div>
                </div>
            </div>

            <!-- Tarjeta del Docente y su Asistencia -->
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] uppercase tracking-wider text-slate-400 font-bold">Profesor a Cargo</span>
                        <span id="badge-asist-profesor" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                            Verificando...
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 flex items-center justify-center font-bold text-lg">
                            👨‍🏫
                        </div>
                        <div>
                            <h4 id="profesor-nombre" class="font-bold text-slate-900 dark:text-white text-base leading-tight">-</h4>
                            <p id="profesor-ci" class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5">CI: -</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600 dark:text-slate-400">
                    <div>
                        <span id="profesor-hora-marcacion" class="font-medium block">Hora: --:--:--</span>
                        <span id="profesor-origen" class="text-[10px] text-slate-400 font-mono">Origen: Pendiente</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($puedeMarcarManual): ?>
                        <button id="btn-marcar-prof-manual" type="button" class="px-2.5 py-1.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 rounded-lg text-xs font-semibold border border-indigo-200 dark:border-indigo-800/40 transition-colors shadow-2xs" title="Asistencia docente manual">
                            ✏️ Asistencia
                        </button>
                        <?php endif; ?>
                        <button id="btn-simular-prof" type="button" class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg text-xs font-semibold transition-colors" title="Simular marcación de huella docente desde hardware">
                            👆 Huella
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Contenido Pedagógico / Lección del Día -->
        <div class="bg-gradient-to-r from-indigo-900 to-slate-900 rounded-xl shadow-md p-6 text-white">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-4 border-b border-white/10">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-500/40 text-indigo-200 border border-indigo-400/30 uppercase tracking-wider">
                            Plan Anual MEC Paraguay
                        </span>
                        <span id="leccion-unidad" class="text-xs text-indigo-200 font-medium">-</span>
                    </div>
                    <h3 id="leccion-tema" class="text-lg font-bold text-white mt-1 leading-snug">
                        Cargando lección del día...
                    </h3>
                </div>
                <div class="text-right">
                    <span class="text-xs text-indigo-300 block">Horas Cátedra</span>
                    <span id="leccion-hc-badge" class="text-sm font-bold text-white font-mono bg-white/10 px-3 py-1 rounded-md inline-block mt-0.5">
                        - HC (40m c/u)
                    </span>
                </div>
            </div>

            <!-- Capacidad y Contenido -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                <div class="md:col-span-2">
                    <span class="text-[11px] font-bold text-indigo-300 uppercase tracking-wide">Capacidad a Desarrollar</span>
                    <p id="leccion-capacidad" class="text-xs text-slate-200 mt-1 leading-relaxed">-</p>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-indigo-300 uppercase tracking-wide">Evaluación Pedagógica</span>
                    <div id="leccion-evaluacion" class="text-xs text-slate-200 mt-1 space-y-1">-</div>
                </div>
            </div>

            <!-- Indicadores de Logro con Checkbox en Tiempo Real -->
            <div class="mt-4 pt-4 border-t border-white/10">
                <span class="text-[11px] font-bold text-indigo-300 uppercase tracking-wider block mb-2">
                    Indicadores de Logro de la Clase (Marcar cumplimiento durante la clase)
                </span>
                <div id="leccion-indicadores-list" class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                    <!-- Dinámico -->
                </div>
            </div>
        </div>

        <!-- 3. Métricas y Asistencia de Estudiantes -->
        <div>
            <!-- Tarjetas Métricas -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-slate-400 uppercase">Matriculados</span>
                    <p id="metric-total" class="text-2xl font-bold text-slate-900 dark:text-white mt-1">0</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase">Presentes (Huella)</span>
                    <p id="metric-presentes" class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">0</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-rose-500 dark:text-rose-400 uppercase">Ausentes</span>
                    <p id="metric-ausentes" class="text-2xl font-bold text-rose-500 dark:text-rose-400 mt-1">0</p>
                </div>
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase">% Asistencia</span>
                    <p id="metric-porcentaje" class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">0%</p>
                </div>
            </div>

            <!-- Columnas: Presentes vs Ausentes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Columna Presentes -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 flex flex-col">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-sm">Estudiantes Presentes</h4>
                        </div>
                        <span id="badge-count-presentes" class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-800/40">0</span>
                    </div>

                    <div id="lista-presentes" class="space-y-2 flex-1 overflow-y-auto max-h-96 pr-1">
                        <!-- Items dinámicos -->
                    </div>
                </div>

                <!-- Columna Ausentes -->
                <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 p-5 flex flex-col">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            <h4 class="font-bold text-slate-900 dark:text-white text-sm">Estudiantes Ausentes</h4>
                        </div>
                        <span id="badge-count-ausentes" class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-100 dark:border-rose-800/40">0</span>
                    </div>

                    <div id="lista-ausentes" class="space-y-2 flex-1 overflow-y-auto max-h-96 pr-1">
                        <!-- Items dinámicos -->
                    </div>
                </div>

            </div>
        </div>

        <!-- 4. Consola de Pruebas: Simulación Rápida de Huella Dactilar -->
        <div class="bg-slate-100 dark:bg-slate-900/90 rounded-xl p-5 text-slate-800 dark:text-white shadow-md border border-slate-200 dark:border-slate-800">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-amber-500 dark:text-amber-400">⚡ Consola de Pruebas Rápidas:</span>
                        <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-200">Simulador de Marcación Biométrica</h4>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Permite disparar la marcación de huella de cualquier estudiante o docente sin tocar hardware físico para verificar la detección de clase y lección.
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <select id="simular-select-estudiante" class="px-3 py-2 bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-lg text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 flex-1 min-w-[220px]">
                    <option value="">Seleccione estudiante del curso...</option>
                </select>

                <button id="btn-disparar-huella" type="button" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                    <span>👆</span> Marcar Huella Estudiante
                </button>

                <?php if ($puedeMarcarManual): ?>
                <button id="btn-marcar-todos" type="button" class="px-3 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-lg text-xs font-medium transition-all" title="Marcar a todos los ausentes como presentes de forma manual">
                    Marcar Todos Presentes
                </button>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <?php if ($puedeMarcarManual): ?>
    <!-- Modal para Ajuste / Rectificación de Asistencia Manual -->
    <div id="modal-ajuste-asistencia" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs hidden" aria-labelledby="modal-ajuste-titulo" role="dialog" aria-modal="true">
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-150">
            <!-- Header -->
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                        ✏️
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white" id="modal-ajuste-titulo">Ajustar Asistencia Manual</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400" id="modal-ajuste-subtitulo">Registro institucional</p>
                    </div>
                </div>
                <button type="button" id="btn-cerrar-modal-ajuste" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors text-xl font-bold leading-none" aria-label="Cerrar modal">
                    &times;
                </button>
            </div>

            <!-- Body -->
            <div class="p-5 space-y-4">
                <!-- Info Persona -->
                <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 text-xs">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-slate-400 font-semibold uppercase text-[10px]" id="modal-persona-tipo">Estudiante</span>
                        <span class="font-mono text-slate-500 dark:text-slate-400 text-[11px]" id="modal-persona-ci">CI: -</span>
                    </div>
                    <div class="font-bold text-slate-800 dark:text-white text-sm" id="modal-persona-nombre">-</div>
                </div>

                <!-- Estado Selector -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                        Estado a Asignar
                    </label>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-emerald-500 cursor-pointer transition-all">
                            <input type="radio" name="modal_estado_asistencia" value="PRESENTE" class="text-emerald-600 focus:ring-0">
                            <div>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 block">🟢 Presente</span>
                                <span class="text-[10px] text-slate-400 block leading-tight">En aula / horario</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-amber-500 cursor-pointer transition-all">
                            <input type="radio" name="modal_estado_asistencia" value="TARDANZA" class="text-amber-600 focus:ring-0">
                            <div>
                                <span class="font-bold text-amber-600 dark:text-amber-400 block">🟡 Tardanza</span>
                                <span class="text-[10px] text-slate-400 block leading-tight">Llegada posterior</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-blue-500 cursor-pointer transition-all">
                            <input type="radio" name="modal_estado_asistencia" value="JUSTIFICADO" class="text-blue-600 focus:ring-0">
                            <div>
                                <span class="font-bold text-blue-600 dark:text-blue-400 block">🔵 Justificado</span>
                                <span class="text-[10px] text-slate-400 block leading-tight">Permiso oficial</span>
                            </div>
                        </label>
                        <label id="opt-estado-ausente" class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-rose-500 cursor-pointer transition-all">
                            <input type="radio" name="modal_estado_asistencia" value="AUSENTE" class="text-rose-600 focus:ring-0">
                            <div>
                                <span class="font-bold text-rose-600 dark:text-rose-400 block">🔴 Ausente</span>
                                <span class="text-[10px] text-slate-400 block leading-tight">Reversión (Solo Admin)</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Alerta de Política Anti-Duplicado y Origen -->
                <div class="flex items-start gap-2 p-2.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-800/40 text-[11px] text-indigo-700 dark:text-indigo-300">
                    <span class="text-sm">🛡️</span>
                    <div>
                        <strong>Control Institucional:</strong> Se registrará como marcación manual. La base de datos garantiza registro único por persona y día (cero doble escritura).
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-3 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                <button type="button" id="btn-cancelar-modal-ajuste" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                    Cancelar
                </button>
                <button type="button" id="btn-confirmar-modal-ajuste" class="px-4 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white shadow-sm transition-colors flex items-center gap-1.5">
                    <span>💾</span> Guardar Cambio
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const ES_ADMIN = <?= json_encode($esAdmin) ?>;
    const PUEDE_MARCAR = <?= json_encode($puedeMarcarManual) ?>;
    const API_PANEL = `${window.BASE_URL}src/api/Clases/panel_en_vivo.php`;
    const API_INDICADOR = `${window.BASE_URL}src/api/Clases/actualizar_indicador.php`;
    const API_MARCAR = `${window.BASE_URL}src/api/Clases/marcar_asistencia.php`;
    const API_SIMULAR = `${window.BASE_URL}src/api/Clases/simular_marcacion.php`;

    const labelTiempo = document.getElementById('label-servidor-tiempo');
    const badgeRealtime = document.getElementById('badge-realtime-status');
    const dotRealtime = document.getElementById('dot-realtime');
    const textRealtime = document.getElementById('text-realtime');
    const btnRefrescarManual = document.getElementById('btn-refrescar-manual');
    const iconRefrescar = document.getElementById('icon-refrescar');

    const filtroTurno = document.getElementById('filtro-turno');
    const filtroNivel = document.getElementById('filtro-nivel');
    const filtroBachillerato = document.getElementById('filtro-bachillerato');
    const filtroGrado = document.getElementById('filtro-grado');
    const labelEstadoFiltro = document.getElementById('label-estado-filtro');

    const promptSeleccionGrado = document.getElementById('prompt-seleccion-grado');
    const noClaseBanner = document.getElementById('no-clase-banner');
    const noClaseDetalle = document.getElementById('no-clase-detalle');
    const claseContainer = document.getElementById('clase-activa-container');

    const simularSelectEst = document.getElementById('simular-select-estudiante');
    const btnDispararHuella = document.getElementById('btn-disparar-huella');
    const btnSimularProf = document.getElementById('btn-simular-prof');
    const btnMarcarTodos = document.getElementById('btn-marcar-todos');

    // Elementos del Modal de Ajuste de Asistencia Manual
    const modalAjuste = document.getElementById('modal-ajuste-asistencia');
    const modalAjusteTitulo = document.getElementById('modal-ajuste-titulo');
    const modalAjusteSubtitulo = document.getElementById('modal-ajuste-subtitulo');
    const modalPersonaTipo = document.getElementById('modal-persona-tipo');
    const modalPersonaNombre = document.getElementById('modal-persona-nombre');
    const modalPersonaCi = document.getElementById('modal-persona-ci');
    const btnCerrarModalAjuste = document.getElementById('btn-cerrar-modal-ajuste');
    const btnCancelarModalAjuste = document.getElementById('btn-cancelar-modal-ajuste');
    const btnConfirmarModalAjuste = document.getElementById('btn-confirmar-modal-ajuste');
    const btnMarcarProfManual = document.getElementById('btn-marcar-prof-manual');

    let currentClaseData = null;
    let isFetching = false;
    let activeAbortController = null;
    let supabaseClient = null;
    let realtimeChannel = null;
    let modalPersonaData = null;
    let previosPresentesIds = null;

    let todosGrados = [];
    let turnosData = [];
    let nivelesData = [];
    let bachilleratosData = [];
    let filtrosInicializados = false;

    // 1. Inicializar y cargar estructura de filtros
    function init() {
        mostrarEstadoPrompt();

        fetch(API_PANEL)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    todosGrados = res.data.grados || [];
                    turnosData = res.data.turnos || [];
                    nivelesData = res.data.niveles || [];
                    bachilleratosData = res.data.bachilleratos || [];

                    if (res.data.servidor) {
                        actualizarRelojServidor(res.data.servidor);
                    }

                    if (!filtrosInicializados) {
                        poblarFiltrosCascada();
                        filtrosInicializados = true;
                    }

                    // Si es rol Profesor con clase activa detectada automáticamente
                    if (res.data.clase) {
                        if (res.data.clase.id_grado && filtroGrado) {
                            filtroGrado.value = res.data.clase.id_grado;
                        }
                        renderizarPanel(res.data);
                    } else {
                        mostrarEstadoPrompt();
                    }
                }
            })
            .catch(e => console.error("Error en init panel en vivo:", e));
    }

    function actualizarRelojServidor(servidor) {
        if (!labelTiempo || !servidor) return;
        labelTiempo.textContent = `${servidor.dia_semana} ${servidor.fecha_actual} — ${servidor.hora_actual}`;
    }

    function mostrarEstadoPrompt() {
        if (promptSeleccionGrado) promptSeleccionGrado.classList.remove('hidden');
        if (noClaseBanner) noClaseBanner.classList.add('hidden');
        if (claseContainer) claseContainer.classList.add('hidden');
        currentClaseData = null;
    }

    // 2. Poblar opciones de los filtros en cascada
    function poblarFiltrosCascada() {
        if (filtroTurno && turnosData.length > 0) {
            filtroTurno.innerHTML = '<option value="">Todos los turnos</option>' +
                turnosData.map(t => `<option value="${t.id_turno}">${t.nombre}</option>`).join('');
        }

        if (filtroNivel && nivelesData.length > 0) {
            filtroNivel.innerHTML = '<option value="">Todos los ciclos</option>' +
                nivelesData.map(n => `<option value="${n.id_nivel}">${n.nombre}</option>`).join('');
        }

        if (filtroBachillerato && bachilleratosData.length > 0) {
            filtroBachillerato.innerHTML = '<option value="">Todos / No aplica</option>' +
                bachilleratosData.map(b => `<option value="${b.id_bachillerato}">${b.codigo ? b.codigo + ' - ' : ''}${b.nombre}</option>`).join('');
        }

        actualizarOpcionesGrados();
    }

    // 3. Filtrar grados según la cascada seleccionada
    function actualizarOpcionesGrados() {
        if (!filtroGrado) return;

        const turnoSel = filtroTurno ? filtroTurno.value : '';
        const nivelSel = filtroNivel ? filtroNivel.value : '';
        const bachiSel = filtroBachillerato ? filtroBachillerato.value : '';

        const filtrados = todosGrados.filter(g => {
            if (turnoSel !== '' && String(g.id_turno) !== String(turnoSel)) return false;
            if (nivelSel !== '' && String(g.id_nivel) !== String(nivelSel)) return false;
            if (bachiSel !== '' && String(g.id_bachillerato) !== String(bachiSel)) return false;
            return true;
        });

        const valorPrevio = filtroGrado.value;
        filtroGrado.innerHTML = '<option value="">Seleccione grado o curso...</option>' +
            filtrados.map(g => {
                const aulaInfo = g.aula_nombre ? ` [${g.aula_nombre}]` : '';
                return `<option value="${g.id_grado}">${g.nombre}${g.seccion ? ' - Secc. ' + g.seccion : ''}${aulaInfo}</option>`;
            }).join('');

        if (valorPrevio && filtrados.some(g => String(g.id_grado) === String(valorPrevio))) {
            filtroGrado.value = valorPrevio;
        } else {
            filtroGrado.value = '';
            mostrarEstadoPrompt();
        }

        if (labelEstadoFiltro) {
            labelEstadoFiltro.textContent = `${filtrados.length} curso(s) disponible(s)`;
        }
    }

    if (filtroTurno) filtroTurno.addEventListener('change', () => { actualizarOpcionesGrados(); });
    if (filtroNivel) filtroNivel.addEventListener('change', () => { actualizarOpcionesGrados(); });
    if (filtroBachillerato) filtroBachillerato.addEventListener('change', () => { actualizarOpcionesGrados(); });
    
    if (filtroGrado) {
        filtroGrado.addEventListener('change', () => {
            const idGrado = filtroGrado.value;
            if (idGrado) {
                refrescarPanel(false);
            } else {
                mostrarEstadoPrompt();
            }
        });
    }

    // 4. Refrescar datos del panel de forma ligera y protegida
    function refrescarPanel(silencioso = false) {
        const idGrado = filtroGrado ? filtroGrado.value : '';
        if (!idGrado) {
            mostrarEstadoPrompt();
            return;
        }

        // Cancelar petición anterior si aún no terminó para evitar acumulación
        if (isFetching && activeAbortController) {
            activeAbortController.abort();
        }

        activeAbortController = new AbortController();
        isFetching = true;

        const url = `${API_PANEL}?id_grado=${encodeURIComponent(idGrado)}&solo_clase=1`;

        fetch(url, { signal: activeAbortController.signal })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    renderizarPanel(res.data);
                }
            })
            .catch(e => {
                if (e.name !== 'AbortError') {
                    console.error("Error al refrescar panel en vivo:", e);
                }
            })
            .finally(() => {
                isFetching = false;
            });
    }

    // 5. Renderizar vista completa
    function renderizarPanel(data) {
        if (data.servidor) {
            actualizarRelojServidor(data.servidor);
        }

        const clase = data.clase;
        currentClaseData = clase;

        if (promptSeleccionGrado) promptSeleccionGrado.classList.add('hidden');

        if (!clase) {
            noClaseBanner.classList.remove('hidden');
            claseContainer.classList.add('hidden');
            if (noClaseDetalle && data.servidor) {
                noClaseDetalle.textContent = `No se detectó clase en curso para este grado en el horario actual (${data.servidor.dia_semana} ${data.servidor.hora_actual}). Verifique los horarios programados del grado.`;
            }
            return;
        }

        noClaseBanner.classList.add('hidden');
        claseContainer.classList.remove('hidden');

        // Datos de Clase
        document.getElementById('clase-materia').textContent = clase.materia_nombre || 'Materia';
        document.getElementById('clase-grado-badge').textContent = clase.grado_nombre || 'Curso';
        document.getElementById('clase-horario').textContent = `${clase.horario_inicio || ''} - ${clase.horario_fin || ''}`;
        document.getElementById('clase-aula').textContent = clase.aula_nombre || 'Aula';

        const hc = parseFloat(clase.horas_catedra || 2);
        const durMin = hc * 40;
        document.getElementById('clase-hc').textContent = `${hc} Horas Cátedra`;
        document.getElementById('clase-duracion').textContent = `${durMin} min (40m/HC)`;
        document.getElementById('clase-fecha').textContent = clase.fecha;

        // Docente
        document.getElementById('profesor-nombre').textContent = `${clase.profesor_nombre} ${clase.profesor_apellido}`;
        document.getElementById('profesor-ci').textContent = `CI: ${clase.profesor_ci || '-'}`;

        const profAsist = clase.profesor_asistencia || {};
        const badgeProf = document.getElementById('badge-asist-profesor');
        const profHoraElem = document.getElementById('profesor-hora-marcacion');
        const profOrigenElem = document.getElementById('profesor-origen');

        if (profAsist.presente) {
            const isManual = (profAsist.tipo_registro === 'MANUAL');
            const estado = profAsist.estado || 'PRESENTE';
            let colorClases = 'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40';
            let iconoEstado = '🟢';
            if (estado === 'TARDANZA') {
                colorClases = 'bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40';
                iconoEstado = '🟡';
            } else if (estado === 'JUSTIFICADO') {
                colorClases = 'bg-blue-100 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800/40';
                iconoEstado = '🔵';
            }
            badgeProf.className = `inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-bold ${colorClases}`;
            badgeProf.textContent = `${iconoEstado} ${estado} (${isManual ? 'MANUAL' : 'HUELLA'})`;
            profHoraElem.textContent = `Hora: ${profAsist.hora || '--:--'}`;
            if (profOrigenElem) {
                profOrigenElem.textContent = `Origen: ${profAsist.nodo_origen || (isManual ? 'Panel Web' : 'Nodo Aula')}`;
            }
        } else {
            badgeProf.className = 'inline-flex items-center px-2.5 py-0.5 rounded text-[11px] font-bold bg-rose-100 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800/40';
            badgeProf.textContent = '🔴 AUSENTE';
            profHoraElem.textContent = 'Hora: Pendiente';
            if (profOrigenElem) {
                profOrigenElem.textContent = 'Origen: Pendiente';
            }
        }

        // Control dinámico del botón de asistencia docente según reglas de integridad
        if (btnMarcarProfManual) {
            if (!profAsist.presente) {
                btnMarcarProfManual.style.display = '';
                btnMarcarProfManual.disabled = !PUEDE_MARCAR;
                btnMarcarProfManual.innerHTML = '✏️ Asistencia';
                btnMarcarProfManual.title = 'Registrar asistencia docente manual (Docentes, Coordinadores y Admins)';
                btnMarcarProfManual.className = 'px-2.5 py-1.5 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 rounded-lg text-xs font-semibold border border-indigo-200 dark:border-indigo-800/40 transition-colors shadow-2xs';
            } else if (profAsist.tipo_registro === 'BIOMETRICO') {
                btnMarcarProfManual.style.display = '';
                btnMarcarProfManual.disabled = true;
                btnMarcarProfManual.innerHTML = '🔒 Huella Inmutable';
                btnMarcarProfManual.title = 'La asistencia por huella dactilar es inmutable y no puede modificarse';
                btnMarcarProfManual.className = 'px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 cursor-not-allowed';
            } else if (profAsist.tipo_registro === 'MANUAL') {
                if (ES_ADMIN) {
                    btnMarcarProfManual.style.display = '';
                    btnMarcarProfManual.disabled = false;
                    btnMarcarProfManual.innerHTML = '✏️ Modificar (Admin)';
                    btnMarcarProfManual.title = 'Rectificar asistencia docente manual (Solo Administrador)';
                    btnMarcarProfManual.className = 'px-2.5 py-1.5 bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/60 rounded-lg text-xs font-semibold border border-amber-200 dark:border-amber-800/40 transition-colors shadow-2xs';
                } else {
                    btnMarcarProfManual.style.display = '';
                    btnMarcarProfManual.disabled = true;
                    btnMarcarProfManual.innerHTML = '✍️ Manual Registrado';
                    btnMarcarProfManual.title = 'Solo administradores pueden modificar o revertir asistencias registradas';
                    btnMarcarProfManual.className = 'px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded-lg text-xs font-semibold border border-slate-200 dark:border-slate-700 cursor-not-allowed';
                }
            }
        }

        // Lección Pedagógica
        document.getElementById('leccion-unidad').textContent = clase.unidad_nombre || 'Unidad Temática';
        document.getElementById('leccion-tema').textContent = clase.tema_titulo || 'Contenido calendarizado del día';
        document.getElementById('leccion-hc-badge').textContent = `${hc} HC (${durMin} min)`;
        document.getElementById('leccion-capacidad').textContent = clase.capacidad_descripcion || 'Capacidad pedagógica institucional.';

        // Evaluación
        const evalList = [
            ...(clase.procedimientos || []),
            ...(clase.instrumentos || [])
        ];
        document.getElementById('leccion-evaluacion').innerHTML = evalList.length > 0
            ? evalList.map(item => `<div>• ${item}</div>`).join('')
            : '<div>• Registro Secuencia Aprendizaje (RSA)</div><div>• Observación sistemática</div>';

        // Indicadores con checkboxes interactivos
        const indContainer = document.getElementById('leccion-indicadores-list');
        if (clase.indicadores && clase.indicadores.length > 0) {
            indContainer.innerHTML = clase.indicadores.map(ind => `
                <label class="flex items-start gap-2.5 p-2 rounded bg-white/5 hover:bg-white/10 transition-colors cursor-pointer border border-white/5">
                    <input type="checkbox" data-indicador="${ind.id_indicador}" ${parseInt(ind.cumplido) === 1 ? 'checked' : ''} class="mt-0.5 rounded border-white/30 text-indigo-500 focus:ring-0 cursor-pointer indicador-chk">
                    <span class="${parseInt(ind.cumplido) === 1 ? 'line-through text-indigo-300' : 'text-slate-100'}">${ind.descripcion}</span>
                </label>
            `).join('');

            // Event listener en indicadores
            indContainer.querySelectorAll('.indicador-chk').forEach(chk => {
                chk.addEventListener('change', (e) => {
                    const idInd = e.target.getAttribute('data-indicador');
                    const cumplido = e.target.checked ? 1 : 0;
                    fetch(API_INDICADOR, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id_clase: clase.id_clase,
                            id_indicador: idInd,
                            cumplido: cumplido
                        })
                    }).then(() => refrescarPanel(true));
                });
            });
        } else {
            indContainer.innerHTML = '<span class="text-xs text-slate-400 italic">No hay indicadores registrados para este tema.</span>';
        }

        // Métricas
        const metricas = clase.metricas || {};
        document.getElementById('metric-total').textContent = metricas.total_matriculados || 0;
        document.getElementById('metric-presentes').textContent = metricas.presentes || 0;
        document.getElementById('metric-ausentes').textContent = metricas.ausentes || 0;
        document.getElementById('metric-porcentaje').textContent = `${metricas.porcentaje_asistencia || 0}%`;

        document.getElementById('badge-count-presentes').textContent = metricas.presentes || 0;
        document.getElementById('badge-count-ausentes').textContent = metricas.ausentes || 0;

        // Detección proactiva de nuevas marcaciones en aula física (Live Feedback)
        const actualesPresentesIds = new Set((clase.estudiantes_presentes || []).map(e => e.id_estudiante));
        if (previosPresentesIds !== null) {
            const nuevos = (clase.estudiantes_presentes || []).filter(e => !previosPresentesIds.has(e.id_estudiante));
            if (nuevos.length > 0 && window.Toast) {
                nuevos.forEach(n => {
                    const origenTxt = n.tipo_registro === 'MANUAL' ? 'Registro Manual' : 'Nodo del Aula';
                    Toast.info(`👆 ${n.nombre_completo} marcó asistencia (${n.hora_marcacion || ''}) vía ${origenTxt}`, "Marcación en Aula");
                });
            }
        }
        previosPresentesIds = actualesPresentesIds;

        // Estudiantes Presentes
        const listaPresentes = document.getElementById('lista-presentes');
        if (clase.estudiantes_presentes && clase.estudiantes_presentes.length > 0) {
            listaPresentes.innerHTML = clase.estudiantes_presentes.map(e => {
                const isManual = (e.tipo_registro === 'MANUAL');
                const estado = e.estado_asistencia || 'PRESENTE';
                let badgeClass = 'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40';
                let estadoTxt = '🟢 PRESENTE';
                if (estado === 'TARDANZA') {
                    badgeClass = 'bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40';
                    estadoTxt = '🟡 TARDANZA';
                } else if (estado === 'JUSTIFICADO') {
                    badgeClass = 'bg-blue-100 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800/40';
                    estadoTxt = '🔵 JUSTIFICADO';
                }
                const badgeOrigen = isManual
                    ? '<span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/40">✍️ MANUAL</span>'
                    : '<span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">📡 HUELLA</span>';

                let accionPresenteHtml = '';
                if (e.tipo_registro === 'BIOMETRICO') {
                    accionPresenteHtml = `
                        <span class="px-2 py-1 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 rounded text-[10px] font-semibold border border-slate-200 dark:border-slate-700 flex items-center gap-1 shadow-2xs" title="Marcación biométrica por huella física en el aula (Inmutable, protegida por hardware)">
                            🔒 Inmutable
                        </span>
                    `;
                } else if (ES_ADMIN) {
                    accionPresenteHtml = `
                        <button type="button" data-id="${e.id_estudiante}" data-nombre="${encodeURIComponent(e.nombre_completo)}" data-ci="${e.cedula_identidad}" data-estado="${estado}" data-tipo-registro="${e.tipo_registro || 'MANUAL'}" class="btn-rectificar-estudiante px-2 py-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 rounded text-[10px] font-semibold text-slate-700 dark:text-slate-200 shadow-2xs transition-colors" title="Rectificar o modificar asistencia manual (Solo Administrador)">
                            ✏️ Modificar
                        </button>
                    `;
                }

                return `
                    <div class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-emerald-50/40 dark:bg-emerald-950/15 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 transition-colors text-xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="text-base flex-shrink-0">👤</span>
                            <div class="truncate">
                                <span class="font-bold text-slate-800 dark:text-slate-100 block truncate">${e.nombre_completo}</span>
                                <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">
                                    <span>CI: ${e.cedula_identidad}</span>
                                    <span>•</span>
                                    ${badgeOrigen}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold ${badgeClass}">
                                    ${estadoTxt}
                                </span>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono block mt-0.5">${e.hora_marcacion || '--:--'}</span>
                            </div>
                            ${accionPresenteHtml}
                        </div>
                    </div>
                `;
            }).join('');

            if (ES_ADMIN) {
                listaPresentes.querySelectorAll('.btn-rectificar-estudiante').forEach(btn => {
                    btn.addEventListener('click', (ev) => {
                        const b = ev.currentTarget;
                        abrirModalAjuste({
                            tipo_persona: 'estudiante',
                            id_persona: parseInt(b.getAttribute('data-id')),
                            nombre: decodeURIComponent(b.getAttribute('data-nombre')),
                            ci: b.getAttribute('data-ci'),
                            estado_actual: b.getAttribute('data-estado'),
                            tipo_registro: b.getAttribute('data-tipo-registro') || 'MANUAL',
                            es_modificacion: true
                        });
                    });
                });
            }
        } else {
            listaPresentes.innerHTML = `
                <div class="p-6 text-center text-slate-400 dark:text-slate-500 text-xs italic">
                    Aún no se han registrado huellas dactilares de estudiantes para esta clase.
                </div>
            `;
        }

        // Estudiantes Ausentes
        const listaAusentes = document.getElementById('lista-ausentes');
        if (clase.estudiantes_ausentes && clase.estudiantes_ausentes.length > 0) {
            listaAusentes.innerHTML = clase.estudiantes_ausentes.map(e => {
                const accionesAusenteHtml = PUEDE_MARCAR ? `
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <button type="button" data-id="${e.id_estudiante}" class="btn-marcar-presente-rapido px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-[11px] font-bold shadow-2xs transition-colors flex items-center gap-1" title="Marcar PRESENTE manualmente">
                            <span>✓</span> Presente
                        </button>
                        <button type="button" data-id="${e.id_estudiante}" data-nombre="${encodeURIComponent(e.nombre_completo)}" data-ci="${e.cedula_identidad}" class="btn-abrir-modal-estudiante px-2 py-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 hover:text-indigo-600 dark:hover:text-indigo-300 rounded text-[11px] font-bold text-slate-600 dark:text-slate-300 shadow-2xs transition-colors" title="Opciones avanzadas (Tardanza / Justificado)">
                            ⋯
                        </button>
                    </div>
                ` : `
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800/40">
                            🔴 AUSENTE
                        </span>
                    </div>
                `;

                return `
                    <div class="flex items-center justify-between p-2.5 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 hover:bg-slate-100/60 dark:hover:bg-slate-800 transition-colors text-xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="text-base text-slate-400 flex-shrink-0">👤</span>
                            <div class="truncate">
                                <span class="font-medium text-slate-700 dark:text-slate-300 block truncate">${e.nombre_completo}</span>
                                <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">CI: ${e.cedula_identidad}</span>
                            </div>
                        </div>
                        ${accionesAusenteHtml}
                    </div>
                `;
            }).join('');

            if (PUEDE_MARCAR) {
                // Listeners para marcar rápido como PRESENTE (Docentes, Coordinadores y Administradores)
                listaAusentes.querySelectorAll('.btn-marcar-presente-rapido').forEach(btn => {
                    btn.addEventListener('click', (ev) => {
                        const idEst = ev.currentTarget.getAttribute('data-id');
                        fetch(API_MARCAR, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                tipo_persona: 'estudiante',
                                id_estudiante: parseInt(idEst),
                                fecha: clase.fecha,
                                estado: 'PRESENTE',
                                id_horario: clase.id_horario || null
                            })
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.status === 'success') {
                                if (window.Toast) Toast.success(res.message || "Estudiante marcado como PRESENTE", "Asistencia Registrada");
                                refrescarPanel(true);
                            } else {
                                if (window.Toast) Toast.error(res.message || "Error al registrar asistencia");
                            }
                        })
                        .catch(err => {
                            if (window.Toast) Toast.error(err.message);
                        });
                    });
                });

                // Listeners para abrir modal avanzado (Docentes, Coordinadores y Administradores)
                listaAusentes.querySelectorAll('.btn-abrir-modal-estudiante').forEach(btn => {
                    btn.addEventListener('click', (ev) => {
                        const b = ev.currentTarget;
                        abrirModalAjuste({
                            tipo_persona: 'estudiante',
                            id_persona: parseInt(b.getAttribute('data-id')),
                            nombre: decodeURIComponent(b.getAttribute('data-nombre')),
                            ci: b.getAttribute('data-ci'),
                            estado_actual: 'AUSENTE',
                            tipo_registro: null,
                            es_modificacion: false
                        });
                    });
                });
            }
        } else {
            listaAusentes.innerHTML = `
                <div class="p-6 text-center text-emerald-600 dark:text-emerald-400 text-xs font-semibold">
                    🎉 ¡100% de asistencia! No hay estudiantes ausentes.
                </div>
            `;
        }

        // Llenar selector del simulador con los estudiantes del curso
        const todos = [
            ...(clase.estudiantes_ausentes || []),
            ...(clase.estudiantes_presentes || [])
        ];
        simularSelectEst.innerHTML = '<option value="">Seleccione estudiante del curso...</option>' +
            todos.map(e => `<option value="${e.id_estudiante}">${e.nombre_completo} (CI: ${e.cedula_identidad})</option>`).join('');
    }

    // Modal de Ajuste de Asistencia Manual
    function abrirModalAjuste(persona) {
        // Regla 1: Asistencias biométricas son INMUTABLES
        if (persona.tipo_registro === 'BIOMETRICO') {
            if (window.Toast) {
                Toast.warning("Las asistencias registradas por huella dactilar en los nodos de aula son inmutables y no pueden modificarse.", "Biometría Inmutable");
            } else {
                alert("Las asistencias biométricas son inmutables.");
            }
            return;
        }

        // Regla 2: Si ya cuenta con asistencia previa (modificación/reversión), EXCLUSIVAMENTE Administradores
        if (persona.es_modificacion && !ES_ADMIN) {
            if (window.Toast) {
                Toast.warning("Acceso denegado: sola y exclusivamente los usuarios con rol de Administrador pueden modificar o revertir asistencias.", "Permisos Insuficientes");
            } else {
                alert("Acceso denegado: solo administradores pueden modificar asistencias.");
            }
            return;
        }

        // Regla 3: Si es registro inicial de ausente, requiere PUEDE_MARCAR
        if (!persona.es_modificacion && !PUEDE_MARCAR) {
            if (window.Toast) {
                Toast.warning("Acceso denegado: su rol solo tiene permisos de visualización.", "Permisos Insuficientes");
            } else {
                alert("Acceso denegado: solo lectura.");
            }
            return;
        }

        modalPersonaData = persona;
        if (modalAjusteTitulo) {
            const operacion = persona.es_modificacion ? 'Rectificar Asistencia' : 'Registrar Asistencia Manual';
            modalAjusteTitulo.textContent = persona.tipo_persona === 'profesor' ? `${operacion} Docente` : `${operacion} Estudiante`;
        }
        if (modalAjusteSubtitulo) {
            modalAjusteSubtitulo.textContent = persona.es_modificacion 
                ? 'Modificación autorizada exclusivamente para Administradores'
                : 'Registro manual institucional (Docentes, Coordinadores y Admins)';
        }
        if (modalPersonaTipo) {
            modalPersonaTipo.textContent = persona.tipo_persona === 'profesor' ? 'Profesor a Cargo' : 'Estudiante del Curso';
        }
        if (modalPersonaNombre) {
            modalPersonaNombre.textContent = persona.nombre || '-';
        }
        if (modalPersonaCi) {
            modalPersonaCi.textContent = persona.ci ? `CI: ${persona.ci}` : 'CI: No registrado';
        }

        const optAusente = document.getElementById('opt-estado-ausente');
        if (optAusente) {
            // Reversión a AUSENTE está reservada a Administradores
            if (ES_ADMIN) {
                optAusente.classList.remove('hidden');
                const inp = optAusente.querySelector('input');
                if (inp) inp.disabled = false;
            } else {
                optAusente.classList.add('hidden');
                const inp = optAusente.querySelector('input');
                if (inp) inp.disabled = true;
            }
        }

        const estadoActual = (persona.estado_actual && persona.estado_actual !== 'AUSENTE') ? persona.estado_actual.toUpperCase() : 'PRESENTE';
        const radios = document.querySelectorAll('input[name="modal_estado_asistencia"]');
        radios.forEach(r => {
            r.checked = (r.value === estadoActual);
        });

        if (modalAjuste) modalAjuste.classList.remove('hidden');
    }

    function cerrarModalAjuste() {
        if (modalAjuste) modalAjuste.classList.add('hidden');
        modalPersonaData = null;
    }

    if (btnCerrarModalAjuste) btnCerrarModalAjuste.addEventListener('click', cerrarModalAjuste);
    if (btnCancelarModalAjuste) btnCancelarModalAjuste.addEventListener('click', cerrarModalAjuste);

    if (btnConfirmarModalAjuste) {
        btnConfirmarModalAjuste.addEventListener('click', () => {
            if (!modalPersonaData || !currentClaseData) return;

            // Guardas de integridad en frontend
            if (modalPersonaData.tipo_registro === 'BIOMETRICO') {
                if (window.Toast) Toast.error("La asistencia por huella dactilar es inmutable.");
                cerrarModalAjuste();
                return;
            }

            if (modalPersonaData.es_modificacion && !ES_ADMIN) {
                if (window.Toast) Toast.warning("Acceso denegado: solo Administradores pueden modificar asistencias.");
                cerrarModalAjuste();
                return;
            }

            if (!modalPersonaData.es_modificacion && !PUEDE_MARCAR) {
                if (window.Toast) Toast.warning("Acceso denegado: permisos insuficientes para marcar asistencia.");
                cerrarModalAjuste();
                return;
            }

            const checkedRadio = document.querySelector('input[name="modal_estado_asistencia"]:checked');
            const nuevoEstado = checkedRadio ? checkedRadio.value : 'PRESENTE';

            if (nuevoEstado === 'AUSENTE' && !ES_ADMIN) {
                if (window.Toast) Toast.warning("Solo los administradores pueden revertir una asistencia a AUSENTE.");
                return;
            }

            const payload = {
                tipo_persona: modalPersonaData.tipo_persona,
                id_persona: modalPersonaData.id_persona,
                id_estudiante: modalPersonaData.tipo_persona === 'estudiante' ? modalPersonaData.id_persona : null,
                id_profesor: modalPersonaData.tipo_persona === 'profesor' ? modalPersonaData.id_persona : null,
                fecha: currentClaseData.fecha,
                estado: nuevoEstado,
                id_horario: currentClaseData.id_horario || null
            };

            btnConfirmarModalAjuste.disabled = true;
            btnConfirmarModalAjuste.innerHTML = '<span>⏳</span> Guardando...';

            fetch(API_MARCAR, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    cerrarModalAjuste();
                    if (window.Toast) Toast.success(res.message || "Asistencia guardada correctamente", "Asistencia Manual");
                    refrescarPanel(true);
                } else {
                    if (window.Toast) Toast.error(res.message || "Error al actualizar asistencia", "Error");
                    else alert(res.message);
                }
            })
            .catch(err => {
                if (window.Toast) Toast.error(err.message, "Error");
            })
            .finally(() => {
                btnConfirmarModalAjuste.disabled = false;
                btnConfirmarModalAjuste.innerHTML = '<span>💾</span> Guardar Cambio';
            });
        });
    }

    if (btnMarcarProfManual) {
        btnMarcarProfManual.addEventListener('click', () => {
            if (!currentClaseData) return;
            const profAsist = currentClaseData.profesor_asistencia || {};
            const esPresente = !!profAsist.presente;

            if (esPresente && profAsist.tipo_registro === 'BIOMETRICO') {
                if (window.Toast) Toast.warning("La asistencia por huella dactilar es inmutable y no puede modificarse.", "Biometría Inmutable");
                return;
            }

            if (esPresente && !ES_ADMIN) {
                if (window.Toast) Toast.warning("Acceso denegado: solo Administradores pueden modificar asistencias ya registradas.", "Acceso Restringido");
                return;
            }

            if (!esPresente && !PUEDE_MARCAR) {
                if (window.Toast) Toast.warning("Acceso denegado: permisos insuficientes para registrar asistencia.", "Acceso Restringido");
                return;
            }

            abrirModalAjuste({
                tipo_persona: 'profesor',
                id_persona: parseInt(currentClaseData.id_profesor),
                nombre: `${currentClaseData.profesor_nombre} ${currentClaseData.profesor_apellido}`,
                ci: currentClaseData.profesor_ci,
                estado_actual: esPresente ? (profAsist.estado || 'PRESENTE') : 'AUSENTE',
                tipo_registro: profAsist.tipo_registro || null,
                es_modificacion: esPresente
            });
        });
    }

    // 6. Simulador de Marcación de Huella
    btnDispararHuella.addEventListener('click', () => {
        const idEst = simularSelectEst.value;
        if (!idEst) {
            if (window.Toast) Toast.warning("Por favor seleccione un estudiante en el menú desplegable");
            else alert("Por favor seleccione un estudiante");
            return;
        }
        if (!currentClaseData) return;

        btnDispararHuella.disabled = true;

        fetch(API_SIMULAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tipo_persona: 'estudiante',
                id_persona: parseInt(idEst),
                id_aula: parseInt(currentClaseData.id_aula),
                timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19)
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                if (res.duplicado) {
                    if (window.Toast) Toast.warning(res.message || "Asistencia ya registrada hoy (cero doble escritura)", "Control Anti-Duplicado");
                } else {
                    if (window.Toast) Toast.success(res.message || "Marcación biométrica registrada exitosamente");
                }
                refrescarPanel(true);
            } else {
                if (window.Toast) Toast.error(res.message, "Error en simulación");
                else alert(`Error: ${res.message}`);
            }
        })
        .catch(e => {
            if (window.Toast) Toast.error(e.message);
            else alert(e.message);
        })
        .finally(() => {
            btnDispararHuella.disabled = false;
        });
    });

    btnSimularProf.addEventListener('click', () => {
        if (!currentClaseData) return;

        btnSimularProf.disabled = true;

        fetch(API_SIMULAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tipo_persona: 'profesor',
                id_persona: parseInt(currentClaseData.id_profesor),
                id_aula: parseInt(currentClaseData.id_aula),
                timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19)
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                if (res.duplicado) {
                    if (window.Toast) Toast.warning(res.message || "El profesor ya registró su asistencia hoy (cero doble escritura)", "Control Anti-Duplicado");
                } else {
                    if (window.Toast) Toast.success(res.message || "Profesor registrado en el aula");
                }
                refrescarPanel(true);
            } else {
                if (window.Toast) Toast.error(res.message, "Error");
                else alert(`Error: ${res.message}`);
            }
        })
        .catch(e => {
            if (window.Toast) Toast.error(e.message);
            else alert(e.message);
        })
        .finally(() => {
            btnSimularProf.disabled = false;
        });
    });

    if (btnMarcarTodos) {
        btnMarcarTodos.addEventListener('click', () => {
            if (!PUEDE_MARCAR) {
                if (window.Toast) Toast.warning("Acceso denegado: función reservada a personal docente, coordinadores y administradores.");
                return;
            }
            if (!currentClaseData || !currentClaseData.estudiantes_ausentes || currentClaseData.estudiantes_ausentes.length === 0) {
                if (window.Toast) Toast.info("No hay estudiantes ausentes pendientes de marcar", "Asistencia Completa");
                return;
            }

            const ausentes = currentClaseData.estudiantes_ausentes;
            btnMarcarTodos.disabled = true;
            btnMarcarTodos.textContent = "Marcando a todos...";

            const promises = ausentes.map(e => {
                return fetch(API_MARCAR, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        tipo_persona: 'estudiante',
                        id_estudiante: e.id_estudiante,
                        fecha: currentClaseData.fecha,
                        estado: 'PRESENTE',
                        id_horario: currentClaseData.id_horario || null
                    })
                }).then(r => r.json());
            });

            Promise.all(promises)
                .then(() => {
                    if (window.Toast) Toast.success(`Se marcaron ${ausentes.length} estudiantes como PRESENTES`, "Marcación Masiva");
                    refrescarPanel(true);
                })
                .catch(err => {
                    if (window.Toast) Toast.error(err.message, "Error");
                })
                .finally(() => {
                    btnMarcarTodos.disabled = false;
                    btnMarcarTodos.textContent = "Marcar Todos Presentes";
                });
        });
    }

    // 7. Supabase Realtime (WebSockets) - Cero Consultas Innecesarias a BD
    function iniciarSupabaseRealtime() {
        const sbUrl = window.CONFIG_SUPABASE?.url;
        const sbKey = window.CONFIG_SUPABASE?.anonKey;

        if (!window.supabase || !sbUrl || !sbKey) {
            console.info("[SISCO Realtime] Supabase anonKey no configurada aún en config.local.php. Operando en modo reactivo bajo demanda (Cero consultas periódicas a BD).");
            if (badgeRealtime && dotRealtime && textRealtime) {
                badgeRealtime.className = "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40 shadow-2xs";
                dotRealtime.className = "w-2 h-2 rounded-full bg-amber-400";
                textRealtime.textContent = "⚡ Realtime: Standby (Bajo Demanda)";
                badgeRealtime.title = "Configure SUPABASE_ANON_KEY en config.local.php para activar WebSockets automáticos de Supabase";
            }
            return;
        }

        try {
            supabaseClient = window.supabase.createClient(sbUrl, sbKey, {
                realtime: {
                    params: {
                        eventsPerSecond: 10
                    }
                }
            });

            // Suscribirse a cambios en las tablas de asistencias
            realtimeChannel = supabaseClient.channel('sisco-clase-en-vivo')
                .on('postgres_changes', {
                    event: '*',
                    schema: 'public',
                    table: 'asistencias_estudiantes'
                }, (payload) => {
                    procesarEventoRealtime('estudiante', payload);
                })
                .on('postgres_changes', {
                    event: '*',
                    schema: 'public',
                    table: 'asistencias_profesores'
                }, (payload) => {
                    procesarEventoRealtime('profesor', payload);
                })
                .on('postgres_changes', {
                    event: '*',
                    schema: 'public',
                    table: 'clase_diaria_indicadores'
                }, (payload) => {
                    if (currentClaseData && payload.new && String(payload.new.id_clase) === String(currentClaseData.id_clase)) {
                        refrescarPanel(true);
                    }
                })
                .subscribe((status) => {
                    if (status === 'SUBSCRIBED') {
                        if (badgeRealtime && dotRealtime && textRealtime) {
                            badgeRealtime.className = "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40 shadow-2xs";
                            dotRealtime.className = "w-2 h-2 rounded-full bg-emerald-500 animate-pulse";
                            textRealtime.textContent = "⚡ Realtime: Conectado (WebSockets)";
                            badgeRealtime.title = "Conexión WebSockets activa con Supabase. Cero consultas periódicas.";
                        }
                    } else if (status === 'CLOSED' || status === 'CHANNEL_ERROR') {
                        if (badgeRealtime && dotRealtime && textRealtime) {
                            badgeRealtime.className = "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40 shadow-2xs";
                            dotRealtime.className = "w-2 h-2 rounded-full bg-amber-400";
                            textRealtime.textContent = "⚡ Realtime: Reconectando...";
                        }
                    }
                });
        } catch (e) {
            console.error("[SISCO Realtime] Error al inicializar WebSockets:", e);
        }
    }

    function procesarEventoRealtime(tipo, payload) {
        if (!currentClaseData) return;

        const regNuevo = payload.new || {};
        const regViejo = payload.old || {};
        const fechaEvento = regNuevo.fecha || regViejo.fecha;

        // Si el evento no corresponde a la fecha de la clase actual, ignorar
        if (fechaEvento && fechaEvento !== currentClaseData.fecha) {
            return;
        }

        // Si es de estudiante, comprobar si pertenece al curso monitoreado
        if (tipo === 'estudiante') {
            const idEst = regNuevo.id_estudiante || regViejo.id_estudiante;
            const todos = [
                ...(currentClaseData.estudiantes_presentes || []),
                ...(currentClaseData.estudiantes_ausentes || [])
            ];
            const pertenece = todos.some(e => Number(e.id_estudiante) === Number(idEst));
            if (!pertenece && idEst) {
                return; // Evento de otro grado, no sobrecargar la vista
            }
        }

        // Notificar y refrescar inmediatamente
        if (window.Toast) {
            const origen = (regNuevo.tipo_registro === 'BIOMETRICO') ? 'Nodo del Aula' : 'Panel Web';
            Toast.info(`⚡ Actualización recibida en tiempo real (${origen})`, "Realtime WebSockets");
        }

        refrescarPanel(true);
    }

    // Botón manual de actualización (Bajo demanda)
    if (btnRefrescarManual) {
        btnRefrescarManual.addEventListener('click', () => {
            if (iconRefrescar) iconRefrescar.classList.add('animate-spin');
            refrescarPanel(false);
            setTimeout(() => {
                if (iconRefrescar) iconRefrescar.classList.remove('animate-spin');
            }, 600);
        });
    }

    init();
    iniciarSupabaseRealtime();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
