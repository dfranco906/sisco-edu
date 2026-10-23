<?php
require_once __DIR__ . '/../../src/middleware/auth.php';
verificarRol(['SuperAdmin', 'Administrador', 'Administracion']);

require_once __DIR__ . '/../../src/config/app.php';
require_once __DIR__ . '/../../src/config/db.php';
require_once 'layouts/header.php';
require_once 'layouts/sidebar.php';

// Formato de fecha legible en español
$diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$meses = [1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'];
$diaNombre = $diasSemana[(int)date('w')];
$mesNombre = $meses[(int)date('n')];
$fechaLegible = "{$diaNombre}, " . date('j') . " de {$mesNombre} de " . date('Y');

$hoy = date('Y-m-d');
$totalEstudiantes = 0;
$totalProfesores = 0;
$totalAulas = 0;
$totalTutores = 0;
$presentesHoy = 0;
$tardanzasHoy = 0;
$totalAsistieron = 0;
$pctAsistencia = 0;
$ausentesHoy = 0;
$profPresentesHoy = 0;
$ultimasMarcaciones = [];

try {
    $db = (new Database())->getConnection();

    // Contadores generales
    $totalEstudiantes = (int)$db->query("SELECT COUNT(*) FROM estudiantes WHERE activo = 1")->fetchColumn();
    $totalProfesores = (int)$db->query("SELECT COUNT(*) FROM profesores WHERE activo = 1")->fetchColumn();
    $totalAulas = (int)$db->query("SELECT COUNT(*) FROM aulas WHERE activo = 1")->fetchColumn();
    $totalTutores = (int)$db->query("SELECT COUNT(*) FROM tutores WHERE activo = 1")->fetchColumn();

    // Asistencia de estudiantes hoy
    $stmtHoy = $db->prepare("
        SELECT 
            COUNT(*) AS total_marcaciones,
            SUM(CASE WHEN estado = 'PRESENTE' THEN 1 ELSE 0 END) AS presentes,
            SUM(CASE WHEN estado = 'TARDANZA' THEN 1 ELSE 0 END) AS tardanzas
        FROM asistencias_estudiantes 
        WHERE fecha = :hoy AND activo = 1
    ");
    $stmtHoy->execute([':hoy' => $hoy]);
    $asistHoy = $stmtHoy->fetch(PDO::FETCH_ASSOC);

    $presentesHoy = (int)($asistHoy['presentes'] ?? 0);
    $tardanzasHoy = (int)($asistHoy['tardanzas'] ?? 0);
    $totalAsistieron = $presentesHoy + $tardanzasHoy;
    $pctAsistencia = ($totalEstudiantes > 0) ? round(($totalAsistieron / $totalEstudiantes) * 100) : 0;
    $ausentesHoy = max(0, $totalEstudiantes - $totalAsistieron);

    // Asistencia de profesores hoy
    $stmtProfHoy = $db->prepare("
        SELECT COUNT(DISTINCT id_profesor)
        FROM asistencias_profesores
        WHERE fecha = :hoy AND activo = 1
    ");
    $stmtProfHoy->execute([':hoy' => $hoy]);
    $profPresentesHoy = (int)$stmtProfHoy->fetchColumn();

    // Últimas marcaciones registradas
    $stmtUltimas = $db->prepare("
        SELECT ae.hora, ae.fecha, ae.estado, e.nombre, e.apellido, g.nombre AS grado_nombre, a.nombre AS aula_nombre
        FROM asistencias_estudiantes ae
        JOIN estudiantes e ON ae.id_estudiante = e.id_estudiante
        LEFT JOIN grados g ON e.id_grado = g.id_grado
        LEFT JOIN aulas a ON g.id_aula = a.id_aula
        WHERE ae.activo = 1
        ORDER BY ae.fecha DESC, ae.hora DESC, ae.id_asistencia_estudiante DESC
        LIMIT 6
    ");
    $stmtUltimas->execute();
    $ultimasMarcaciones = $stmtUltimas->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    error_log("Error cargando métricas en dashboard: " . $e->getMessage());
}
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Banner de Bienvenida y Estado en Vivo Accesible -->
        <div class="app-page-header p-6 sm:p-8 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span class="px-3.5 py-1 text-sm font-extrabold rounded-full bg-emerald-100 text-emerald-900 border border-emerald-200 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Sistema Operativo En Línea
                    </span>
                    <span class="text-base text-slate-600 font-semibold">
                        <?= htmlspecialchars($fechaLegible, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    ¡Buen día, <?= htmlspecialchars($_SESSION['usuario'] ?? 'Usuario', ENT_QUOTES, 'UTF-8'); ?>!
                </h1>
                <p class="text-base sm:text-lg text-slate-700 mt-2 max-w-2xl leading-relaxed">
                    Panel general del colegio. Supervisá en tiempo real la asistencia biométrica, el dictado de lecciones y la comunidad escolar.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <a href="<?= base_url('mvc/views/clases/panel.php') ?>" class="min-h-[50px] px-6 py-3 bg-rose-600 hover:bg-rose-700 text-white text-base font-extrabold rounded-2xl shadow-sm transition flex items-center gap-3">
                    <span class="w-3.5 h-3.5 rounded-full bg-white animate-pulse"></span>
                    <span>Clase en Vivo en Aula</span>
                </a>
                <button type="button" onclick="abrirModalDiagnostico()" class="btn btn-muted min-h-[50px] px-5 py-3 text-base font-bold rounded-2xl flex items-center gap-2 border border-slate-300">
                    <span>🔍</span>
                    <span>Diagnóstico IoT</span>
                </button>
            </div>
        </div>

        <!-- Barra de Acciones Rápidas Prioritarias (Sin repetir módulos) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- 1. Clase en Vivo -->
            <a href="<?= base_url('mvc/views/clases/panel.php') ?>" class="p-5 rounded-2xl bg-white border-2 border-slate-200 hover:border-rose-400 hover:shadow-md transition flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-2xl group-hover:scale-105 transition-transform">
                    🔴
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-rose-600 transition">Clase en Vivo</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Monitor en tiempo real</p>
                </div>
            </a>

            <!-- 2. Coordinación Académica -->
            <a href="<?= base_url('mvc/views/coordinacion/index.php') ?>" class="p-5 rounded-2xl bg-white border-2 border-slate-200 hover:border-indigo-400 hover:shadow-md transition flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl group-hover:scale-105 transition-transform">
                    🏫
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-indigo-600 transition">Coordinación</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Aulas y supervisión</p>
                </div>
            </a>

            <!-- 3. Control de Asistencias -->
            <a href="<?= base_url('mvc/views/asistencias_estudiantes/index.php') ?>" class="p-5 rounded-2xl bg-white border-2 border-slate-200 hover:border-emerald-400 hover:shadow-md transition flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl group-hover:scale-105 transition-transform">
                    ⏱️
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-emerald-600 transition">Asistencias</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Historial y justificaciones</p>
                </div>
            </a>

            <!-- 4. Matricular Estudiante -->
            <a href="<?= base_url('mvc/views/estudiantes/index.php') ?>" class="p-5 rounded-2xl bg-white border-2 border-slate-200 hover:border-blue-400 hover:shadow-md transition flex items-center gap-4 group">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-2xl group-hover:scale-105 transition-transform">
                    🎓
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 group-hover:text-blue-600 transition">Estudiantes</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Nómina y biometría</p>
                </div>
            </a>
        </div>

        <!-- Tarjetas KPI del Día con Números Grandes y Alto Contraste -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- 1. Asistencia Estudiantes Hoy -->
            <div class="p-6 rounded-3xl bg-gradient-to-br from-indigo-50 via-white to-indigo-50/40 border-2 border-indigo-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-extrabold uppercase tracking-wider text-indigo-900">Asistencia Hoy</span>
                        <span class="text-2xl">📊</span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-4xl sm:text-5xl font-black text-slate-900"><?= $pctAsistencia ?>%</span>
                        <span class="text-base text-indigo-800 font-bold"><?= $totalAsistieron ?> de <?= $totalEstudiantes ?></span>
                    </div>
                    <!-- Barra de progreso -->
                    <div class="w-full h-3 bg-slate-200 rounded-full overflow-hidden mt-3 shadow-inner">
                        <div class="h-full bg-indigo-600 transition-all duration-300" style="width: <?= min(100, $pctAsistencia) ?>%"></div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-indigo-100 flex items-center justify-between text-xs sm:text-sm font-bold">
                    <span class="text-emerald-700">🟢 <?= $presentesHoy ?> pres.</span>
                    <span class="text-amber-700">🟡 <?= $tardanzasHoy ?> tard.</span>
                    <span class="text-rose-700">🔴 <?= $ausentesHoy ?> aus.</span>
                </div>
            </div>

            <!-- 2. Presencia Docente -->
            <div class="p-6 rounded-3xl bg-gradient-to-br from-emerald-50 via-white to-emerald-50/40 border-2 border-emerald-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-extrabold uppercase tracking-wider text-emerald-900">Docentes Hoy</span>
                        <span class="text-2xl">👨‍🏫</span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-4xl sm:text-5xl font-black text-slate-900"><?= $profPresentesHoy ?></span>
                        <span class="text-base text-emerald-800 font-bold">de <?= $totalProfesores ?> registrados</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-2 font-medium">Marcaciones de profesores registradas hoy</p>
                </div>
                <div class="mt-4 pt-3 border-t border-emerald-100">
                    <a href="<?= base_url('mvc/views/asistencias_profesores/index.php') ?>" class="text-sm font-bold text-emerald-800 hover:underline flex items-center justify-between">
                        <span>Ver asistencias docentes</span>
                        <span>→</span>
                    </a>
                </div>
            </div>

            <!-- 3. Aulas Físicas Activas -->
            <div class="p-6 rounded-3xl bg-gradient-to-br from-blue-50 via-white to-blue-50/40 border-2 border-blue-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-extrabold uppercase tracking-wider text-blue-900">Aulas Físicas</span>
                        <span class="text-2xl">🚪</span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-4xl sm:text-5xl font-black text-slate-900"><?= $totalAulas ?></span>
                        <span class="text-base text-blue-800 font-bold">salas</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-2 font-medium">Salas de clase y laboratorios equipados</p>
                </div>
                <div class="mt-4 pt-3 border-t border-blue-100">
                    <a href="<?= base_url('mvc/views/coordinacion/index.php') ?>" class="text-sm font-bold text-blue-800 hover:underline flex items-center justify-between">
                        <span>Explorar aulas coordinadas</span>
                        <span>→</span>
                    </a>
                </div>
            </div>

            <!-- 4. Familias y Tutores -->
            <div class="p-6 rounded-3xl bg-gradient-to-br from-amber-50 via-white to-amber-50/40 border-2 border-amber-200 shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-extrabold uppercase tracking-wider text-amber-900">Comunidad Familiar</span>
                        <span class="text-2xl">👨‍👩‍👧‍👦</span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span class="text-4xl sm:text-5xl font-black text-slate-900"><?= $totalTutores ?></span>
                        <span class="text-base text-amber-800 font-bold">tutores</span>
                    </div>
                    <p class="text-sm text-slate-600 mt-2 font-medium">Padres y responsables vinculados con alumnos</p>
                </div>
                <div class="mt-4 pt-3 border-t border-amber-100">
                    <a href="<?= base_url('mvc/views/tutores/index.php') ?>" class="text-sm font-bold text-amber-800 hover:underline flex items-center justify-between">
                        <span>Gestionar familias</span>
                        <span>→</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Bloque Principal Dividido: Monitor de Marcaciones Recientes y Centros Estratégicos -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Columna Izquierda: Monitor Biométrico de Últimas Marcaciones (2 Columnas en Desktop) -->
            <div class="lg:col-span-2 space-y-5">
                <div class="flex items-center justify-between border-b-2 border-slate-200 pb-3">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 flex items-center gap-3">
                            <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 inline-block animate-pulse"></span>
                            Monitor de Marcaciones Biométricas
                        </h2>
                        <p class="text-base text-slate-600 mt-0.5">Últimos registros capturados por los sensores AS608/DY50 en las aulas</p>
                    </div>
                    <a href="<?= base_url('mvc/views/asistencias_estudiantes/index.php') ?>" class="text-sm font-bold text-indigo-700 hover:underline hidden sm:inline">
                        Ver todas →
                    </a>
                </div>

                <?php if (empty($ultimasMarcaciones)): ?>
                    <div class="p-12 text-center rounded-3xl bg-white border-2 border-slate-200 shadow-sm space-y-3">
                        <span class="text-4xl block">⏱️</span>
                        <h3 class="text-xl font-bold text-slate-900">Aún no se han registrado marcaciones hoy</h3>
                        <p class="text-base text-slate-600 max-w-md mx-auto">
                            El sistema está a la espera de que los estudiantes apoyen su dedo en los sensores biométricos de sus respectivas aulas.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-3xl border-2 border-slate-200/90 bg-white shadow-sm">
                        <table class="w-full text-left text-base">
                            <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-sm border-b-2 border-slate-200">
                                <tr>
                                    <th class="p-4 sm:p-5">Estudiante</th>
                                    <th class="p-4">Curso / Grado</th>
                                    <th class="p-4">Hora</th>
                                    <th class="p-4 text-right">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 text-slate-800">
                                <?php foreach ($ultimasMarcaciones as $m): ?>
                                    <?php
                                    $esPres = ($m['estado'] === 'PRESENTE');
                                    $badgeClass = $esPres ? 'bg-emerald-100 text-emerald-900 border-emerald-200' : 'bg-amber-100 text-amber-900 border-amber-200';
                                    ?>
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="p-4 sm:p-5 font-bold text-slate-900">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 font-bold flex items-center justify-center text-sm border border-indigo-200 shrink-0">
                                                    <?= strtoupper(substr($m['nombre'] ?? 'A', 0, 1)) ?>
                                                </div>
                                                <span><?= htmlspecialchars(($m['nombre'] ?? '') . ' ' . ($m['apellido'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        </td>
                                        <td class="p-4 font-semibold text-slate-700">
                                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-sm">
                                                <?= htmlspecialchars($m['grado_nombre'] ?? 'Curso no asignado', ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="p-4 font-mono font-bold text-slate-700">
                                            <?= htmlspecialchars(substr($m['hora'], 0, 5), ENT_QUOTES, 'UTF-8') ?> hs
                                        </td>
                                        <td class="p-4 text-right">
                                            <span class="inline-block px-3 py-1 rounded-full text-sm font-extrabold border <?= $badgeClass ?>">
                                                <?= $esPres ? '✓ Presente' : '⏱ Tardanza' ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Columna Derecha: Centros Estratégicos del Sistema (Organizados) -->
            <div class="space-y-5">
                <div class="border-b-2 border-slate-200 pb-3">
                    <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 flex items-center gap-2.5">
                        <span class="text-2xl">🏛️</span>
                        <span>Centros Operativos</span>
                    </h2>
                    <p class="text-base text-slate-600 mt-0.5">Acceso a los ejes principales de gestión escolar</p>
                </div>

                <div class="space-y-4">
                    <!-- Eje 1: Pedagógico y Curricular -->
                    <div class="p-6 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl font-bold">
                                📖
                            </span>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Eje Pedagógico</h3>
                                <p class="text-xs text-slate-500">Planes de estudio y contenidos</p>
                            </div>
                        </div>
                        <p class="text-sm text-slate-700 leading-relaxed">
                            Organización de planes anuales según normas MEC, distribución de horarios semanales y avance pedagógico.
                        </p>
                        <div class="pt-2 flex flex-col gap-2">
                            <a href="<?= base_url('mvc/views/planes_anuales/index.php') ?>" class="text-sm font-bold text-indigo-700 hover:underline flex items-center justify-between">
                                <span>Planes Anuales MEC</span>
                                <span>→</span>
                            </a>
                            <a href="<?= base_url('mvc/views/horarios/index.php') ?>" class="text-sm font-bold text-indigo-700 hover:underline flex items-center justify-between">
                                <span>Horarios de Clases</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>

                    <!-- Eje 2: Comunidad Escolar -->
                    <div class="p-6 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold">
                                👥
                            </span>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Comunidad Escolar</h3>
                                <p class="text-xs text-slate-500">Cuerpo docente y familias</p>
                            </div>
                        </div>
                        <p class="text-sm text-slate-700 leading-relaxed">
                            Padrón docente con materias asignadas, directorio de tutores legales y expedientes de estudiantes.
                        </p>
                        <div class="pt-2 flex flex-col gap-2">
                            <a href="<?= base_url('mvc/views/profesores/index.php') ?>" class="text-sm font-bold text-emerald-800 hover:underline flex items-center justify-between">
                                <span>Nómina de Profesores</span>
                                <span>→</span>
                            </a>
                            <a href="<?= base_url('mvc/views/tutores/index.php') ?>" class="text-sm font-bold text-emerald-800 hover:underline flex items-center justify-between">
                                <span>Directorio de Tutores y Familias</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>

                    <!-- Eje 3: Centro de Administración e Infraestructura -->
                    <div class="p-6 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl font-bold">
                                ⚙️
                            </span>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Centro de Administración</h3>
                                <p class="text-xs text-slate-500">Malla curricular, salas e IoT</p>
                            </div>
                        </div>
                        <p class="text-sm text-slate-700 leading-relaxed">
                            Configuración de turnos, ciclos, bachilleratos técnicos, materias, aulas físicas y parámetros de hardware.
                        </p>
                        <div class="pt-2 flex flex-col gap-2">
                            <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="text-sm font-bold text-blue-800 hover:underline flex items-center justify-between">
                                <span>Abrir Centro de Control Administrativo</span>
                                <span>→</span>
                            </a>
                            <a href="<?= base_url('mvc/views/configuracion/index.php') ?>" class="text-sm font-bold text-blue-800 hover:underline flex items-center justify-between">
                                <span>Configuración de Red & IoT</span>
                                <span>→</span>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</main>

<!-- Inclusión del Submodal de Diagnóstico del Dashboard -->
<?php require_once __DIR__ . '/modal_diagnostico_dashboard.php'; ?>

<?php require_once 'layouts/footer.php'; ?>
