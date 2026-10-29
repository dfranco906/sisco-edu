<?php
// mvc/views/administracion/promociones.php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/middleware/auth.php';

// RBAC: Únicamente SuperAdmin y Administrador tienen acceso a este módulo
verificarRol(['SuperAdmin', 'Administrador']);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10 text-base">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Breadcrumb de Navegación Accesible -->
        <nav class="flex items-center gap-2 text-base text-slate-600 dark:text-slate-400 font-medium" aria-label="Migas de pan">
            <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition flex items-center gap-1.5 focus:outline-none focus:underline">
                <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Inicio
            </a>
            <span class="text-slate-400 dark:text-slate-600">/</span>
            <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition focus:outline-none focus:underline">
                Administración
            </a>
            <span class="text-slate-400 dark:text-slate-600">/</span>
            <span class="text-slate-900 dark:text-white font-bold">Promoción de Cursos</span>
        </nav>

        <!-- Cabecera de Página con Acciones -->
        <div class="app-page-header p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 text-xl font-bold shadow-xs">
                        🎓
                    </span>
                    <span class="px-3.5 py-1 text-sm font-extrabold rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-indigo-900 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 uppercase tracking-wide">
                        Gestión Curricular y Transición Anual
                    </span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Promoción de Cursos y Matrículas
                </h1>
                <p class="text-base sm:text-lg text-slate-700 dark:text-slate-300 mt-2 max-w-3xl leading-relaxed">
                    Promueve masivamente a los estudiantes al siguiente ciclo lectivo, reasigna sus aulas físicas y encola automáticamente sus huellas registradas para su transmisión LoRa al nuevo sensor del aula.
                </p>
            </div>

            <!-- Botonera Superior -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="btn btn-muted min-h-[46px] px-5 py-2.5 text-base font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 flex items-center gap-2">
                    <span>←</span>
                    <span>Administración</span>
                </a>
                <a href="<?= base_url('mvc/views/administracion/sync_biometrica.php') ?>" class="btn min-h-[46px] px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-base font-bold rounded-2xl shadow-sm transition flex items-center gap-2">
                    <span>📡</span>
                    <span>Cola LoRa</span>
                </a>
            </div>
        </div>

        <!-- Banner Informativo de Continuidad Biométrica -->
        <div class="p-5 sm:p-6 rounded-3xl bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-slate-900 dark:to-indigo-950/30 border-2 border-blue-200 dark:border-indigo-900/60 shadow-xs flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-2xl shrink-0 shadow-sm">
                💡
            </div>
            <div class="space-y-1 text-base text-slate-700 dark:text-slate-300">
                <h2 class="text-lg font-bold text-slate-900 dark:text-white">
                    Continuidad Biométrica sin Re-enrolamiento
                </h2>
                <p>
                    Las plantillas biométricas (1536 bytes / 3072 HEX) se encuentran resguardadas centralmente en el servidor. Al promover estudiantes con huella registrada a un curso con aula asignada, el sistema programará automáticamente su envío al sensor DY50 de la nueva aula.
                </p>
            </div>
        </div>

        <!-- Formulario Interactivo de Promoción -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Columna Izquierda: Configuración de Origen y Destino -->
            <div class="lg:col-span-1 space-y-6">

                <!-- Tarjeta Paso 1: Origen -->
                <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4">
                    <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-bold text-sm tracking-wide uppercase">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-950/60 flex items-center justify-center text-xs">1</span>
                        <span>Curso de Origen</span>
                    </div>

                    <div>
                        <label for="select-grado-origen" class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                            Seleccionar Grado / Curso a Promover:
                        </label>
                        <select id="select-grado-origen" onchange="cambiarGradoOrigen()" class="w-full px-4 py-3 rounded-2xl border-2 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-medium focus:border-indigo-500 focus:outline-none transition">
                            <option value="">-- Cargando cursos... --</option>
                        </select>
                    </div>

                    <!-- Resumen del origen seleccionado -->
                    <div id="info-grado-origen" class="hidden p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-sm space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 dark:text-slate-400">Aula actual:</span>
                            <span id="txt-origen-aula" class="font-bold text-slate-800 dark:text-slate-200">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 dark:text-slate-400">Turno / Nivel:</span>
                            <span id="txt-origen-nivel" class="font-bold text-slate-800 dark:text-slate-200">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 dark:text-slate-400">Estudiantes activos:</span>
                            <span id="txt-origen-total" class="font-bold text-indigo-600 dark:text-indigo-400">0</span>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta Paso 2: Destino y Parámetros -->
                <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm space-y-5">
                    <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-bold text-sm tracking-wide uppercase">
                        <span class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-950/60 flex items-center justify-center text-xs">2</span>
                        <span>Destino y Ciclo Lectivo</span>
                    </div>

                    <!-- Checkbox de Graduación / Egreso -->
                    <div class="p-3.5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" id="check-graduacion" onchange="toggleGraduacion()" class="w-5 h-5 mt-0.5 rounded text-amber-600 focus:ring-amber-500 border-slate-300 dark:border-slate-600">
                            <div>
                                <span class="font-bold text-slate-900 dark:text-white block">🎓 Graduación / Egresados</span>
                                <span class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed block mt-0.5">
                                    Marcar la cohorte como graduada (ej. 3° curso). Se cerrarán sus accesos y se revocarán los permisos biométricos de aulas.
                                </span>
                            </div>
                        </label>
                    </div>

                    <!-- Selector de Grado Destino -->
                    <div id="contenedor-grado-destino">
                        <label for="select-grado-destino" class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                            Grado / Curso de Destino:
                        </label>
                        <select id="select-grado-destino" onchange="actualizarInfoDestino()" class="w-full px-4 py-3 rounded-2xl border-2 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-medium focus:border-indigo-500 focus:outline-none transition">
                            <option value="">-- Seleccionar grado destino --</option>
                        </select>
                    </div>

                    <!-- Resumen del destino seleccionado -->
                    <div id="info-grado-destino" class="hidden p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-sm space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 dark:text-slate-400">Aula destino:</span>
                            <span id="txt-destino-aula" class="font-bold text-slate-800 dark:text-slate-200">--</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500 dark:text-slate-400">Nodo LoRa aula:</span>
                            <span id="txt-destino-lora" class="font-bold text-indigo-600 dark:text-indigo-400">--</span>
                        </div>
                    </div>

                    <!-- Año Lectivo -->
                    <div>
                        <label for="input-anho-lectivo" class="block text-sm font-bold text-slate-900 dark:text-white mb-2">
                            Año Lectivo de la Promoción:
                        </label>
                        <input type="number" id="input-anho-lectivo" value="<?= (int)date('Y') + 1 ?>" min="2020" max="2050" class="w-full px-4 py-3 rounded-2xl border-2 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-medium focus:border-indigo-500 focus:outline-none transition">
                    </div>

                    <!-- Checkbox Migración de Huellas -->
                    <div id="contenedor-check-migrar" class="p-3.5 rounded-2xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/60">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" id="check-migrar-huellas" checked class="w-5 h-5 mt-0.5 rounded text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-600">
                            <div>
                                <span class="font-bold text-slate-900 dark:text-white block">📡 Encolar huellas a la nueva aula</span>
                                <span class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed block mt-0.5">
                                    Genera tareas de transmisión LoRa hacia el sensor del aula destino para todos los estudiantes con huella activa.
                                </span>
                            </div>
                        </label>
                    </div>

                    <!-- Botón de Ejecutar Promoción -->
                    <button type="button" id="btn-preparar-promocion" onclick="abrirModalConfirmacion()" disabled class="w-full min-h-[50px] py-3 px-5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-extrabold text-base transition flex items-center justify-center gap-2 shadow-sm">
                        <span>🎓</span>
                        <span id="btn-promover-texto">Promover Estudiantes</span>
                    </button>
                </div>

            </div>

            <!-- Columna Derecha: Nómina de Estudiantes del Curso -->
            <div class="lg:col-span-2 space-y-6">

                <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm space-y-5">
                    
                    <!-- Barra de Control de la Nómina -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>👥</span>
                                <span>Estudiantes del Curso</span>
                            </h2>
                            <p id="subtitulo-nomina" class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">
                                Seleccione un grado de origen para cargar la nómina.
                            </p>
                        </div>

                        <!-- Filtro de Búsqueda Rápida -->
                        <div class="relative w-full sm:w-64">
                            <input type="text" id="filtro-estudiante" oninput="filtrarTablaEstudiantes()" placeholder="Buscar alumno o CI..." class="w-full pl-9 pr-4 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                🔍
                            </span>
                        </div>
                    </div>

                    <!-- Resumen de Selección y Controles Rápidos -->
                    <div id="barra-seleccion" class="hidden flex flex-wrap items-center justify-between gap-3 p-3.5 rounded-2xl bg-slate-100 dark:bg-slate-800 text-sm font-semibold">
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="seleccionarTodos(true)" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                Seleccionar todos
                            </button>
                            <span class="text-slate-400">|</span>
                            <button type="button" onclick="seleccionarTodos(false)" class="text-slate-600 dark:text-slate-400 hover:underline">
                                Deseleccionar
                            </button>
                        </div>
                        <div id="badge-contador-seleccion" class="text-slate-700 dark:text-slate-300">
                            0 de 0 seleccionados
                        </div>
                    </div>

                    <!-- Tabla de Estudiantes -->
                    <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead class="bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 uppercase tracking-wider text-xs font-bold">
                                <tr>
                                    <th class="p-3 text-center w-12">
                                        <input type="checkbox" id="check-todos-header" onchange="toggleTodosHeader(this)" class="w-4 h-4 rounded text-indigo-600 border-slate-300">
                                    </th>
                                    <th class="p-3">Estudiante</th>
                                    <th class="p-3">Cédula</th>
                                    <th class="p-3 text-center">Biometría DY50</th>
                                    <th class="p-3">Aula Actual</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-estudiantes" class="divide-y divide-slate-200 dark:divide-slate-800 text-slate-800 dark:text-slate-200">
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-slate-500 dark:text-slate-400">
                                        Seleccione un grado de origen en el panel lateral para listar los alumnos.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

        </div>

    </div>
