<?php
require_once __DIR__ . '/../../../src/middleware/auth.php';
verificarRol(['SuperAdmin', 'Administrador', 'Profesor']);

require_once __DIR__ . '/../../../src/config/db.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$db = (new Database())->getConnection();
$idUsuario = (int)$_SESSION['id_usuario'];
$rol = $_SESSION['rol'];

// Buscar id_profesor
$profesor = null;
if (in_array($rol, ['SuperAdmin', 'Administrador'], true) && isset($_GET['id_profesor'])) {
    $stmtP = $db->prepare("SELECT id_profesor, nombre, apellido, cedula_identidad FROM profesores WHERE id_profesor = :id LIMIT 1");
    $stmtP->execute([':id' => (int)$_GET['id_profesor']]);
    $profesor = $stmtP->fetch(PDO::FETCH_ASSOC) ?: null;
} elseif (!empty($_SESSION['id_profesor'])) {
    $stmtP = $db->prepare("SELECT id_profesor, nombre, apellido, cedula_identidad FROM profesores WHERE id_profesor = :id LIMIT 1");
    $stmtP->execute([':id' => (int)$_SESSION['id_profesor']]);
    $profesor = $stmtP->fetch(PDO::FETCH_ASSOC) ?: null;
} else {
    $stmtP = $db->prepare("SELECT id_profesor, nombre, apellido, cedula_identidad FROM profesores WHERE id_usuario = :id_usuario LIMIT 1");
    $stmtP->execute([':id_usuario' => $idUsuario]);
    $profesor = $stmtP->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (!$profesor) {
    // Si la cuenta aún no está asociada a la tabla profesores
    ?>
    <main class="app-main flex-1 p-4 sm:p-6 lg:p-10 text-slate-800 dark:text-slate-100 min-h-screen">
        <div class="max-w-2xl mx-auto p-8 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-center shadow-sm">
            <span class="text-4xl">👨‍🏫</span>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mt-4">Perfil de Profesor no vinculado</h2>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                Tu usuario actual (<?= htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8') ?>) aún no está asociado a un registro de la nómina docente.
            </p>
            <p class="text-xs text-slate-500 mt-4">
                Solicita a la Administración o Coordinación la asignación de tus materias cátedra.
            </p>
        </div>
    </main>
    <?php
    require_once __DIR__ . '/../layouts/footer.php';
    exit();
}

$idProfesor = (int)$profesor['id_profesor'];

// Consultar materias y avance curricular en UNA SOLA consulta agregada (sin N+1)
$sqlMaterias = "SELECT ad.id_asignacion, ad.carga_horaria,
                       m.id_materia, m.nombre AS materia_nombre,
                       g.id_grado, g.nombre AS grado_nombre, g.seccion, g.turno,
                       a.nombre AS aula_nombre,
                       pa.id_plan, pa.estado AS plan_estado,
                       COALESCE(tt.total_temas, 0) AS total_temas,
                       COALESCE(td.temas_dictados, 0) AS temas_dictados
                FROM asignacion_docente ad
                JOIN materias m ON ad.id_materia = m.id_materia
                LEFT JOIN grados g ON ad.id_grado = g.id_grado
                LEFT JOIN aulas a ON g.id_aula = a.id_aula
                LEFT JOIN planes_anuales pa ON pa.id_asignacion = ad.id_asignacion
                LEFT JOIN (
                    SELECT pu.id_plan, COUNT(pt.id_tema) AS total_temas
                    FROM plan_temas pt
                    JOIN plan_capacidades pc ON pt.id_capacidad = pc.id_capacidad
                    JOIN plan_unidades pu ON pc.id_unidad = pu.id_unidad
                    GROUP BY pu.id_plan
                ) tt ON tt.id_plan = pa.id_plan
                LEFT JOIN (
                    SELECT cd.id_plan, COUNT(DISTINCT cd.id_tema) AS temas_dictados
                    FROM clases_diarias cd
                    WHERE cd.id_tema IS NOT NULL
                    GROUP BY cd.id_plan
                ) td ON td.id_plan = pa.id_plan
                WHERE ad.id_profesor = :id_profesor AND ad.activo = 1
                ORDER BY g.nombre ASC, m.nombre ASC";
$stmtMat = $db->prepare($sqlMaterias);
$stmtMat->execute([':id_profesor' => $idProfesor]);
$materias = $stmtMat->fetchAll(PDO::FETCH_ASSOC);

foreach ($materias as &$mat) {
    $totalTemas = (int)$mat['total_temas'];
    $temasDictados = (int)$mat['temas_dictados'];
    $mat['porcentaje_avance'] = ($totalTemas > 0) ? round(($temasDictados / $totalTemas) * 100) : 0;
}
unset($mat);
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10 text-slate-800 dark:text-slate-100 min-h-screen">
    <!-- Encabezado del Profesor -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-indigo-50 via-white to-slate-50 dark:from-slate-900 dark:via-indigo-950/40 dark:to-slate-900 border border-slate-200 dark:border-slate-800 mb-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-500/30 mb-2">
                    Panel Docente
                </span>
                <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 dark:text-white">
                    Prof. <?= htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido'], ENT_QUOTES, 'UTF-8') ?>
                </h2>
                <div class="flex items-center gap-3 mt-1.5 text-xs text-slate-600 dark:text-slate-300">
                    <span class="font-mono bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 rounded text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700">CI: <?= htmlspecialchars($profesor['cedula_identidad'] ?? 'S/D', ENT_QUOTES, 'UTF-8') ?></span>
                    <span>• Cátedras Asignadas: <strong class="text-slate-900 dark:text-white"><?= count($materias) ?></strong></span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= base_url('mvc/views/clases/panel.php') ?>" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold rounded-xl shadow-sm transition-colors flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-white animate-ping"></span>
                    🔴 Iniciar Clase en Vivo
                </a>
                <a href="<?= base_url('mvc/views/planes_anuales/index.php') ?>" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-colors">
                    📖 Mis Planes Anuales
                </a>
            </div>
        </div>
    </div>

    <!-- Sección Materias Asignadas -->
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Mis Materias Asignadas</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Accede a tus cursos para gestionar la planificación y lecciones en aula</p>
        </div>
        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
            <?= count($materias) ?> asignatura(s)
        </span>
    </div>

    <?php if (empty($materias)): ?>
        <div class="p-12 text-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-slate-600 dark:text-slate-300 font-semibold">No tienes materias asignadas actualmente.</p>
            <p class="text-xs text-slate-500 mt-1">Coordina con la dirección o coordinación para registrar tus horas cátedra.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php foreach ($materias as $mat): ?>
                <?php
                $pct = (int)$mat['porcentaje_avance'];
                $colorBar = ($pct >= 75) ? 'bg-emerald-500' : (($pct >= 40) ? 'bg-indigo-500' : 'bg-amber-500');
                ?>
                <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex flex-col justify-between shadow-sm hover:border-indigo-300 dark:hover:border-slate-700 transition-colors">
                    <div>
                        <!-- Curso y Turno -->
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/20">
                                <?= htmlspecialchars($mat['grado_nombre'] ?? 'Curso', ENT_QUOTES, 'UTF-8') ?>
                                <?= !empty($mat['seccion']) ? ' - Secc. ' . htmlspecialchars($mat['seccion'], ENT_QUOTES, 'UTF-8') : '' ?>
                            </span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                🚪 <?= htmlspecialchars($mat['aula_nombre'] ?? 'Aula', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>

                        <!-- Nombre Materia -->
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white mb-1"><?= htmlspecialchars($mat['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4"><?= (int)($mat['carga_horaria'] ?? 0) ?> hs cátedra semanales</p>

                        <!-- Indicador de Avance Pedagógico -->
                        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80 mb-4 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-600 dark:text-slate-300 font-semibold">Desarrollo Curricular:</span>
                                <span class="font-extrabold text-slate-900 dark:text-white"><?= $pct ?>%</span>
                            </div>
                            <div class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full <?= $colorBar ?>" style="width: <?= $pct ?>%"></div>
                            </div>
                            <div class="flex justify-between text-[11px] text-slate-500 dark:text-slate-400">
                                <span><?= $mat['temas_dictados'] ?> de <?= $mat['total_temas'] ?> temas impartidos</span>
                                <?php if (!empty($mat['plan_estado'])): ?>
                                    <span class="font-semibold text-slate-700 dark:text-slate-300"><?= htmlspecialchars($mat['plan_estado'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Botones de Acción de la Materia -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-2">
                        <a href="<?= base_url('mvc/views/clases/panel.php') ?>" class="flex-1 text-center py-2 px-3 bg-rose-50 hover:bg-rose-100 dark:bg-rose-600/20 dark:hover:bg-rose-600/30 text-rose-700 dark:text-rose-300 font-bold text-xs rounded-xl border border-rose-200 dark:border-transparent transition-colors">
                            🔴 Dictar Clase
                        </a>
                        <a href="<?= base_url('mvc/views/planes_anuales/index.php') ?>" class="flex-1 text-center py-2 px-3 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-600/20 dark:hover:bg-indigo-600/30 text-indigo-700 dark:text-indigo-300 font-semibold text-xs rounded-xl border border-indigo-200 dark:border-transparent transition-colors">
                            📖 Plan Anual
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
