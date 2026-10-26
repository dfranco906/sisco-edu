<?php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/middleware/auth.php';

// RBAC: Solo SuperAdmin y Administración pueden acceder a la configuración del sistema
verificarRol(['SuperAdmin', 'Administracion', 'Administrador']);

$localConfigFile = __DIR__ . '/../../../config.local.php';
$localConfig = file_exists($localConfigFile) ? (include $localConfigFile) : [];
$modoEntorno = $localConfig['MODO_ENTORNO'] ?? 'cloud';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10 text-base">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Breadcrumb de Navegación Accesible -->
        <nav class="flex items-center gap-2 text-base text-slate-600 font-medium" aria-label="Migas de pan">
            <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="hover:text-blue-600 transition flex items-center gap-1.5 focus:outline-none focus:underline">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Inicio
            </a>
            <span class="text-slate-400">/</span>
            <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="hover:text-blue-600 transition focus:outline-none focus:underline">
                Administración
            </a>
            <span class="text-slate-400">/</span>
            <span class="text-slate-900 font-bold">Configuración de Red & IoT</span>
        </nav>

        <!-- Cabecera de Página con Títulos Grandes y Botonera Accesible -->
        <div class="app-page-header p-6 sm:p-8 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-2xl bg-blue-100 text-blue-700 text-xl font-bold">
                        ⚙️
                    </span>
                    <span class="px-3.5 py-1 text-sm font-extrabold rounded-full bg-blue-50 text-blue-900 border border-blue-200 uppercase tracking-wide">
                        Infraestructura & Conectividad
                    </span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Configuración de Redes e IoT
                </h1>
                <p class="text-base sm:text-lg text-slate-700 mt-2 max-w-3xl leading-relaxed">
                    Administración dinámica de direcciones IP, vinculación de nodos LoRa en aulas, parámetros del sensor de huellas y enlaces de alta disponibilidad.
                </p>
            </div>

            <!-- Botonera de Acción Primaria -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="btn btn-muted min-h-[46px] px-5 py-2.5 text-base font-semibold rounded-2xl border border-slate-300 flex items-center gap-2">
                    <span>←</span>
                    <span>Administración</span>
                </a>
                <button type="button" id="btn-guardar-todo" class="min-h-[46px] px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-base font-bold rounded-2xl shadow-sm transition flex items-center gap-2.5 focus:outline-none focus:ring-4 focus:ring-blue-200 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Guardar Cambios</span>
                </button>
            </div>
        </div>

        <!-- Franja Superior de KPIs de Conectividad IoT -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- 1. ESP32 Registrador -->
            <div class="p-6 rounded-3xl bg-blue-50/70 border-2 border-blue-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-blue-900">Nodo Registrador</span>
                    <div id="kpi-ip-registrador" class="text-xl sm:text-2xl font-black text-slate-900 font-mono mt-2 truncate max-w-[180px]">
                        192.168.100.125
                    </div>
                    <p class="text-sm text-slate-600 mt-0.5">Sensor biométrico DY50</p>
                </div>
                <span class="text-4xl">👆</span>
            </div>

            <!-- 2. Gateway LoRa -->
            <div class="p-6 rounded-3xl bg-indigo-50/70 border-2 border-indigo-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-indigo-900">Gateway LoRa</span>
                    <div id="kpi-ip-gateway" class="text-xl sm:text-2xl font-black text-slate-900 font-mono mt-2 truncate max-w-[180px]">
                        192.168.100.17
                    </div>
                    <p class="text-sm text-slate-600 mt-0.5">Concentrador RYLR998</p>
                </div>
                <span class="text-4xl">📡</span>
            </div>

            <!-- 3. Nodos en Aulas -->
            <div class="p-6 rounded-3xl bg-emerald-50/70 border-2 border-emerald-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-emerald-900">Nodos de Aulas</span>
                    <div id="kpi-total-nodos" class="text-4xl font-black text-slate-900 mt-2">
                        0
                    </div>
                    <p class="text-sm text-slate-600 mt-0.5">Salas físicas enlazadas</p>
                </div>
                <span class="text-4xl">🏫</span>
            </div>

            <!-- 4. Servidor Backend -->
            <div class="p-6 rounded-3xl bg-amber-50/70 border-2 border-amber-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-amber-900">Host Central</span>
                    <div id="kpi-ip-servidor" class="text-xl sm:text-2xl font-black text-slate-900 font-mono mt-2 truncate max-w-[180px]">
                        Local / LAN
                    </div>
                    <p class="text-sm text-slate-600 mt-0.5">Servicio HTTP SISCO-EDU</p>
                </div>
                <span class="text-4xl">🖥️</span>
            </div>
        </div>

        <!-- Mensaje de Alerta General de Alto Contraste -->
        <div id="alerta-global" class="hidden p-5 rounded-2xl border-2 text-base flex items-start gap-3 transition-all duration-200 shadow-xs">
            <span id="alerta-icono" class="text-2xl leading-none"></span>
            <div class="flex-1 font-semibold" id="alerta-texto"></div>
            <button type="button" onclick="cerrarAlerta()" class="text-slate-400 hover:text-slate-700 font-bold text-2xl leading-none cursor-pointer">&times;</button>
        </div>

        <!-- Formulario Principal de Parámetros de Red -->
        <form id="form-configuracion" onsubmit="event.preventDefault(); guardarConfiguraciones();">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- PANEL 1: Nodos IoT Locales (Hardware ESP32) -->
                <div class="p-6 sm:p-8 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b-2 border-slate-100">
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                                <span>📡</span> Nodos IoT Locales (ESP32)
                            </h2>
                            <p class="text-base text-slate-600 mt-1">Dispositivos físicos enroladores y concentradores en red escolar.</p>
                        </div>
                        <span class="text-sm px-3.5 py-1 rounded-full font-extrabold bg-emerald-100 text-emerald-900 border border-emerald-300 uppercase tracking-wide">
                            Hardware
                        </span>
                    </div>

                    <!-- ESP32 Registrador -->
                    <div class="p-5 rounded-2xl bg-slate-50 border-2 border-slate-200/80 space-y-2.5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <label for="ip_registrador" class="font-extrabold text-base text-slate-900">
                                IP ESP32 Registrador (Sensor DY50)
                            </label>
                            <button type="button" onclick="probarConexion('registrador')" id="btn-test-registrador" class="min-h-[40px] px-3.5 py-1.5 bg-white hover:bg-slate-100 text-slate-800 text-sm font-bold rounded-xl border border-slate-300 shadow-2xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                <span>⚡</span>
                                <span>Probar Conexión</span>
                            </button>
                        </div>
                        <input type="text" id="ip_registrador" name="ip_registrador" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 font-mono text-base font-semibold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs" placeholder="192.168.100.125" required>
                        <p class="text-sm text-slate-600 leading-relaxed font-medium">
                            Dirección IP del ESP32 dedicado a enrolar huellas biométricas en Secretaría (contrato de 1536 bytes / CRC32).
                        </p>
                        <div id="status-registrador" class="hidden mt-3 text-sm p-3 rounded-xl border-2 font-mono font-semibold"></div>
                    </div>

                    <!-- ESP32 Gateway LoRa -->
                    <div class="p-5 rounded-2xl bg-slate-50 border-2 border-slate-200/80 space-y-2.5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <label for="ip_gateway" class="font-extrabold text-base text-slate-900">
                                IP ESP32 Gateway (LoRa RYLR998)
                            </label>
                            <div class="flex items-center gap-2">
                                <a id="btn-portal-gateway" href="http://192.168.100.17/" target="_blank" class="min-h-[40px] px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-900 text-sm font-bold rounded-xl border border-indigo-200 shadow-2xs transition flex items-center gap-1.5" title="Abrir portal web embebido del Gateway">
                                    <span>🌐</span>
                                    <span>Portal Web</span>
                                </a>
                                <button type="button" onclick="probarConexion('gateway')" id="btn-test-gateway" class="min-h-[40px] px-3.5 py-1.5 bg-white hover:bg-slate-100 text-slate-800 text-sm font-bold rounded-xl border border-slate-300 shadow-2xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                                    <span>📶</span>
                                    <span>Probar Ping</span>
                                </button>
                            </div>
                        </div>
                        <input type="text" id="ip_gateway" name="ip_gateway" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 font-mono text-base font-semibold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs" placeholder="192.168.100.17" required>
                        <p class="text-sm text-slate-600 leading-relaxed font-medium">
                            Dirección IP del concentrador Gateway que retransmite plantillas y marcaciones hacia las aulas por radiofrecuencia.
                        </p>
                        <div id="status-gateway" class="hidden mt-3 text-sm p-3 rounded-xl border-2 font-mono font-semibold"></div>
                    </div>

                    <!-- URL Webhook de Sincronización Gateway -->
                    <div class="p-5 rounded-2xl bg-slate-50 border-2 border-slate-200/80 space-y-2">
                        <label for="gateway_sync_url" class="font-extrabold text-base text-slate-900 block">
                            URL Webhook de Sincronización Inmediata
                        </label>
                        <input type="url" id="gateway_sync_url" name="gateway_sync_url" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 font-mono text-base font-semibold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs" placeholder="http://192.168.100.17/sync">
                        <p class="text-sm text-slate-600 leading-relaxed font-medium">
                            Endpoint HTTP al que el backend notifica en tiempo real para despachar huellas actualizadas a las aulas.
                        </p>
                    </div>
                </div>

                <!-- PANEL 2: Servidor Central & Parámetros de Enlace -->
                <div class="p-6 sm:p-8 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b-2 border-slate-100">
                        <div>
                            <h2 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                                <span>🖥️</span> Servidor Central & Seguridad
                            </h2>
                            <p class="text-base text-slate-600 mt-1">Host de escucha y claves de autorización para enlaces de hardware.</p>
                        </div>
                        <span class="text-sm px-3.5 py-1 rounded-full font-extrabold bg-blue-100 text-blue-900 border border-blue-300 uppercase tracking-wide">
                            Red & API
                        </span>
                    </div>

                    <!-- IP o Dominio del Servidor -->
                    <div class="p-5 rounded-2xl bg-slate-50 border-2 border-slate-200/80 space-y-2">
                        <label for="ip_servidor" class="font-extrabold text-base text-slate-900 block">
                            IP o Hostname del Servidor Backend
                        </label>
                        <input type="text" id="ip_servidor" name="ip_servidor" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 font-mono text-base font-semibold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs" placeholder="192.168.100.109" required>
                        <p class="text-sm text-slate-600 leading-relaxed font-medium">
                            Dirección IP en red local del colegio o dominio configurado mediante túnel seguro (ej. Cloudflare Tunnel).
                        </p>
                    </div>

                    <!-- URL Base Forzada -->
                    <div class="p-5 rounded-2xl bg-slate-50 border-2 border-slate-200/80 space-y-2">
                        <label for="url_servidor" class="font-extrabold text-base text-slate-900 block">
                            URL Base Forzada (Opcional)
                        </label>
                        <input type="text" id="url_servidor" name="url_servidor" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 font-mono text-base font-semibold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs" placeholder="Dejar en blanco para resolución automática dinámicamente">
                        <p class="text-sm text-slate-600 leading-relaxed font-medium">
                            Si está vacío, el sistema resuelve la ruta institucional con la función portátil <code>base_url()</code>.
                        </p>
                    </div>

                    <!-- Clave Secreta API Gateway -->
                    <div class="p-5 rounded-2xl bg-slate-50 border-2 border-slate-200/80 space-y-2">
                        <label for="gateway_api_key" class="font-extrabold text-base text-slate-900 block">
                            Clave de Autorización Gateway (X-GATEWAY-KEY)
                        </label>
                        <div class="relative">
                            <input type="password" id="gateway_api_key" name="gateway_api_key" class="w-full min-h-[48px] px-4 py-2.5 pr-14 rounded-xl border border-slate-300 font-mono text-base font-semibold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs" placeholder="Clave secreta compartida">
                            <button type="button" onclick="togglePasswordVisibility('gateway_api_key')" class="absolute right-2 top-2 min-h-[36px] px-3 rounded-lg text-slate-500 hover:text-slate-800 text-sm font-bold flex items-center justify-center cursor-pointer" title="Mostrar/ocultar clave">
                                👁️
                            </button>
                        </div>
                        <p class="text-sm text-slate-600 leading-relaxed font-medium">
                            Token enviado en la cabecera HTTP para blindar el endpoint contra peticiones no autorizadas.
                        </p>
                    </div>

                    <!-- Parámetros Radio LoRa -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-5 rounded-2xl bg-slate-50 border-2 border-slate-200/80">
                        <div>
                            <label for="lora_red_id" class="font-extrabold text-base text-slate-900 block mb-1">
                                Red LoRa (Network ID)
                            </label>
                            <input type="number" id="lora_red_id" name="lora_red_id" min="1" max="255" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 font-mono text-base font-semibold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs" placeholder="18">
                            <p class="text-xs text-slate-500 mt-1 font-semibold">Identificador de red RF compartido</p>
                        </div>
                        <div>
                            <label for="lora_id_gateway" class="font-extrabold text-base text-slate-900 block mb-1">
                                Dirección Gateway (Addr)
                            </label>
                            <input type="number" id="lora_id_gateway" name="lora_id_gateway" min="1" max="65535" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 font-mono text-base font-semibold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs" placeholder="100">
                            <p class="text-xs text-slate-500 mt-1 font-semibold">Dirección LoRa del concentrador</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción Inmediata de la Sección de Parámetros -->
            <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                <button type="button" onclick="cargarConfiguraciones()" class="btn btn-muted min-h-[48px] px-6 py-2.5 text-base font-bold rounded-2xl border border-slate-300 transition cursor-pointer">
                    Descartar / Recargar
                </button>
                <button type="submit" class="min-h-[48px] px-7 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-base font-bold rounded-2xl shadow-sm transition flex items-center gap-2.5 focus:outline-none focus:ring-4 focus:ring-blue-200 cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Guardar Parámetros de Red</span>
                </button>
            </div>
        </form>

        <!-- PANEL: GESTIÓN DE NODOS LoRa DE AULAS -->
        <div class="p-6 sm:p-8 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b-2 border-slate-100 gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 flex items-center gap-3">
                        <span>📡</span> Nodos LoRa de Aulas Conectadas (RYLR998)
                    </h2>
                    <p class="text-base text-slate-600 mt-1 leading-relaxed max-w-3xl">
                        Mapeo centralizado de dispositivos receptores en salas de clase. El Gateway consulta esta lista dinámicamente para despachar huellas y registrar asistencias.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    <button type="button" onclick="cargarNodosLora()" class="btn btn-muted min-h-[46px] px-4 py-2 text-base font-semibold rounded-2xl border border-slate-300 flex items-center gap-2 cursor-pointer" title="Recargar lista de nodos">
                        <span>🔄</span>
                        <span>Actualizar</span>
                    </button>
                    <button type="button" onclick="abrirModalNuevoNodo()" class="min-h-[46px] px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-base font-bold rounded-2xl shadow-sm transition flex items-center gap-2 cursor-pointer">
                        <span class="text-xl leading-none">+</span>
                        <span>Añadir Nodo de Aula</span>
                    </button>
                </div>
            </div>

            <!-- Tabla de Nodos LoRa Accesible -->
            <div class="overflow-x-auto rounded-2xl border-2 border-slate-200/90 shadow-2xs">
                <table class="w-full text-left text-base">
                    <thead class="bg-slate-100 text-slate-700 uppercase font-bold text-sm border-b-2 border-slate-200">
                        <tr>
                            <th class="p-4 sm:p-5">Aula Física Vinculada</th>
                            <th class="p-4">Bloque / Piso</th>
                            <th class="p-4 text-center">Red LoRa</th>
                            <th class="p-4 text-center">Dirección LoRa</th>
                            <th class="p-4 text-center">Código RF (Antena)</th>
                            <th class="p-4 text-center">Estado</th>
                            <th class="p-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-nodos-body" class="divide-y divide-slate-200 text-slate-800 bg-white">
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-500 text-base font-bold">
                                <span class="animate-spin inline-block mr-2">⏳</span> Cargando catálogo de nodos LoRa...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Guía Paso a Paso con Estilo Accesible -->
            <div class="p-6 bg-amber-50/80 border-2 border-amber-200 rounded-3xl flex flex-col sm:flex-row items-start gap-4 text-slate-800">
                <span class="text-3xl p-2 rounded-2xl bg-amber-100 border border-amber-300 text-amber-900 shrink-0">💡</span>
                <div class="leading-relaxed">
                    <h4 class="text-lg font-black text-amber-950 mb-1">Paso a paso para instalar un nuevo nodo en aula física:</h4>
                    <p class="text-base text-slate-700">
                        1. Registrá el aula en este panel asignándole un <strong>LoRa ID</strong> único y su <strong>Código de Aula</strong> correspondiente.<br>
                        2. En el aula física, encendé el ESP32 receptor y conéctate con un teléfono o laptop a su red Wi-Fi de configuración <code>SISCO-AULA-CONFIG</code>.<br>
                        3. Ingresá exactamente estos mismos parámetros en el portal web seguro del ESP32 para que comience a recibir las huellas y validar asistencias al instante.
                    </p>
                </div>
            </div>
        </div>

        <!-- PANEL 3: Sincronización Local & Cloud (1 Solo Clic) -->
        <div class="p-6 sm:p-8 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b-2 border-slate-100 gap-4">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 flex items-center gap-3">
                        <span>⚡</span> Sincronización de Base de Datos (1 Clic)
                    </h2>
                    <p class="text-base text-slate-600 mt-1 leading-relaxed">
                        Permite trabajar 100% offline con la base de datos local y sincronizar asistencias y huellas a la nube cuando haya Internet.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span id="badge-modo-entorno" class="text-sm px-4 py-1.5 rounded-full font-black uppercase tracking-wide <?= ($modoEntorno === 'local') ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-emerald-100 text-emerald-900 border border-emerald-300' ?>">
                        <?= ($modoEntorno === 'local') ? '🟠 Modo Local (Offline)' : '🟢 Modo Cloud (Supabase)' ?>
                    </span>
                </div>
            </div>

            <!-- Botonera de Acciones 1 Clic -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Tarjeta 1: Subir cambios locales a la nube -->
                <div class="p-6 rounded-3xl border-2 border-indigo-200 bg-indigo-50/50 space-y-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2.5 text-indigo-950 font-black text-lg mb-2">
                            <span class="text-2xl">☁️</span>
                            <span>Subir a la Nube</span>
                        </div>
                        <p class="text-sm text-slate-700 leading-relaxed font-medium">
                            Envía a Supabase Cloud las huellas enroladas, asistencias registradas y eventos generados mientras estuviste offline.
                        </p>
                    </div>
                    <button type="button" onclick="ejecutarSincronizacion('subir')" id="btn-sync-subir" class="w-full py-3.5 px-4 rounded-xl font-black text-white bg-indigo-600 hover:bg-indigo-700 active:scale-98 transition flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                        <span id="icon-sync-subir">🚀</span>
                        <span>Sincronizar a la Nube (1 Clic)</span>
                    </button>
                </div>

                <!-- Tarjeta 2: Descargar de la nube a local -->
                <div class="p-6 rounded-3xl border-2 border-blue-200 bg-blue-50/50 space-y-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2.5 text-blue-950 font-black text-lg mb-2">
                            <span class="text-2xl">⬇️</span>
                            <span>Descargar de la Nube</span>
                        </div>
                        <p class="text-sm text-slate-700 leading-relaxed font-medium">
                            Actualiza la base de datos local con los últimos estudiantes, materias, horarios y profesores dados de alta en la nube.
                        </p>
                    </div>
                    <button type="button" onclick="ejecutarSincronizacion('bajar')" id="btn-sync-bajar" class="w-full py-3.5 px-4 rounded-xl font-black text-blue-900 bg-blue-200 hover:bg-blue-300 active:scale-98 transition flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                        <span id="icon-sync-bajar">📥</span>
                        <span>Actualizar Base Local (1 Clic)</span>
                    </button>
                </div>

                <!-- Tarjeta 3: Cambiar Entorno Activo -->
                <div class="p-6 rounded-3xl border-2 border-slate-200 bg-slate-50 space-y-4 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2.5 text-slate-900 font-black text-lg mb-2">
                            <span class="text-2xl">🔄</span>
                            <span>Entorno del Sistema</span>
                        </div>
                        <p class="text-sm text-slate-700 leading-relaxed font-medium">
                            Alterna la conexión principal entre PostgreSQL Local (127.0.0.1) y Supabase Cloud (aws-0-us-east-1).
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="cambiarEntorno('local')" class="flex-1 py-3 px-3 rounded-xl font-extrabold text-sm transition cursor-pointer <?= ($modoEntorno === 'local') ? 'bg-amber-500 text-white shadow-xs' : 'bg-slate-200 text-slate-700 hover:bg-slate-300' ?>">
                            Modo Local
                        </button>
                        <button type="button" onclick="cambiarEntorno('cloud')" class="flex-1 py-3 px-3 rounded-xl font-extrabold text-sm transition cursor-pointer <?= ($modoEntorno === 'cloud') ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-200 text-slate-700 hover:bg-slate-300' ?>">
                            Modo Cloud
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANEL 4: Alta Disponibilidad & Estrategia Cloud -->
        <div class="p-6 sm:p-8 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm space-y-6">
            <div class="flex items-center justify-between pb-4 border-b-2 border-slate-100">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900 flex items-center gap-3">
                        <span>☁️</span> Estrategia de Alta Disponibilidad & Redes Híbridas
                    </h2>
                    <p class="text-base text-slate-600 mt-1 leading-relaxed">
                        Pautas arquitectónicas para independizar los nodos y la web de cambios de IP local en la institución.
                    </p>
                </div>
                <span class="text-sm px-3.5 py-1 rounded-full font-extrabold bg-indigo-100 text-indigo-900 border border-indigo-300 uppercase tracking-wide">
                    Arquitectura
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Opción 1: Base de Datos en la Nube -->
                <div class="p-6 rounded-3xl border-2 border-indigo-200 bg-indigo-50/40 space-y-3">
                    <div class="flex items-center gap-2 text-indigo-950 font-black text-lg">
                        <span class="w-8 h-8 rounded-xl bg-indigo-200 text-indigo-900 flex items-center justify-center text-sm font-bold">1</span>
                        <span>Base de Datos Cloud</span>
                    </div>
                    <p class="text-base text-slate-700 leading-relaxed font-medium">
                        Alojar PostgreSQL / MySQL en la nube para que múltiples terminales y dispositivos sincronicen sin depender de una sola máquina encendida en secretaría.
                    </p>
                    <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800 font-mono text-xs sm:text-sm text-slate-200 space-y-1">
                        <div class="text-slate-400">// config.local.php</div>
                        <div>putenv('DB_HOST=cloud-db.internal');</div>
                        <div>putenv('DB_PORT=5432');</div>
                        <div>putenv('DB_SSL=true');</div>
                    </div>
                </div>

                <!-- Opción 2: Cloudflare Tunnel / Ngrok -->
                <div class="p-6 rounded-3xl border-2 border-amber-200 bg-amber-50/40 space-y-3">
                    <div class="flex items-center gap-2 text-amber-950 font-black text-lg">
                        <span class="w-8 h-8 rounded-xl bg-amber-200 text-amber-900 flex items-center justify-center text-sm font-bold">2</span>
                        <span>Túnel Seguro HTTPS</span>
                    </div>
                    <p class="text-base text-slate-700 leading-relaxed font-medium">
                        Publicar el servidor web local con dominio seguro y certificado TLS sin abrir puertos en el módem escolar. Los nodos apuntan a esta URL permanente.
                    </p>
                    <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800 font-mono text-xs sm:text-sm text-slate-200 space-y-1">
                        <div class="text-slate-400">// Ejecutar túnel</div>
                        <div>cloudflared tunnel run sisco-edu</div>
                        <div class="text-emerald-400">URL: https://sisco.colegio.edu.py</div>
                    </div>
                </div>

                <!-- Opción 3: Nombre Local mDNS -->
                <div class="p-6 rounded-3xl border-2 border-emerald-200 bg-emerald-50/40 space-y-3">
                    <div class="flex items-center gap-2 text-emerald-950 font-black text-lg">
                        <span class="w-8 h-8 rounded-xl bg-emerald-200 text-emerald-900 flex items-center justify-center text-sm font-bold">3</span>
                        <span>Resolución Local mDNS</span>
                    </div>
                    <p class="text-base text-slate-700 leading-relaxed font-medium">
                        Para aulas sin acceso a Internet, los ESP32 resuelven el servidor mediante nombres directos (<code>http://sisco-servidor.local</code>) sin importar la IP DHCP.
                    </p>
                    <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800 font-mono text-xs sm:text-sm text-slate-200 space-y-1">
                        <div class="text-slate-400">// Firmware ESP32</div>
                        <div>MDNS.begin("sisco-servidor");</div>
                        <div class="text-emerald-400">Host: http://sisco-servidor.local</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- SUBMODAL ACCESIBLE: AÑADIR / EDITAR NODO LORA -->
<div id="modal-nodo" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 sm:p-8 border-2 border-slate-200 relative animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Cabecera del Submodal Accesible -->
        <div class="flex items-center justify-between pb-4 border-b-2 border-slate-100 mb-6">
            <div class="flex items-center gap-3">
                <span class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 text-2xl flex items-center justify-center font-bold">
                    📡
                </span>
                <div>
                    <h3 id="modal-nodo-titulo" class="text-2xl font-black text-slate-900 tracking-tight">
                        Añadir Nodo de Aula LoRa
                    </h3>
                    <p class="text-sm text-slate-500 font-semibold mt-0.5">Vincular receptor RYLR998 con un aula física</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalNodo()" class="w-10 h-10 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 font-bold text-2xl flex items-center justify-center transition leading-none cursor-pointer" aria-label="Cerrar modal">
                &times;
            </button>
        </div>

        <form id="form-nodo" onsubmit="event.preventDefault(); guardarNodo();" class="space-y-5">
            <input type="hidden" id="modal_id_nodo" value="">

            <!-- FILTROS RÁPIDOS DE BÚSQUEDA DE AULA -->
            <div class="p-4 bg-slate-50 border-2 border-slate-200/90 rounded-2xl space-y-3">
                <div class="text-xs font-extrabold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <span>🔍</span> Filtros Rápidos de Campus / Edificio
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="filtro_modal_bloque" class="block text-sm font-bold text-slate-700 mb-1">Bloque / Pabellón</label>
                        <select id="filtro_modal_bloque" onchange="filtrarAulasDisponiblesModal()" class="w-full min-h-[44px] px-3.5 py-2 text-base font-semibold rounded-xl border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos los bloques</option>
                        </select>
                    </div>
                    <div>
                        <label for="filtro_modal_piso" class="block text-sm font-bold text-slate-700 mb-1">Piso / Nivel</label>
                        <select id="filtro_modal_piso" onchange="filtrarAulasDisponiblesModal()" class="w-full min-h-[44px] px-3.5 py-2 text-base font-semibold rounded-xl border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Todos los pisos</option>
                        </select>
                    </div>
                </div>
                <div>
                    <input type="text" id="filtro_modal_buscar" oninput="filtrarAulasDisponiblesModal()" placeholder="Buscar aula por nombre o código (ej. BTI, Lab, 7mo)..." class="w-full min-h-[44px] px-4 py-2 text-base font-semibold rounded-xl border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <!-- Aula Física a Vincular -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="modal_id_aula" class="font-extrabold text-base text-slate-900">
                        Aula Física a Vincular *
                    </label>
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-1.5 text-xs sm:text-sm font-bold text-slate-600 cursor-pointer select-none">
                            <input type="checkbox" id="filtro_solo_libres" onchange="filtrarAulasDisponiblesModal()" class="w-4 h-4 rounded border-slate-300 text-blue-600">
                            <span>Solo libres</span>
                        </label>
                        <span id="texto-aulas-disponibles-count" class="text-xs font-bold px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 border border-slate-200">
                            0 disponibles
                        </span>
                    </div>
                </div>
                <select id="modal_id_aula" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 text-base font-semibold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs" required onchange="alCambiarAulaModal(this)">
                    <option value="">-- Selecciona el aula física --</option>
                </select>
                <div id="aviso-aula-asignada" class="hidden mt-2.5 p-3 rounded-xl bg-amber-50 border-2 border-amber-200 text-sm font-semibold text-amber-900 flex items-start gap-2">
                    <span class="text-lg leading-none">⚠️</span>
                    <span id="aviso-aula-texto" class="leading-relaxed"></span>
                </div>
            </div>

            <!-- Parámetros LoRa -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Red LoRa -->
                <div>
                    <label for="modal_lora_red_id" class="font-extrabold text-base text-slate-900 block mb-1">
                        Red LoRa (Network ID) *
                    </label>
                    <input type="number" id="modal_lora_red_id" min="1" max="255" value="18" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 font-mono text-base font-semibold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs" required>
                    <p class="text-xs text-slate-500 mt-1 font-semibold">Debe coincidir con la red del Gateway (18).</p>
                </div>

                <!-- Dirección LoRa -->
                <div>
                    <label for="modal_lora_id" class="font-extrabold text-base text-slate-900 block mb-1">
                        Dirección LoRa (Addr) *
                    </label>
                    <input type="number" id="modal_lora_id" min="1" max="65535" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 font-mono text-base font-semibold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-xs" placeholder="111" required>
                    <p class="text-xs text-slate-500 mt-1 font-semibold">Dirección única de antena RYLR998 para esta sala.</p>
                </div>
            </div>

            <!-- Código RF del Aula (Autocompletado) -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="modal_room_id" class="font-extrabold text-base text-slate-900">
                        Código RF de Aula (Filtro Antena y Pantalla) *
                    </label>
                    <span id="badge-auto-rf" class="text-xs text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded-lg font-bold border border-emerald-300">
                        ⚡ Autocompletado del Aula
                    </span>
                </div>
                <input type="text" id="modal_room_id" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 font-mono text-base font-bold text-indigo-700 bg-slate-100 shadow-xs" placeholder="ej. A-P1-01" required readonly>
                <p class="text-xs text-slate-500 mt-1 font-semibold leading-relaxed">
                    Heredado del aula para garantizar sincronización exacta entre la base de datos y la antena física.
                </p>
            </div>

            <!-- Pie de Acciones del Submodal -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t-2 border-slate-100">
                <button type="button" onclick="cerrarModalNodo()" class="btn btn-muted min-h-[46px] px-5 py-2.5 text-base font-semibold rounded-xl border border-slate-300 cursor-pointer">
                    Cancelar
                </button>
                <button type="submit" id="btn-guardar-nodo" class="min-h-[46px] px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-base font-bold rounded-xl shadow-sm transition flex items-center gap-2 cursor-pointer">
                    <span>💾</span>
                    <span>Guardar Nodo</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const API_LEER = "<?= base_url('src/api/Configuracion/leer_configuraciones.php') ?>";
const API_ACTUALIZAR = "<?= base_url('src/api/Configuracion/actualizar_configuracion.php') ?>";
const API_TEST = "<?= base_url('src/api/Configuracion/probar_conexion.php') ?>";
const API_NODOS_LISTAR = "<?= base_url('src/api/Nodos/listar_nodos.php') ?>";
const API_NODOS_GUARDAR = "<?= base_url('src/api/Nodos/guardar_nodo.php') ?>";
const API_NODOS_ELIMINAR = "<?= base_url('src/api/Nodos/eliminar_nodo.php') ?>";

let listaNodosCache = [];
let aulasDisponiblesCache = [];
let siguienteLoraIdSugerido = 101;

function mostrarAlerta(tipo, mensaje) {
    const contenedor = document.getElementById('alerta-global');
    const icono = document.getElementById('alerta-icono');
    const texto = document.getElementById('alerta-texto');

    contenedor.className = "p-5 rounded-2xl border-2 text-base flex items-start gap-3 transition-all duration-200 shadow-xs";

    if (tipo === 'success') {
        contenedor.classList.add('bg-emerald-50', 'border-emerald-300', 'text-emerald-950');
        icono.textContent = "✅";
        if (window.Toast) Toast.success(mensaje, "Configuración");
    } else if (tipo === 'error') {
        contenedor.classList.add('bg-rose-50', 'border-rose-300', 'text-rose-950');
        icono.textContent = "❌";
        if (window.Toast) Toast.error(mensaje, "Error de Configuración");
    } else {
        contenedor.classList.add('bg-blue-50', 'border-blue-300', 'text-blue-950');
        icono.textContent = "ℹ️";
        if (window.Toast) Toast.info(mensaje, "Información");
    }

    texto.innerHTML = mensaje;
    contenedor.classList.remove('hidden');
    contenedor.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function cerrarAlerta() {
    document.getElementById('alerta-global').classList.add('hidden');
}

function togglePasswordVisibility(fieldId) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.type = field.type === 'password' ? 'text' : 'password';
    }
}