</main>

<!-- Modal de Confirmación de Promoción -->
<div id="modal-confirmacion" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 hidden" role="dialog" aria-modal="true" aria-labelledby="modal-confirmacion-titulo">
    <div class="bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 rounded-3xl max-w-xl w-full max-h-[92vh] flex flex-col overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Header -->
        <div class="p-5 sm:p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-800/90 flex items-start justify-between gap-4 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-2xl font-bold shrink-0 border border-indigo-200 dark:border-indigo-800 shadow-2xs">
                    🎓
                </div>
                <div class="min-w-0">
                    <h3 id="modal-confirmacion-titulo" class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white truncate">
                        Confirmar Promoción de Cohorte
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                        Verifique los detalles antes de aplicar los cambios en la base de datos.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalConfirmacion()" class="min-w-[44px] min-h-[44px] w-11 h-11 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer hover:bg-slate-200/70 dark:hover:bg-slate-800 transition-colors shrink-0" aria-label="Cerrar modal">&times;</button>
        </div>

        <!-- Body con scroll -->
        <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-5 text-base">
            <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border-2 border-slate-200 dark:border-slate-700 space-y-3.5">
                <div class="flex justify-between items-center text-base">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Grado Origen:</span>
                    <span id="resumen-origen" class="font-black text-slate-900 dark:text-white text-lg">--</span>
                </div>
                <div class="flex justify-between items-center text-base">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Destino:</span>
                    <span id="resumen-destino" class="font-black text-indigo-600 dark:text-indigo-400 text-lg">--</span>
                </div>
                <div class="flex justify-between items-center text-base">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Año Lectivo:</span>
                    <span id="resumen-anho" class="font-black text-slate-900 dark:text-white text-lg">--</span>
                </div>
                <div class="flex justify-between items-center text-base pt-2 border-t border-slate-200 dark:border-slate-700">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Estudiantes a promover:</span>
                    <span id="resumen-total-estudiantes" class="font-black text-slate-900 dark:text-white text-xl">0</span>
                </div>
                <div class="flex justify-between items-center text-base">
                    <span class="text-slate-600 dark:text-slate-400 font-medium">Huellas a encolar en LoRa:</span>
                    <span id="resumen-total-huellas" class="font-black text-amber-600 dark:text-amber-400 text-xl">0</span>
                </div>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 p-4 rounded-2xl">
                ⚠️ <strong>Aviso Importante:</strong> Esta acción actualizará los registros de los alumnos seleccionados, reasignará su aula correspondiente e insertará las huellas en la cola de distribución LoRa del gateway.
            </p>
        </div>

        <!-- Footer fijo -->
        <div class="p-4 sm:p-6 bg-slate-50/90 dark:bg-slate-800/90 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3 shrink-0">
            <button type="button" onclick="cerrarModalConfirmacion()" class="btn btn-secondary min-h-[46px] px-5 py-2.5 rounded-xl text-base font-semibold cursor-pointer">
                Cancelar
            </button>
            <button type="button" id="btn-ejecutar-confirmado" onclick="ejecutarPromocion()" class="btn btn-primary min-h-[46px] px-6 py-2.5 rounded-xl text-base font-bold shadow-sm flex items-center gap-2 cursor-pointer active:scale-95">
                <span id="spinner-ejecutar" class="hidden animate-spin">⏳</span>
                <span>Confirmar y Promover</span>
            </button>
        </div>

    </div>
