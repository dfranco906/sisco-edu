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

$metricas = $coordinadorService->obtenerMetricasGenerales($idUsuario, $rol, $fechaFiltro);
$grados = $coordinadorService->obtenerGradosCoordinados($idUsuario, $rol);
?>

<main class="flex-1 p-6 md:p-10 bg-slate-950 text-slate-100 min-h-screen">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 font-bold text-lg">🏫</span>
                <h2 class="text-2xl font-bold tracking-tight text-white">Coordinación Académica</h2>
            </div>
            <p class="text-sm text-slate-400 mt-1">Supervisión en tiempo real de asistencia, grados coordinados y avance pedagógico</p>
        </div>

        <form method="GET" class="flex items-center gap-2 bg-slate-900 border border-slate-800 p-2 rounded-xl">
            <label for="fecha" class="text-xs text-slate-400 font-medium px-1">Fecha:</label>
            <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($fechaFiltro, ENT_QUOTES, 'UTF-8') ?>"
                   class="bg-slate-800 text-slate-200 text-xs rounded-lg px-2.5 py-1.5 border border-slate-700 focus:outline-none focus:border-indigo-500">
            <button type="submit" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-lg transition-colors">
                Filtrar
            </button>
        </form>
    </div>

    <!-- Métricas del Día (KPI Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Presentes -->
        <div class="p-5 rounded-2xl bg-gradient-to-br from-emerald-950/40 to-slate-900 border border-emerald-800/40 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Presentes Hoy</span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 text-lg">🟢</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white"><?= $metricas['presentes'] ?></span>
                <span class="text-xs text-emerald-400 font-medium">alumnos</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Marcación biométrica puntual</p>
        </div>

        <!-- Ausentes -->
        <div class="p-5 rounded-2xl bg-gradient-to-br from-rose-950/40 to-slate-900 border border-rose-800/40 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-400">Ausentes</span>
                <span class="p-2 rounded-xl bg-rose-500/10 text-rose-400 text-lg">🔴</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white"><?= $metricas['ausentes'] ?></span>
                <span class="text-xs text-rose-400 font-medium">alumnos</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Sin registro biométrico al momento</p>
        </div>

        <!-- Tardanzas -->
        <div class="p-5 rounded-2xl bg-gradient-to-br from-amber-950/40 to-slate-900 border border-amber-800/40 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Tardanzas</span>
                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 text-lg">🟡</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white"><?= $metricas['tardanzas'] ?></span>
                <span class="text-xs text-amber-400 font-medium">alumnos</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Ingresos fuera del margen de tolerancia</p>
        </div>

        <!-- Justificados / Tasa General -->
        <div class="p-5 rounded-2xl bg-gradient-to-br from-indigo-950/40 to-slate-900 border border-indigo-800/40 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-400">Tasa de Asistencia</span>
                <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 text-lg">📊</span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-3xl font-extrabold text-white"><?= $metricas['porcentaje_asistencia'] ?>%</span>
                <span class="text-xs text-slate-400">de <?= $metricas['total_estudiantes'] ?> matriculados</span>
            </div>
            <p class="text-xs text-indigo-300 mt-2"><?= $metricas['justificados'] ?> justificados hoy</p>
        </div>
    </div>

    <!-- Sección de Aulas / Grados Coordinados -->
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-white">Aulas y Grados Coordinados</h3>
            <p class="text-xs text-slate-400">Selecciona un grado para gestionar materias, estudiantes y tutores</p>
        </div>
        <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-slate-800 text-slate-300 border border-slate-700">
            <?= count($grados) ?> aula(s)
        </span>
    </div>

    <?php if (empty($grados)): ?>
        <div class="p-12 text-center rounded-2xl bg-slate-900/50 border border-slate-800">
            <p class="text-base font-semibold text-slate-300">No tienes aulas o grados asignados a tu supervisión actualmente.</p>
            <p class="text-xs text-slate-500 mt-1">Solicita al Administrador la vinculación de tus grados correspondientes.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($grados as $g): ?>
                <?php
                $idGrado = (int)$g['id_grado'];
                $totalAlumnos = (int)$g['total_estudiantes'];
                $totalMaterias = (int)$g['total_materias'];
                $hoyPres = (int)$g['metricas_hoy']['presentes'];
                $hoyTard = (int)$g['metricas_hoy']['tardanzas'];
                $hoyAus = (int)$g['metricas_hoy']['ausentes'];
                $tasaHoy = ($totalAlumnos > 0) ? round((($hoyPres + $hoyTard) / $totalAlumnos) * 100) : 0;
                ?>
                <a href="<?= base_url('mvc/views/coordinacion/aula_detalle.php?id_grado=' . $idGrado) ?>"
                   class="group block p-6 rounded-2xl bg-slate-900 border border-slate-800 hover:border-indigo-500/60 hover:bg-slate-850/80 transition-all duration-200 shadow-lg hover:shadow-indigo-500/5">
                    <!-- Cabecera de la tarjeta -->
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 mb-1">
                                <?= htmlspecialchars($g['turno'] ?? 'Turno General', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <h4 class="text-xl font-bold text-white group-hover:text-indigo-300 transition-colors">
                                <?= htmlspecialchars($g['grado_nombre'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($g['seccion'])): ?>
                                    <span class="text-sm text-slate-400 font-normal">Secc. <?= htmlspecialchars($g['seccion'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </h4>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-800 text-xs font-medium text-slate-300 border border-slate-700/60">
                                🚪 <?= htmlspecialchars($g['aula_nombre'] ?? 'Sin asignar', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if (!empty($g['capacidad'])): ?>
                                <p class="text-[10px] text-slate-400 mt-1">Cap. <?= (int)$g['capacidad'] ?> pers.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Datos cuantitativos -->
                    <div class="grid grid-cols-2 gap-3 p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 mb-4 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400 font-medium">👥 Estudiantes:</span>
                            <span class="font-bold text-white"><?= $totalAlumnos ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-400 font-medium">📚 Materias:</span>
                            <span class="font-bold text-white"><?= $totalMaterias ?></span>
                        </div>
                    </div>

                    <!-- Barra de Asistencia de Hoy -->
                    <div class="space-y-1.5 mb-4">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-400">Asistencia de hoy:</span>
                            <span class="font-bold text-emerald-400"><?= $tasaHoy ?>%</span>
                        </div>
                        <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden flex">
                            <?php
                            $pctPres = ($totalAlumnos > 0) ? ($hoyPres / $totalAlumnos) * 100 : 0;
                            $pctTard = ($totalAlumnos > 0) ? ($hoyTard / $totalAlumnos) * 100 : 0;
                            ?>
                            <div style="width: <?= $pctPres ?>%" class="h-full bg-emerald-500" title="Presentes"></div>
                            <div style="width: <?= $pctTard ?>%" class="h-full bg-amber-500" title="Tardanzas"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-400">
                            <span>🟢 <?= $hoyPres ?> pres.</span>
                            <span>🟡 <?= $hoyTard ?> tard.</span>
                            <span>🔴 <?= $hoyAus ?> aus.</span>
                        </div>
                    </div>

                    <!-- Footer enlace -->
                    <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs font-semibold text-indigo-400 group-hover:text-indigo-300">
                        <span>Explorar Aula (Materias, Alumnos, Tutores)</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