async function cargarConfiguraciones() {
    try {
        const resp = await fetch(API_LEER);
        const data = await resp.json();

        if (data.status === 'success' && data.data && data.data.valores) {
            const vals = data.data.valores;
            for (const [clave, valor] of Object.entries(vals)) {
                const input = document.getElementById(clave);
                if (input) {
                    input.value = valor ?? '';
                }
            }

            // Actualizar KPIs de franja superior
            const kpiReg = document.getElementById('kpi-ip-registrador');
            if (kpiReg && vals.ip_registrador) kpiReg.textContent = vals.ip_registrador;

            const kpiGw = document.getElementById('kpi-ip-gateway');
            if (kpiGw && vals.ip_gateway) kpiGw.textContent = vals.ip_gateway;

            const kpiSrv = document.getElementById('kpi-ip-servidor');
            if (kpiSrv && vals.ip_servidor) kpiSrv.textContent = vals.ip_servidor;

            const btnPortal = document.getElementById('btn-portal-gateway');
            if (btnPortal && vals.ip_gateway) {
                btnPortal.href = `http://${vals.ip_gateway}/`;
            }
        } else {
            mostrarAlerta('error', data.message || 'No se pudieron cargar las configuraciones');
        }
    } catch (e) {
        mostrarAlerta('error', 'Error al consultar la API de configuraciones: ' + e.message);
    }
}