</div>

<!-- Modal de Resultado / Éxito -->
<div id="modal-resultado" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 hidden" role="dialog" aria-modal="true">
    <div class="bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full max-h-[92vh] flex flex-col overflow-hidden shadow-2xl animate-in fade-in zoom-in-95 duration-150">
        
        <div class="p-6 sm:p-8 overflow-y-auto flex-1 text-center space-y-6">
            <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-3xl font-bold mx-auto border border-emerald-200 dark:border-emerald-800 shadow-sm">
                ✅
            </div>

            <div class="space-y-2">
                <h3 class="text-2xl font-extrabold text-slate-900 dark:text-white">
                    Promoción Exitosa
                </h3>
                <p id="txt-resultado-mensaje" class="text-base text-slate-600 dark:text-slate-400 leading-relaxed">
                    Los estudiantes fueron promovidos y las huellas biométricas se encolaron exitosamente.
                </p>
            </div>
        </div>

        <div class="p-4 sm:p-6 bg-slate-50/90 dark:bg-slate-800/90 border-t border-slate-200 dark:border-slate-800 flex flex-col gap-2.5 shrink-0">
            <a href="<?= base_url('mvc/views/administracion/sync_biometrica.php') ?>" class="w-full min-h-[46px] py-3 px-5 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-bold transition flex items-center justify-center gap-2 text-base shadow-sm">
                <span>📡</span>
                <span>Ver Cola LoRa de Huellas</span>
            </a>
            <button type="button" onclick="cerrarModalResultado()" class="btn btn-secondary w-full min-h-[46px] py-2.5 px-5 rounded-2xl text-base font-semibold">
                Continuar en Promociones
            </button>
        </div>

    </div>
