<?php
require_once __DIR__ . '/../../../src/middleware/auth.php';
verificarRol(['SuperAdmin', 'Administrador', 'Coordinador']);

require_once __DIR__ . '/../../../src/config/db.php';
require_once __DIR__ . '/../../../src/classes/Coordinador.php';

$idGrado = (int)($_GET['id_grado'] ?? 0);

if ($idGrado <= 0) {
    header('Location: ' . base_url('mvc/views/coordinacion/index.php?error=grado_invalido'));
    exit();
}

$db = (new Database())->getConnection();
$coordinadorService = new Coordinador($db);
$detalle = $coordinadorService->obtenerDetalleAula($idGrado);

if (!$detalle) {
    header('Location: ' . base_url('mvc/views/coordinacion/index.php?error=grado_no_encontrado'));
    exit();
}

$grado = $detalle['grado'];
$materias = $detalle['materias'];
$estudiantes = $detalle['estudiantes'];
$tutores = $detalle['tutores'];

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Breadcrumb & Cabecera Accesible -->
        <div class="space-y-4">
            <a href="<?= base_url('mvc/views/coordinacion/index.php') ?>" class="inline-flex items-center gap-2 text-base text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-bold focus:outline-none focus:underline">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                </svg>
                <span>Volver a Aulas Coordinadas</span>
            </a>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm">
                <div>
                    <div class="flex items-center gap-3 mb-2 flex-wrap">
                        <span class="px-3.5 py-1.5 rounded-full text-sm font-bold uppercase tracking-wider bg-indigo-100 dark:bg-indigo-950/60 text-indigo-900 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            <?= htmlspecialchars($grado['turno'] ?? 'Turno Regular', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="text-base text-slate-600 dark:text-slate-400 font-semibold">Año Lectivo <?= htmlspecialchars((string)($grado['anho_lectivo'] ?? date('Y')), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        <?= htmlspecialchars($grado['grado_nombre'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($grado['seccion'])): ?>
                            <span class="text-slate-600 dark:text-slate-400 text-2xl sm:text-3xl font-semibold"> • Sección <?= htmlspecialchars($grado['seccion'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </h1>
                    <div class="flex flex-wrap items-center gap-4 mt-3 text-base text-slate-700 dark:text-slate-300 font-medium">
                        <span class="bg-slate-100 dark:bg-slate-800/80 px-3 py-1 rounded-xl border border-slate-200 dark:border-slate-700">
                            🚪 Aula Física: <strong class="text-slate-900 dark:text-white"><?= htmlspecialchars($grado['aula_nombre'] ?? 'Sin asignar', ENT_QUOTES, 'UTF-8') ?></strong>
                        </span>
                        <?php if (!empty($grado['capacidad'])): ?>
                            <span class="bg-slate-100 dark:bg-slate-800/80 px-3 py-1 rounded-xl border border-slate-200 dark:border-slate-700">
                                👥 Capacidad: <strong class="text-slate-900 dark:text-white"><?= (int)$grado['capacidad'] ?> alumnos</strong>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($grado['ubicacion'])): ?>
                            <span class="bg-slate-100 dark:bg-slate-800/80 px-3 py-1 rounded-xl border border-slate-200 dark:border-slate-700">
                                📍 Ubicación: <strong class="text-slate-900 dark:text-white"><?= htmlspecialchars($grado['ubicacion'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    <a href="<?= base_url('mvc/views/clases/panel.php') ?>" class="min-h-[48px] px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-base font-bold rounded-2xl shadow-sm transition flex items-center gap-2.5">
                        <span class="w-3 h-3 rounded-full bg-white animate-pulse"></span>
                        <span>Clase en Vivo</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Pestañas Principales (Tab Bar con Botones Grandes y Cómodos) -->
        <div class="border-b-2 border-slate-200 dark:border-slate-800">
            <nav class="flex space-x-4 sm:space-x-8 overflow-x-auto" aria-label="Pestañas de Coordinación">
                <button type="button" onclick="cambiarPestana('tab-materias')" id="btn-tab-materias"
                        class="tab-btn min-h-[52px] pb-4 px-2 border-b-4 font-extrabold text-base sm:text-lg flex items-center gap-2.5 border-indigo-600 dark:border-indigo-500 text-indigo-700 dark:text-indigo-400 transition">
                    <span>📚 Materias Curriculares</span>
                    <span class="px-3 py-0.5 rounded-full text-sm font-bold bg-indigo-100 dark:bg-indigo-950/80 text-indigo-900 dark:text-indigo-300"><?= count($materias) ?></span>
                </button>

                <button type="button" onclick="cambiarPestana('tab-estudiantes')" id="btn-tab-estudiantes"
                        class="tab-btn min-h-[52px] pb-4 px-2 border-b-4 font-semibold text-base sm:text-lg flex items-center gap-2.5 border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                    <span>🎓 Nómina de Estudiantes</span>
                    <span class="px-3 py-0.5 rounded-full text-sm font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300"><?= count($estudiantes) ?></span>
                </button>

                <button type="button" onclick="cambiarPestana('tab-tutores')" id="btn-tab-tutores"
                        class="tab-btn min-h-[52px] pb-4 px-2 border-b-4 font-semibold text-base sm:text-lg flex items-center gap-2.5 border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                    <span>👨‍👩‍👧‍👦 Tutores y Familias</span>
                    <span class="px-3 py-0.5 rounded-full text-sm font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300"><?= count($tutores) ?></span>
                </button>
            </nav>
        </div>

        <!-- CONTENIDO PESTAÑA 1: MATERIAS -->
        <section id="tab-materias" class="tab-content block space-y-6">
            <?php if (empty($materias)): ?>
                <div class="p-16 text-center rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                    <span class="text-5xl block">📚</span>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">No hay materias asignadas a este grado todavía</h3>
                    <p class="text-base text-slate-600 dark:text-slate-400 max-w-md mx-auto">Puedes vincular materias y profesores en el módulo de Asignaciones Docentes.</p>
                    <div class="pt-3">
                        <a href="<?= base_url('mvc/views/asignaciones/index.php') ?>" class="btn btn-primary min-h-[46px] px-6 text-base font-bold rounded-2xl">
                            Ir a Asignaciones
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php foreach ($materias as $mat): ?>
                        <?php
                        $pct = (int)$mat['porcentaje_avance'];
                        $colorBar = ($pct >= 75) ? 'bg-emerald-500' : (($pct >= 40) ? 'bg-indigo-500' : 'bg-amber-500');
                        $alumnosJson = json_encode($mat['alumnos_proceso'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                        ?>
                        <div class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-600 hover:shadow-lg transition-all flex flex-col justify-between shadow-xs">
                            <div>
                                <!-- Encabezado Materia -->
                                <div class="flex items-start justify-between gap-3 mb-4">
                                    <div>
                                        <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white"><?= htmlspecialchars($mat['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <p class="text-base text-slate-600 dark:text-slate-400 font-semibold mt-1">Carga Horaria: <?= (int)($mat['carga_horaria'] ?? 0) ?> hs cátedra</p>
                                    </div>
                                    <?php if (!empty($mat['plan_estado'])): ?>
                                        <span class="px-3.5 py-1.5 rounded-full text-sm font-bold uppercase tracking-wider <?= $mat['plan_estado'] === 'PUBLICADO' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700' ?>">
                                            <?= htmlspecialchars($mat['plan_estado'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="px-3.5 py-1.5 rounded-full text-sm font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-900 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                            Sin Plan Anual
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Docente Responsable -->
                                <div class="flex items-center gap-4 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 mb-5">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-lg border border-indigo-200 dark:border-indigo-800 shrink-0">
                                        <?= strtoupper(substr($mat['prof_nombre'] ?? 'P', 0, 1)) ?>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase">Docente Responsable</p>
                                        <p class="text-lg font-bold text-slate-900 dark:text-white"><?= htmlspecialchars(($mat['prof_nombre'] ?? '') . ' ' . ($mat['prof_apellido'] ?? 'No Asignado'), ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </div>

                                <!-- Indicador de Avance Pedagógico del Plan Anual -->
                                <div class="mb-6 space-y-2.5">
                                    <div class="flex items-center justify-between text-base">
                                        <span class="text-slate-700 dark:text-slate-300 font-bold">Avance del Plan Curricular:</span>
                                        <span class="font-black text-slate-900 dark:text-white text-lg"><?= $pct ?>%</span>
                                    </div>
                                    <div class="w-full h-3.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden shadow-inner">
                                        <div class="h-full <?= $colorBar ?> transition-all duration-300" style="width: <?= $pct ?>%"></div>
                                    </div>
                                    <div class="flex justify-between items-center text-sm font-semibold text-slate-600 dark:text-slate-400 pt-1">
                                        <span><?= $mat['temas_dictados'] ?> de <?= $mat['total_temas'] ?> temas desarrollados</span>
                                        <?php if (!empty($mat['id_plan'])): ?>
                                            <a href="<?= base_url('mvc/views/planes_anuales/index.php') ?>" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-bold hover:underline">
                                                Ver Plan Anual →
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Botón Submodal Accesible para Ver Asistencias -->
                            <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                                <button type="button"
                                        onclick='abrirModalAsistenciaMateria(<?= json_encode($mat['materia_nombre']) ?>, <?= json_encode(($mat['prof_nombre'] ?? '') . " " . ($mat['prof_apellido'] ?? '')) ?>, <?= htmlspecialchars($alumnosJson, ENT_QUOTES, 'UTF-8') ?>)'
                                        class="w-full min-h-[46px] py-2.5 px-4 rounded-xl bg-indigo-50/90 dark:bg-indigo-950/60 hover:bg-indigo-600 dark:hover:bg-indigo-600 text-indigo-700 dark:text-indigo-300 hover:text-white dark:hover:text-white border border-indigo-200 dark:border-indigo-800/60 flex items-center justify-between text-base font-bold transition">
                                    <span class="flex items-center gap-2">
                                        <span>📊</span>
                                        <span>Ver Asistencias por Estudiante (<?= count($mat['alumnos_proceso']) ?>)</span>
                                    </span>
                                    <span class="text-xl">↗</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- CONTENIDO PESTAÑA 2: ESTUDIANTES -->
        <section id="tab-estudiantes" class="tab-content hidden space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white">Nómina de Estudiantes</h2>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1">Alumnos matriculados activamente en este grado con control biométrico</p>
                </div>
                <a href="<?= base_url('mvc/views/estudiantes/index.php?ref=' . urlencode('mvc/views/coordinacion/aula_detalle.php?id_grado=' . $idGrado)) ?>" class="btn btn-primary min-h-[46px] px-6 text-base font-bold rounded-2xl flex items-center gap-2 self-start sm:self-auto">
                    <span>+</span>
                    <span>Matricular / Gestionar</span>
                </a>
            </div>

            <?php if (empty($estudiantes)): ?>
                <div class="p-16 text-center rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
                    <span class="text-5xl block">🎓</span>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">No hay estudiantes matriculados en este grado</h3>
                    <p class="text-base text-slate-600 dark:text-slate-400 max-w-md mx-auto">Matriculá estudiantes para comenzar a registrar asistencias y vincular tutores.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                    <table class="w-full text-left text-base">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 uppercase font-bold text-sm border-b-2 border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="p-5">Estudiante</th>
                                <th class="p-5">Cédula</th>
                                <th class="p-5">Biometría DY50</th>
                                <th class="p-5">Tutor(es) a Cargo</th>
                                <th class="p-5 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                            <?php foreach ($estudiantes as $e): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="p-5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-11 h-11 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-base border border-indigo-200 dark:border-indigo-800 shrink-0">
                                                <?= strtoupper(substr($e['nombre'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <p class="text-lg font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($e['apellido'] . ', ' . $e['nombre'], ENT_QUOTES, 'UTF-8') ?></p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">ID #<?= (int)$e['id_estudiante'] ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-5 font-mono text-base font-bold text-slate-700 dark:text-slate-300">
                                        <?= htmlspecialchars($e['cedula_identidad'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="p-5">
                                        <?php if (!empty($e['huella_registrada']) || !empty($e['huella_id'])): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-300 text-sm font-bold border border-emerald-200 dark:border-emerald-800">
                                                ✓ Huella Activa (#<?= (int)$e['huella_id'] ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300 text-sm font-bold border border-amber-200 dark:border-amber-800">
                                                ⚠ Sin huella
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-5">
                                        <?php if (empty($e['tutores'])): ?>
                                            <div class="flex items-center gap-3">
                                                <span class="text-slate-500 dark:text-slate-400 text-sm font-medium">Sin tutor vinculado</span>
                                                <button type="button" 
                                                        onclick="abrirModalVincular(<?= (int)$e['id_estudiante'] ?>, '<?= htmlspecialchars(addslashes($e['nombre']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($e['apellido']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($e['cedula_identidad']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($grado['grado_nombre'] ?? 'Curso'), ENT_QUOTES) ?>')"
                                                        class="min-h-[38px] px-3.5 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold text-sm hover:bg-indigo-600 hover:text-white border border-indigo-200 dark:border-indigo-800 transition">
                                                    + Vincular Tutor
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <div class="space-y-1.5">
                                                <?php foreach ($e['tutores'] as $t): ?>
                                                    <div class="flex items-center justify-between gap-2 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 group">
                                                        <div class="text-sm">
                                                            <span class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($t['nombre'] . ' ' . $t['apellido'], ENT_QUOTES, 'UTF-8') ?></span>
                                                            <span class="text-slate-600 dark:text-slate-400 font-medium">(<?= htmlspecialchars($t['parentesco'] ?? 'Tutor', ENT_QUOTES, 'UTF-8') ?>)</span>
                                                            <?php if (!empty($t['celular'])): ?>
                                                                <a href="tel:<?= htmlspecialchars($t['celular'], ENT_QUOTES, 'UTF-8') ?>" class="text-emerald-700 dark:text-emerald-400 font-bold hover:underline ml-2">📞 <?= htmlspecialchars($t['celular'], ENT_QUOTES, 'UTF-8') ?></a>
                                                            <?php endif; ?>
                                                        </div>
                                                        <button type="button" 
                                                                onclick="desvincularTutor(<?= (int)$t['id_tutor'] ?>, <?= (int)$e['id_estudiante'] ?>, '<?= htmlspecialchars(addslashes($t['nombre'] . ' ' . $t['apellido']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($e['nombre'] . ' ' . $e['apellido']), ENT_QUOTES) ?>')"
                                                                class="text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 font-bold text-sm px-2 py-1 rounded transition" 
                                                                title="Quitar vínculo familiar"
                                                                aria-label="Desvincular tutor">
                                                            ✕
                                                        </button>
                                                    </div>
                                                <?php endforeach; ?>
                                                <button type="button" 
                                                        onclick="abrirModalVincular(<?= (int)$e['id_estudiante'] ?>, '<?= htmlspecialchars(addslashes($e['nombre']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($e['apellido']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($e['cedula_identidad']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($grado['grado_nombre'] ?? 'Curso'), ENT_QUOTES) ?>')"
                                                        class="text-sm text-indigo-700 dark:text-indigo-400 hover:underline inline-block mt-1 font-bold">
                                                    + Agregar otro tutor
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-5 text-right">
                                        <a href="<?= base_url('mvc/views/tutores/expediente_estudiante.php?id_estudiante=' . (int)$e['id_estudiante']) ?>"
                                           class="inline-flex items-center min-h-[44px] px-4 py-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-600 text-indigo-700 dark:text-indigo-300 hover:text-white font-bold text-sm border border-indigo-200 dark:border-indigo-800 transition">
                                            <span>Expediente</span>
                                            <span class="ml-1 text-base">→</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- CONTENIDO PESTAÑA 3: TUTORES -->
        <section id="tab-tutores" class="tab-content hidden space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white">Directorio de Tutores y Familiares</h2>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1">Contactos familiares directos para avisos escolares, justificaciones y llamadas</p>
                </div>
                <button type="button" onclick="GestorFamilias.abrir()" class="btn btn-primary min-h-[46px] px-6 text-base font-bold rounded-2xl flex items-center gap-2 self-start sm:self-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>Vincular Estudiante a Tutor</span>
                </button>
            </div>

            <?php if (empty($tutores)): ?>
                <div class="p-16 text-center rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                    <div class="w-16 h-16 rounded-3xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center mx-auto text-3xl font-bold">
                        👨‍👩‍👧‍👦
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 dark:text-white">No hay tutores vinculados a los estudiantes de este grado</h3>
                    <p class="text-base text-slate-600 dark:text-slate-400 max-w-md mx-auto">
                        Vinculá tutores a los alumnos para habilitar llamadas de urgencia, notificaciones y expedientes.
                    </p>
                    <div class="pt-2">
                        <button type="button" onclick="GestorFamilias.abrir()" class="btn btn-primary min-h-[46px] px-6 text-base font-bold rounded-2xl">
                            + Vincular Primer Tutor Ahora
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($tutores as $tut): ?>
                        <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 hover:border-indigo-400 dark:hover:border-indigo-600 hover:shadow-lg transition-all flex flex-col justify-between shadow-xs">
                            <div>
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-lg border border-indigo-200 dark:border-indigo-800 shrink-0">
                                        <?= strtoupper(substr($tut['nombre'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h3 class="text-xl font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($tut['nombre'] . ' ' . $tut['apellido'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <p class="text-sm font-mono font-bold text-slate-500 dark:text-slate-400">CI: <?= htmlspecialchars($tut['cedula_identidad'], ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </div>

                                <!-- Vínculo con Hijos -->
                                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 mb-3 space-y-2">
                                    <span class="text-xs uppercase font-extrabold text-slate-600 dark:text-slate-400 tracking-wider block">Hijo(s) a cargo:</span>
                                    <?php foreach ($tut['hijos'] as $h): ?>
                                        <div class="flex items-center justify-between text-base font-semibold text-slate-800 dark:text-slate-200">
                                            <span>• <?= htmlspecialchars($h['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="text-xs font-bold text-indigo-800 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-950/60 px-2.5 py-1 rounded-lg border border-indigo-200 dark:border-indigo-800/60"><?= htmlspecialchars($h['parentesco'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button type="button" 
                                        onclick="GestorFamilias.abrir({ tutorInicial: { id_tutor: <?= (int)$tut['id_tutor'] ?>, nombre: '<?= htmlspecialchars(addslashes($tut['nombre']), ENT_QUOTES) ?>', apellido: '<?= htmlspecialchars(addslashes($tut['apellido']), ENT_QUOTES) ?>', cedula_identidad: '<?= htmlspecialchars(addslashes($tut['cedula_identidad']), ENT_QUOTES) ?>', celular: '<?= htmlspecialchars(addslashes($tut['celular'] ?? ''), ENT_QUOTES) ?>' } })" 
                                        class="w-full text-center py-2 text-sm text-indigo-700 dark:text-indigo-400 hover:underline font-bold mb-4">
                                    + Vincular otro hijo / hermano
                                </button>
                            </div>

                            <!-- Botones de Contacto Directo Accesibles -->
                            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center gap-2">
                                <?php if (!empty($tut['celular'])): ?>
                                    <a href="tel:<?= htmlspecialchars($tut['celular'], ENT_QUOTES, 'UTF-8') ?>" class="flex-1 min-h-[44px] flex items-center justify-center py-2.5 px-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 font-bold text-sm transition border border-emerald-300 dark:border-emerald-800">
                                        📞 Llamar
                                    </a>
                                    <a href="https://wa.me/595<?= preg_replace('/\D/', '', $tut['celular']) ?>" target="_blank" rel="noopener noreferrer"
                                       class="flex-1 min-h-[44px] flex items-center justify-center py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm transition shadow-xs">
                                        💬 WhatsApp
                                    </a>
                                <?php endif; ?>
                                <?php if (!empty($tut['email'])): ?>
                                    <a href="mailto:<?= htmlspecialchars($tut['email'], ENT_QUOTES, 'UTF-8') ?>" class="min-h-[44px] w-12 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-lg transition border border-slate-300 dark:border-slate-700" title="<?= htmlspecialchars($tut['email'], ENT_QUOTES, 'UTF-8') ?>">
                                        ✉️
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>

    <!-- Submodal Accesible de Detalle de Asistencia de la Materia -->
    <?php require_once __DIR__ . '/modal_asistencia_materia.php'; ?>

    <!-- Modal Gestor Inteligente de Familias y Vínculos -->
    <?php require_once __DIR__ . '/../tutores/modal_vincular_familia.php'; ?>
</main>

<script>
function cambiarPestana(targetId) {
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('block');
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-indigo-600', 'dark:border-indigo-500', 'text-indigo-700', 'dark:text-indigo-400', 'font-extrabold');
        btn.classList.add('border-transparent', 'text-slate-600', 'dark:text-slate-400', 'font-semibold');
    });

    const activeContent = document.getElementById(targetId);
    if (activeContent) {
        activeContent.classList.remove('hidden');
        activeContent.classList.add('block');
    }

    const activeBtn = document.getElementById('btn-' + targetId);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-slate-600', 'dark:text-slate-400', 'font-semibold');
        activeBtn.classList.add('border-indigo-600', 'dark:border-indigo-500', 'text-indigo-700', 'dark:text-indigo-400', 'font-extrabold');
    }
}

async function desvincularTutor(idTutor, idEstudiante, nombreTutor, nombreEstudiante) {
    const msg = `¿Desea desvincular a "${nombreTutor}" como tutor de "${nombreEstudiante}"?`;
    if (!confirm(msg)) return;

    try {
        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Tutor/desvincular_hijo.php';
        const formData = new FormData();
        formData.append('id_tutor', idTutor);
        formData.append('id_estudiante', idEstudiante);

        const res = await fetch(url, { method: 'POST', body: formData });
        const json = await res.json();

        if (json.status === 'success') {
            if (window.Toast) Toast.success(json.message);
            setTimeout(() => location.reload(), 600);
        } else {
            if (window.Toast) Toast.error(json.message || "Error al desvincular");
            else alert(json.message);
        }
    } catch (e) {
        console.error(e);
        if (window.Toast) Toast.error("Error al desvincular tutor");
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