async function guardarConfiguraciones() {
    const btnGuardar = document.getElementById('btn-guardar-todo');
    btnGuardar.disabled = true;
    btnGuardar.classList.add('opacity-70');

    const form = document.getElementById('form-configuracion');
    const formData = new FormData(form);
    const payload = {};

    for (const [key, value] of formData.entries()) {
        payload[key] = value.trim();
    }

    try {
        const resp = await fetch(API_ACTUALIZAR, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const res = await resp.json();

        if (res.status === 'success') {
            mostrarAlerta('success', res.message || 'Configuraciones guardadas correctamente');
            
            // Actualizar KPIs
            if (payload.ip_registrador) {
                const kpiReg = document.getElementById('kpi-ip-registrador');
                if (kpiReg) kpiReg.textContent = payload.ip_registrador;
            }
            if (payload.ip_gateway) {
                const kpiGw = document.getElementById('kpi-ip-gateway');
                if (kpiGw) kpiGw.textContent = payload.ip_gateway;
            }
            if (payload.ip_servidor) {
                const kpiSrv = document.getElementById('kpi-ip-servidor');
                if (kpiSrv) kpiSrv.textContent = payload.ip_servidor;
            }

            // Actualizar entorno JS en vivo
            if (window.CONFIG_RED) {
                if (payload.ip_registrador) window.CONFIG_RED.ipRegistrador = payload.ip_registrador;
                if (payload.ip_gateway) window.CONFIG_RED.ipGateway = payload.ip_gateway;
            }
        } else {
            mostrarAlerta('error', res.message || 'Ocurrió un error al guardar los cambios');
        }
    } catch (e) {
        mostrarAlerta('error', 'Fallo en la comunicación con el servidor: ' + e.message);
    } finally {
        btnGuardar.disabled = false;
        btnGuardar.classList.remove('opacity-70');
    }
}

document.getElementById('btn-guardar-todo')?.addEventListener('click', () => {
    guardarConfiguraciones();
});

async function probarConexion(tipo) {
    const ipInput = document.getElementById(tipo === 'registrador' ? 'ip_registrador' : 'ip_gateway');
    const statusDiv = document.getElementById(tipo === 'registrador' ? 'status-registrador' : 'status-gateway');
    const btn = document.getElementById(tipo === 'registrador' ? 'btn-test-registrador' : 'btn-test-gateway');

    const ip = ipInput ? ipInput.value.trim() : '';

    statusDiv.className = "mt-3 text-sm p-3 rounded-xl border-2 font-mono font-semibold bg-blue-50 text-blue-900 border-blue-200 block";
    statusDiv.innerHTML = `<span class="inline-block animate-spin mr-1">⏳</span> Conectando con ${ip || 'dispositivo'}...`;
    btn.disabled = true;

    try {
        const url = `${API_TEST}?tipo=${encodeURIComponent(tipo)}&ip=${encodeURIComponent(ip)}`;
        const resp = await fetch(url);
        const res = await resp.json();

        if (res.status === 'success') {
            const latencia = res.data?.latencia_ms ? ` (${res.data.latencia_ms} ms)` : '';
            statusDiv.className = "mt-3 text-sm p-3 rounded-xl border-2 font-mono font-semibold bg-emerald-50 text-emerald-950 border-emerald-300 block";
            statusDiv.innerHTML = `🟢 <strong>ONLINE${latencia}:</strong> ${res.message}`;
            if (window.Toast) Toast.success(`Dispositivo en línea${latencia}`, "Ping Exitoso");
        } else {
            statusDiv.className = "mt-3 text-sm p-3 rounded-xl border-2 font-mono font-semibold bg-rose-50 text-rose-950 border-rose-300 block";
            statusDiv.innerHTML = `🔴 <strong>OFFLINE:</strong> ${res.message}`;
            if (window.Toast) Toast.warning(res.message, "Sin Respuesta");
        }
    } catch (e) {
        statusDiv.className = "mt-3 text-sm p-3 rounded-xl border-2 font-mono font-semibold bg-rose-50 text-rose-950 border-rose-300 block";
        statusDiv.innerHTML = `🔴 <strong>ERROR:</strong> ${e.message}`;
        if (window.Toast) Toast.error(e.message, "Fallo de Red");
    } finally {
        btn.disabled = false;
    }
}

async function cargarNodosLora() {
    const tbody = document.getElementById('tabla-nodos-body');
    const kpiNodos = document.getElementById('kpi-total-nodos');
    try {
        const resp = await fetch(API_NODOS_LISTAR);
        const res = await resp.json();

        if (res.status === 'success' && res.data) {
            listaNodosCache = res.data.nodos || [];
            aulasDisponiblesCache = res.data.aulas_disponibles || [];
            siguienteLoraIdSugerido = res.data.siguiente_lora_id || 101;
            
            if (kpiNodos) {
                kpiNodos.textContent = listaNodosCache.length;
            }

            renderizarTablaNodos(listaNodosCache);
            actualizarFiltrosModalCampus();
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-rose-600 text-base font-bold">${res.message || 'Error al cargar nodos'}</td></tr>`;
        }
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-rose-600 text-base font-bold">Error de conexión: ${e.message}</td></tr>`;
    }
}

function renderizarTablaNodos(nodos) {
    const tbody = document.getElementById('tabla-nodos-body');
    if (!nodos.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="py-12 text-center text-slate-500 text-base font-medium">
                    No hay nodos LoRa registrados en este momento.<br>
                    <button type="button" onclick="abrirModalNuevoNodo()" class="mt-3 min-h-[44px] px-5 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-xl font-bold border border-blue-200 transition">
                        + Añadir el primer nodo de aula
                    </button>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = nodos.map(n => `
        <tr class="hover:bg-slate-50/80 transition-colors">
            <td class="p-4 sm:p-5 font-bold text-slate-900">
                <div class="flex items-center gap-2.5">
                    <span class="text-xl">🚪</span>
                    <div>
                        <span class="text-base font-black text-slate-900 block">${n.nombre_aula || 'Aula sin nombre'}</span>
                        <span class="text-xs font-semibold text-slate-500 font-mono">[${n.codigo_sala || n.room_id || 'S/C'}]</span>
                    </div>
                </div>
            </td>
            <td class="p-4 text-slate-700 font-semibold text-sm">
                <span class="px-3 py-1 rounded-xl bg-slate-100 border border-slate-200">
                    ${n.bloque ? `${n.bloque} - ` : ''}${n.ubicacion || n.piso || 'Sin sector'}
                </span>
            </td>
            <td class="p-4 text-center">
                <span class="inline-block px-3 py-1 rounded-xl font-mono text-sm font-extrabold bg-indigo-50 text-indigo-900 border border-indigo-200">
                    Red ${n.lora_red_id || 18}
                </span>
            </td>
            <td class="p-4 text-center">
                <span class="inline-block px-3.5 py-1 rounded-xl font-mono text-sm font-black bg-blue-50 text-blue-900 border border-blue-200">
                    ID: ${n.lora_id}
                </span>
            </td>
            <td class="p-4 text-center">
                <code class="px-2.5 py-1 bg-slate-100 text-indigo-950 font-bold rounded-lg border border-slate-300 font-mono text-sm">
                    ${n.room_id || n.codigo_sala || '-'}
                </code>
            </td>
            <td class="p-4 text-center">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-900 border border-emerald-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Activo
                </span>
            </td>
            <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-2 flex-wrap">
                    <button type="button" onclick="abrirModalEditarNodo(${n.id_nodo})" class="min-h-[40px] px-3.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 text-sm font-bold rounded-xl border border-amber-200 transition shadow-2xs cursor-pointer flex items-center gap-1" title="Editar parámetros del nodo">
                        <span>✏️</span>
                        <span>Editar</span>
                    </button>
                    <button type="button" onclick="eliminarNodo(${n.id_nodo}, '${n.nombre_aula || n.room_id}')" class="min-h-[40px] px-3.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-800 text-sm font-bold rounded-xl border border-rose-200 transition shadow-2xs cursor-pointer flex items-center gap-1" title="Desvincular nodo">
                        <span>🗑️</span>
                        <span>Desvincular</span>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

let nodoEditandoActual = null;

function actualizarFiltrosModalCampus() {
    const selBloque = document.getElementById('filtro_modal_bloque');
    const selPiso = document.getElementById('filtro_modal_piso');
    if (!selBloque || !selPiso) return;

    const bloquesSet = new Set();
    const pisosSet = new Set();

    aulasDisponiblesCache.forEach(a => {
        if (a.bloque) bloquesSet.add(a.bloque);
        if (a.piso) pisosSet.add(a.piso);
    });

    listaNodosCache.forEach(n => {
        if (n.bloque) bloquesSet.add(n.bloque);
        if (n.piso) pisosSet.add(n.piso);
    });

    const bloqueActual = selBloque.value;
    const pisoActual = selPiso.value;

    selBloque.innerHTML = '<option value="">Todos los bloques</option>' + Array.from(bloquesSet).sort().map(b => `
        <option value="${b}" ${b === bloqueActual ? 'selected' : ''}>${b}</option>
    `).join('');

    selPiso.innerHTML = '<option value="">Todos los pisos</option>' + Array.from(pisosSet).sort().map(p => `
        <option value="${p}" ${p === pisoActual ? 'selected' : ''}>${p}</option>
    `).join('');
}

function filtrarAulasDisponiblesModal() {
    const selBloque = document.getElementById('filtro_modal_bloque');
    const selPiso = document.getElementById('filtro_modal_piso');
    const inputBuscar = document.getElementById('filtro_modal_buscar');
    const chkSoloLibres = document.getElementById('filtro_solo_libres');
    const selectAula = document.getElementById('modal_id_aula');
    const contador = document.getElementById('texto-aulas-disponibles-count');

    const fBloque = selBloque ? selBloque.value : '';
    const fPiso = selPiso ? selPiso.value : '';
    const fBuscar = inputBuscar ? inputBuscar.value.toLowerCase().trim() : '';
    const fSoloLibres = chkSoloLibres ? chkSoloLibres.checked : false;

    let lista = [...aulasDisponiblesCache];

    const filtradas = lista.filter(a => {
        if (fSoloLibres && a.nodo_asignado_id && (!nodoEditandoActual || nodoEditandoActual.id_nodo != a.nodo_asignado_id)) {
            return false;
        }
        if (fBloque && a.bloque !== fBloque) return false;
        if (fPiso && a.piso !== fPiso) return false;
        if (fBuscar) {
            const nom = (a.nombre || '').toLowerCase();
            const cod = (a.codigo || a.codigo_sala || '').toLowerCase();
            const bloq = (a.bloque || '').toLowerCase();
            const pis = (a.piso || '').toLowerCase();
            if (!nom.includes(fBuscar) && !cod.includes(fBuscar) && !bloq.includes(fBuscar) && !pis.includes(fBuscar)) {
                return false;
            }
        }
        return true;
    });

    if (contador) {
        contador.textContent = `${filtradas.length} aulas encontradas`;
    }

    const valorSeleccionadoPrevio = selectAula.value;
    selectAula.innerHTML = '<option value="">-- Selecciona el aula física --</option>';

    if (!filtradas.length) {
        selectAula.innerHTML = '<option value="">Sin aulas para estos filtros</option>';
        document.getElementById('modal_room_id').value = '';
        const aviso = document.getElementById('aviso-aula-asignada');
        if (aviso) aviso.classList.add('hidden');
        return;
    }

    filtradas.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.id_aula;
        const cod = a.codigo || a.codigo_sala || '';
        opt.dataset.codigo = cod;
        opt.dataset.asignado = a.nodo_asignado_id || '';
        opt.dataset.loraId = a.nodo_lora_id || '';

        let estadoTexto = '';
        if (a.nodo_asignado_id) {
            if (nodoEditandoActual && nodoEditandoActual.id_nodo == a.nodo_asignado_id) {
                estadoTexto = ' ★ Vinculada a este nodo';
            } else {
                estadoTexto = ` (⚠️ En uso: Nodo LoRa #${a.nodo_lora_id || a.nodo_asignado_id})`;
            }
        } else {
            estadoTexto = ' [Disponible]';
        }

        opt.textContent = `[${cod || 'S/C'}] ${a.nombre} — ${a.bloque || ''} ${a.piso ? `(${a.piso})` : ''}${estadoTexto}`;
        if (String(a.id_aula) === String(valorSeleccionadoPrevio)) {
            opt.selected = true;
        }
        selectAula.appendChild(opt);
    });

    alCambiarAulaModal(selectAula);
}

