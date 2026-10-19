<?php
require_once __DIR__ . '/../../../src/middleware/auth.php';
verificarRol(['SuperAdmin', 'Administrador', 'Tutor']);

require_once __DIR__ . '/../../../src/config/db.php';
require_once __DIR__ . '/../../../src/classes/Tutor.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$db = (new Database())->getConnection();
$tutorService = new Tutor($db);

$idUsuario = (int)$_SESSION['id_usuario'];
$rol = $_SESSION['rol'];

// Soporte para pruebas o admins que inspeccionan
$perfil = null;
if (in_array($rol, ['SuperAdmin', 'Administrador'], true) && isset($_GET['id_tutor'])) {
    $stmtT = $db->prepare("SELECT id_tutor, nombre, apellido, cedula_identidad, celular, email, direccion FROM tutores WHERE id_tutor = :id_tutor LIMIT 1");
    $stmtT->execute([':id_tutor' => (int)$_GET['id_tutor']]);
    $perfil = $stmtT->fetch(PDO::FETCH_ASSOC) ?: null;
} else {
    $perfil = $tutorService->obtenerPerfilPorUsuario($idUsuario);
}

if (!$perfil) {
    // Si no tiene perfil creado aún
    ?>
    <main class="flex-1 p-6 md:p-10 bg-slate-950 text-slate-100 min-h-screen">
        <div class="max-w-2xl mx-auto p-8 rounded-2xl bg-slate-900 border border-slate-800 text-center">
            <span class="text-4xl">👨‍👩‍👧‍👦</span>
            <h2 class="text-xl font-bold text-white mt-4">Perfil de Tutor no configurado</h2>
            <p class="text-sm text-slate-400 mt-2">
                Tu usuario actual (<?= htmlspecialchars($_SESSION['usuario'], ENT_QUOTES, 'UTF-8') ?>) no cuenta con un registro en el directorio de padres/tutores.
            </p>
            <p class="text-xs text-slate-500 mt-4">
                Comunícate con la administración institucional para vincular a tus hijos a cargo.
            </p>
        </div>
    </main>
    <?php
    require_once __DIR__ . '/../layouts/footer.php';
    exit();
}

$idTutor = (int)$perfil['id_tutor'];
$hijos = $tutorService->obtenerHijos($idTutor);
?>

