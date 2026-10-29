<?php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/middleware/auth.php';
require_once __DIR__ . '/../../../src/config/db.php';

verificarRol(['SuperAdmin', 'Administrador', 'Administracion']);

$rol = $_SESSION['rol'] ?? '';

// Cargar catálogos para los selectores del formulario
$turnos = [];
$niveles = [];
$bachilleratos = [];
$grados = [];

try {
    $db = (new Database())->getConnection();
    $turnos = $db->query("SELECT id_turno, nombre FROM public.turnos WHERE activo = 1 ORDER BY id_turno ASC")->fetchAll(PDO::FETCH_ASSOC);
    $niveles = $db->query("SELECT id_nivel, nombre FROM public.niveles_academicos WHERE activo = 1 ORDER BY id_nivel ASC")->fetchAll(PDO::FETCH_ASSOC);
    $bachilleratos = $db->query("SELECT id_bachillerato, nombre, codigo FROM public.bachilleratos WHERE activo = 1 ORDER BY id_bachillerato ASC")->fetchAll(PDO::FETCH_ASSOC);
    $grados = $db->query("SELECT g.id_grado, g.nombre, t.nombre AS turno_nombre FROM public.grados g LEFT JOIN public.turnos t ON g.id_turno = t.id_turno WHERE g.activo = 1 ORDER BY g.nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log("Error cargando catálogos para marcos horarios: " . $e->getMessage());
}

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10 overflow-y-auto">
    <!-- Breadcrumb de Navegación -->
    <nav class="flex items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400 mb-3" aria-label="Ruta de navegación">
        <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors flex items-center gap-1.5">
            <span>🏠</span> Inicio
        </a>
        <span>/</span>
        <a href="<?= base_url('mvc/views/horarios/index.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
            Horarios
        </a>
        <span>/</span>
        <span class="text-slate-800 dark:text-slate-200">Configuración Institucional de Horarios</span>
    </nav>

    <!-- Header Principal -->
    <header class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold text-2xl shadow-sm">
                        ⚙️
                    </span>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                            Horarios & Recesos Institucionales
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                            Control Determinista de Jornadas, Tolerancias IoT y Pausas Pedagógicas
                        </p>
                    </div>
                </div>
                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 mt-3 font-normal leading-relaxed">
                    Administrá los límites diarios de clases (apertura, tardanzas y reseteo LoRa) y configurá los recesos exactos por ciclo o curso técnico sin depender de cálculos aproximados.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" id="btn-accion-principal" onclick="ejecutarAccionPrincipal()" class="min-h-[46px] px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-extrabold shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                    <span class="text-lg">+</span>
                    <span id="btn-accion-texto">Nuevo Marco Horario</span>
                </button>
                <a href="<?= base_url('mvc/views/horarios/index.php') ?>" class="min-h-[46px] px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-sm font-bold border-2 border-slate-200 dark:border-slate-700 transition-all flex items-center gap-2">
                    <span>📅</span>
                    <span>Horario por Materias</span>
                </a>
                <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="min-h-[46px] px-5 py-2.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 rounded-xl text-sm font-bold border-2 border-indigo-200 dark:border-indigo-800 transition-all flex items-center gap-2">
                    <span>⬅️</span>
                    <span>Administración</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Pestañas Principales (Tabs) -->
    <div class="flex items-center gap-2 p-1.5 bg-slate-200/80 dark:bg-slate-800/80 rounded-2xl w-fit mb-6 shadow-inner">
        <button type="button" id="tab-btn-marcos" onclick="cambiarPestanaPrincipal('marcos')" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center gap-2 bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-300 shadow-sm">
            <span>⏱️</span>
            <span>Marcos de Jornada IoT</span>
        </button>
        <button type="button" id="tab-btn-recesos" onclick="cambiarPestanaPrincipal('recesos')" class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
            <span>☕</span>
            <span>Recesos Institucionales</span>
        </button>
    </div>

    <!-- SECCIÓN 1: MARCOS DE JORNADA IOT -->
    <div id="seccion-marcos" class="space-y-6">
        <!-- Banner Informativo LoRa -->
        <div class="p-5 rounded-3xl bg-indigo-50/80 dark:bg-indigo-950/40 border-2 border-indigo-200/80 dark:border-indigo-800/60 shadow-xs text-indigo-950 dark:text-indigo-200 space-y-2">
            <div class="flex items-center gap-2 font-black text-sm sm:text-base">
                <span>🛡️</span>
                <span>Reglas de Biometría & Sincronización LoRa en Tiempo Real</span>
            </div>
            <ul class="text-xs sm:text-sm space-y-1 text-indigo-900 dark:text-indigo-300 list-disc list-inside">
                <li><strong>Apertura de Aula:</strong> El sensor óptico DY50 comienza a aceptar marcaciones a partir de la <em>Hora de Apertura</em>.</li>
                <li><strong>Tolerancia de Tardanza:</strong> Quienes marquen entre la <em>Hora de Inicio</em> y los minutos de tolerancia se computan como <code>PRESENTE</code>. Pasado ese tiempo, se registra <code>TARDANZA</code>.</li>
                <li><strong>Cierre de Jornada & Reseteo:</strong> Al alcanzarse la <em>Hora de Cierre</em> de ese curso, el Gateway LoRa transmite la trama <code>CMD:RESET_DIA</code> al aula para limpiar la memoria diaria de marcaciones.</li>
                <li><strong>Prioridad Jerárquica:</strong> Curso Específico &gt; Bachillerato &gt; Nivel Académico &gt; Turno General.</li>
            </ul>
        </div>

        <!-- Filtros de Marcos -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-wrap" id="contenedor-pestanas-dias">
                    <button type="button" data-dia="" class="btn-filtro-dia px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold transition bg-indigo-600 text-white shadow-xs">
                        Todos los días
                    </button>
                    <button type="button" data-dia="Lunes" class="btn-filtro-dia px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold transition bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                        Lunes
                    </button>
                    <button type="button" data-dia="Martes" class="btn-filtro-dia px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold transition bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                        Martes
                    </button>
                    <button type="button" data-dia="Miercoles" class="btn-filtro-dia px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold transition bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                        Miércoles
                    </button>
                    <button type="button" data-dia="Jueves" class="btn-filtro-dia px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold transition bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                        Jueves
                    </button>
                    <button type="button" data-dia="Viernes" class="btn-filtro-dia px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold transition bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                        Viernes
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <select id="filtro-turno" class="px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">Todos los turnos</option>
                        <?php foreach ($turnos as $t): ?>
                            <option value="<?= $t['id_turno'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filtro-bachi" class="px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">Todos los bachilleratos</option>
                        <?php foreach ($bachilleratos as $b): ?>
                            <option value="<?= $b['id_bachillerato'] ?>"><?= htmlspecialchars($b['codigo'] ?? $b['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla de Marcos Horarios -->
        <div class="rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                    <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3.5 px-4">Día</th>
                            <th class="py-3.5 px-4">Turno</th>
                            <th class="py-3.5 px-4">Alcance / Especialidad</th>
                            <th class="py-3.5 px-4 text-center">Apertura</th>
                            <th class="py-3.5 px-4 text-center">Inicio Clases</th>
                            <th class="py-3.5 px-4 text-center">Tolerancia</th>
                            <th class="py-3.5 px-4 text-center">Cierre Jornada</th>
                            <th class="py-3.5 px-4">Descripción</th>
                            <th class="py-3.5 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-marcos-body" class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <span class="inline-block animate-spin text-2xl mb-2">⏳</span>
                                <p class="font-bold">Cargando marcos horarios institucionales...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: RECESOS INSTITUCIONALES -->
    <div id="seccion-recesos" class="space-y-6 hidden">
        <!-- Banner Informativo Recesos -->
        <div class="p-5 rounded-3xl bg-amber-50/80 dark:bg-amber-950/40 border-2 border-amber-200/80 dark:border-amber-800/60 shadow-xs text-amber-950 dark:text-amber-200 space-y-2">
            <div class="flex items-center gap-2 font-black text-sm sm:text-base">
                <span>☕</span>
                <span>Configuración Determinista de Recesos, Almuerzos y Pausas</span>
            </div>
            <p class="text-xs sm:text-sm text-amber-900 dark:text-amber-300 leading-relaxed">
                A diferencia de los cálculos matemáticos aproximados, los recesos aquí definidos son <strong>exactos</strong>. Si un curso (ej. 3° BTI) tiene almuerzo de 12:10 a 12:50 los Lunes, Martes y Jueves, pero los Miércoles tiene horario continuado, el sistema sabrá con precisión cuándo mostrar la pausa y cuándo continuar la clase en vivo.
            </p>
        </div>

        <!-- Filtros de Recesos -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-wrap" id="contenedor-pestanas-recesos-dias">
                    <button type="button" data-dia-receso="" class="btn-filtro-receso-dia px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold transition bg-amber-600 text-white shadow-xs">
                        Todos los días
                    </button>
                    <button type="button" data-dia-receso="Lunes" class="btn-filtro-receso-dia px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold transition bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                        Lunes
                    </button>
                    <button type="button" data-dia-receso="Martes" class="btn-filtro-receso-dia px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold transition bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                        Martes
                    </button>
                    <button type="button" data-dia-receso="Miercoles" class="btn-filtro-receso-dia px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold transition bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                        Miércoles
                    </button>
                    <button type="button" data-dia-receso="Jueves" class="btn-filtro-receso-dia px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold transition bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                        Jueves
                    </button>
                    <button type="button" data-dia-receso="Viernes" class="btn-filtro-receso-dia px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold transition bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200">
                        Viernes
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <select id="filtro-receso-turno" class="px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">Todos los turnos</option>
                        <?php foreach ($turnos as $t): ?>
                            <option value="<?= $t['id_turno'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filtro-receso-bachi" class="px-3 py-1.5 rounded-xl text-xs sm:text-sm font-bold border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white">
                        <option value="">Todos los bachilleratos</option>
                        <?php foreach ($bachilleratos as $b): ?>
                            <option value="<?= $b['id_bachillerato'] ?>"><?= htmlspecialchars($b['codigo'] ?? $b['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla de Recesos Institucionales -->
        <div class="rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                    <thead class="bg-amber-50/60 dark:bg-amber-950/40 text-xs uppercase font-extrabold text-amber-900 dark:text-amber-200 border-b border-amber-200/60 dark:border-amber-800/60">
                        <tr>
                            <th class="py-3.5 px-4">Día</th>
                            <th class="py-3.5 px-4">Turno</th>
                            <th class="py-3.5 px-4">Nombre del Receso</th>
                            <th class="py-3.5 px-4">Alcance / Curso</th>
                            <th class="py-3.5 px-4 text-center">Horario</th>
                            <th class="py-3.5 px-4 text-center">Duración</th>
                            <th class="py-3.5 px-4">Descripción</th>
                            <th class="py-3.5 px-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-recesos-body" class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                <span class="inline-block animate-spin text-2xl mb-2">⏳</span>
                                <p class="font-bold">Cargando recesos institucionales...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Modal Crear / Editar Marco Horario Institucional -->
<div id="modal-marco" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-marco-titulo">
    <div class="relative w-full max-w-xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header Modal -->
        <div class="flex items-center justify-between p-5 sm:p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/80">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-xl font-bold">
                    ⏱️
                </div>
                <div>
                    <h2 id="modal-marco-titulo" class="text-xl font-extrabold text-slate-900 dark:text-white">
                        Nuevo Marco Horario
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Configuración de jornada institucional para biometría y reset LoRa
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalMarco()" class="w-9 h-9 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800 flex items-center justify-center text-lg font-bold transition cursor-pointer" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <!-- Formulario -->
        <form id="form-marco" onsubmit="guardarMarcoHorario(event)" class="p-5 sm:p-6 space-y-4">
            <input type="hidden" id="marco-id" name="id_marco" value="0">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="marco-dia" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Día de la Semana *
                    </label>
                    <select id="marco-dia" name="dia_semana" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm font-semibold">
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miercoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sabado">Sábado</option>
                    </select>
                </div>

                <div>
                    <label for="marco-turno" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Turno *
                    </label>
                    <select id="marco-turno" name="id_turno" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm font-semibold">
                        <?php foreach ($turnos as $t): ?>
                            <option value="<?= $t['id_turno'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="marco-nivel" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Nivel Académico (Opcional)
                    </label>
                    <select id="marco-nivel" name="id_nivel" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm font-semibold">
                        <option value="">Todos los niveles</option>
                        <?php foreach ($niveles as $n): ?>
                            <option value="<?= $n['id_nivel'] ?>"><?= htmlspecialchars($n['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="marco-bachi" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Bachillerato / Excepción *
                    </label>
                    <select id="marco-bachi" name="id_bachillerato" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm font-semibold">
                        <option value="">General (Todos los bachilleratos)</option>
                        <?php foreach ($bachilleratos as $b): ?>
                            <option value="<?= $b['id_bachillerato'] ?>"><?= htmlspecialchars($b['nombre']) ?> (<?= $b['codigo'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label for="marco-grado" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                    Curso / Grado Específico (Opcional — Máxima Prioridad de Salida)
                </label>
                <select id="marco-grado" name="id_grado" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm font-semibold">
                    <option value="">Aplica a todo el bachillerato / nivel seleccionado</option>
                    <?php foreach ($grados as $g): ?>
                        <option value="<?= $g['id_grado'] ?>"><?= htmlspecialchars($g['nombre']) ?> (<?= htmlspecialchars($g['turno_nombre'] ?? 'Sin turno') ?>)</option>
                    <?php endforeach; ?>
                </select>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">Ideal para cursos con salida extendida variable (ej. 1° BTI Viernes 14:10, 2° BTI Martes 16:00, 3° BTI Lunes/Jueves 15:30).</span>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/80 space-y-3">
                <span class="text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400 block">
                    Horarios de la Jornada
                </span>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label for="marco-apertura" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                            Apertura Aula
                        </label>
                        <input type="time" id="marco-apertura" name="hora_apertura" required value="06:45"
                               class="w-full px-2.5 py-1.5 text-center font-mono font-bold text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label for="marco-inicio" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                            Inicio Clases
                        </label>
                        <input type="time" id="marco-inicio" name="hora_inicio" required value="07:00"
                               class="w-full px-2.5 py-1.5 text-center font-mono font-bold text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label for="marco-tolerancia" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                            Tolerancia (Min)
                        </label>
                        <input type="number" id="marco-tolerancia" name="tolerancia_minutos" min="0" max="60" required value="15"
                               class="w-full px-2.5 py-1.5 text-center font-mono font-bold text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label for="marco-cierre" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                            Cierre Jornada
                        </label>
                        <input type="time" id="marco-cierre" name="hora_cierre" required value="12:00"
                               class="w-full px-2.5 py-1.5 text-center font-mono font-bold text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                    </div>
                </div>
            </div>

            <div>
                <label for="marco-desc" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                    Descripción / Observaciones
                </label>
                <input type="text" id="marco-desc" name="descripcion" placeholder="Ej. Jornada extendida con materias técnicas BTI"
                       class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm">
            </div>

            <!-- Acciones Modal -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="cerrarModalMarco()" class="px-4 py-2 text-sm font-semibold rounded-xl border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-marco" class="px-5 py-2 text-sm font-bold rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition flex items-center gap-2">
                    <span>💾</span>
                    <span>Guardar Marco Horario</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Crear / Editar Receso Institucional -->
<div id="modal-receso" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-receso-titulo">
    <div class="relative w-full max-w-xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-amber-200 dark:border-amber-800 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header Modal Receso -->
        <div class="flex items-center justify-between p-5 sm:p-6 border-b border-amber-200/80 dark:border-amber-800/80 bg-amber-50/70 dark:bg-amber-950/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-200 flex items-center justify-center text-xl font-bold">
                    ☕
                </div>
                <div>
                    <h2 id="modal-receso-titulo" class="text-xl font-extrabold text-amber-950 dark:text-amber-100">
                        Nuevo Receso Institucional
                    </h2>
                    <p class="text-xs text-amber-800 dark:text-amber-300">
                        Pausa pedagógica o almuerzo programado con precisión quirúrgica
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalReceso()" class="w-9 h-9 rounded-xl text-amber-700 hover:text-amber-950 dark:text-amber-300 dark:hover:text-white hover:bg-amber-100 dark:hover:bg-amber-900/60 flex items-center justify-center text-lg font-bold transition cursor-pointer" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <!-- Formulario Receso -->
        <form id="form-receso" onsubmit="guardarReceso(event)" class="p-5 sm:p-6 space-y-4">
            <input type="hidden" id="receso-id" name="id_receso" value="0">

            <div>
                <label for="receso-nombre" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                    Nombre del Receso / Pausa *
                </label>
                <input type="text" id="receso-nombre" name="nombre" required placeholder="Ej. Receso Principal Matutino, Almuerzo BTI, Pausa 9ª HC"
                       class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm font-semibold">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="receso-dia" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Día de la Semana *
                    </label>
                    <select id="receso-dia" name="dia_semana" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm font-semibold">
                        <option value="Todos">Todos los días (Lunes a Viernes)</option>
                        <option value="Lunes">Lunes</option>
                        <option value="Martes">Martes</option>
                        <option value="Miercoles">Miércoles</option>
                        <option value="Jueves">Jueves</option>
                        <option value="Viernes">Viernes</option>
                        <option value="Sabado">Sábado</option>
                    </select>
                </div>

                <div>
                    <label for="receso-turno" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Turno *
                    </label>
                    <select id="receso-turno" name="id_turno" required class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm font-semibold">
                        <?php foreach ($turnos as $t): ?>
                            <option value="<?= $t['id_turno'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="receso-nivel" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Nivel Académico (Opcional)
                    </label>
                    <select id="receso-nivel" name="id_nivel" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm font-semibold">
                        <option value="">Todos los niveles</option>
                        <?php foreach ($niveles as $n): ?>
                            <option value="<?= $n['id_nivel'] ?>"><?= htmlspecialchars($n['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="receso-bachi" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                        Bachillerato (Opcional)
                    </label>
                    <select id="receso-bachi" name="id_bachillerato" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm font-semibold">
                        <option value="">General (Todos los bachilleratos)</option>
                        <?php foreach ($bachilleratos as $b): ?>
                            <option value="<?= $b['id_bachillerato'] ?>"><?= htmlspecialchars($b['nombre']) ?> (<?= $b['codigo'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div>
                <label for="receso-grado" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                    Curso / Grado Específico (Opcional — Máxima Precisión)
                </label>
                <select id="receso-grado" name="id_grado" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm font-semibold">
                    <option value="">Aplica a todo el nivel o bachillerato seleccionado</option>
                    <?php foreach ($grados as $g): ?>
                        <option value="<?= $g['id_grado'] ?>"><?= htmlspecialchars($g['nombre']) ?> (<?= htmlspecialchars($g['turno_nombre'] ?? 'Sin turno') ?>)</option>
                    <?php endforeach; ?>
                </select>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">Configurá pausas particulares (ej. almuerzo 12:10 para 3° BTI solo Lunes/Martes/Jueves).</span>
            </div>

            <div class="p-4 rounded-2xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/60 space-y-3">
                <span class="text-xs font-black uppercase tracking-wider text-amber-700 dark:text-amber-400 block">
                    Horario de Inicio y Finalización del Receso
                </span>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="receso-inicio" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                            Hora Inicio *
                        </label>
                        <input type="time" id="receso-inicio" name="hora_inicio" required value="10:10"
                               class="w-full px-2.5 py-1.5 text-center font-mono font-bold text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                    </div>
                    <div>
                        <label for="receso-fin" class="block text-[11px] font-bold text-slate-600 dark:text-slate-400 mb-1">
                            Hora Fin *
                        </label>
                        <input type="time" id="receso-fin" name="hora_fin" required value="10:50"
                               class="w-full px-2.5 py-1.5 text-center font-mono font-bold text-sm rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white">
                    </div>
                </div>
            </div>

            <div>
                <label for="receso-desc" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                    Descripción / Observaciones
                </label>
                <input type="text" id="receso-desc" name="descripcion" placeholder="Ej. Pausa pedagógica intermedia y merienda escolar"
                       class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-sm">
            </div>

            <!-- Acciones Modal Receso -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="cerrarModalReceso()" class="px-4 py-2 text-sm font-semibold rounded-xl border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-receso" class="px-5 py-2 text-sm font-bold rounded-xl bg-amber-600 hover:bg-amber-700 text-white shadow-sm transition flex items-center gap-2">
                    <span>💾</span>
                    <span>Guardar Receso</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
window.BASE_URL = "<?= base_url('') ?>";

(function() {
    let pestanaActiva = 'marcos';
    let marcosData = [];
    let recesosData = [];
    let diaSeleccionado = '';
    let diaRecesoSeleccionado = '';

    document.addEventListener('DOMContentLoaded', () => {
        cargarMarcos();
        cargarRecesos();

        // Filtro por pestañas de días (Marcos)
        document.querySelectorAll('.btn-filtro-dia').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.btn-filtro-dia').forEach(b => {
                    b.classList.remove('bg-indigo-600', 'text-white', 'shadow-xs');
                    b.classList.add('bg-slate-100', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
                });
                this.classList.add('bg-indigo-600', 'text-white', 'shadow-xs');
                this.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');

                diaSeleccionado = this.dataset.dia || '';
                aplicarFiltrosMarcos();
            });
        });

        // Filtro por pestañas de días (Recesos)
        document.querySelectorAll('.btn-filtro-receso-dia').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.btn-filtro-receso-dia').forEach(b => {
                    b.classList.remove('bg-amber-600', 'text-white', 'shadow-xs');
                    b.classList.add('bg-slate-100', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
                });
                this.classList.add('bg-amber-600', 'text-white', 'shadow-xs');
                this.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');

                diaRecesoSeleccionado = this.dataset.diaReceso || '';
                aplicarFiltrosRecesos();
            });
        });

        // Filtros selects Marcos
        document.getElementById('filtro-turno').addEventListener('change', aplicarFiltrosMarcos);
        document.getElementById('filtro-bachi').addEventListener('change', aplicarFiltrosMarcos);

        // Filtros selects Recesos
        document.getElementById('filtro-receso-turno').addEventListener('change', aplicarFiltrosRecesos);
        document.getElementById('filtro-receso-bachi').addEventListener('change', aplicarFiltrosRecesos);
    });

    window.cambiarPestanaPrincipal = function(tab) {
        pestanaActiva = tab;
        const tabMarcos = document.getElementById('tab-btn-marcos');
        const tabRecesos = document.getElementById('tab-btn-recesos');
        const secMarcos = document.getElementById('seccion-marcos');
        const secRecesos = document.getElementById('seccion-recesos');
        const btnAccionTexto = document.getElementById('btn-accion-texto');
        const btnAccionPrincipal = document.getElementById('btn-accion-principal');

        if (tab === 'marcos') {
            tabMarcos.className = "px-5 py-2.5 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center gap-2 bg-white dark:bg-slate-900 text-indigo-700 dark:text-indigo-300 shadow-sm";
            tabRecesos.className = "px-5 py-2.5 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white";
            secMarcos.classList.remove('hidden');
            secRecesos.classList.add('hidden');
            btnAccionTexto.textContent = "Nuevo Marco Horario";
            btnAccionPrincipal.className = "min-h-[46px] px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-extrabold shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95";
        } else {
            tabRecesos.className = "px-5 py-2.5 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center gap-2 bg-white dark:bg-slate-900 text-amber-700 dark:text-amber-300 shadow-sm";
            tabMarcos.className = "px-5 py-2.5 rounded-xl text-xs sm:text-sm font-black transition-all flex items-center gap-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white";
            secRecesos.classList.remove('hidden');
            secMarcos.classList.add('hidden');
            btnAccionTexto.textContent = "Nuevo Receso Institucional";
            btnAccionPrincipal.className = "min-h-[46px] px-5 py-2.5 bg-amber-600 hover:bg-amber-500 text-white rounded-xl text-sm font-extrabold shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95";
        }
    };

    window.ejecutarAccionPrincipal = function() {
        if (pestanaActiva === 'marcos') {
            abrirModalCrearMarco();
        } else {
            abrirModalCrearReceso();
        }
    };

    // ==========================================
    // LÓGICA DE MARCOS HORARIOS
    // ==========================================
    async function cargarMarcos() {
        const tbody = document.getElementById('tabla-marcos-body');
        try {
            const res = await fetch(`${window.BASE_URL}src/api/Horario/leer_marcos_horarios.php`);
            const data = await res.json();

            if (data.status === 'success' && Array.isArray(data.data)) {
                marcosData = data.data;
                aplicarFiltrosMarcos();
            } else {
                tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-rose-500 font-bold">Error al cargar marcos: ${data.message || ''}</td></tr>`;
            }
        } catch (err) {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-rose-500 font-bold">Error de conexión al cargar marcos horarios.</td></tr>`;
        }
    }

    function aplicarFiltrosMarcos() {
        const idTurno = document.getElementById('filtro-turno').value;
        const idBachi = document.getElementById('filtro-bachi').value;

        const filtrados = marcosData.filter(m => {
            if (diaSeleccionado && m.dia_semana.toLowerCase() !== diaSeleccionado.toLowerCase()) return false;
            if (idTurno && String(m.id_turno) !== String(idTurno)) return false;
            if (idBachi && String(m.id_bachillerato) !== String(idBachi)) return false;
            return true;
        });

        renderizarTablaMarcos(filtrados);
    }

    function renderizarTablaMarcos(lista) {
        const tbody = document.getElementById('tabla-marcos-body');
        tbody.innerHTML = '';

        if (!lista || lista.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-slate-400">No hay marcos horarios registrados para este filtro.</td></tr>`;
            return;
        }

        lista.forEach(m => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 dark:hover:bg-slate-800/50 transition';

            let bachiBadge = '';
            if (m.id_grado && m.grado_nombre) {
                bachiBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-purple-100 text-purple-900 dark:bg-purple-950/80 dark:text-purple-300 border border-purple-300 dark:border-purple-800">🎯 Curso: ${m.grado_nombre}</span>`;
            } else if (m.id_bachillerato) {
                bachiBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-100 text-indigo-800 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">⭐ ${m.bachillerato_codigo || m.bachillerato_nombre}</span>`;
            } else if (m.id_nivel) {
                bachiBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">${m.nivel_nombre}</span>`;
            } else {
                bachiBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">General Turno</span>`;
            }

            tr.innerHTML = `
                <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full ${obtenerColorDia(m.dia_semana)}"></span>
                        <span>${m.dia_semana}</span>
                    </span>
                </td>
                <td class="py-3 px-4 font-semibold">
                    <span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200/50">
                        ${m.turno_nombre}
                    </span>
                </td>
                <td class="py-3 px-4">
                    ${bachiBadge}
                    <div class="text-[11px] text-slate-400 mt-0.5">${m.nivel_nombre || 'Todos los niveles'}</div>
                </td>
                <td class="py-3 px-4 text-center font-mono font-bold text-slate-600 dark:text-slate-400">
                    ${m.hora_apertura.substring(0, 5)}
                </td>
                <td class="py-3 px-4 text-center font-mono font-extrabold text-emerald-600 dark:text-emerald-400">
                    ${m.hora_inicio.substring(0, 5)}
                </td>
                <td class="py-3 px-4 text-center font-mono text-xs">
                    <span class="px-2 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 font-bold border border-amber-200/50">
                        +${m.tolerancia_minutos} min
                    </span>
                </td>
                <td class="py-3 px-4 text-center font-mono font-extrabold text-indigo-600 dark:text-indigo-400">
                    ${m.hora_cierre.substring(0, 5)}
                </td>
                <td class="py-3 px-4 text-xs text-slate-500 max-w-xs truncate" title="${m.descripcion || ''}">
                    ${m.descripcion || '-'}
                </td>
                <td class="py-3 px-4 text-center">
                    <div class="inline-flex items-center gap-1.5">
                        <button type="button" onclick='abrirModalEditarMarco(${JSON.stringify(m)})' class="p-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition cursor-pointer" title="Editar">
                            ✏️
                        </button>
                        <button type="button" onclick="desactivarMarco(${m.id_marco})" class="p-1.5 rounded-lg text-slate-600 hover:text-rose-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition cursor-pointer" title="Desactivar">
                            🗑️
                        </button>
                    </div>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    window.abrirModalCrearMarco = function() {
        document.getElementById('modal-marco-titulo').textContent = 'Nuevo Marco Horario Institucional';
        document.getElementById('marco-id').value = '0';
        document.getElementById('marco-dia').value = diaSeleccionado || 'Lunes';
        document.getElementById('marco-turno').selectedIndex = 0;
        document.getElementById('marco-nivel').value = '';
        document.getElementById('marco-bachi').value = '';
        document.getElementById('marco-grado').value = '';
        document.getElementById('marco-apertura').value = '06:45';
        document.getElementById('marco-inicio').value = '07:00';
        document.getElementById('marco-tolerancia').value = '15';
        document.getElementById('marco-cierre').value = '12:00';
        document.getElementById('marco-desc').value = '';

        document.getElementById('modal-marco').classList.remove('hidden');
    };

    window.abrirModalEditarMarco = function(m) {
        document.getElementById('modal-marco-titulo').textContent = 'Editar Marco Horario Institucional';
        document.getElementById('marco-id').value = m.id_marco;
        document.getElementById('marco-dia').value = m.dia_semana;
        document.getElementById('marco-turno').value = m.id_turno;
        document.getElementById('marco-nivel').value = m.id_nivel || '';
        document.getElementById('marco-bachi').value = m.id_bachillerato || '';
        document.getElementById('marco-grado').value = m.id_grado || '';
        document.getElementById('marco-apertura').value = m.hora_apertura.substring(0, 5);
        document.getElementById('marco-inicio').value = m.hora_inicio.substring(0, 5);
        document.getElementById('marco-tolerancia').value = m.tolerancia_minutos;
        document.getElementById('marco-cierre').value = m.hora_cierre.substring(0, 5);
        document.getElementById('marco-desc').value = m.descripcion || '';

        document.getElementById('modal-marco').classList.remove('hidden');
    };

    window.cerrarModalMarco = function() {
        document.getElementById('modal-marco').classList.add('hidden');
    };

    window.guardarMarcoHorario = async function(e) {
        e.preventDefault();
        const form = document.getElementById('form-marco');
        const formData = new FormData(form);
        const btn = document.getElementById('btn-submit-marco');

        btn.disabled = true;
        btn.classList.add('opacity-70');

        try {
            const res = await fetch(`${window.BASE_URL}src/api/Horario/guardar_marco_horario.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                cerrarModalMarco();
                cargarMarcos();
                alert('✅ ' + data.message);
            } else {
                alert('⚠️ ' + (data.message || 'Error al guardar marco horario.'));
            }
        } catch (err) {
            console.error(err);
            alert('❌ Error de conexión al guardar marco horario.');
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-70');
        }
    };

    window.desactivarMarco = async function(id) {
        if (!confirm('¿Está seguro de desactivar este marco horario institucional?')) return;

        try {
            const res = await fetch(`${window.BASE_URL}src/api/Horario/desactivar_marco_horario.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_marco=${encodeURIComponent(id)}`
            });
            const data = await res.json();

            if (data.status === 'success') {
                cargarMarcos();
            } else {
                alert('⚠️ ' + data.message);
            }
        } catch (err) {
            console.error(err);
            alert('❌ Error de conexión al desactivar.');
        }
    };

    // ==========================================
    // LÓGICA DE RECESOS INSTITUCIONALES
    // ==========================================
    async function cargarRecesos() {
        const tbody = document.getElementById('tabla-recesos-body');
        try {
            const res = await fetch(`${window.BASE_URL}src/api/Horario/leer_recesos.php`);
            const data = await res.json();

            if (data.status === 'success' && Array.isArray(data.data)) {
                recesosData = data.data;
                aplicarFiltrosRecesos();
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="py-8 text-center text-rose-500 font-bold">Error al cargar recesos: ${data.message || ''}</td></tr>`;
            }
        } catch (err) {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="8" class="py-8 text-center text-rose-500 font-bold">Error de conexión al cargar recesos institucionales.</td></tr>`;
        }
    }

    function aplicarFiltrosRecesos() {
        const idTurno = document.getElementById('filtro-receso-turno').value;
        const idBachi = document.getElementById('filtro-receso-bachi').value;

        const filtrados = recesosData.filter(r => {
            if (diaRecesoSeleccionado && r.dia_semana.toLowerCase() !== diaRecesoSeleccionado.toLowerCase() && r.dia_semana !== 'Todos') return false;
            if (idTurno && String(r.id_turno) !== String(idTurno)) return false;
            if (idBachi && String(r.id_bachillerato) !== String(idBachi)) return false;
            return true;
        });

        renderizarTablaRecesos(filtrados);
    }

    function renderizarTablaRecesos(lista) {
        const tbody = document.getElementById('tabla-recesos-body');
        tbody.innerHTML = '';

        if (!lista || lista.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="py-8 text-center text-slate-400">No hay recesos institucionales registrados para este filtro.</td></tr>`;
            return;
        }

        lista.forEach(r => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-amber-50/40 dark:hover:bg-amber-950/20 transition';

            let alcanceBadge = '';
            if (r.id_grado && r.grado_nombre) {
                alcanceBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-purple-100 text-purple-900 dark:bg-purple-950/80 dark:text-purple-300 border border-purple-300 dark:border-purple-800">🎯 Curso: ${r.grado_nombre}</span>`;
            } else if (r.id_bachillerato) {
                alcanceBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-100 text-indigo-800 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">⭐ ${r.bachillerato_codigo || r.bachillerato_nombre}</span>`;
            } else if (r.id_nivel) {
                alcanceBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">${r.nivel_nombre}</span>`;
            } else {
                alcanceBadge = `<span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">General Turno</span>`;
            }

            const hIni = r.hora_inicio.substring(0, 5);
            const hFin = r.hora_fin.substring(0, 5);
            const minutosDuracion = Math.max(0, Math.round((new Date(`1970-01-01T${r.hora_fin}`) - new Date(`1970-01-01T${r.hora_inicio}`)) / 60000));

            tr.innerHTML = `
                <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full ${r.dia_semana === 'Todos' ? 'bg-amber-500' : obtenerColorDia(r.dia_semana)}"></span>
                        <span>${r.dia_semana === 'Todos' ? 'Lunes a Viernes' : r.dia_semana}</span>
                    </span>
                </td>
                <td class="py-3 px-4 font-semibold">
                    <span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200/50">
                        ${r.turno_nombre}
                    </span>
                </td>
                <td class="py-3 px-4 font-extrabold text-amber-900 dark:text-amber-200">
                    <div class="flex items-center gap-1.5">
                        <span>☕</span>
                        <span>${r.nombre}</span>
                    </div>
                </td>
                <td class="py-3 px-4">
                    ${alcanceBadge}
                    <div class="text-[11px] text-slate-400 mt-0.5">${r.nivel_nombre || 'Todos los niveles'}</div>
                </td>
                <td class="py-3 px-4 text-center font-mono font-black text-slate-800 dark:text-slate-200">
                    ${hIni} - ${hFin}
                </td>
                <td class="py-3 px-4 text-center">
                    <span class="px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-950/70 text-amber-900 dark:text-amber-200 font-bold text-xs border border-amber-300 dark:border-amber-800">
                        ${minutosDuracion} min
                    </span>
                </td>
                <td class="py-3 px-4 text-xs text-slate-500 max-w-xs truncate" title="${r.descripcion || ''}">
                    ${r.descripcion || '-'}
                </td>
                <td class="py-3 px-4 text-center">
                    <div class="inline-flex items-center gap-1.5">
                        <button type="button" onclick='abrirModalEditarReceso(${JSON.stringify(r)})' class="p-1.5 rounded-lg text-slate-600 hover:text-amber-600 hover:bg-amber-50 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition cursor-pointer" title="Editar">
                            ✏️
                        </button>
                        <button type="button" onclick="desactivarReceso(${r.id_receso})" class="p-1.5 rounded-lg text-slate-600 hover:text-rose-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition cursor-pointer" title="Desactivar">
                            🗑️
                        </button>
                    </div>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    window.abrirModalCrearReceso = function() {
        document.getElementById('modal-receso-titulo').textContent = 'Nuevo Receso Institucional';
        document.getElementById('receso-id').value = '0';
        document.getElementById('receso-nombre').value = '';
        document.getElementById('receso-dia').value = diaRecesoSeleccionado || 'Todos';
        document.getElementById('receso-turno').selectedIndex = 0;
        document.getElementById('receso-nivel').value = '';
        document.getElementById('receso-bachi').value = '';
        document.getElementById('receso-grado').value = '';
        document.getElementById('receso-inicio').value = '10:10';
        document.getElementById('receso-fin').value = '10:50';
        document.getElementById('receso-desc').value = '';

        document.getElementById('modal-receso').classList.remove('hidden');
    };

    window.abrirModalEditarReceso = function(r) {
        document.getElementById('modal-receso-titulo').textContent = 'Editar Receso Institucional';
        document.getElementById('receso-id').value = r.id_receso;
        document.getElementById('receso-nombre').value = r.nombre;
        document.getElementById('receso-dia').value = r.dia_semana;
        document.getElementById('receso-turno').value = r.id_turno;
        document.getElementById('receso-nivel').value = r.id_nivel || '';
        document.getElementById('receso-bachi').value = r.id_bachillerato || '';
        document.getElementById('receso-grado').value = r.id_grado || '';
        document.getElementById('receso-inicio').value = r.hora_inicio.substring(0, 5);
        document.getElementById('receso-fin').value = r.hora_fin.substring(0, 5);
        document.getElementById('receso-desc').value = r.descripcion || '';

        document.getElementById('modal-receso').classList.remove('hidden');
    };

    window.cerrarModalReceso = function() {
        document.getElementById('modal-receso').classList.add('hidden');
    };

    window.guardarReceso = async function(e) {
        e.preventDefault();
        const form = document.getElementById('form-receso');
        const formData = new FormData(form);
        const btn = document.getElementById('btn-submit-receso');

        btn.disabled = true;
        btn.classList.add('opacity-70');

        try {
            const res = await fetch(`${window.BASE_URL}src/api/Horario/guardar_receso.php`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                cerrarModalReceso();
                cargarRecesos();
                alert('✅ ' + data.message);
            } else {
                alert('⚠️ ' + (data.message || 'Error al guardar receso institucional.'));
            }
        } catch (err) {
            console.error(err);
            alert('❌ Error de conexión al guardar receso institucional.');
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-70');
        }
    };

    window.desactivarReceso = async function(id) {
        if (!confirm('¿Está seguro de desactivar este receso institucional?')) return;

        try {
            const res = await fetch(`${window.BASE_URL}src/api/Horario/desactivar_receso.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_receso=${encodeURIComponent(id)}`
            });
            const data = await res.json();

            if (data.status === 'success') {
                cargarRecesos();
            } else {
                alert('⚠️ ' + data.message);
            }
        } catch (err) {
            console.error(err);
            alert('❌ Error de conexión al desactivar receso.');
        }
    };

    // ==========================================
    // UTILIDADES
    // ==========================================
    function obtenerColorDia(dia) {
        const d = (dia || '').toLowerCase();
        if (d.includes('lun')) return 'bg-blue-500';
        if (d.includes('mar')) return 'bg-cyan-500';
        if (d.includes('mie') || d.includes('mié')) return 'bg-teal-500';
        if (d.includes('jue')) return 'bg-emerald-500';
        if (d.includes('vie')) return 'bg-amber-500';
        return 'bg-purple-500';
    }
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