function abrirModalNuevoNodo() {
    nodoEditandoActual = null;
    document.getElementById('modal-nodo-titulo').textContent = 'Añadir Nodo de Aula LoRa';
    document.getElementById('modal_id_nodo').value = '';
    document.getElementById('modal_lora_red_id').value = '18';
    document.getElementById('modal_lora_id').value = siguienteLoraIdSugerido;
    document.getElementById('modal_room_id').value = '';

    const selBloque = document.getElementById('filtro_modal_bloque');
    const selPiso = document.getElementById('filtro_modal_piso');
    const inputBuscar = document.getElementById('filtro_modal_buscar');
    const chkSoloLibres = document.getElementById('filtro_solo_libres');
    if (selBloque) selBloque.value = '';
    if (selPiso) selPiso.value = '';
    if (inputBuscar) inputBuscar.value = '';
    if (chkSoloLibres) chkSoloLibres.checked = false;

    actualizarFiltrosModalCampus();
    filtrarAulasDisponiblesModal();

    document.getElementById('modal-nodo').classList.remove('hidden');
}

function abrirModalEditarNodo(idNodo) {
    const nodo = listaNodosCache.find(n => n.id_nodo == idNodo);
    if (!nodo) return;

    nodoEditandoActual = nodo;
    document.getElementById('modal-nodo-titulo').textContent = 'Editar Nodo de Aula LoRa';
    document.getElementById('modal_id_nodo').value = nodo.id_nodo;
    document.getElementById('modal_lora_red_id').value = nodo.lora_red_id || 18;
    document.getElementById('modal_lora_id').value = nodo.lora_id;
    document.getElementById('modal_room_id').value = nodo.room_id || '';

    const selBloque = document.getElementById('filtro_modal_bloque');
    const selPiso = document.getElementById('filtro_modal_piso');
    const inputBuscar = document.getElementById('filtro_modal_buscar');
    const chkSoloLibres = document.getElementById('filtro_solo_libres');
    if (selBloque) selBloque.value = '';
    if (selPiso) selPiso.value = '';
    if (inputBuscar) inputBuscar.value = '';
    if (chkSoloLibres) chkSoloLibres.checked = false;

    actualizarFiltrosModalCampus();
    filtrarAulasDisponiblesModal();

    const select = document.getElementById('modal_id_aula');
    select.value = nodo.id_aula;
    alCambiarAulaModal(select);

    document.getElementById('modal-nodo').classList.remove('hidden');
}