<main class="flex-1 p-6 md:p-10 bg-slate-950 text-slate-100 min-h-screen">
    <!-- Encabezado del Tutor -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 mb-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 mb-2">
                    Portal de Padres y Tutores
                </span>
                <h2 class="text-2xl md:text-3xl font-extrabold text-white">
                    Bienvenido, <?= htmlspecialchars($perfil['nombre'] . ' ' . $perfil['apellido'], ENT_QUOTES, 'UTF-8') ?>
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                    Supervisión de asistencia biométrica, desarrollo de contenidos y avance escolar de tus hijos
                </p>
            </div>

            <div class="text-right">
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/60 text-xs text-slate-300">
                    👨‍👩‍👧‍👦 <?= count($hijos) ?> hijo(s) a cargo
                </span>
            </div>
        </div>
    </div>

    <!-- Sección de Hijos -->
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-white">Hijos a Cargo</h3>
            <p class="text-xs text-slate-400">Haz clic en la tarjeta de tu hijo para consultar su expediente y currículum completo de avance</p>
        </div>
    </div>

    <?php if (empty($hijos)): ?>
        <div class="p-12 text-center rounded-2xl bg-slate-900 border border-slate-800">
            <p class="text-slate-300 font-semibold">No tienes hijos o tutelados vinculados actualmente.</p>
            <p class="text-xs text-slate-500 mt-1">Acércate a la secretaría del colegio para verificar tu asignación familiar.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($hijos as $h): ?>
                <?php
                $idEst = (int)$h['id_estudiante'];
                $asistHoy = $h['asistencia_hoy'];
                $tasa = (int)$h['tasa_asistencia'];
                ?>
                <a href="<?= base_url('mvc/views/tutores/expediente_estudiante.php?id_estudiante=' . $idEst) ?>"
                   class="group block p-6 rounded-2xl bg-slate-900 border border-slate-800 hover:border-indigo-500/60 transition-all duration-200 shadow-sm hover:shadow-indigo-500/5">
                    
                    <!-- Encabezado Tarjeta de Hijo -->
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-400 text-white font-extrabold flex items-center justify-center text-lg shadow-sm">
                                <?= strtoupper(substr($h['nombre'], 0, 1)) ?>
                            </div>
                            <div>
                                <h4 class="text-lg font-bold text-white group-hover:text-indigo-300 transition-colors">
                                    <?= htmlspecialchars($h['nombre'] . ' ' . $h['apellido'], ENT_QUOTES, 'UTF-8') ?>
                                </h4>
                                <p class="text-xs font-mono text-slate-400">CI: <?= htmlspecialchars($h['cedula_identidad'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>

                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 text-slate-300 border border-slate-700">
                            <?= htmlspecialchars($h['parentesco'] ?? 'Hijo', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <!-- Grado y Aula -->
                    <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 mb-4 text-xs space-y-1">
                        <div class="flex items-center justify-between text-slate-300">
                            <span class="text-slate-400">Grado / Curso:</span>
                            <strong class="text-white"><?= htmlspecialchars($h['grado_nombre'] ?? 'Sin asignar', ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="flex items-center justify-between text-slate-300">
                            <span class="text-slate-400">Aula & Turno:</span>
                            <span><?= htmlspecialchars($h['aula_nombre'] ?? 'General', ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($h['turno'] ?? 'Mañana', ENT_QUOTES, 'UTF-8') ?>)</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-300">
                            <span class="text-slate-400">Materias Inscriptas:</span>
                            <span class="font-semibold text-white"><?= (int)$h['total_materias'] ?> asignaturas</span>
                        </div>
                    </div>

                    <!-- Estado Asistencia Hoy -->
                    <div class="mb-4 p-3 rounded-xl <?= $asistHoy === 'PRESENTE' ? 'bg-emerald-950/30 border border-emerald-800/40 text-emerald-300' : ($asistHoy === 'TARDANZA' ? 'bg-amber-950/30 border border-amber-800/40 text-amber-300' : 'bg-slate-800/50 border border-slate-700/60 text-slate-300') ?>">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium">Asistencia Hoy:</span>
                            <span class="font-bold uppercase flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full <?= $asistHoy === 'PRESENTE' ? 'bg-emerald-400' : ($asistHoy === 'TARDANZA' ? 'bg-amber-400' : 'bg-slate-400') ?>"></span>
                                <?= htmlspecialchars($asistHoy, ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($h['hora_hoy'])): ?>
                                    <span class="text-[10px] font-mono opacity-80">(<?= htmlspecialchars($h['hora_hoy'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Tasa de Asistencia General Acumulada -->
                    <div class="space-y-1.5 mb-5">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-400">Tasa de Asistencia Acumulada:</span>
                            <span class="font-bold <?= $tasa >= 80 ? 'text-emerald-400' : 'text-amber-400' ?>"><?= $tasa ?>%</span>
                        </div>
                        <div class="w-full h-2 bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full <?= $tasa >= 80 ? 'bg-emerald-500' : 'bg-amber-500' ?>" style="width: <?= $tasa ?>%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-400">
                            <span>🟢 <?= $h['total_asistencias'] ?> asist.</span>
                            <span>🟡 <?= $h['total_tardanzas'] ?> tard.</span>
                            <span>🔴 <?= $h['total_ausencias'] ?> faltas</span>
                        </div>
                    </div>

                    <!-- Botón Enlace -->
                    <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs font-semibold text-indigo-400 group-hover:text-indigo-300">
                        <span>Ver Expediente e Informe Completo</span>
                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
