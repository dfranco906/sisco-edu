<?php
require_once __DIR__ . '/../../../src/middleware/auth.php';
verificarRol(['SuperAdmin', 'Administrador', 'Coordinador']);

require_once __DIR__ . '/../../../src/config/db.php';
require_once __DIR__ . '/../../../src/classes/Coordinador.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

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
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10">
    <!-- Breadcrumb & Cabecera -->
    <div class="mb-6 space-y-3">
        <a href="<?= base_url('mvc/views/coordinacion/index.php') ?>" class="inline-flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Volver a Aulas Coordinadas
        </a>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-50 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-500/30">
                        <?= htmlspecialchars($grado['turno'] ?? 'Turno Regular', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Año Lectivo <?= htmlspecialchars((string)($grado['anho_lectivo'] ?? date('Y')), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white mt-1">
                    <?= htmlspecialchars($grado['grado_nombre'], ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($grado['seccion'])): ?>
                        <span class="text-slate-500 dark:text-slate-400 text-lg sm:text-xl font-normal">Sección <?= htmlspecialchars($grado['seccion'], ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </h2>
                <div class="flex flex-wrap items-center gap-4 mt-2 text-xs text-slate-600 dark:text-slate-300">
                    <span>🚪 Aula Física: <strong class="text-slate-900 dark:text-white"><?= htmlspecialchars($grado['aula_nombre'] ?? 'Sin asignar', ENT_QUOTES, 'UTF-8') ?></strong></span>
                    <?php if (!empty($grado['capacidad'])): ?>
                        <span>👥 Capacidad: <strong class="text-slate-900 dark:text-white"><?= (int)$grado['capacidad'] ?> pers.</strong></span>
                    <?php endif; ?>
                    <?php if (!empty($grado['ubicacion'])): ?>
                        <span>📍 Ubicación: <strong class="text-slate-900 dark:text-white"><?= htmlspecialchars($grado['ubicacion'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="<?= base_url('mvc/views/clases/panel.php') ?>" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-sm transition-colors flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    Clase en Vivo
                </a>
            </div>
        </div>
    </div>

    <!-- Pestañas Principales (Tab Bar) -->
    <div class="mb-6 border-b border-slate-200 dark:border-slate-800">
        <nav class="flex space-x-6" aria-label="Pestañas de Coordinación">
            <button type="button" onclick="cambiarPestana('tab-materias')" id="btn-tab-materias"
                    class="tab-btn pb-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 border-indigo-600 dark:border-indigo-500 text-indigo-600 dark:text-indigo-400 transition-colors">
                <span>📚 Materias</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-indigo-100 dark:bg-indigo-500/20 text-indigo-700 dark:text-indigo-300"><?= count($materias) ?></span>
            </button>

            <button type="button" onclick="cambiarPestana('tab-estudiantes')" id="btn-tab-estudiantes"
                    class="tab-btn pb-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
                <span>🎓 Estudiantes</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"><?= count($estudiantes) ?></span>
            </button>

            <button type="button" onclick="cambiarPestana('tab-tutores')" id="btn-tab-tutores"
                    class="tab-btn pb-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
                <span>👨‍👩‍👧‍👦 Tutores</span>
                <span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300"><?= count($tutores) ?></span>
            </button>
        </nav>
    </div>

    <!-- CONTENIDO PESTAÑA 1: MATERIAS -->
    <section id="tab-materias" class="tab-content block space-y-6">
        <?php if (empty($materias)): ?>
            <div class="p-12 text-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <p class="text-slate-700 dark:text-slate-300 font-semibold">No hay materias asignadas a este grado todavía.</p>
                <p class="text-xs text-slate-500 mt-1">Puedes asignar materias en el módulo de Asignaciones del Administrador.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <?php foreach ($materias as $mat): ?>
                    <?php
                    $pct = (int)$mat['porcentaje_avance'];
                    $colorBar = ($pct >= 75) ? 'bg-emerald-500' : (($pct >= 40) ? 'bg-indigo-500' : 'bg-amber-500');
                    ?>
                    <div class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex flex-col justify-between shadow-sm">
                        <div>
                            <!-- Encabezado Materia -->
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div>
                                    <h4 class="text-xl font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($mat['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Carga: <?= (int)($mat['carga_horaria'] ?? 0) ?> hs cátedra</p>
                                </div>
                                <?php if (!empty($mat['plan_estado'])): ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider <?= $mat['plan_estado'] === 'PUBLICADO' ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' ?>">
                                        <?= htmlspecialchars($mat['plan_estado'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-500/20">
                                        Sin Plan Anual
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Docente Responsable -->
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80 mb-4">
                                <div class="w-9 h-9 rounded-full bg-indigo-100 dark:bg-indigo-600/30 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-sm border border-indigo-200 dark:border-indigo-500/20">
                                    <?= strtoupper(substr($mat['prof_nombre'] ?? 'P', 0, 1)) ?>
                                </div>
                                <div class="text-xs">
                                    <p class="text-slate-500 dark:text-slate-400 font-medium">Docente Responsable:</p>
                                    <p class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars(($mat['prof_nombre'] ?? '') . ' ' . ($mat['prof_apellido'] ?? 'No Asignado'), ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>

                            <!-- Indicador de Avance Pedagógico del Plan Anual -->
                            <div class="mb-5 space-y-2">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-700 dark:text-slate-300 font-medium">Progreso del Plan Anual:</span>
                                    <span class="font-extrabold text-slate-900 dark:text-white"><?= $pct ?>%</span>
                                </div>
                                <div class="w-full h-2.5 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full <?= $colorBar ?> transition-all duration-300" style="width: <?= $pct ?>%"></div>
                                </div>
                                <div class="flex justify-between text-[11px] text-slate-500 dark:text-slate-400">
                                    <span><?= $mat['temas_dictados'] ?> de <?= $mat['total_temas'] ?> temas desarrollados</span>
                                    <?php if (!empty($mat['id_plan'])): ?>
                                        <a href="<?= base_url('mvc/views/planes_anuales/index.php') ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">
                                            Ver Plan Anual →
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Desplegable / Detalle de Asistencia de Alumnos en esta materia -->
                            <details class="group rounded-xl bg-slate-50 dark:bg-slate-950/40 border border-slate-200 dark:border-slate-800/60 p-3 text-xs">
                                <summary class="cursor-pointer font-bold text-slate-700 dark:text-slate-300 flex items-center justify-between select-none">
                                    <span>📊 Ver Asistencias por Estudiante (<?= count($mat['alumnos_proceso']) ?>)</span>
                                    <span class="group-open:rotate-180 transition-transform text-slate-400">▼</span>
                                </summary>
                                <div class="mt-3 overflow-x-auto max-h-48 overflow-y-auto">
                                    <table class="w-full text-left">
                                        <thead>
                                            <tr class="text-[10px] uppercase text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                                                <th class="pb-1">Estudiante</th>
                                                <th class="pb-1 text-center">Pres.</th>
                                                <th class="pb-1 text-center">Tard.</th>
                                                <th class="pb-1 text-center">Aus.</th>
                                                <th class="pb-1 text-right">% Asist.</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-200 dark:divide-slate-850">
                                            <?php foreach ($mat['alumnos_proceso'] as $p): ?>
                                                <tr class="hover:bg-slate-100 dark:hover:bg-slate-800/30">
                                                    <td class="py-1 text-slate-800 dark:text-slate-200"><?= htmlspecialchars($p['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td class="py-1 text-center text-emerald-600 dark:text-emerald-400 font-semibold"><?= $p['presentes'] ?></td>
                                                    <td class="py-1 text-center text-amber-600 dark:text-amber-400 font-semibold"><?= $p['tardanzas'] ?></td>
                                                    <td class="py-1 text-center text-rose-600 dark:text-rose-400 font-semibold"><?= $p['ausentes'] ?></td>
                                                    <td class="py-1 text-right font-bold <?= $p['porcentaje'] >= 80 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' ?>">
                                                        <?= $p['porcentaje'] ?>%
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- CONTENIDO PESTAÑA 2: ESTUDIANTES -->
    <section id="tab-estudiantes" class="tab-content hidden space-y-4">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Nómina de Estudiantes</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Alumnos matriculados activamente en este grado</p>
            </div>
            <a href="<?= base_url('mvc/views/estudiantes/index.php') ?>" class="btn btn-primary text-xs">
                + Matricular / Gestionar
            </a>
        </div>

        <?php if (empty($estudiantes)): ?>
            <div class="p-12 text-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <p class="text-slate-700 dark:text-slate-300 font-semibold">No hay estudiantes matriculados en este grado.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-950/80 text-slate-600 dark:text-slate-400 uppercase font-semibold text-[11px] border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="p-4">Estudiante</th>
                            <th class="p-4">Cédula</th>
                            <th class="p-4">Biometría</th>
                            <th class="p-4">Tutor(es) Asociados</th>
                            <th class="p-4 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        <?php foreach ($estudiantes as $e): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="p-4 font-semibold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars($e['apellido'] . ', ' . $e['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="p-4 font-mono text-slate-600 dark:text-slate-300">
                                    <?= htmlspecialchars($e['cedula_identidad'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="p-4">
                                    <?php if (!empty($e['huella_registrada']) || !empty($e['huella_id'])): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold border border-emerald-200 dark:border-emerald-500/20">
                                            ✓ Huella Activa (#<?= (int)$e['huella_id'] ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400 text-[10px] font-medium border border-amber-200 dark:border-amber-500/20">
                                            ⚠ Sin huella
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4">
                                    <?php if (empty($e['tutores'])): ?>
                                        <span class="text-slate-400 text-[11px]">Sin tutor vinculado</span>
                                    <?php else: ?>
                                        <div class="space-y-1">
                                            <?php foreach ($e['tutores'] as $t): ?>
                                                <div class="text-[11px]">
                                                    <span class="font-medium text-slate-800 dark:text-slate-200"><?= htmlspecialchars($t['nombre'] . ' ' . $t['apellido'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="text-slate-500 dark:text-slate-400">(<?= htmlspecialchars($t['parentesco'] ?? 'Tutor', ENT_QUOTES, 'UTF-8') ?>)</span>
                                                    <?php if (!empty($t['celular'])): ?>
                                                        <a href="tel:<?= htmlspecialchars($t['celular'], ENT_QUOTES, 'UTF-8') ?>" class="text-emerald-600 dark:text-emerald-400 hover:underline ml-1">📞 <?= htmlspecialchars($t['celular'], ENT_QUOTES, 'UTF-8') ?></a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-right">
                                    <a href="<?= base_url('mvc/views/tutores/expediente_estudiante.php?id_estudiante=' . (int)$e['id_estudiante']) ?>"
                                       class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-600/20 hover:bg-indigo-100 dark:hover:bg-indigo-600/40 text-indigo-700 dark:text-indigo-300 font-semibold text-[11px] transition-colors">
                                        Expediente →
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
    <section id="tab-tutores" class="tab-content hidden space-y-4">
        <div class="mb-2">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Directorio de Tutores y Familiares</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Contactos principales para comunicaciones escolares y seguimiento</p>
        </div>

        <?php if (empty($tutores)): ?>
            <div class="p-12 text-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <p class="text-slate-700 dark:text-slate-300 font-semibold">No hay tutores vinculados a los estudiantes de este grado.</p>
                <p class="text-xs text-slate-500 mt-1">Vincula tutores a los alumnos para habilitar su seguimiento y acceso familiar.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <?php foreach ($tutores as $tut): ?>
                    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex flex-col justify-between shadow-sm">
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold flex items-center justify-center text-sm border border-slate-200 dark:border-slate-700">
                                    <?= strtoupper(substr($tut['nombre'], 0, 1)) ?>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($tut['nombre'] . ' ' . $tut['apellido'], ENT_QUOTES, 'UTF-8') ?></h4>
                                    <p class="text-[11px] font-mono text-slate-500 dark:text-slate-400">CI: <?= htmlspecialchars($tut['cedula_identidad'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>

                            <!-- Vínculo con Hijos -->
                            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80 mb-3 text-xs space-y-1">
                                <span class="text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">Hijo(s) a cargo:</span>
                                <?php foreach ($tut['hijos'] as $h): ?>
                                    <div class="flex items-center justify-between text-slate-800 dark:text-slate-200">
                                        <span>• <?= htmlspecialchars($h['nombre_completo'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="text-[10px] text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-500/10 px-1.5 py-0.5 rounded font-semibold"><?= htmlspecialchars($h['parentesco'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Contacto directo -->
                        <div class="pt-3 border-t border-slate-200 dark:border-slate-800/80 flex items-center gap-2">
                            <?php if (!empty($tut['celular'])): ?>
                                <a href="tel:<?= htmlspecialchars($tut['celular'], ENT_QUOTES, 'UTF-8') ?>" class="flex-1 text-center py-1.5 px-3 rounded-lg bg-emerald-50 dark:bg-emerald-600/20 hover:bg-emerald-100 dark:hover:bg-emerald-600/30 text-emerald-700 dark:text-emerald-300 font-semibold text-xs transition-colors border border-emerald-200 dark:border-transparent">
                                    📞 Llamar
                                </a>
                                <a href="https://wa.me/595<?= preg_replace('/\D/', '', $tut['celular']) ?>" target="_blank" rel="noopener noreferrer"
                                   class="flex-1 text-center py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs transition-colors">
                                    💬 WhatsApp
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($tut['email'])): ?>
                                <a href="mailto:<?= htmlspecialchars($tut['email'], ENT_QUOTES, 'UTF-8') ?>" class="py-1.5 px-2.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs transition-colors border border-slate-200 dark:border-transparent" title="<?= htmlspecialchars($tut['email'], ENT_QUOTES, 'UTF-8') ?>">
                                    ✉️
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
function cambiarPestana(targetId) {
    document.querySelectorAll('.tab-content').forEach(el => {
        el.classList.add('hidden');
        el.classList.remove('block');
    });

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-indigo-600', 'text-indigo-600', 'dark:border-indigo-500', 'dark:text-indigo-400', 'font-bold');
        btn.classList.add('border-transparent', 'text-slate-500', 'dark:text-slate-400', 'font-medium');
    });

    const activeContent = document.getElementById(targetId);
    if (activeContent) {
        activeContent.classList.remove('hidden');
        activeContent.classList.add('block');
    }

    const activeBtn = document.getElementById('btn-' + targetId);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400', 'font-medium');
        activeBtn.classList.add('border-indigo-600', 'text-indigo-600', 'dark:border-indigo-500', 'dark:text-indigo-400', 'font-bold');
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