function alCambiarAulaModal(selectElem) {
    const opt = selectElem.selectedOptions[0];
    const inputRoom = document.getElementById('modal_room_id');
    const aviso = document.getElementById('aviso-aula-asignada');
    const avisoTexto = document.getElementById('aviso-aula-texto');

    if (opt && opt.dataset && opt.dataset.codigo) {
        inputRoom.value = opt.dataset.codigo;
        inputRoom.classList.add('bg-emerald-100');
        setTimeout(() => inputRoom.classList.remove('bg-emerald-100'), 400);

        if (opt.dataset.asignado && (!nodoEditandoActual || nodoEditandoActual.id_nodo != opt.dataset.asignado)) {
            if (aviso && avisoTexto) {
                avisoTexto.textContent = `Esta aula ya estaba vinculada al Nodo LoRa #${opt.dataset.loraId || opt.dataset.asignado}. Al guardar este formulario, se reasignará automáticamente a este nodo.`;
                aviso.classList.remove('hidden');
            }
        } else {
            if (aviso) aviso.classList.add('hidden');
        }
    } else {
        if (!nodoEditandoActual) {
            inputRoom.value = '';
        }
        if (aviso) aviso.classList.add('hidden');
    }
}

function cerrarModalNodo() {
    document.getElementById('modal-nodo').classList.add('hidden');
}

