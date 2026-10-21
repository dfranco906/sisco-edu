<?php
require_once __DIR__ . '/../../../src/middleware/auth.php';
verificarRol(['SuperAdmin', 'Administrador', 'Coordinador', 'Tutor']);

require_once __DIR__ . '/../../../src/config/db.php';
require_once __DIR__ . '/../../../src/classes/Tutor.php';

$idEstudiante = (int)($_GET['id_estudiante'] ?? 0);
$idUsuario = (int)$_SESSION['id_usuario'];
$rol = $_SESSION['rol'];

if ($idEstudiante <= 0) {
    header('Location: ' . base_url('mvc/views/tutores/mi_panel.php?error=estudiante_invalido'));
    exit();
}

$db = (new Database())->getConnection();
$tutorService = new Tutor($db);

// Si el usuario es Tutor, validar que realmente sea tutor de este estudiante
if ($rol === 'Tutor') {
    $stmtCheck = $db->prepare("SELECT COUNT(*) 
                               FROM estudiante_tutores et
                               JOIN tutores t ON et.id_tutor = t.id_tutor
                               WHERE et.id_estudiante = :id_estudiante AND t.id_usuario = :id_usuario AND et.activo = 1");
    $stmtCheck->execute([
        ':id_estudiante' => $idEstudiante,
        ':id_usuario' => $idUsuario,
    ]);
    if ((int)$stmtCheck->fetchColumn() === 0) {
        header('Location: ' . base_url('mvc/views/tutores/mi_panel.php?error=acceso_denegado'));
        exit();
    }
}

$expediente = $tutorService->obtenerExpedienteEstudiante($idEstudiante);

if (!$expediente) {
    header('Location: ' . base_url('mvc/views/tutores/mi_panel.php?error=no_encontrado'));
    exit();
}

$est = $expediente['estudiante'];
$materias = $expediente['materias'];
$historial = $expediente['historial_asistencias'];
$tutores = $expediente['tutores'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

// Totales de asistencia
$totalPres = 0;
$totalTard = 0;
$totalAus = 0;
foreach ($materias as $m) {
    $totalPres += (int)($m['presentes'] ?? 0);
    $totalTard += (int)($m['tardanzas'] ?? 0);
    $totalAus += (int)($m['ausencias'] ?? 0);
}
$totalRegistros = $totalPres + $totalTard + $totalAus;
$tasaGlobal = ($totalRegistros > 0) ? round((($totalPres + $totalTard) / $totalRegistros) * 100) : 100;
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10 text-slate-800 dark:text-slate-100 min-h-screen">
    <!-- Barra de acciones superior -->
    <div class="flex items-center justify-between mb-6 no-print">
        <a href="<?= base_url($rol === 'Tutor' ? 'mvc/views/tutores/mi_panel.php' : 'mvc/views/coordinacion/index.php') ?>"
           class="inline-flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Volver
        </a>

        <button type="button" onclick="window.print()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl border border-slate-200 dark:border-slate-700 flex items-center gap-2 transition-colors">
            🖨️ Imprimir / Guardar PDF
        </button>
    </div>

    <!-- FORMATO DOSSIER / CURRÍCULUM DEL ESTUDIANTE -->
    <div class="max-w-5xl mx-auto rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden p-6 sm:p-8 md:p-12 space-y-8">
        
        <!-- ENCABEZADO INSTITUCIONAL DEL EXPEDIENTE -->
        <div class="border-b border-slate-200 dark:border-slate-800 pb-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-tr from-indigo-700 to-indigo-500 text-white font-black text-3xl flex items-center justify-center shadow-lg border border-indigo-400/30">
                    <?= strtoupper(substr($est['nombre'], 0, 1)) . strtoupper(substr($est['apellido'], 0, 1)) ?>
                </div>
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Expediente Académico y Biométrico</span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-0.5">
                        <?= htmlspecialchars($est['apellido'] . ', ' . $est['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </h1>
                    <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-slate-600 dark:text-slate-300 font-mono">
                        <span class="bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 rounded text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700">CI: <?= htmlspecialchars($est['cedula_identidad'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span>• ID Alumno: #<?= (int)$est['id_estudiante'] ?></span>
                        <span>• Año Lectivo <?= htmlspecialchars((string)($est['anho_lectivo'] ?? date('Y')), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>

            <!-- Ficha Rápida del Curso -->
            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 text-xs space-y-1 md:text-right">
                <p class="text-slate-500 dark:text-slate-400">Grado / Curso:</p>
                <p class="text-base font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($est['grado_nombre'] ?? 'Sin asignar', ENT_QUOTES, 'UTF-8') ?></p>
                <p class="text-slate-500 dark:text-slate-400">Aula: <span class="text-slate-700 dark:text-slate-200"><?= htmlspecialchars($est['aula_nombre'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></span> | Turno: <span class="text-slate-700 dark:text-slate-200"><?= htmlspecialchars($est['turno'] ?? 'Mañana', ENT_QUOTES, 'UTF-8') ?></span></p>
            </div>
        </div>

        <!-- RESUMEN EJECUTIVO DE ASISTENCIA Y PUNTUALIDAD -->
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">Métricas Globales de Asistencia</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 text-center">
                    <span class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">Tasa de Asistencia</span>
                    <div class="text-2xl font-black <?= $tasaGlobal >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' ?> mt-1"><?= $tasaGlobal ?>%</div>
                </div>
                <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/30 text-center">
                    <span class="text-[10px] font-bold uppercase text-emerald-700 dark:text-emerald-400">Presentes</span>
                    <div class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1"><?= $totalPres ?></div>
                </div>
                <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/30 text-center">
                    <span class="text-[10px] font-bold uppercase text-amber-700 dark:text-amber-400">Tardanzas</span>
                    <div class="text-2xl font-black text-amber-700 dark:text-amber-300 mt-1"><?= $totalTard ?></div>
                </div>
                <div class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800/30 text-center">
                    <span class="text-[10px] font-bold uppercase text-rose-700 dark:text-rose-400">Ausencias</span>
                    <div class="text-2xl font-black text-rose-700 dark:text-rose-300 mt-1"><?= $totalAus ?></div>
                </div>
            </div>
        </div>

        <!-- DESGLOSE CURRICULAR POR MATERIA (EL CORAZÓN DEL CURRÍCULUM) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Desglose de Materias y Progreso Pedagógico</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Cumplimiento del plan anual y desempeño de asistencia por asignatura</p>
                </div>
                <span class="text-xs text-slate-500 dark:text-slate-400 font-semibold"><?= count($materias) ?> Asignaturas</span>
            </div>

            <div class="app-table-wrap overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950/40">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 uppercase text-[10px] border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-4">Materia / Cátedra</th>
                            <th class="p-4">Docente</th>
                            <th class="p-4">Avance del Plan Anual</th>
                            <th class="p-4 text-center">Asist. / Faltas</th>
                            <th class="p-4 text-right">% Asistencia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-850 text-slate-700 dark:text-slate-300">
                        <?php foreach ($materias as $m): ?>
                            <?php
                            $pctAvance = (int)$m['porcentaje_avance'];
                            $colorBar = ($pctAvance >= 75) ? 'bg-emerald-500' : (($pctAvance >= 40) ? 'bg-indigo-500' : 'bg-amber-500');
                            ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 dark:text-white text-sm"><?= htmlspecialchars($m['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5"><?= (int)($m['carga_horaria'] ?? 0) ?> hs cátedra semanales</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-medium text-slate-700 dark:text-slate-200">
                                        <?= htmlspecialchars(($m['prof_nombre'] ?? '') . ' ' . ($m['prof_apellido'] ?? 'No asignado'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <?php if (!empty($m['prof_email'])): ?>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400"><?= htmlspecialchars($m['prof_email'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 w-48">
                                    <div class="flex items-center justify-between text-[11px] mb-1">
                                        <span class="text-slate-500 dark:text-slate-400"><?= $m['temas_dictados'] ?>/<?= $m['total_temas'] ?> temas</span>
                                        <span class="font-bold text-slate-900 dark:text-white"><?= $pctAvance ?>%</span>
                                    </div>
                                    <div class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                        <div class="h-full <?= $colorBar ?>" style="width: <?= $pctAvance ?>%"></div>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold"><?= $m['presentes'] ?>P</span> /
                                    <span class="text-amber-600 dark:text-amber-400 font-bold"><?= $m['tardanzas'] ?>T</span> /
                                    <span class="text-rose-600 dark:text-rose-400 font-bold"><?= $m['ausencias'] ?>F</span>
                                </td>
                                <td class="p-4 text-right">
                                    <span class="font-extrabold text-sm <?= $m['tasa_asistencia'] >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' ?>">
                                        <?= $m['tasa_asistencia'] ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- HISTORIAL RECIENTE DE MARCACIONES BIOMÉTRICAS -->
        <?php if (!empty($historial)): ?>
            <div class="space-y-3">
                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>🕒</span> Historial de Marcaciones en Aula
                </h3>
                <div class="app-table-wrap overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950/40">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-400 uppercase text-[10px] border-b border-slate-200 dark:border-slate-800">
                            <tr>
                                <th class="p-3">Fecha</th>
                                <th class="p-3">Hora</th>
                                <th class="p-3">Materia / Lección</th>
                                <th class="p-3">Método</th>
                                <th class="p-3 text-right">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-850 text-slate-700 dark:text-slate-300">
                            <?php foreach ($historial as $h): ?>
                                <tr>
                                    <td class="p-3 font-medium"><?= htmlspecialchars((string)$h['fecha'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="p-3 font-mono text-slate-500 dark:text-slate-400"><?= htmlspecialchars(substr((string)$h['hora_marcacion'], 0, 5), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="p-3 text-slate-800 dark:text-slate-200"><?= htmlspecialchars($h['materia_nombre'] ?? 'Entrada General', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="p-3 text-slate-500 dark:text-slate-400"><?= htmlspecialchars($h['tipo_registro'] ?? 'BIOMETRICO', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="p-3 text-right">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $h['estado'] === 'PRESENTE' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300' : ($h['estado'] === 'TARDANZA' ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300') ?>">
                                            <?= htmlspecialchars((string)$h['estado'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- SECCIÓN DE TUTORES RESPONSABLES -->
        <div class="pt-6 border-t border-slate-200 dark:border-slate-800">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">Tutores / Padres Responsables Registrados</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php foreach ($tutores as $tut): ?>
                    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                        <div>
                            <p class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($tut['nombre'] . ' ' . $tut['apellido'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-slate-500 dark:text-slate-400 font-mono text-[11px]">CI: <?= htmlspecialchars($tut['cedula_identidad'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($tut['parentesco'], ENT_QUOTES, 'UTF-8') ?>)</p>
                        </div>
                        <?php if (!empty($tut['celular'])): ?>
                            <a href="tel:<?= htmlspecialchars($tut['celular'], ENT_QUOTES, 'UTF-8') ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline font-semibold">
                                📞 <?= htmlspecialchars($tut['celular'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</main>

<style>
@media print {
    body { background: white !important; color: black !important; }
    .no-print, #app-sidebar, #app-menu-toggle { display: none !important; }
    main { padding: 0 !important; }
    .rounded-3xl, .rounded-2xl, .rounded-xl { border-radius: 0 !important; border: 1px solid #ccc !important; }
    .bg-slate-950, .bg-slate-900, .bg-slate-800 { background: white !important; color: black !important; }
    .text-white, .text-slate-200, .text-slate-300 { color: black !important; }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
