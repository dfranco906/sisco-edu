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

try {
    $db = (new Database())->getConnection();
    $turnos = $db->query("SELECT id_turno, nombre FROM public.turnos WHERE activo = 1 ORDER BY id_turno ASC")->fetchAll(PDO::FETCH_ASSOC);
    $niveles = $db->query("SELECT id_nivel, nombre FROM public.niveles_academicos WHERE activo = 1 ORDER BY id_nivel ASC")->fetchAll(PDO::FETCH_ASSOC);
    $bachilleratos = $db->query("SELECT id_bachillerato, nombre, codigo FROM public.bachilleratos WHERE activo = 1 ORDER BY id_bachillerato ASC")->fetchAll(PDO::FETCH_ASSOC);
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
        <span class="text-slate-800 dark:text-slate-200">Marco Horario Institucional</span>
    </nav>

    <!-- Header Principal -->
    <header class="p-6 sm:p-7 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold text-2xl shadow-sm">
                        ⏱️
                    </span>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                            Marco Horario Institucional
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium mt-0.5">
                            Gestión Global de Apertura, Inicio de Clases, Tolerancia de Tardanzas y Cierre de Jornada IoT
                        </p>
                    </div>
                </div>
                <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 mt-3 font-normal leading-relaxed">
                    Establece las franjas horarias operativas para cada día de la semana por turno y especialidad (ej. salida 13:30 hs para BTI). Controla cuándo las aulas reciben huellas y el momento exacto en que el Gateway LoRa envía la orden de reseteo diario.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" onclick="abrirModalCrearMarco()" class="min-h-[46px] px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-sm font-extrabold shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95">
                    <span class="text-lg">+</span>
                    <span>Nuevo Marco Horario</span>
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

    <!-- Banner Informativo de Lógica de Operación -->
    <div class="p-5 rounded-3xl bg-indigo-50/80 dark:bg-indigo-950/40 border-2 border-indigo-200/80 dark:border-indigo-800/60 shadow-xs mb-6 text-indigo-950 dark:text-indigo-200 space-y-2">
        <div class="flex items-center gap-2 font-black text-sm sm:text-base">
            <span>🛡️</span>
            <span>Reglas de Biometría & Sincronización LoRa en Tiempo Real</span>
        </div>
        <ul class="text-xs sm:text-sm space-y-1 text-indigo-900 dark:text-indigo-300 list-disc list-inside">
            <li><strong>Apertura de Aula:</strong> El sensor óptico DY50 comienza a aceptar marcaciones a partir de la <em>Hora de Apertura</em>.</li>
            <li><strong>Tolerancia de Tardanza:</strong> Quienes marquen entre la <em>Hora de Inicio</em> y los minutos de tolerancia se computan como <code>PRESENTE</code>. Pasado ese tiempo, el sistema registra automáticamente <code>TARDANZA</code>.</li>
            <li><strong>Cierre de Jornada & Reseteo:</strong> Al alcanzarse la <em>Hora de Cierre</em> de ese curso, el Gateway LoRa transmite la trama <code>CMD:RESET_DIA</code> al aula para limpiar la memoria diaria de marcaciones.</li>
            <li><strong>Prioridad de Bachillerato:</strong> Los bachilleratos técnicos como <strong>BTI</strong> con jornada extendida (ej. 13:30 hs) prevalecen sobre el horario matutino general (12:00 hs).</li>
        </ul>
    </div>

    <!-- Filtros de Navegación -->
    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm mb-6 space-y-4">
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
    <div class="rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden mb-6">
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

<script>
window.BASE_URL = "<?= base_url('') ?>";

(function() {
    let marcosData = [];
    let diaSeleccionado = '';

    document.addEventListener('DOMContentLoaded', () => {
        cargarMarcos();

        // Filtro por pestañas de días
        document.querySelectorAll('.btn-filtro-dia').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.btn-filtro-dia').forEach(b => {
                    b.classList.remove('bg-indigo-600', 'text-white', 'shadow-xs');
                    b.classList.add('bg-slate-100', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
                });
                this.classList.add('bg-indigo-600', 'text-white', 'shadow-xs');
                this.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');

                diaSeleccionado = this.dataset.dia || '';
                aplicarFiltros();
            });
        });

        // Filtros selects
        document.getElementById('filtro-turno').addEventListener('change', aplicarFiltros);
        document.getElementById('filtro-bachi').addEventListener('change', aplicarFiltros);
    });

    async function cargarMarcos() {
        const tbody = document.getElementById('tabla-marcos-body');
        try {
            const res = await fetch(`${window.BASE_URL}src/api/Horario/leer_marcos_horarios.php`);
            const data = await res.json();

            if (data.status === 'success' && Array.isArray(data.data)) {
                marcosData = data.data;
                aplicarFiltros();
            } else {
                tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-rose-500 font-bold">Error al cargar marcos: ${data.message || ''}</td></tr>`;
            }
        } catch (err) {
            console.error(err);
            tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-rose-500 font-bold">Error de conexión al cargar marcos horarios.</td></tr>`;
        }
    }

    function aplicarFiltros() {
        const idTurno = document.getElementById('filtro-turno').value;
        const idBachi = document.getElementById('filtro-bachi').value;

        const filtrados = marcosData.filter(m => {
            if (diaSeleccionado && m.dia_semana.toLowerCase() !== diaSeleccionado.toLowerCase()) return false;
            if (idTurno && String(m.id_turno) !== String(idTurno)) return false;
            if (idBachi && String(m.id_bachillerato) !== String(idBachi)) return false;
            return true;
        });

        renderizarTabla(filtrados);
    }

    function renderizarTabla(lista) {
        const tbody = document.getElementById('tabla-marcos-body');
        tbody.innerHTML = '';

        if (!lista || lista.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-slate-400">No hay marcos horarios registrados para este filtro.</td></tr>`;
            return;
        }

        lista.forEach(m => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50 dark:hover:bg-slate-800/50 transition';

            const esEspecial = m.id_bachillerato !== null;
            const bachiBadge = esEspecial 
                ? `<span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-100 text-indigo-800 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">⭐ ${m.bachillerato_codigo || m.bachillerato_nombre}</span>` 
                : `<span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">General</span>`;

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
                        <button type="button" onclick='abrirModalEditarMarco(${JSON.stringify(m)})' class="p-1.5 rounded-lg text-slate-600 hover:text-indigo-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition" title="Editar">
                            ✏️
                        </button>
                        <button type="button" onclick="desactivarMarco(${m.id_marco})" class="p-1.5 rounded-lg text-slate-600 hover:text-rose-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition" title="Desactivar">
                            🗑️
                        </button>
                    </div>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    function obtenerColorDia(dia) {
        return matchDiaColor(dia);
    }

    function matchDiaColor(dia) {
        const d = (dia || '').toLowerCase();
        if (d.includes('lun')) return 'bg-blue-500';
        if (d.includes('mar')) return 'bg-cyan-500';
        if (d.includes('mie') || d.includes('mié')) return 'bg-teal-500';
        if (d.includes('jue')) return 'bg-emerald-500';
        if (d.includes('vie')) return 'bg-amber-500';
        return 'bg-purple-500';
    }

    window.abrirModalCrearMarco = function() {
        document.getElementById('modal-marco-titulo').textContent = 'Nuevo Marco Horario Institucional';
        document.getElementById('marco-id').value = '0';
        document.getElementById('marco-dia').value = diaSeleccionado || 'Lunes';
        document.getElementById('marco-turno').selectedIndex = 0;
        document.getElementById('marco-nivel').value = '';
        document.getElementById('marco-bachi').value = '';
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
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