async function guardarNodo() {
    const btn = document.getElementById('btn-guardar-nodo');
    btn.disabled = true;

    const payload = {
        id_nodo: document.getElementById('modal_id_nodo').value.trim(),
        id_aula: document.getElementById('modal_id_aula').value.trim(),
        lora_red_id: document.getElementById('modal_lora_red_id').value.trim(),
        lora_id: document.getElementById('modal_lora_id').value.trim(),
        room_id: document.getElementById('modal_room_id').value.trim()
    };

    try {
        const resp = await fetch(API_NODOS_GUARDAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const res = await resp.json();

        if (res.status === 'success') {
            mostrarAlerta('success', res.message || 'Nodo LoRa guardado con éxito.');
            cerrarModalNodo();
            cargarNodosLora();
        } else {
            if (window.Toast) Toast.error(res.message || 'Error al guardar el nodo');
            else alert(res.message || 'Error al guardar el nodo');
        }
    } catch (e) {
        if (window.Toast) Toast.error('Fallo de comunicación: ' + e.message);
        else alert('Fallo de comunicación: ' + e.message);
    } finally {
        btn.disabled = false;
    }
}

async function eliminarNodo(idNodo, nombreAula) {
    const confirmacion = async () => {
        if (window.SISCO && typeof window.SISCO.confirm === 'function') {
            return await window.SISCO.confirm({
                title: '¿Desvincular nodo LoRa?',
                message: `¿Estás seguro de que deseas desvincular el nodo LoRa de "${nombreAula}"?`,
                confirmText: 'Sí, desvincular',
                cancelText: 'Cancelar',
                type: 'danger'
            });
        }
        return confirm(`¿Estás seguro de que deseas desvincular el nodo LoRa de "${nombreAula}"?`);
    };

    const deseaEliminar = await confirmacion();
    if (!deseaEliminar) return;

    try {
        const resp = await fetch(API_NODOS_ELIMINAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_nodo: idNodo })
        });
        const res = await resp.json();

        if (res.status === 'success') {
            mostrarAlerta('success', res.message || 'Nodo desvinculado correctamente.');
            cargarNodosLora();
        } else {
            mostrarAlerta('error', res.message || 'No se pudo desvincular el nodo.');
        }
    } catch (e) {
        mostrarAlerta('error', 'Error al desvincular nodo: ' + e.message);
    }
}