</div>

<script>
let catalogoGrados = [];
let estudiantesActuales = [];

document.addEventListener('DOMContentLoaded', () => {
    cargarDatosIniciales();
});

async function cargarDatosIniciales() {
    try {
        const resp = await fetch('<?= base_url('src/api/Promocion/obtener_datos_promocion.php') ?>');
        const res = await resp.json();
        if (res.status !== 'success') {
            throw new Error(res.message || 'Error cargando datos');
        }

        catalogoGrados = res.data.grados || [];
        poblarSelectGrados(catalogoGrados);
    } catch (err) {
        console.error('Error inicializando promociones:', err);
        mostrarAlerta('Error al cargar catálogo de grados: ' + err.message, 'error');
    }
}

function poblarSelectGrados(grados) {
    const selOrigen = document.getElementById('select-grado-origen');
    selOrigen.innerHTML = '<option value="">-- Seleccionar curso de origen --</option>';

    grados.forEach(g => {
        const opt = document.createElement('option');
        opt.value = g.id_grado;
        const infoAula = g.aula_nombre ? `(${g.aula_nombre})` : '(Sin aula)';
        const infoAlumnos = `[${g.total_estudiantes || 0} alumnos]`;
        opt.textContent = `${g.nombre} ${infoAula} ${infoAlumnos}`;
        selOrigen.appendChild(opt);
    });

    poblarSelectDestino();
}

