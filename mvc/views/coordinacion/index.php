<?php
require_once __DIR__ . '/../../../src/middleware/auth.php';
verificarRol(['SuperAdmin', 'Administrador', 'Coordinador']);

require_once __DIR__ . '/../../../src/config/db.php';
require_once __DIR__ . '/../../../src/classes/Coordinador.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$db = (new Database())->getConnection();
$coordinadorService = new Coordinador($db);

$idUsuario = (int)$_SESSION['id_usuario'];
$rol = $_SESSION['rol'];
$fechaFiltro = $_GET['fecha'] ?? date('Y-m-d');

$grados = $coordinadorService->obtenerGradosCoordinados($idUsuario, $rol, $fechaFiltro);
$metricas = $coordinadorService->obtenerMetricasGenerales($idUsuario, $rol, $fechaFiltro, $grados);

// Opciones únicas para filtros en cascada
$turnosDisponibles = array_values(array_filter(array_unique(array_map(fn($g) => trim((string)($g['turno_nombre'] ?? $g['turno'] ?? '')), $grados))));
$nivelesDisponibles = array_values(array_filter(array_unique(array_map(fn($g) => trim((string)($g['nivel_nombre'] ?? '')), $grados))));
$bachilleratosDisponibles = array_values(array_filter(array_unique(array_map(fn($g) => trim((string)($g['bachillerato_nombre'] ?? '')), $grados))));
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Breadcrumb Accesible -->
        <nav class="flex items-center gap-2 text-base text-slate-600 font-medium" aria-label="Migas de pan">
            <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="hover:text-indigo-600 transition flex items-center gap-1.5 focus:outline-none focus:underline">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Inicio
            </a>
            <span class="text-slate-400">/</span>
            <span class="text-slate-900 font-bold">Coordinación Académica</span>
        </nav>

        <!-- Encabezado con Letras Grandes y Selector de Fecha Cómodo -->
        <div class="app-page-header p-6 sm:p-8 rounded-3xl flex flex-col md:flex-row md:items-center md:justify-between gap-6 border-2 border-slate-200/90 bg-white shadow-sm">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-700 font-bold text-xl">
                        🏫
                    </span>
                    <span class="px-3.5 py-1 text-sm font-bold rounded-full bg-indigo-50 text-indigo-800 border border-indigo-200 uppercase tracking-wide">
                        Supervisión Pedagógica
                    </span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900">
                    Coordinación Académica
                </h1>
                <p class="text-base sm:text-lg text-slate-700 mt-2 max-w-3xl leading-relaxed">
                    Supervisión en tiempo real de asistencia, grados coordinados, desarrollo de clases y acompañamiento familiar.
                </p>
            </div>

            <!-- Formulario de Fecha Accesible -->
            <form method="GET" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 bg-slate-50 border-2 border-slate-200/90 p-3 sm:p-3.5 rounded-2xl shadow-xs">
                <div class="flex items-center gap-2">
                    <label for="fecha" class="text-base text-slate-700 font-bold px-1 whitespace-nowrap">Fecha:</label>
                    <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($fechaFiltro, ENT_QUOTES, 'UTF-8') ?>"
                           class="bg-white text-slate-900 text-base font-semibold rounded-xl px-3.5 py-2.5 border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-xs"
                           aria-label="Seleccionar fecha para filtrar">
                </div>
                <button type="submit" class="min-h-[46px] px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-base font-bold rounded-xl transition shadow-sm flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <span>Consultar</span>
                </button>
            </form>
        </div>

        <!-- Métricas del Día (KPI Cards con Números Grandes y Alto Contraste) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Presentes -->
            <div class="p-6 rounded-3xl bg-gradient-to-br from-emerald-50 via-white to-emerald-50/50 border-2 border-emerald-200 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-extrabold uppercase tracking-wider text-emerald-800">Presentes Hoy</span>
                    <span class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold">🟢</span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-4xl sm:text-5xl font-black text-slate-900"><?= (int)$metricas['presentes'] ?></span>
                    <span class="text-base text-emerald-800 font-bold">alumnos</span>
                </div>
                <p class="text-sm font-medium text-slate-600 mt-2">Marcación biométrica registrada a tiempo</p>
            </div>

            <!-- Ausentes -->
            <div class="p-6 rounded-3xl bg-gradient-to-br from-rose-50 via-white to-rose-50/50 border-2 border-rose-200 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-extrabold uppercase tracking-wider text-rose-800">Ausentes</span>
                    <span class="w-10 h-10 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-xl font-bold">🔴</span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-4xl sm:text-5xl font-black text-slate-900"><?= (int)$metricas['ausentes'] ?></span>
                    <span class="text-base text-rose-800 font-bold">alumnos</span>
                </div>
                <p class="text-sm font-medium text-slate-600 mt-2">Sin registro biométrico al momento</p>
            </div>

            <!-- Tardanzas -->
            <div class="p-6 rounded-3xl bg-gradient-to-br from-amber-50 via-white to-amber-50/50 border-2 border-amber-200 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-extrabold uppercase tracking-wider text-amber-800">Tardanzas</span>
                    <span class="w-10 h-10 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl font-bold">🟡</span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-4xl sm:text-5xl font-black text-slate-900"><?= (int)$metricas['tardanzas'] ?></span>
                    <span class="text-base text-amber-800 font-bold">alumnos</span>
                </div>
                <p class="text-sm font-medium text-slate-600 mt-2">Ingresos fuera del margen de tolerancia</p>
            </div>

            <!-- Justificados / Tasa General -->
            <div class="p-6 rounded-3xl bg-gradient-to-br from-indigo-50 via-white to-indigo-50/50 border-2 border-indigo-200 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-extrabold uppercase tracking-wider text-indigo-800">Tasa de Asistencia</span>
                    <span class="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl font-bold">📊</span>
                </div>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-4xl sm:text-5xl font-black text-slate-900"><?= (int)$metricas['porcentaje_asistencia'] ?>%</span>
                    <span class="text-sm text-slate-600 font-semibold">de <?= (int)$metricas['total_estudiantes'] ?></span>
                </div>
                <p class="text-sm font-bold text-indigo-700 mt-2"><?= (int)$metricas['justificados'] ?> ausencias justificadas</p>
            </div>
        </div>

        <!-- Barra de Filtros en Cascada y Navegación Pedagógica Accesible -->
        <div class="bg-white border-2 border-slate-200/90 p-6 sm:p-7 rounded-3xl shadow-sm space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-200">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2.5">
                        <span class="w-3 h-3 rounded-full bg-indigo-600 inline-block"></span>
                        Filtros de Navegación Pedagógica
                    </h2>
                    <p class="text-base text-slate-600 mt-0.5">Seleccioná turno, ciclo formativo o especialidad para localizar el aula al instante.</p>
                </div>
                <span id="label-conteo-aulas" class="text-base font-bold px-4 py-2 rounded-xl bg-indigo-50 text-indigo-800 border border-indigo-200 shrink-0 self-start sm:self-auto">
                    <?= count($grados) ?> aula(s) asignadas
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Turno -->
                <div>
                    <label for="filtro-coord-turno" class="block text-sm font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                        1. Turno
                    </label>
                    <select id="filtro-coord-turno" class="w-full min-h-[46px] bg-slate-50 border border-slate-300 text-slate-900 text-base font-semibold rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Todos los turnos</option>
                        <?php foreach ($turnosDisponibles as $t): ?>
                            <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. Ciclo / Nivel -->
                <div>
                    <label for="filtro-coord-nivel" class="block text-sm font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                        2. Ciclo / Nivel
                    </label>
                    <select id="filtro-coord-nivel" class="w-full min-h-[46px] bg-slate-50 border border-slate-300 text-slate-900 text-base font-semibold rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Todos los ciclos</option>
                        <?php foreach ($nivelesDisponibles as $n): ?>
                            <option value="<?= htmlspecialchars($n, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($n, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 3. Bachillerato -->
                <div>
                    <label for="filtro-coord-bach" class="block text-sm font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                        3. Bachillerato
                    </label>
                    <select id="filtro-coord-bach" class="w-full min-h-[46px] bg-slate-50 border border-slate-300 text-slate-900 text-base font-semibold rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Todos / General</option>
                        <?php foreach ($bachilleratosDisponibles as $b): ?>
                            <option value="<?= htmlspecialchars($b, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($b, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 4. Buscador por texto -->
                <div>
                    <label for="filtro-coord-busqueda" class="block text-sm font-extrabold uppercase tracking-wider text-slate-700 mb-1.5">
                        Buscar Aula o Curso
                    </label>
                    <div class="relative">
                        <input type="text" id="filtro-coord-busqueda" placeholder="Ej: 1° BTI, Sala 4..."
                               class="w-full min-h-[46px] bg-slate-50 border border-slate-300 text-slate-900 text-base font-semibold rounded-xl px-3.5 py-2.5 pl-10 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            🔍
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($grados)): ?>
            <div class="p-16 text-center rounded-3xl bg-white border-2 border-slate-200 shadow-sm space-y-3">
                <span class="text-5xl block">🏫</span>
                <h3 class="text-2xl font-bold text-slate-900">No tienes aulas o grados asignados actualmente</h3>
                <p class="text-base text-slate-600 max-w-lg mx-auto">
                    Solicita al Administrador del establecimiento que vincule tus grados bajo tu supervisión de coordinación.
                </p>
                <div class="pt-4">
                    <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="btn btn-muted min-h-[46px] px-6 text-base font-bold rounded-2xl">
                        Volver al Dashboard
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div id="grid-grados-coordinacion" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                <?php foreach ($grados as $g): ?>
                    <?php
                    $idGrado = (int)$g['id_grado'];
                    $totalAlumnos = (int)$g['total_estudiantes'];
                    $totalMaterias = (int)$g['total_materias'];
                    $hoyPres = (int)$g['metricas_hoy']['presentes'];
                    $hoyTard = (int)$g['metricas_hoy']['tardanzas'];
                    $hoyAus = (int)$g['metricas_hoy']['ausentes'];
                    $tasaHoy = ($totalAlumnos > 0) ? round((($hoyPres + $hoyTard) / $totalAlumnos) * 100) : 0;
                    $turnoStr = trim((string)($g['turno_nombre'] ?? $g['turno'] ?? ''));
                    $nivelStr = trim((string)($g['nivel_nombre'] ?? ''));
                    $bachStr = trim((string)($g['bachillerato_nombre'] ?? ''));
                    $nombreCompleto = strtolower($g['grado_nombre'] . ' ' . ($g['seccion'] ?? '') . ' ' . ($g['aula_nombre'] ?? '') . ' ' . $turnoStr . ' ' . $nivelStr . ' ' . $bachStr);
                    ?>
                    <div class="card-grado group p-6 rounded-3xl bg-white border-2 border-slate-200/90 hover:border-indigo-500 hover:shadow-xl transition-all duration-200 flex flex-col justify-between"
                         data-turno="<?= htmlspecialchars($turnoStr, ENT_QUOTES, 'UTF-8') ?>"
                         data-nivel="<?= htmlspecialchars($nivelStr, ENT_QUOTES, 'UTF-8') ?>"
                         data-bachillerato="<?= htmlspecialchars($bachStr, ENT_QUOTES, 'UTF-8') ?>"
                         data-texto="<?= htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div>
                            <!-- 1. El Curso (Título Limpio y Destacado) -->
                            <div class="mb-4">
                                <h3 class="text-2xl font-extrabold text-slate-900 group-hover:text-indigo-700 transition-colors leading-snug">
                                    <?= htmlspecialchars($g['grado_nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($g['seccion'])): ?>
                                        <span class="text-lg text-slate-600 font-semibold block sm:inline">Secc. <?= htmlspecialchars($g['seccion'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </h3>
                            </div>

                            <!-- 2. Cantidad de Alumnos y Cantidad de Materias -->
                            <div class="grid grid-cols-2 gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-200 mb-4">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-2xl">👥</span>
                                    <div>
                                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Alumnos</p>
                                        <p class="text-xl font-black text-slate-900"><?= $totalAlumnos ?></p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5">
                                    <span class="text-2xl">📚</span>
                                    <div>
                                        <p class="text-xs text-slate-500 font-bold uppercase tracking-wider">Materias</p>
                                        <p class="text-xl font-black text-slate-900"><?= $totalMaterias ?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Asistencias del Día -->
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 mb-5 space-y-2">
                                <div class="flex justify-between items-center text-sm font-bold">
                                    <span class="text-slate-700">Asistencia de hoy:</span>
                                    <span class="text-base font-extrabold text-emerald-700"><?= $tasaHoy ?>%</span>
                                </div>
                                <div class="w-full h-2.5 bg-slate-200 rounded-full overflow-hidden flex shadow-inner">
                                    <?php
                                    $pctPres = ($totalAlumnos > 0) ? ($hoyPres / $totalAlumnos) * 100 : 0;
                                    $pctTard = ($totalAlumnos > 0) ? ($hoyTard / $totalAlumnos) * 100 : 0;
                                    ?>
                                    <div style="width: <?= $pctPres ?>%" class="h-full bg-emerald-500" title="Presentes"></div>
                                    <div style="width: <?= $pctTard ?>%" class="h-full bg-amber-500" title="Tardanzas"></div>
                                </div>
                                <div class="flex flex-wrap justify-between gap-1 text-xs sm:text-sm font-bold text-slate-700 pt-0.5">
                                    <span class="text-emerald-700">🟢 <?= $hoyPres ?> pres.</span>
                                    <span class="text-amber-700">🟡 <?= $hoyTard ?> tard.</span>
                                    <span class="text-rose-700">🔴 <?= $hoyAus ?> aus.</span>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Botón de Acción Principal -->
                        <div class="pt-3 border-t border-slate-200">
                            <a href="<?= base_url('mvc/views/coordinacion/aula_detalle.php?id_grado=' . $idGrado) ?>"
                               class="w-full min-h-[48px] py-2.5 px-4 rounded-xl bg-indigo-50/90 hover:bg-indigo-600 text-indigo-700 hover:text-white flex items-center justify-between text-base font-bold transition shadow-2xs">
                                <span>Ver Aula (materias, alumnos, familias)</span>
                                <span class="text-xl group-hover:translate-x-1 transition-transform">→</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Estado Vacío cuando no coinciden los filtros -->
            <div id="empty-grados-filtros" class="hidden p-14 text-center rounded-3xl bg-white border-2 border-slate-200 shadow-sm space-y-3 my-6">
                <span class="text-5xl block">🔍</span>
                <h3 class="text-2xl font-bold text-slate-900">No encontramos ningún aula con esos filtros</h3>
                <p class="text-base text-slate-600 max-w-md mx-auto">
                    Probá restableciendo los selectores de turno, ciclo o especialidad, o limpiando el texto de búsqueda.
                </p>
                <div class="pt-2">
                    <button type="button" onclick="restablecerFiltros()" class="btn btn-primary min-h-[46px] px-6 text-base font-bold rounded-2xl">
                        Restablecer todos los filtros
                    </button>
                </div>
            </div>
        <?php endif; ?>

    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const selTurno = document.getElementById("filtro-coord-turno");
    const selNivel = document.getElementById("filtro-coord-nivel");
    const selBach = document.getElementById("filtro-coord-bach");
    const inputBusqueda = document.getElementById("filtro-coord-busqueda");
    const labelConteo = document.getElementById("label-conteo-aulas");
    const emptyBox = document.getElementById("empty-grados-filtros");
    const cards = document.querySelectorAll(".card-grado");

    function aplicarFiltros() {
        const turnoVal = (selTurno ? selTurno.value : "").trim().toLowerCase();
        const nivelVal = (selNivel ? selNivel.value : "").trim().toLowerCase();
        const bachVal = (selBach ? selBach.value : "").trim().toLowerCase();
        const queryVal = (inputBusqueda ? inputBusqueda.value : "").trim().toLowerCase();

        let visibles = 0;

        cards.forEach(card => {
            const cTurno = (card.dataset.turno || "").toLowerCase();
            const cNivel = (card.dataset.nivel || "").toLowerCase();
            const cBach = (card.dataset.bachillerato || "").toLowerCase();
            const cTexto = (card.dataset.texto || "").toLowerCase();

            const matchTurno = !turnoVal || cTurno === turnoVal;
            const matchNivel = !nivelVal || cNivel === nivelVal;
            const matchBach = !bachVal || cBach === bachVal;
            const matchQuery = !queryVal || cTexto.includes(queryVal);

            if (matchTurno && matchNivel && matchBach && matchQuery) {
                card.classList.remove("hidden");
                visibles++;
            } else {
                card.classList.add("hidden");
            }
        });

        if (labelConteo) {
            labelConteo.textContent = `${visibles} de ${cards.length} aula(s)`;
        }

        if (emptyBox) {
            if (visibles === 0 && cards.length > 0) {
                emptyBox.classList.remove("hidden");
            } else {
                emptyBox.classList.add("hidden");
            }
        }
    }

    if (selTurno) selTurno.addEventListener("change", aplicarFiltros);
    if (selNivel) selNivel.addEventListener("change", aplicarFiltros);
    if (selBach) selBach.addEventListener("change", aplicarFiltros);
    if (inputBusqueda) inputBusqueda.addEventListener("input", aplicarFiltros);

    window.restablecerFiltros = function() {
        if (selTurno) selTurno.value = "";
        if (selNivel) selNivel.value = "";
        if (selBach) selBach.value = "";
        if (inputBusqueda) inputBusqueda.value = "";
        aplicarFiltros();
    };
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
