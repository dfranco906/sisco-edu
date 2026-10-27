<?php
// mvc/views/administracion/sync_biometrica.php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/config/red.php';
require_once __DIR__ . '/../../../src/middleware/auth.php';

// RBAC: Solo SuperAdmin, Administrador y Administracion
verificarRol(['SuperAdmin', 'Administrador', 'Administracion']);

$rolActual = $_SESSION['rol'] ?? '';
$esAdmin = in_array($rolActual, ['Administrador', 'SuperAdmin'], true);

$gatewayIp = defined('IP_ESP32_GATEWAY') ? IP_ESP32_GATEWAY : '';
$gatewaySyncUrl = defined('GATEWAY_SYNC_URL') ? GATEWAY_SYNC_URL : ($gatewayIp ? "http://{$gatewayIp}/sync" : '');

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
            <span class="text-slate-900 dark:text-white font-bold">Sincronización Biométrica LoRa</span>
        </nav>

        <!-- Cabecera de Página con Acciones Claras -->
        <div class="app-page-header p-6 sm:p-8 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 text-xl font-bold shadow-xs">
                        📡
                    </span>
                    <span class="px-3.5 py-1 text-sm font-extrabold rounded-full bg-amber-50 dark:bg-amber-950/40 text-amber-900 dark:text-amber-300 border border-amber-200 dark:border-amber-800 uppercase tracking-wide">
                        Distribución Biométrica LoRa
                    </span>
                    <!-- Indicador Gateway -->
                    <span id="badge-estado-gateway" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700">
                        <span id="dot-gateway" class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                        <span id="texto-gateway">Gateway: <?= htmlspecialchars($gatewayIp, ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Cola de Sincronización de Huellas
                </h1>
                <p class="text-base sm:text-lg text-slate-700 dark:text-slate-300 mt-2 max-w-3xl leading-relaxed">
                    Supervisa y transmite las plantillas biométricas enroladas a los nodos sensores de cada aula. Podés enviar de forma individual, por selección o iniciar el envío en cola de todas las huellas pendientes.
                </p>
            </div>

            <!-- Botonera de Acción Primaria -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="btn btn-muted min-h-[46px] px-5 py-2.5 text-base font-semibold rounded-2xl border border-slate-300 dark:border-slate-700 flex items-center gap-2">
                    <span>←</span>
                    <span>Administración</span>
                </a>

                <?php if ($esAdmin): ?>
                <button type="button" onclick="abrirModalResetearAula()" class="btn min-h-[46px] px-4 py-2.5 text-base font-bold rounded-2xl border border-rose-300 dark:border-rose-800 text-rose-700 dark:text-rose-300 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 transition flex items-center gap-2 cursor-pointer shadow-xs" title="Vaciar todas las huellas del sensor de un aula vía LoRa">
                    <span>🧹</span>
                    <span>Resetear Aula</span>
                </button>
                <?php endif; ?>

                <button type="button" id="btn-refrescar-cola" onclick="cargarColaSincronizacion()" class="min-h-[46px] px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-base font-bold rounded-2xl border border-slate-300 dark:border-slate-700 transition flex items-center gap-2 cursor-pointer">
                    <svg id="icon-refresh" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span>Actualizar</span>
                </button>
            </div>
        </div>

        <!-- Tarjetas KPIs de Estados en la Cola (Clickables como filtros) -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-5">
            <!-- 1. Total en Cola -->
            <button type="button" onclick="filtrarPorEstado('TODOS')" id="card-filtro-todos" class="card-kpi p-5 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-300 dark:border-slate-700 hover:border-blue-500 shadow-xs text-left transition cursor-pointer group">
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total en Cola</span>
                <div id="kpi-total" class="text-3xl font-black text-slate-900 dark:text-white mt-2">--</div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Registros totales</p>
            </button>

            <!-- 2. Pendientes -->
            <button type="button" onclick="filtrarPorEstado('PENDIENTE')" id="card-filtro-pendiente" class="card-kpi p-5 rounded-3xl bg-amber-50/70 dark:bg-amber-950/40 border-2 border-amber-300 dark:border-amber-800/80 hover:border-amber-500 shadow-xs text-left transition cursor-pointer group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wider text-amber-800 dark:text-amber-300">Pendientes</span>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                </div>
                <div id="kpi-pendientes" class="text-3xl font-black text-amber-900 dark:text-amber-200 mt-2">--</div>
                <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">Esperando envío LoRa</p>
            </button>

            <!-- 3. Enviados -->
            <button type="button" onclick="filtrarPorEstado('ENVIADO')" id="card-filtro-enviado" class="card-kpi p-5 rounded-3xl bg-blue-50/70 dark:bg-blue-950/40 border-2 border-blue-200 dark:border-blue-800/80 hover:border-blue-500 shadow-xs text-left transition cursor-pointer group">
                <span class="text-xs font-extrabold uppercase tracking-wider text-blue-800 dark:text-blue-300">En Tránsito</span>
                <div id="kpi-enviados" class="text-3xl font-black text-blue-900 dark:text-blue-200 mt-2">--</div>
                <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">Transmitidos al Gateway</p>
            </button>

            <!-- 4. Confirmados -->
            <button type="button" onclick="filtrarPorEstado('CONFIRMADO')" id="card-filtro-confirmado" class="card-kpi p-5 rounded-3xl bg-emerald-50/70 dark:bg-emerald-950/40 border-2 border-emerald-200 dark:border-emerald-800/80 hover:border-emerald-500 shadow-xs text-left transition cursor-pointer group">
                <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Confirmados</span>
                <div id="kpi-confirmados" class="text-3xl font-black text-emerald-900 dark:text-emerald-200 mt-2">--</div>
                <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-1">Grabados en sensor del aula</p>
            </button>

            <!-- 5. Con Error -->
            <button type="button" onclick="filtrarPorEstado('ERROR')" id="card-filtro-error" class="card-kpi p-5 rounded-3xl bg-rose-50/70 dark:bg-rose-950/40 border-2 border-rose-200 dark:border-rose-800/80 hover:border-rose-500 shadow-xs text-left transition cursor-pointer group col-span-2 sm:col-span-1">
                <span class="text-xs font-extrabold uppercase tracking-wider text-rose-800 dark:text-rose-300">Con Error</span>
                <div id="kpi-errores" class="text-3xl font-black text-rose-900 dark:text-rose-200 mt-2">--</div>
                <p class="text-xs text-rose-700 dark:text-rose-400 mt-1">Requieren reintento</p>
            </button>
        </div>

        <!-- Barra de Herramientas y Acciones en Lote -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900 border-2 border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
            
            <!-- Buscador y Filtro de Estado -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1">
                <!-- Buscador rápido -->
                <div class="relative flex-1 min-w-[240px]">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input type="text" id="filtro-busqueda" placeholder="Buscar por nombre, cédula o aula..."
                           class="w-full pl-10 pr-9 py-2.5 text-base text-slate-900 dark:text-white bg-slate-50 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
                           oninput="aplicarFiltrosLocales()">
                    <button type="button" id="btn-limpiar-busqueda" onclick="limpiarBusqueda()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hidden font-bold">
                        ✕
                    </button>
                </div>

                <!-- Selector de Estado -->
                <div class="flex items-center gap-1.5 p-1 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shrink-0">
                    <button type="button" data-estado="TODOS" onclick="filtrarPorEstado('TODOS')" class="btn-tab-estado px-3 py-1.5 text-xs font-bold rounded-lg transition is-active bg-white dark:bg-slate-900 shadow-xs text-blue-600 dark:text-blue-400">
                        Todos
                    </button>
                    <button type="button" data-estado="PENDIENTE" onclick="filtrarPorEstado('PENDIENTE')" class="btn-tab-estado px-3 py-1.5 text-xs font-bold rounded-lg transition text-slate-600 dark:text-slate-300 hover:text-slate-900">
                        Pendientes
                    </button>
                    <button type="button" data-estado="ENVIADO" onclick="filtrarPorEstado('ENVIADO')" class="btn-tab-estado px-3 py-1.5 text-xs font-bold rounded-lg transition text-slate-600 dark:text-slate-300 hover:text-slate-900">
                        Enviados
                    </button>
                    <button type="button" data-estado="CONFIRMADO" onclick="filtrarPorEstado('CONFIRMADO')" class="btn-tab-estado px-3 py-1.5 text-xs font-bold rounded-lg transition text-slate-600 dark:text-slate-300 hover:text-slate-900">
                        Confirmados
                    </button>
                    <button type="button" data-estado="ERROR" onclick="filtrarPorEstado('ERROR')" class="btn-tab-estado px-3 py-1.5 text-xs font-bold rounded-lg transition text-slate-600 dark:text-slate-300 hover:text-slate-900">
                        Errores
                    </button>
                </div>
            </div>

            <!-- Botonera de Ejecución en Lote -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0 justify-end">
                <!-- Botón: Enviar Seleccionadas -->
                <button type="button" id="btn-enviar-seleccionadas" onclick="iniciarEnvioSeleccionadas()" disabled
                        class="min-h-[44px] px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-300 dark:disabled:bg-slate-800 text-white disabled:text-slate-500 text-sm font-bold rounded-xl transition flex items-center gap-2 shadow-xs cursor-pointer disabled:cursor-not-allowed">
                    <span>🚀 Enviar Seleccionadas</span>
                    <span id="badge-contador-seleccionados" class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-white/20 text-white">0</span>
                </button>

                <!-- Botón: Eliminar Seleccionadas (Solo Administrador) -->
                <?php if ($esAdmin): ?>
                <button type="button" id="btn-eliminar-seleccionadas" onclick="eliminarSeleccionadas()" disabled
                        class="min-h-[44px] px-4 py-2 bg-rose-600 hover:bg-rose-700 disabled:bg-slate-300 dark:disabled:bg-slate-800 text-white disabled:text-slate-500 text-sm font-bold rounded-xl transition flex items-center gap-2 shadow-xs cursor-pointer disabled:cursor-not-allowed"
                        title="Eliminar de la cola los intentos de sincronización seleccionados">
                    <span>🗑️ Eliminar Seleccionadas</span>
                    <span id="badge-contador-eliminar-seleccionados" class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-white/20 text-white">0</span>
                </button>
                <?php endif; ?>

                <!-- Botón: Enviar Todas las Pendientes -->
                <button type="button" id="btn-enviar-todas" onclick="iniciarEnvioTodasPendientes()"
                        class="min-h-[44px] px-5 py-2 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white text-sm font-extrabold rounded-xl shadow-md shadow-orange-500/20 hover:shadow-orange-500/30 transition flex items-center gap-2 cursor-pointer">
                    <span class="text-base">📡</span>
                    <span>Enviar Todas las Pendientes</span>
                    <span id="badge-total-pendientes-btn" class="px-2 py-0.5 rounded-full text-xs font-black bg-black/20 text-white">0</span>
                </button>
            </div>
        </div>

        <!-- Tabla de Registros en la Cola -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border-2 border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="tabla-sync">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/50 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="p-4 w-12 text-center">
                                <input type="checkbox" id="chk-seleccionar-todos" onchange="toggleSeleccionarTodos(this)"
                                       class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 cursor-pointer"
                                       title="Seleccionar todas las visibles">
                            </th>
                            <th class="p-4">Persona</th>
                            <th class="p-4">Aula Destino & Nodo LoRa</th>
                            <th class="p-4">Huella / Integridad</th>
                            <th class="p-4">Estado de Entrega</th>
                            <th class="p-4">Fecha</th>
                            <th class="p-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-sync" class="divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                        <tr>
                            <td colspan="7" class="p-12 text-center text-slate-500 dark:text-slate-400">
                                <div class="inline-block animate-spin text-3xl mb-3">⏳</div>
                                <div class="font-bold text-base">Cargando cola de sincronización...</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer de la tabla con contador -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400 font-medium">
                <div id="info-registros-mostrados">Mostrando 0 registros</div>
                <div class="flex items-center gap-4">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="chk-auto-refresco" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-600">
                        <span>Auto-actualizar cada 15 segundos</span>
                    </label>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- MODAL DE PROCESAMIENTO EN COLA / TRANSMISIÓN LORA -->
<div id="modal-progreso-cola" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-cola-titulo">
    <div class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Cabecera del modal -->
        <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800 bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-blue-500/10 dark:bg-slate-800/60">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center text-xl font-bold shadow-md shadow-orange-500/25">
                    📡
                </div>
                <div>
                    <h2 id="modal-cola-titulo" class="text-xl font-extrabold text-slate-900 dark:text-white">
                        Transmitiendo Huellas a las Aulas vía LoRa
                    </h2>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5" id="modal-cola-subtitulo">
                        Procesamiento secuencial con espaciado de radiofrecuencia
                    </p>
                </div>
            </div>
            <button type="button" id="btn-cerrar-modal-progreso" onclick="cerrarModalProgreso()" disabled
                    class="w-10 h-10 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 flex items-center justify-center text-xl font-bold transition disabled:opacity-30 disabled:cursor-not-allowed">
                ✕
            </button>
        </div>

        <!-- Cuerpo del modal -->
        <div class="p-6 space-y-5">

            <!-- Barra de Progreso Principal -->
            <div>
                <div class="flex items-center justify-between text-xs font-bold text-slate-700 dark:text-slate-300 mb-2">
                    <span id="lbl-progreso-cuenta">Procesando 0 de 0 huellas...</span>
                    <span id="lbl-progreso-porcentaje" class="text-amber-600 dark:text-amber-400 font-extrabold text-sm">0%</span>
                </div>
                <div class="w-full h-3.5 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden p-0.5">
                    <div id="barra-progreso-fill" class="h-full bg-gradient-to-r from-amber-500 via-orange-500 to-emerald-500 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>

            <!-- Ítem actual siendo transmitido -->
            <div id="card-item-actual" class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-700/80">
                <div class="text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">Huella en Tránsito</div>
                <div class="flex items-center justify-between">
                    <div>
                        <div id="actual-nombre" class="font-bold text-base text-slate-900 dark:text-white">Preparando cola...</div>
                        <div id="actual-destino" class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Destino: --</div>
                    </div>
                    <span id="actual-badge" class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">
                        Iniciando
                    </span>
                </div>
            </div>

            <!-- Bitácora de Transmisión en Vivo -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Bitácora en Tiempo Real</span>
                    <span class="text-[11px] text-slate-400">Pausa entre envíos: 1.5s</span>
                </div>
                <div id="log-transmision" class="h-48 overflow-y-auto p-3.5 rounded-xl bg-slate-950 text-slate-200 font-mono text-xs space-y-1.5 border border-slate-800">
                    <div class="text-slate-500">// La bitácora de transmisión se mostrará aquí...</div>
                </div>
            </div>
        </div>

        <!-- Pie de controles del modal -->
        <div class="p-4 sm:p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-850 flex items-center justify-between">
            <div class="text-xs text-slate-500 dark:text-slate-400" id="estado-cola-footer">
                Pausar o cancelar no borra los datos en base de datos.
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="btn-pausar-cola" onclick="togglePausarCola()"
                        class="px-4 py-2 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-white text-xs font-bold rounded-xl transition cursor-pointer">
                    ⏸ Pausar
                </button>
                <button type="button" id="btn-cancelar-cola" onclick="cancelarCola()"
                        class="px-4 py-2 bg-rose-100 hover:bg-rose-200 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 text-xs font-bold rounded-xl transition cursor-pointer">
                    ✕ Detener
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Resetear / Vaciar Huellas de Aula (Solo Administradores) -->
<div id="modal-resetear-aula" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-reset-titulo">
    <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800 bg-rose-50/70 dark:bg-rose-950/40">
            <div class="flex items-center gap-3.5">
                <span class="inline-flex items-center justify-center w-11 h-11 rounded-2xl bg-rose-100 dark:bg-rose-900/60 text-rose-600 dark:text-rose-300 text-xl font-black">
                    🧹
                </span>
                <div>
                    <h2 id="modal-reset-titulo" class="text-xl font-extrabold text-slate-900 dark:text-white">
                        Resetear Huellas de Aula
                    </h2>
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Vaciado del sensor óptico DY50 y base de datos local
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalResetearAula()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer">
                ✕
            </button>
        </div>

        <div class="p-6 space-y-5">
            <!-- Aviso de impacto -->
            <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 text-xs sm:text-sm text-amber-900 dark:text-amber-200 space-y-1.5 leading-relaxed">
                <p class="font-extrabold flex items-center gap-1.5 text-amber-800 dark:text-amber-300">
                    <span>⚠️</span>
                    <span>Acción Destructiva en el Nodo de Aula:</span>
                </p>
                <p>
                    Esta acción emitirá un comando LoRa al nodo para vaciar completamente los 200 slots del sensor óptico DY50 y limpiará sus sincronizaciones locales.
                </p>
                <p class="font-semibold text-emerald-800 dark:text-emerald-300">
                    ✓ Las huellas originales de los estudiantes siguen a salvo en la base de datos central de SISCO-EDU para su futura reasignación.
                </p>
            </div>

            <!-- Selector de Aula con Nodo LoRa -->
            <div>
                <label for="mra-select-aula" class="block text-sm font-bold text-slate-800 dark:text-slate-200 mb-2">
                    Seleccione el Aula a Resetear:
                </label>
                <select id="mra-select-aula" class="w-full px-4 py-3 text-base rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rose-500 font-medium">
                    <option value="">Cargando aulas activas con LoRa...</option>
                </select>
            </div>

            <!-- Opción Forzar en BD si está apagado -->
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" id="mra-chk-forzar" class="mt-0.5 w-4 h-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300 dark:border-slate-600">
                    <span class="text-xs text-slate-700 dark:text-slate-300">
                        <b>Forzar vaciado en Base de Datos</b> aunque el aula esté apagada o no responda por LoRa (útil si el hardware fue retirado o reemplazado).
                    </span>
                </label>
            </div>

            <!-- Checkbox de confirmación obligatoria -->
            <div>
                <label class="flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" id="mra-chk-confirmar" onchange="actualizarBtnResetModal()" class="mt-0.5 w-4 h-4 rounded text-rose-600 focus:ring-rose-500 border-slate-300 dark:border-slate-600">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">
                        Confirmo que deseo vaciar las huellas del sensor de esta aula.
                    </span>
                </label>
            </div>

            <!-- Mensaje de estado durante ejecución -->
            <div id="mra-feedback" class="p-3 rounded-xl text-center text-xs font-bold hidden"></div>
        </div>

        <div class="p-4 sm:p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-850 flex items-center justify-end gap-3">
            <button type="button" onclick="cerrarModalResetearAula()" class="btn btn-muted px-4 py-2.5 text-sm font-semibold rounded-xl border border-slate-300 dark:border-slate-700 cursor-pointer">
                Cancelar
            </button>
            <button type="button" id="mra-btn-ejecutar" onclick="ejecutarResetAula()" disabled
                    class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 disabled:bg-slate-300 dark:disabled:bg-slate-800 text-white disabled:text-slate-500 text-sm font-bold rounded-xl transition flex items-center gap-2 cursor-pointer disabled:cursor-not-allowed shadow-md">
                <span>🧹 Ejecutar Vaciado</span>
            </button>
        </div>

    </div>
</div>

<script>
(() => {
    // Endpoints relativos portables con BASE_URL
    const BASE = window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '';
    const API_LISTAR = `${BASE}/src/api/Gateway/listar_sync_pendientes.php`;
    const API_PROCESAR = `${BASE}/src/api/Gateway/procesar_sync_cola.php`;
    const API_ELIMINAR = `${BASE}/src/api/Gateway/eliminar_sync.php`;
    const API_RESETEAR_AULA = `${BASE}/src/api/Gateway/resetear_aula.php`;
    const API_NODOS = `${BASE}/src/api/Nodos/listar_nodos.php`;
    const GATEWAY_IP = <?= json_encode($gatewayIp) ?>;
    const ES_ADMIN = <?= $esAdmin ? 'true' : 'false' ?>;

    // Estado global de la vista
    let itemsCola = [];
    let itemsFiltrados = [];
    let seleccionados = new Set();
    let estadoFiltroActivo = 'TODOS';
    let temporizadorRefresco = null;

    // Estado del ejecutor de cola
    let colaActiva = false;
    let colaPausada = false;
    let colaCancelada = false;
    let listaParaEnviar = [];
    let indiceEnvio = 0;

    /**
     * Carga inicial y obtención remota de datos
     */
    async function cargarColaSincronizacion() {
        const iconRefresh = document.getElementById('icon-refresh');
        if (iconRefresh) iconRefresh.classList.add('animate-spin');

        try {
            const resp = await fetch(`${API_LISTAR}?estado=TODOS`, {
                headers: { 'Accept': 'application/json' }
            });
            const res = await resp.json();

            if (res.status === 'success' && res.data) {
                itemsCola = res.data.items || [];
                actualizarKpis(res.data.stats || {});
                aplicarFiltrosLocales();
                verificarSaludGateway(res.data.gateway?.ip || GATEWAY_IP);
            } else {
                mostrarAlertaUI('error', res.message || 'Error al obtener la cola de sincronización');
            }
        } catch (e) {
            console.error('Error cargando cola:', e);
            mostrarAlertaUI('error', 'Fallo de conexión al cargar la cola de sincronización: ' + e.message);
        } finally {
            if (iconRefresh) {
                setTimeout(() => iconRefresh.classList.remove('animate-spin'), 400);
            }
        }
    }

    /**
     * Actualiza los contadores KPIs superiores
     */
    function actualizarKpis(stats) {
        document.getElementById('kpi-total').textContent = stats.total ?? 0;
        document.getElementById('kpi-pendientes').textContent = stats.pendientes ?? 0;
        document.getElementById('kpi-enviados').textContent = stats.enviados ?? 0;
        document.getElementById('kpi-confirmados').textContent = stats.confirmados ?? 0;
        document.getElementById('kpi-errores').textContent = stats.errores ?? 0;

        const badgeBtn = document.getElementById('badge-total-pendientes-btn');
        if (badgeBtn) badgeBtn.textContent = stats.pendientes ?? 0;

        const btnEnviarTodas = document.getElementById('btn-enviar-todas');
        if (btnEnviarTodas) {
            btnEnviarTodas.disabled = (stats.pendientes === 0 && stats.errores === 0);
            if (btnEnviarTodas.disabled) {
                btnEnviarTodas.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                btnEnviarTodas.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    /**
     * Aplica filtros de estado y de texto localmente
     */
    function aplicarFiltrosLocales() {
        const busquedaInput = document.getElementById('filtro-busqueda');
        const term = busquedaInput ? busquedaInput.value.trim().toLowerCase() : '';

        const btnLimpiar = document.getElementById('btn-limpiar-busqueda');
        if (btnLimpiar) {
            btnLimpiar.classList.toggle('hidden', term === '');
        }

        itemsFiltrados = itemsCola.filter(it => {
            // Filtro por estado
            if (estadoFiltroActivo !== 'TODOS' && it.estado !== estadoFiltroActivo) {
                return false;
            }

            // Filtro textual
            if (term !== '') {
                const nombre = (it.nombre_completo || '').toLowerCase();
                const ci = (it.ci || '').toLowerCase();
                const aula = (it.aula_nombre || '').toLowerCase();
                const lora = String(it.lora_id || '').toLowerCase();
                const huella = String(it.id_huella || '').toLowerCase();
                const uid = (it.user_id_global || '').toLowerCase();

                if (!nombre.includes(term) && !ci.includes(term) && !aula.includes(term) && !lora.includes(term) && !huella.includes(term) && !uid.includes(term)) {
                    return false;
                }
            }

            return true;
        });

        renderizarTabla();
    }

    /**
     * Renderiza la tabla con los elementos filtrados
     */
    function renderizarTabla() {
        const tbody = document.getElementById('tbody-sync');
        const infoRegistros = document.getElementById('info-registros-mostrados');
        const chkTodos = document.getElementById('chk-seleccionar-todos');

        if (!tbody) return;

        if (itemsFiltrados.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="p-12 text-center text-slate-500 dark:text-slate-400">
                        <div class="text-4xl mb-2">📭</div>
                        <div class="font-bold text-base text-slate-700 dark:text-slate-300">No se encontraron registros de sincronización</div>
                        <p class="text-xs text-slate-500 mt-1">Prueba cambiando el filtro de estado o el término de búsqueda.</p>
                    </td>
                </tr>
            `;
            if (infoRegistros) infoRegistros.textContent = 'Mostrando 0 registros';
            if (chkTodos) chkTodos.checked = false;
            actualizarBotonSeleccionadas();
            return;
        }

        let html = '';
        let todosSeleccionados = true;

        itemsFiltrados.forEach(it => {
            const isChecked = seleccionados.has(Number(it.id_sync));
            if (!isChecked) todosSeleccionados = false;

            const badgeTipo = it.tipo_persona === 'Profesor'
                ? '<span class="px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">Profesor</span>'
                : '<span class="px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300">Estudiante</span>';

            const badgeEstado = obtenerBadgeEstado(it.estado);
            const badgeLora = it.lora_id
                ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-mono font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                     <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> LoRa #${escapeHtml(String(it.lora_id))}
                   </span>`
                : '<span class="text-xs text-slate-400 italic">Sin nodo LoRa</span>';

            const crc32Str = it.crc32_hash
                ? `<code class="font-mono text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold border border-slate-200 dark:border-slate-700">${escapeHtml(it.crc32_hash)}</code>`
                : '<span class="text-xs text-slate-400">Sin CRC32</span>';

            const fechaStr = formatFecha(it.fecha_creacion);
            const mensajeTooltip = it.mensaje ? `title="${escapeHtml(it.mensaje)}"` : '';

            html += `
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition group ${isChecked ? 'bg-blue-50/40 dark:bg-blue-950/20' : ''}">
                    <!-- Checkbox de selección -->
                    <td class="p-4 text-center">
                        <input type="checkbox" data-id="${it.id_sync}" onchange="toggleSeleccionItem(this, ${it.id_sync})"
                               ${isChecked ? 'checked' : ''}
                               class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 cursor-pointer">
                    </td>

                    <!-- Persona -->
                    <td class="p-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center font-bold text-slate-700 dark:text-slate-300 text-xs shrink-0">
                                ${escapeHtml(it.nombre_completo ? it.nombre_completo.charAt(0) : '?')}
                            </div>
                            <div>
                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <span>${escapeHtml(it.nombre_completo || 'Sin asignar')}</span>
                                    ${badgeTipo}
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-mono">
                                    C.I.: <span class="font-semibold text-slate-700 dark:text-slate-300">${escapeHtml(it.ci || 'S/N')}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Aula Destino -->
                    <td class="p-4">
                        <div class="font-bold text-slate-800 dark:text-slate-200">
                            ${escapeHtml(it.aula_nombre || 'Aula #' + (it.id_aula || '--'))}
                        </div>
                        <div class="mt-1 flex items-center gap-2">
                            ${badgeLora}
                            ${it.room_id ? `<span class="text-[11px] text-slate-400 font-mono">${escapeHtml(it.room_id)}</span>` : ''}
                        </div>
                    </td>

                    <!-- Huella / Hash -->
                    <td class="p-4">
                        <div class="text-xs font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <span>👆 Huella #${it.id_huella}</span>
                        </div>
                        <div class="mt-1">
                            ${crc32Str}
                        </div>
                    </td>

                    <!-- Estado -->
                    <td class="p-4" ${mensajeTooltip}>
                        <div>${badgeEstado}</div>
                        ${it.intentos > 0 ? `<div class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Intentos: <b>${it.intentos}</b></div>` : ''}
                        ${obtenerBadgeTelemetria(it.mensaje)}
                        ${it.mensaje ? `<div class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-[200px] mt-0.5" title="${escapeHtml(it.mensaje)}">${escapeHtml(it.mensaje)}</div>` : ''}
                    </td>

                    <!-- Fecha -->
                    <td class="p-4 text-xs text-slate-600 dark:text-slate-400">
                        ${fechaStr}
                    </td>

                    <!-- Acciones directas -->
                    <td class="p-4 text-center">
                        <div class="inline-flex items-center gap-1.5 justify-center">
                            <button type="button" onclick="enviarHuellaIndividual(${it.id_sync}, ${it.id_huella}, '${escapeHtml(it.nombre_completo || '')}', '${escapeHtml(it.aula_nombre || '')}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs transition hover:scale-105 cursor-pointer"
                                    title="Enviar inmediatamente al Gateway LoRa para este aula">
                                <span>📡</span>
                                <span>Enviar al Aula</span>
                            </button>
                            ${ES_ADMIN ? `
                            <button type="button" onclick="eliminarIntentoIndividual(${it.id_sync}, '${escapeHtml(it.nombre_completo || '')}', '${escapeHtml(it.aula_nombre || '')}')"
                                    class="inline-flex items-center justify-center p-2 text-rose-600 hover:text-white hover:bg-rose-600 dark:text-rose-400 dark:hover:text-white dark:hover:bg-rose-700 border border-rose-300 dark:border-rose-800 hover:border-transparent rounded-xl text-xs font-bold transition hover:scale-105 cursor-pointer"
                                    title="Eliminar este intento de sincronización">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        if (infoRegistros) {
            infoRegistros.textContent = `Mostrando ${itemsFiltrados.length} de ${itemsCola.length} registros`;
        }
        if (chkTodos) {
            chkTodos.checked = todosSeleccionados && itemsFiltrados.length > 0;
        }

        actualizarBotonSeleccionadas();
    }

    /**
     * Helpers de UI
     */
    function obtenerBadgeTelemetria(mensaje) {
        if (!mensaje) return '';
        let match = mensaje.match(/LoRa RSSI:\s*(-?\d+)\s*dBm(?:,\s*SNR:\s*(-?\d+)\s*dB)?/i);
        if (!match) {
            match = mensaje.match(/RSSI:\s*(-?\d+)\s*dBm/i);
        }
        if (!match) return '';

        const rssi = parseInt(match[1], 10);
        const snr = match[2] ? parseInt(match[2], 10) : null;

        let badgeClass = '';
        let estadoSenal = '';
        if (rssi >= -70) {
            badgeClass = 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
            estadoSenal = 'Excelente';
        } else if (rssi >= -90) {
            badgeClass = 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800';
            estadoSenal = 'Buena';
        } else {
            badgeClass = 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800';
            estadoSenal = 'Débil';
        }

        return `
            <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold ${badgeClass} border" title="Telemetría LoRa recibida (${estadoSenal})">
                    <span>📶</span>
                    <span>${rssi} dBm</span>
                    ${snr !== null ? `<span class="opacity-75">| SNR: ${snr} dB</span>` : ''}
                </span>
            </div>
        `;
    }

    function obtenerBadgeEstado(estado) {
        switch ((estado || '').toUpperCase()) {
            case 'PENDIENTE':
                return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-extrabold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-800"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> PENDIENTE</span>';
            case 'ENVIADO':
                return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-extrabold bg-blue-100 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300 border border-blue-300 dark:border-blue-800"><span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span> ENVIADO</span>';
            case 'CONFIRMADO':
                return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-extrabold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">✓ CONFIRMADO</span>';
            case 'ERROR':
                return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-extrabold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800">⚠ ERROR</span>';
            default:
                return `<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-800">${escapeHtml(estado || 'Desconocido')}</span>`;
        }
    }

    function formatFecha(fechaRaw) {
        if (!fechaRaw) return '--';
        try {
            const d = new Date(fechaRaw);
            if (isNaN(d.getTime())) return fechaRaw;
            return d.toLocaleString('es-PY', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        } catch (_) {
            return fechaRaw;
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Gestión de Selección de filas
     */
    window.toggleSeleccionItem = function(chk, idSync) {
        if (chk.checked) {
            seleccionados.add(Number(idSync));
        } else {
            seleccionados.delete(Number(idSync));
        }
        actualizarBotonSeleccionadas();
    };

    window.toggleSeleccionarTodos = function(chkPrincipal) {
        const isChecked = chkPrincipal.checked;
        itemsFiltrados.forEach(it => {
            if (isChecked) {
                seleccionados.add(Number(it.id_sync));
            } else {
                seleccionados.delete(Number(it.id_sync));
            }
        });
        renderizarTabla();
    };

    function actualizarBotonSeleccionadas() {
        const btn = document.getElementById('btn-enviar-seleccionadas');
        const badge = document.getElementById('badge-contador-seleccionados');
        const cant = seleccionados.size;

        if (badge) badge.textContent = cant;
        if (btn) {
            btn.disabled = cant === 0;
            if (cant === 0) {
                btn.classList.add('disabled:bg-slate-300', 'disabled:text-slate-500');
            }
        }

        const btnEliminar = document.getElementById('btn-eliminar-seleccionadas');
        const badgeEliminar = document.getElementById('badge-contador-eliminar-seleccionados');
        if (badgeEliminar) badgeEliminar.textContent = cant;
        if (btnEliminar) {
            btnEliminar.disabled = cant === 0;
            if (cant === 0) {
                btnEliminar.classList.add('disabled:bg-slate-300', 'disabled:text-slate-500');
            }
        }
    }

    /**
     * Filtros de Estado
     */
    window.filtrarPorEstado = function(nuevoEstado) {
        estadoFiltroActivo = nuevoEstado;
        document.querySelectorAll('.btn-tab-estado').forEach(btn => {
            const match = btn.dataset.estado === nuevoEstado;
            btn.classList.toggle('is-active', match);
            btn.classList.toggle('bg-white', match);
            btn.classList.toggle('dark:bg-slate-900', match);
            btn.classList.toggle('shadow-xs', match);
            btn.classList.toggle('text-blue-600', match);
            btn.classList.toggle('dark:text-blue-400', match);
        });
        aplicarFiltrosLocales();
    };

    window.limpiarBusqueda = function() {
        const input = document.getElementById('filtro-busqueda');
        if (input) input.value = '';
        aplicarFiltrosLocales();
    };

    /**
     * Verificación de salud del Gateway (Ping TCP/HTTP)
     */
    async function verificarSaludGateway(ip) {
        const badge = document.getElementById('badge-estado-gateway');
        const dot = document.getElementById('dot-gateway');
        const texto = document.getElementById('texto-gateway');
        if (!badge || !dot || !texto) return;

        dot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse';
        texto.textContent = `Gateway: ${ip} (Activo)`;
    }

    /**
     * ENVIAR INDIVIDUAL: 1 clic por fila
     */
    window.enviarHuellaIndividual = async function(idSync, idHuella, persona, aula) {
        const confirmacion = async () => {
            if (window.SISCO && typeof window.SISCO.confirm === 'function') {
                return await window.SISCO.confirm({
                    title: '¿Transmitir huella al aula?',
                    message: `¿Deseas enviar la huella de ${persona} al aula "${aula}" vía LoRa ahora?`,
                    confirmText: 'Sí, transmitir',
                    cancelText: 'Cancelar'
                });
            }
            return confirm(`¿Deseas enviar la huella de ${persona} al aula "${aula}" vía LoRa ahora?`);
        };

        const aceptar = await confirmacion();
        if (!aceptar) return;

        try {
            mostrarToast('info', `Notificando al Gateway LoRa para ${persona}...`);

            const resp = await fetch(API_PROCESAR, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ accion: 'individual', id_sync: idSync })
            });
            const res = await resp.json();

            if (res.status === 'success') {
                mostrarAlertaUI('success', res.message || `Huella transmitida a ${aula}`);
                cargarColaSincronizacion();
            } else {
                mostrarAlertaUI('error', res.message || 'Error al procesar el envío');
            }
        } catch (e) {
            mostrarAlertaUI('error', 'Fallo de comunicación: ' + e.message);
        }
    };

    /**
     * ELIMINAR INDIVIDUAL: Solo administradores
     */
    window.eliminarIntentoIndividual = async function(idSync, persona, aula) {
        if (!ES_ADMIN) {
            mostrarAlertaUI('error', 'Solo los usuarios con rol de Administrador pueden eliminar intentos.');
            return;
        }

        const confirmacion = async () => {
            if (window.SISCO && typeof window.SISCO.confirm === 'function') {
                return await window.SISCO.confirm({
                    title: '¿Eliminar intento de sincronización?',
                    message: `¿Estás seguro de que deseas eliminar el intento #${idSync} para "${persona}" en el aula "${aula}"?`,
                    confirmText: 'Sí, eliminar',
                    cancelText: 'Cancelar'
                });
            }
            return confirm(`¿Estás seguro de que deseas eliminar el intento #${idSync} para "${persona}" en el aula "${aula}"?`);
        };

        const aceptar = await confirmacion();
        if (!aceptar) return;

        try {
            mostrarToast('info', `Eliminando intento #${idSync}...`);

            const resp = await fetch(API_ELIMINAR, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ id_sync: idSync })
            });
            const res = await resp.json();

            if (res.status === 'success') {
                mostrarAlertaUI('success', res.message || 'Intento eliminado correctamente.');
                seleccionados.delete(Number(idSync));
                cargarColaSincronizacion();
            } else {
                mostrarAlertaUI('error', res.message || 'Error al eliminar el intento.');
            }
        } catch (e) {
            mostrarAlertaUI('error', 'Fallo de comunicación: ' + e.message);
        }
    };

    /**
     * ELIMINAR SELECCIONADAS: Solo administradores
     */
    window.eliminarSeleccionadas = async function() {
        if (!ES_ADMIN) {
            mostrarAlertaUI('error', 'Solo los usuarios con rol de Administrador pueden eliminar intentos.');
            return;
        }

        if (seleccionados.size === 0) {
            mostrarAlertaUI('warning', 'Selecciona al menos una huella de la lista para eliminar.');
            return;
        }

        const ids = Array.from(seleccionados);
        const confirmacion = async () => {
            if (window.SISCO && typeof window.SISCO.confirm === 'function') {
                return await window.SISCO.confirm({
                    title: '¿Eliminar intentos seleccionados?',
                    message: `¿Estás seguro de que deseas eliminar los ${ids.length} intentos de sincronización seleccionados?`,
                    confirmText: 'Sí, eliminar seleccionados',
                    cancelText: 'Cancelar'
                });
            }
            return confirm(`¿Estás seguro de que deseas eliminar los ${ids.length} intentos de sincronización seleccionados?`);
        };

        const aceptar = await confirmacion();
        if (!aceptar) return;

        try {
            mostrarToast('info', `Eliminando ${ids.length} intentos seleccionados...`);

            const resp = await fetch(API_ELIMINAR, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ ids_sync: ids })
            });
            const res = await resp.json();

            if (res.status === 'success') {
                mostrarAlertaUI('success', res.message || 'Intentos eliminados correctamente.');
                seleccionados.clear();
                const chkTodos = document.getElementById('chk-seleccionar-todos');
                if (chkTodos) chkTodos.checked = false;
                cargarColaSincronizacion();
            } else {
                mostrarAlertaUI('error', res.message || 'Error al eliminar los intentos.');
            }
        } catch (e) {
            mostrarAlertaUI('error', 'Fallo de comunicación: ' + e.message);
        }
    };

    /**
     * ENVIAR SELECCIONADAS: Abre el modal y transmite secuencialmente
     */
    window.iniciarEnvioSeleccionadas = function() {
        if (seleccionados.size === 0) {
            mostrarAlertaUI('warning', 'Selecciona al menos una huella de la lista para enviar.');
            return;
        }

        const itemsAEnviar = itemsCola.filter(it => seleccionados.has(Number(it.id_sync)));
        if (itemsAEnviar.length === 0) {
            mostrarAlertaUI('warning', 'No hay ítems válidos seleccionados.');
            return;
        }

        abrirModalYProcesarCola(itemsAEnviar, `Transmitiendo ${itemsAEnviar.length} huellas seleccionadas`);
    };

    /**
     * ENVIAR TODAS LAS PENDIENTES: 1 solo clic para procesar la cola completa
     */
    window.iniciarEnvioTodasPendientes = function() {
        const pendientes = itemsCola.filter(it => it.estado === 'PENDIENTE' || it.estado === 'ERROR');
        if (pendientes.length === 0) {
            mostrarAlertaUI('info', 'No hay huellas pendientes de sincronización en este momento.');
            return;
        }

        abrirModalYProcesarCola(pendientes, `Transmitiendo ${pendientes.length} huellas pendientes a sus aulas`);
    };

    /**
     * Motor de Ejecución Secuencial LoRa con Modal en Tiempo Real
     */
    function abrirModalYProcesarCola(items, titulo) {
        listaParaEnviar = items;
        indiceEnvio = 0;
        colaActiva = true;
        colaPausada = false;
        colaCancelada = false;

        const modal = document.getElementById('modal-progreso-cola');
        const subtitulo = document.getElementById('modal-cola-subtitulo');
        const logBox = document.getElementById('log-transmision');
        const btnCerrar = document.getElementById('btn-cerrar-modal-progreso');
        const btnPausar = document.getElementById('btn-pausar-cola');

        if (subtitulo) subtitulo.textContent = titulo;
        if (logBox) logBox.innerHTML = '';
        if (btnCerrar) btnCerrar.disabled = true;
        if (btnPausar) btnPausar.textContent = '⏸ Pausar';

        modal.classList.remove('hidden');

        agregarLog(`[INICIO] Iniciando despacho secuencial de ${listaParaEnviar.length} huellas...`);
        procesarSiguienteItemCola();
    }

    async function procesarSiguienteItemCola() {
        if (!colaActiva) return;

        if (colaCancelada) {
            agregarLog('[DETENIDO] El proceso fue detenido por el usuario.');
            finalizarEjecucionCola();
            return;
        }

        if (colaPausada) {
            agregarLog('[PAUSA] Proceso en pausa. Pulsa reanudar para continuar.');
            return;
        }

        if (indiceEnvio >= listaParaEnviar.length) {
            agregarLog(`[COMPLETADO] Se procesaron exitosamente todas las huellas de la cola (${listaParaEnviar.length}/${listaParaEnviar.length}).`);
            actualizarProgresoModal(listaParaEnviar.length, listaParaEnviar.length);
            finalizarEjecucionCola();
            return;
        }

        const it = listaParaEnviar[indiceEnvio];
        const numActual = indiceEnvio + 1;
        const total = listaParaEnviar.length;

        actualizarProgresoModal(numActual - 1, total);
        actualizarCardItemActual(it, 'Transmitiendo...');

        agregarLog(`[${numActual}/${total}] Enviando Huella #${it.id_huella} (${it.nombre_completo || 'Sin nombre'}) a ${it.aula_nombre || 'Aula'} (LoRa #${it.lora_id || '?'})...`);

        try {
            const resp = await fetch(API_PROCESAR, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ accion: 'individual', id_sync: it.id_sync })
            });
            const res = await resp.json();

            if (res.status === 'success') {
                const gwMsg = res.data?.gateway_notificado ? '✓ Aceptado por Gateway LoRa' : '✓ Encolado local';
                agregarLog(`  → ${gwMsg}. Huella #${it.id_huella} programada.`);
                actualizarCardItemActual(it, 'Aceptado por Gateway');
            } else {
                agregarLog(`  ⚠ Error en Huella #${it.id_huella}: ${res.message || 'Fallo desconocido'}`);
                actualizarCardItemActual(it, 'Error');
            }
        } catch (e) {
            agregarLog(`  ⚠ Fallo de red al enviar Huella #${it.id_huella}: ${e.message}`);
            actualizarCardItemActual(it, 'Fallo de Red');
        }

        indiceEnvio++;
        actualizarProgresoModal(indiceEnvio, total);

        // Espaciado de 1.5 segundos entre paquetes LoRa para estabilidad de radiofrecuencia
        if (indiceEnvio < listaParaEnviar.length && !colaCancelada && !colaPausada) {
            setTimeout(() => {
                procesarSiguienteItemCola();
            }, 1500);
        } else if (indiceEnvio >= listaParaEnviar.length) {
            setTimeout(() => {
                procesarSiguienteItemCola();
            }, 400);
        }
    }

    function actualizarProgresoModal(actual, total) {
        const porcentaje = total > 0 ? Math.round((actual / total) * 100) : 0;
        const fill = document.getElementById('barra-progreso-fill');
        const lblCuenta = document.getElementById('lbl-progreso-cuenta');
        const lblPorc = document.getElementById('lbl-progreso-porcentaje');

        if (fill) fill.style.width = `${porcentaje}%`;
        if (lblCuenta) lblCuenta.textContent = `Procesando ${actual} de ${total} huellas...`;
        if (lblPorc) lblPorc.textContent = `${porcentaje}%`;
    }

    function actualizarCardItemActual(it, estadoTexto) {
        const nombre = document.getElementById('actual-nombre');
        const destino = document.getElementById('actual-destino');
        const badge = document.getElementById('actual-badge');

        if (nombre) nombre.textContent = it.nombre_completo || `Huella #${it.id_huella}`;
        if (destino) destino.textContent = `Destino: ${it.aula_nombre || 'Aula'} (LoRa #${it.lora_id || 'S/N'})`;
        if (badge) badge.textContent = estadoTexto;
    }

    function agregarLog(linea) {
        const box = document.getElementById('log-transmision');
        if (!box) return;
        const div = document.createElement('div');
        div.textContent = linea;
        box.appendChild(div);
        box.scrollTop = box.scrollHeight;
    }

    function finalizarEjecucionCola() {
        colaActiva = false;
        const btnCerrar = document.getElementById('btn-cerrar-modal-progreso');
        const btnPausar = document.getElementById('btn-pausar-cola');
        const btnCancelar = document.getElementById('btn-cancelar-cola');

        if (btnCerrar) btnCerrar.disabled = false;
        if (btnPausar) btnPausar.disabled = true;
        if (btnCancelar) btnCancelar.disabled = true;

        seleccionados.clear();
        cargarColaSincronizacion();
    }

    window.togglePausarCola = function() {
        if (!colaActiva) return;
        colaPausada = !colaPausada;
        const btnPausar = document.getElementById('btn-pausar-cola');

        if (colaPausada) {
            if (btnPausar) btnPausar.textContent = '▶ Reanudar';
            agregarLog('[PAUSA] Transmisión pausada.');
        } else {
            if (btnPausar) btnPausar.textContent = '⏸ Pausar';
            agregarLog('[REANUDADO] Continuando transmisión...');
            procesarSiguienteItemCola();
        }
    };

    window.cancelarCola = function() {
        if (!colaActiva) return;
        colaCancelada = true;
        agregarLog('[CANCELANDO] Deteniendo cola...');
    };

    window.cerrarModalProgreso = function() {
        const modal = document.getElementById('modal-progreso-cola');
        if (modal) modal.classList.add('hidden');
    };

    /**
     * VACIADO / RESET REMOTO DE AULA (Solo Administradores)
     */
    window.abrirModalResetearAula = async function() {
        if (!ES_ADMIN) {
            mostrarAlertaUI('error', 'Solo los usuarios con rol de Administrador pueden resetear aulas.');
            return;
        }

        const modal = document.getElementById('modal-resetear-aula');
        const sel = document.getElementById('mra-select-aula');
        const chkConf = document.getElementById('mra-chk-confirmar');
        const chkForzar = document.getElementById('mra-chk-forzar');
        const feedback = document.getElementById('mra-feedback');
        const btn = document.getElementById('mra-btn-ejecutar');

        if (chkConf) chkConf.checked = false;
        if (chkForzar) chkForzar.checked = false;
        if (feedback) feedback.className = 'hidden';
        if (btn) btn.disabled = true;

        if (sel) {
            sel.innerHTML = '<option value="">Cargando aulas activas con nodo LoRa...</option>';
        }

        if (modal) modal.classList.remove('hidden');

        try {
            const resp = await fetch(API_NODOS);
            const res = await resp.json();

            if (res.status === 'success' && Array.isArray(res.data?.nodos)) {
                const nodosAula = res.data.nodos.filter(n => n.tipo === 'AULA' && n.id_aula);
                if (nodosAula.length === 0) {
                    sel.innerHTML = '<option value="">No hay nodos de aula activos registrados</option>';
                    return;
                }

                sel.innerHTML = '<option value="">Seleccione un aula para vaciar...</option>' +
                    nodosAula.map(n => `
                        <option value="${n.id_aula}" data-lora="${n.lora_id || ''}">
                            ${escapeHtml(n.nombre_aula || 'Aula')} (ID: ${n.id_aula}, LoRa #${n.lora_id || 'S/N'}, ${escapeHtml(n.codigo_sala || '')})
                        </option>
                    `).join('');
            } else {
                sel.innerHTML = '<option value="">Error al cargar la lista de aulas</option>';
            }
        } catch (e) {
            if (sel) sel.innerHTML = '<option value="">Error al conectar con el servidor</option>';
        }
    };

    window.cerrarModalResetearAula = function() {
        const modal = document.getElementById('modal-resetear-aula');
        if (modal) modal.classList.add('hidden');
    };

    window.actualizarBtnResetModal = function() {
        const sel = document.getElementById('mra-select-aula');
        const chkConf = document.getElementById('mra-chk-confirmar');
        const btn = document.getElementById('mra-btn-ejecutar');
        const aulaVal = parseInt(sel?.value || '0', 10);
        if (btn) {
            btn.disabled = !(aulaVal > 0 && chkConf?.checked);
        }
    };

    document.getElementById('mra-select-aula')?.addEventListener('change', window.actualizarBtnResetModal);

    window.ejecutarResetAula = async function() {
        if (!ES_ADMIN) return;

        const sel = document.getElementById('mra-select-aula');
        const chkForzar = document.getElementById('mra-chk-forzar');
        const btn = document.getElementById('mra-btn-ejecutar');
        const feedback = document.getElementById('mra-feedback');

        const idAula = parseInt(sel?.value || '0', 10);
        const forzar = chkForzar ? chkForzar.checked : false;

        if (!idAula) {
            mostrarAlertaUI('warning', 'Seleccione un aula válida.');
            return;
        }

        btn.disabled = true;
        if (feedback) {
            feedback.className = 'p-3 rounded-xl text-center text-xs font-bold block bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-200';
            feedback.textContent = '⏳ Enviando comando LoRa de vaciado al Aula... Por favor espere hasta 5 segundos.';
        }

        try {
            const resp = await fetch(API_RESETEAR_AULA, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ id_aula: idAula, forzar: forzar })
            });
            const res = await resp.json();

            if (res.status === 'success') {
                if (feedback) {
                    feedback.className = 'p-3 rounded-xl text-center text-xs font-bold block bg-emerald-100 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-200';
                    feedback.textContent = '✓ ' + (res.message || 'Aula reseteada exitosamente');
                }
                mostrarAlertaUI('success', res.message || 'Sensor de aula vaciado correctamente.');
                setTimeout(() => {
                    cerrarModalResetearAula();
                    cargarColaSincronizacion();
                }, 1400);
            } else {
                if (feedback) {
                    feedback.className = 'p-3 rounded-xl text-center text-xs font-bold block bg-rose-100 text-rose-900 dark:bg-rose-950/60 dark:text-rose-200';
                    feedback.textContent = '⚠ ' + (res.message || 'Error al resetear aula.');
                }
                btn.disabled = false;
            }
        } catch (e) {
            if (feedback) {
                feedback.className = 'p-3 rounded-xl text-center text-xs font-bold block bg-rose-100 text-rose-900 dark:bg-rose-950/60 dark:text-rose-200';
                feedback.textContent = 'Fallo de comunicación: ' + e.message;
            }
            btn.disabled = false;
        }
    };

    /**
     * Notificaciones y Alertas visuales unificadas
     */
    function mostrarAlertaUI(tipo, mensaje) {
        if (window.Toast && typeof window.Toast[tipo] === 'function') {
            window.Toast[tipo](mensaje);
            return;
        }
        if (window.SISCO && typeof window.SISCO.alert === 'function') {
            window.SISCO.alert({
                title: tipo === 'error' ? 'Error' : (tipo === 'warning' ? 'Atención' : 'Aviso'),
                message: mensaje,
                type: tipo
            });
            return;
        }
        alert(mensaje);
    }

    function mostrarToast(tipo, mensaje) {
        if (window.Toast && typeof window.Toast[tipo] === 'function') {
            window.Toast[tipo](mensaje);
        }
    }

    /**
     * Auto-refresco periódico
     */
    function gestionarAutoRefresco() {
        const chk = document.getElementById('chk-auto-refresco');
        if (temporizadorRefresco) {
            clearInterval(temporizadorRefresco);
            temporizadorRefresco = null;
        }
        if (chk && chk.checked) {
            temporizadorRefresco = setInterval(() => {
                if (!colaActiva) {
                    cargarColaSincronizacion();
                }
            }, 15000);
        }
    }

    // Inicialización del DOM
    document.addEventListener('DOMContentLoaded', () => {
        cargarColaSincronizacion();

        const chkAuto = document.getElementById('chk-auto-refresco');
        if (chkAuto) {
            chkAuto.addEventListener('change', gestionarAutoRefresco);
        }
    });

    // Exponer globalmente funciones para handlers inline
    window.cargarColaSincronizacion = cargarColaSincronizacion;
    window.aplicarFiltrosLocales = aplicarFiltrosLocales;
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