function poblarSelectDestino() {
    const selDestino = document.getElementById('select-grado-destino');
    const idOrigen = parseInt(document.getElementById('select-grado-origen').value, 10) || 0;

    selDestino.innerHTML = '<option value="">-- Seleccionar grado destino --</option>';

    catalogoGrados.forEach(g => {
        if (parseInt(g.id_grado, 10) === idOrigen) {
            return; // no permitir mismo grado
        }
        const opt = document.createElement('option');
        opt.value = g.id_grado;
        const infoAula = g.aula_nombre ? `(${g.aula_nombre})` : '(Sin aula)';
        opt.textContent = `${g.nombre} ${infoAula}`;
        selDestino.appendChild(opt);
    });

    actualizarInfoDestino();
    evaluarBotonPromover();
}

async function cambiarGradoOrigen() {
    const idOrigen = parseInt(document.getElementById('select-grado-origen').value, 10) || 0;
    const infoBox = document.getElementById('info-grado-origen');
    const tbody = document.getElementById('tbody-estudiantes');
    const barra = document.getElementById('barra-seleccion');

    poblarSelectDestino();

    if (!idOrigen) {
        infoBox.classList.add('hidden');
        barra.classList.add('hidden');
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="p-8 text-center text-slate-500 dark:text-slate-400">
                    Seleccione un grado de origen en el panel lateral para listar los alumnos.
                </td>
            </tr>
        `;
        estudiantesActuales = [];
        evaluarBotonPromover();
        return;
    }

    const gInfo = catalogoGrados.find(g => parseInt(g.id_grado, 10) === idOrigen);
    if (gInfo) {
        document.getElementById('txt-origen-aula').textContent = gInfo.aula_nombre || 'Sin aula';
        document.getElementById('txt-origen-nivel').textContent = `${gInfo.nivel || ''} ${gInfo.turno ? '(' + gInfo.turno + ')' : ''}`;
        document.getElementById('txt-origen-total').textContent = gInfo.total_estudiantes || 0;
        infoBox.classList.remove('hidden');
    }

    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="p-8 text-center text-slate-500 dark:text-slate-400">
                <span class="inline-block animate-spin mr-2">⏳</span> Cargando nómina de alumnos...
            </td>
        </tr>
    `;

    try {
        const resp = await fetch(`<?= base_url('src/api/Promocion/obtener_datos_promocion.php') ?>?id_grado=${idOrigen}`);
        const res = await resp.json();
        if (res.status !== 'success') {
            throw new Error(res.message || 'Error cargando nómina');
        }

        estudiantesActuales = res.data.estudiantes || [];
        renderizarTablaEstudiantes(estudiantesActuales);
        barra.classList.remove('hidden');
        seleccionarTodos(true);
    } catch (err) {
        console.error('Error cargando estudiantes:', err);
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="p-8 text-center text-rose-500 font-bold">
                    Error al cargar los estudiantes: ${err.message}
                </td>
            </tr>
        `;
    }
}

function renderizarTablaEstudiantes(lista) {
    const tbody = document.getElementById('tbody-estudiantes');
    if (!lista.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="p-8 text-center text-slate-500 dark:text-slate-400">
                    No hay estudiantes activos registrados en este curso.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = lista.map(e => {
        const tieneHuella = parseInt(e.tiene_huella_activa, 10) === 1;
        const badgeHuella = tieneHuella
            ? `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                ✅ Registrada (Slot #${e.sensor_slot || 'N/A'})
               </span>`
            : `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400">
                Sin huella
               </span>`;

        return `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                <td class="p-4 text-center">
                    <input type="checkbox" value="${e.id_estudiante}" class="check-estudiante w-5 h-5 rounded text-indigo-600 border-slate-300 dark:border-slate-600 cursor-pointer" onchange="actualizarContadorSeleccion()">
                </td>
                <td class="p-4 font-bold text-base text-slate-900 dark:text-white">
                    ${e.apellido}, ${e.nombre}
                </td>
                <td class="p-4 font-mono text-sm font-semibold text-slate-700 dark:text-slate-300">
                    ${e.cedula_identidad || 'S/N'}
                </td>
                <td class="p-4 text-center">
                    ${badgeHuella}
                </td>
                <td class="p-4 text-sm font-semibold text-slate-700 dark:text-slate-300">
                    ${e.aula_nombre || 'Sin aula'}
                </td>
            </tr>
        `;
    }).join('');

    actualizarContadorSeleccion();
}

function filtrarTablaEstudiantes() {
    const termino = document.getElementById('filtro-estudiante').value.toLowerCase().trim();
    const filas = document.querySelectorAll('#tbody-estudiantes tr');

    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(termino) ? '' : 'none';
    });
}

function seleccionarTodos(estado) {
    const checks = document.querySelectorAll('.check-estudiante');
    checks.forEach(c => c.checked = estado);
    const headerCheck = document.getElementById('check-todos-header');
    if (headerCheck) headerCheck.checked = estado;
    actualizarContadorSeleccion();
}

function toggleTodosHeader(header) {
    seleccionarTodos(header.checked);
}

function actualizarContadorSeleccion() {
    const checks = document.querySelectorAll('.check-estudiante:checked');
    const total = document.querySelectorAll('.check-estudiante').length;
    const seleccionados = checks.length;

    let huellasSeleccionadas = 0;
    checks.forEach(c => {
        const idEst = parseInt(c.value, 10);
        const est = estudiantesActuales.find(e => parseInt(e.id_estudiante, 10) === idEst);
        if (est && parseInt(est.tiene_huella_activa, 10) === 1) {
            huellasSeleccionadas++;
        }
    });

    const badge = document.getElementById('badge-contador-seleccion');
    badge.textContent = `${seleccionados} de ${total} seleccionados (${huellasSeleccionadas} con huella)`;

    evaluarBotonPromover();
}

function toggleGraduacion() {
    const esGrad = document.getElementById('check-graduacion').checked;
    const contDestino = document.getElementById('contenedor-grado-destino');
    const infoDestino = document.getElementById('info-grado-destino');
    const contCheckMigrar = document.getElementById('contenedor-check-migrar');

    if (esGrad) {
        contDestino.classList.add('opacity-50', 'pointer-events-none');
        infoDestino.classList.add('hidden');
        contCheckMigrar.classList.add('opacity-50', 'pointer-events-none');
        document.getElementById('check-migrar-huellas').checked = false;
        document.getElementById('btn-promover-texto').textContent = 'Graduar Estudiantes';
    } else {
        contDestino.classList.remove('opacity-50', 'pointer-events-none');
        contCheckMigrar.classList.remove('opacity-50', 'pointer-events-none');
        document.getElementById('check-migrar-huellas').checked = true;
        document.getElementById('btn-promover-texto').textContent = 'Promover Estudiantes';
        actualizarInfoDestino();
    }

    evaluarBotonPromover();
}

function actualizarInfoDestino() {
    const idDestino = parseInt(document.getElementById('select-grado-destino').value, 10) || 0;
    const infoBox = document.getElementById('info-grado-destino');

    if (!idDestino) {
        infoBox.classList.add('hidden');
        evaluarBotonPromover();
        return;
    }

    const gInfo = catalogoGrados.find(g => parseInt(g.id_grado, 10) === idDestino);
    if (gInfo) {
        document.getElementById('txt-destino-aula').textContent = gInfo.aula_nombre || 'Sin aula';
        document.getElementById('txt-destino-lora').textContent = gInfo.lora_id ? `Nodo LoRa #${gInfo.lora_id}` : 'Sin nodo LoRa';
        infoBox.classList.remove('hidden');
    }

    evaluarBotonPromover();
}

function evaluarBotonPromover() {
    const btn = document.getElementById('btn-preparar-promocion');
    const idOrigen = parseInt(document.getElementById('select-grado-origen').value, 10) || 0;
    const idDestino = parseInt(document.getElementById('select-grado-destino').value, 10) || 0;
    const esGrad = document.getElementById('check-graduacion').checked;
    const checks = document.querySelectorAll('.check-estudiante:checked');

    if (idOrigen > 0 && checks.length > 0 && (esGrad || idDestino > 0)) {
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
}

function abrirModalConfirmacion() {
    const idOrigen = parseInt(document.getElementById('select-grado-origen').value, 10);
    const idDestino = parseInt(document.getElementById('select-grado-destino').value, 10);
    const esGrad = document.getElementById('check-graduacion').checked;
    const anho = document.getElementById('input-anho-lectivo').value;

    const gOrigen = catalogoGrados.find(g => parseInt(g.id_grado, 10) === idOrigen);
    const gDestino = catalogoGrados.find(g => parseInt(g.id_grado, 10) === idDestino);

    const checks = document.querySelectorAll('.check-estudiante:checked');
    let huellasCount = 0;
    checks.forEach(c => {
        const idEst = parseInt(c.value, 10);
        const est = estudiantesActuales.find(e => parseInt(e.id_estudiante, 10) === idEst);
        if (est && parseInt(est.tiene_huella_activa, 10) === 1) {
            huellasCount++;
        }
    });

    const migrarHuellas = document.getElementById('check-migrar-huellas').checked;

    document.getElementById('resumen-origen').textContent = gOrigen ? gOrigen.nombre : 'N/A';
    document.getElementById('resumen-destino').textContent = esGrad ? '🎓 Cohorte Egresada / Graduada' : (gDestino ? `${gDestino.nombre} (${gDestino.aula_nombre || 'Sin aula'})` : 'N/A');
    document.getElementById('resumen-anho').textContent = anho;
    document.getElementById('resumen-total-estudiantes').textContent = checks.length;
    document.getElementById('resumen-total-huellas').textContent = (!esGrad && migrarHuellas) ? huellasCount : 0;

    document.getElementById('modal-confirmacion').classList.remove('hidden');
}

function cerrarModalConfirmacion() {
    document.getElementById('modal-confirmacion').classList.add('hidden');
}

async function ejecutarPromocion() {
    const btnConfirmar = document.getElementById('btn-ejecutar-confirmado');
    const spinner = document.getElementById('spinner-ejecutar');
    const idOrigen = parseInt(document.getElementById('select-grado-origen').value, 10);
    const idDestino = parseInt(document.getElementById('select-grado-destino').value, 10);
    const esGrad = document.getElementById('check-graduacion').checked;
    const anho = parseInt(document.getElementById('input-anho-lectivo').value, 10);
    const migrarHuellas = document.getElementById('check-migrar-huellas').checked;

    const checks = document.querySelectorAll('.check-estudiante:checked');
    const idsEstudiantes = Array.from(checks).map(c => parseInt(c.value, 10));

    btnConfirmar.disabled = true;
    spinner.classList.remove('hidden');

    try {
        const payload = {
            id_grado_origen: idOrigen,
            id_grado_destino: esGrad ? null : idDestino,
            ids_estudiantes: idsEstudiantes,
            migrar_huellas: migrarHuellas,
            anho_lectivo: anho,
            es_graduacion: esGrad
        };

        const resp = await fetch('<?= base_url('src/api/Promocion/ejecutar_promocion.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const res = await resp.json();
        if (res.status !== 'success') {
            throw new Error(res.message || 'Error al ejecutar la promoción.');
        }

        cerrarModalConfirmacion();
        document.getElementById('txt-resultado-mensaje').textContent = res.message;
        document.getElementById('modal-resultado').classList.remove('hidden');

        // Recargar catálogos
        await cargarDatosIniciales();
        await cambiarGradoOrigen();

    } catch (err) {
        console.error('Error al ejecutar promoción:', err);
        alert('Error: ' + err.message);
    } finally {
        btnConfirmar.disabled = false;
        spinner.classList.add('hidden');
    }
}

function cerrarModalResultado() {
    document.getElementById('modal-resultado').classList.add('hidden');
}

function mostrarAlerta(msg, tipo = 'info') {
    alert(msg);
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
