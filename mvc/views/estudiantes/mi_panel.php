<?php
require_once __DIR__ . '/../../../src/middleware/auth.php';
verificarRol(['SuperAdmin', 'Administrador', 'Estudiante']);

require_once __DIR__ . '/../../../src/config/db.php';
require_once __DIR__ . '/../../../src/classes/EstudiantePortal.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$db = (new Database())->getConnection();
$portal = new EstudiantePortal($db);

$idUsuario = (int)$_SESSION['id_usuario'];
$rol = $_SESSION['rol'];

// Si es admin/superadmin navegando para previsualizar, permitir pasar id_estudiante
$perfil = null;
if (in_array($rol, ['SuperAdmin', 'Administrador'], true) && isset($_GET['id_estudiante'])) {
    $idEstParam = (int)$_GET['id_estudiante'];
    $stmtE = $db->prepare("SELECT e.id_estudiante, e.nombre, e.apellido, e.cedula_identidad, e.id_grado,
                                  e.huella_registrada, e.huella_id,
                                  g.nombre AS grado_nombre, g.seccion, g.turno, g.anho_lectivo,
                                  a.nombre AS aula_nombre, a.codigo_sala, a.ubicacion
                           FROM estudiantes e
                           LEFT JOIN grados g ON e.id_grado = g.id_grado
                           LEFT JOIN aulas a ON g.id_aula = a.id_aula
                           WHERE e.id_estudiante = :id_estudiante LIMIT 1");
    $stmtE->execute([':id_estudiante' => $idEstParam]);
    $perfil = $stmtE->fetch(PDO::FETCH_ASSOC) ?: null;
} else {
    $perfil = $portal->obtenerPerfilPorUsuario($idUsuario);
}

if (!$perfil) {
    // Si la cuenta no está vinculada aún a un estudiante en la base de datos
    ?>
    <main class="flex-1 p-6 md:p-10 bg-slate-950 text-slate-100 min-h-screen">
        <div class="max-w-2xl mx-auto p-8 rounded-2xl bg-slate-900 border border-slate-800 text-center">
            <span class="text-4xl">🎓</span>
            <h2 class="text-xl font-bold text-white mt-4">Perfil de Estudiante no vinculado</h2>
            <p class="text-sm text-slate-400 mt-2">
                Tu usuario actual (<?= htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8') ?>) aún no está enlazado a un registro en la nómina de estudiantes.
            </p>
            <p class="text-xs text-slate-500 mt-4">
                Comunícate con la Coordinación Académica para completar tu matriculación y asignación de grado.
            </p>
        </div>
    </main>
    <?php
    require_once __DIR__ . '/../layouts/footer.php';
    exit();
}

$idEstudiante = (int)$perfil['id_estudiante'];
$idGrado = (int)($perfil['id_grado'] ?? 0);
$materias = $portal->obtenerMisMaterias($idEstudiante, $idGrado);
$historial = $portal->obtenerHistorial($idEstudiante, 10);
?>

<main class="flex-1 p-6 md:p-10 bg-slate-950 text-slate-100 min-h-screen">
    <!-- Encabezado del Perfil del Estudiante -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 mb-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 mb-2">
                    Portal del Estudiante
                </span>
                <h2 class="text-2xl md:text-3xl font-extrabold text-white">
                    ¡Hola, <?= htmlspecialchars($perfil['nombre'], ENT_QUOTES, 'UTF-8') ?>!
                </h2>
                <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-slate-300">
                    <span class="font-mono bg-slate-800 px-2 py-0.5 rounded text-slate-200">CI: <?= htmlspecialchars($perfil['cedula_identidad'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span>• Grado: <strong class="text-white"><?= htmlspecialchars($perfil['grado_nombre'] ?? 'Sin asignar', ENT_QUOTES, 'UTF-8') ?></strong></span>
                    <span>• Aula: <strong class="text-white"><?= htmlspecialchars($perfil['aula_nombre'] ?? 'General', ENT_QUOTES, 'UTF-8') ?></strong></span>
                    <span>• Turno: <strong class="text-white"><?= htmlspecialchars($perfil['turno'] ?? 'Regular', ENT_QUOTES, 'UTF-8') ?></strong></span>
                </div>
            </div>

            <div class="text-right">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/60 text-xs font-semibold text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    Matriculación Activa <?= htmlspecialchars((string)($perfil['anho_lectivo'] ?? date('Y')), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Título Sección Materias -->
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-white">Mis Materias Cursadas</h3>
            <p class="text-xs text-slate-400">Indicadores de desarrollo pedagógico y registro de asistencias por asignatura</p>
        </div>
        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-slate-800 text-slate-300 border border-slate-700">
            <?= count($materias) ?> materia(s)
        </span>
    </div>

    <?php if (empty($materias)): ?>
        <div class="p-12 text-center rounded-2xl bg-slate-900 border border-slate-800">
            <p class="text-slate-300 font-semibold">No tienes materias asignadas en tu curso actualmente.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-10">
            <?php foreach ($materias as $mat): ?>
                <?php
                $pct = (int)$mat['porcentaje_avance'];
                $pctAsist = (int)$mat['porcentaje_asistencia'];
                $colorBar = ($pct >= 75) ? 'bg-emerald-500' : (($pct >= 40) ? 'bg-indigo-500' : 'bg-amber-500');
                ?>
                <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col justify-between shadow-sm hover:border-slate-700 transition-colors">
                    <div>
                        <!-- Título y Docente -->
                        <div class="mb-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-400">Asignatura</span>
                            <h4 class="text-lg font-bold text-white leading-snug"><?= htmlspecialchars($mat['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></h4>
                            <p class="text-xs text-slate-400 mt-1">
                                👨‍🏫 Prof. <?= htmlspecialchars(($mat['prof_nombre'] ?? '') . ' ' . ($mat['prof_apellido'] ?? 'A designar'), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>

                        <!-- Indicador de Avance del Proceso -->
                        <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80 mb-4 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-300 font-semibold">Avance del Programa:</span>
                                <span class="font-extrabold text-white text-sm"><?= $pct ?>%</span>
                            </div>
                            <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full <?= $colorBar ?> transition-all duration-300" style="width: <?= $pct ?>%"></div>
                            </div>
                            <p class="text-[11px] text-slate-400">
                                📌 <?= $mat['temas_dictados'] ?> de <?= $mat['total_temas'] ?> temas planificados impartidos
                            </p>
                        </div>

                        <!-- Asistencia Individual en la Materia -->
                        <div class="grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="p-2 rounded-lg bg-emerald-950/30 border border-emerald-800/30">
                                <div class="text-[10px] text-emerald-400 uppercase font-bold">Presente</div>
                                <div class="text-base font-extrabold text-white mt-0.5"><?= $mat['asistencias_presente'] ?></div>
                            </div>
                            <div class="p-2 rounded-lg bg-amber-950/30 border border-amber-800/30">
                                <div class="text-[10px] text-amber-400 uppercase font-bold">Tardanza</div>
                                <div class="text-base font-extrabold text-white mt-0.5"><?= $mat['asistencias_tardanza'] ?></div>
                            </div>
                            <div class="p-2 rounded-lg bg-rose-950/30 border border-rose-800/30">
                                <div class="text-[10px] text-rose-400 uppercase font-bold">Ausente</div>
                                <div class="text-base font-extrabold text-white mt-0.5"><?= $mat['asistencias_ausente'] ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Tasa individual de asistencia -->
                    <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                        <span class="text-slate-400">Puntualidad & Asistencia:</span>
                        <span class="font-bold <?= $pctAsist >= 80 ? 'text-emerald-400' : 'text-amber-400' ?>"><?= $pctAsist ?>%</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Últimas Marcaciones del Estudiante -->
    <?php if (!empty($historial)): ?>
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800">
            <h4 class="text-base font-bold text-white mb-3 flex items-center gap-2">
                <span>🕒</span> Mis Marcaciones Biométricas Recientes
            </h4>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="text-slate-400 uppercase text-[10px] border-b border-slate-800">
                        <tr>
                            <th class="pb-2">Fecha</th>
                            <th class="pb-2">Hora</th>
                            <th class="pb-2">Materia</th>
                            <th class="pb-2 text-right">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-850 text-slate-300">
                        <?php foreach ($historial as $h): ?>
                            <tr>
                                <td class="py-2.5 font-medium"><?= htmlspecialchars((string)$h['fecha'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2.5 font-mono text-slate-400"><?= htmlspecialchars(substr((string)$h['hora_marcacion'], 0, 5), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2.5 text-slate-200"><?= htmlspecialchars($h['materia_nombre'] ?? 'Entrada General', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="py-2.5 text-right">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?= $h['estado'] === 'PRESENTE' ? 'bg-emerald-500/20 text-emerald-300' : ($h['estado'] === 'TARDANZA' ? 'bg-amber-500/20 text-amber-300' : 'bg-rose-500/20 text-rose-300') ?>">
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
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