async function ejecutarSincronizacion(direccion) {
    const btn = document.getElementById(direccion === 'subir' ? 'btn-sync-subir' : 'btn-sync-bajar');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.classList.add('opacity-70', 'cursor-wait');
    btn.innerHTML = `<span class="inline-block animate-spin mr-2">⏳</span> Procesando...`;

    try {
        const resp = await fetch(BASE_URL + 'src/api/Sincronizacion/sincronizar_nube.php?direccion=' + direccion);
        const res = await resp.json();

        if (res.status === 'success') {
            let resumenTexto = '';
            if (res.data && res.data.resumen) {
                const items = Object.entries(res.data.resumen).filter(([_, count]) => count > 0);
                if (items.length > 0) {
                    resumenTexto = items.map(([tabla, count]) => `${tabla}: ${count}`).join(', ');
                } else {
                    resumenTexto = 'No hubo nuevos registros por transferir.';
                }
            }
            mostrarAlerta('success', res.message + (resumenTexto ? ` Detalle: ${resumenTexto}` : ''));
        } else {
            mostrarAlerta('error', res.message || 'Error durante la sincronización');
        }
    } catch (e) {
        mostrarAlerta('error', 'Fallo de red o servidor: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.classList.remove('opacity-70', 'cursor-wait');
        btn.innerHTML = originalText;
    }
}

async function cambiarEntorno(modo) {
    try {
        const resp = await fetch(BASE_URL + 'src/api/Configuracion/cambiar_entorno.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ modo: modo })
        });
        const res = await resp.json();
        if (res.status === 'success') {
            mostrarAlerta('success', res.message);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            mostrarAlerta('error', res.message || 'Error al cambiar entorno');
        }
    } catch (e) {
        mostrarAlerta('error', 'Error: ' + e.message);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    cargarConfiguraciones();
    cargarNodosLora();

    const ipGw = document.getElementById('ip_gateway');
    if (ipGw) {
        ipGw.addEventListener('input', () => {
            const btnPortal = document.getElementById('btn-portal-gateway');
            if (btnPortal) {
                const ip = ipGw.value.trim();
                btnPortal.href = ip ? `http://${ip}/` : '#';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
