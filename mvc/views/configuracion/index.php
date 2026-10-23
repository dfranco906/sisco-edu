<?php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/middleware/auth.php';

// RBAC: Solo SuperAdmin y Administración pueden acceder a la configuración del sistema
verificarRol(['SuperAdmin', 'Administracion', 'Administrador']);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="flex-1 p-6 md:p-10">
    <div class="app-page-header p-6 mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-700 text-sm font-bold">⚙️</span>
                <h2 class="text-2xl font-bold">Configuraci&oacute;n del Sistema</h2>
            </div>
            <p class="mt-1">Administraci&oacute;n din&aacute;mica de direcciones IP, par&aacute;metros de red IoT y conectividad en la nube.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="btn btn-secondary text-sm">
                ← Volver a Administraci&oacute;n
            </a>
            <button type="button" id="btn-guardar-todo" class="btn btn-primary text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>Guardar Cambios</span>
            </button>
        </div>
    </div>

    <!-- Mensaje de Alerta General -->
    <div id="alerta-global" class="hidden mb-6 p-4 rounded-xl border text-sm flex items-start gap-3 transition-all duration-200">
        <span id="alerta-icono" class="text-lg leading-none"></span>
        <div class="flex-1" id="alerta-texto"></div>
        <button type="button" onclick="cerrarAlerta()" class="text-gray-400 hover:text-gray-700 font-bold">&times;</button>
    </div>

    <form id="form-configuracion" onsubmit="event.preventDefault(); guardarConfiguraciones();">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- PANEL 1: Nodos IoT Locales (Hardware) -->
            <div class="app-panel p-6">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <span>📡</span> Nodos IoT Locales (ESP32)
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Dispositivos f&iacute;sicos en red local escolar.</p>
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Hardware
                    </span>
                </div>

                <!-- ESP32 Registrador -->
                <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="flex items-center justify-between mb-2">
                        <label for="ip_registrador" class="font-semibold text-sm text-slate-800">
                            IP ESP32 Registrador (Sensor DY50)
                        </label>
                        <button type="button" onclick="probarConexion('registrador')" id="btn-test-registrador" class="text-xs btn btn-secondary py-1 px-2.5">
                            Probar Conexi&oacute;n
                        </button>
                    </div>
                    <input type="text" id="ip_registrador" name="ip_registrador" class="app-input w-full font-mono text-sm" placeholder="192.168.100.125" required>
                    <p class="text-xs text-slate-500 mt-2">
                        Direcci&oacute;n IP asignada por DHCP o est&aacute;tica al ESP32 que enrola huellas biom&eacute;tricas en Secretar&iacute;a.
                    </p>
                    <div id="status-registrador" class="hidden mt-3 text-xs p-2 rounded-lg border font-mono"></div>
                </div>

                <!-- ESP32 Gateway LoRa -->
                <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div class="flex items-center justify-between mb-2">
                        <label for="ip_gateway" class="font-semibold text-sm text-slate-800">
                            IP ESP32 Gateway (LoRa RYLR998)
                        </label>
                        <div class="flex items-center gap-1.5">
                            <a id="btn-portal-gateway" href="http://192.168.100.17/" target="_blank" class="text-xs btn btn-secondary py-1 px-2.5 flex items-center gap-1" title="Abrir portal web embebido del Gateway">
                                🌐 Portal Web
                            </a>
                            <button type="button" onclick="probarConexion('gateway')" id="btn-test-gateway" class="text-xs btn btn-secondary py-1 px-2.5">
                                Probar Ping
                            </button>
                        </div>
                    </div>
                    <input type="text" id="ip_gateway" name="ip_gateway" class="app-input w-full font-mono text-sm" placeholder="192.168.100.17" required>
                    <p class="text-xs text-slate-500 mt-2">
                        Direcci&oacute;n IP del nodo Gateway que reenv&iacute;a plantillas y datos hacia las aulas por radiofrecuencia.
                    </p>
                    <div id="status-gateway" class="hidden mt-3 text-xs p-2 rounded-lg border font-mono"></div>
                </div>

                <!-- URL Sync Gateway -->
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                    <label for="gateway_sync_url" class="font-semibold text-sm text-slate-800 block mb-2">
                        URL Webhook de Sincronizaci&oacute;n Gateway
                    </label>
                    <input type="url" id="gateway_sync_url" name="gateway_sync_url" class="app-input w-full font-mono text-sm" placeholder="http://192.168.100.17/sync">
                    <p class="text-xs text-slate-500 mt-2">
                        Endpoint HTTP al cual el backend despacha la orden de retransmisi&oacute;n LoRa inmediata.
                    </p>
                </div>
            </div>

            <!-- PANEL 2: Servidor Central & Enlace LoRa -->
            <div class="app-panel p-6">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <span>🖥️</span> Servidor Central & Par&aacute;metros de Enlace
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Identificaci&oacute;n del host y llaves de autorizaci&oacute;n.</p>
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                        Red & Seguridad
                    </span>
                </div>

                <!-- IP o Dominio del Servidor -->
                <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                    <label for="ip_servidor" class="font-semibold text-sm text-slate-800 block mb-2">
                        IP o Hostname del Servidor Backend
                    </label>
                    <input type="text" id="ip_servidor" name="ip_servidor" class="app-input w-full font-mono text-sm" placeholder="192.168.100.109" required>
                    <p class="text-xs text-slate-500 mt-2">
                        IP local del servidor o nombre de dominio si se publica v&iacute;a Cloudflare Tunnel o hosting remoto.
                    </p>
                </div>

                <!-- URL Base del Servidor -->
                <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                    <label for="url_servidor" class="font-semibold text-sm text-slate-800 block mb-2">
                        URL Base Forzada (Opcional)
                    </label>
                    <input type="text" id="url_servidor" name="url_servidor" class="app-input w-full font-mono text-sm" placeholder="Dejar en blanco para autodetectar autom&aacute;ticamente">
                    <p class="text-xs text-slate-500 mt-2">
                        Si est&aacute; vac&iacute;o, el sistema autocalcula la ruta mediante la funci&oacute;n <code>base_url()</code> din&aacute;mica.
                    </p>
                </div>

                <!-- Clave Secreta API Gateway -->
                <div class="mb-6 p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                    <label for="gateway_api_key" class="font-semibold text-sm text-slate-800 block mb-2">
                        Clave de Autorizaci&oacute;n Gateway (X-GATEWAY-KEY)
                    </label>
                    <div class="relative">
                        <input type="password" id="gateway_api_key" name="gateway_api_key" class="app-input w-full font-mono text-sm pr-10" placeholder="Clave secreta del Gateway">
                        <button type="button" onclick="togglePasswordVisibility('gateway_api_key')" class="absolute right-3 top-3 text-slate-400 hover:text-slate-600 text-xs">
                            👁️
                        </button>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">
                        Token enviado en la cabecera HTTP para proteger el endpoint contra peticiones maliciosas o no autorizadas.
                    </p>
                </div>

                <!-- Parámetros Radio LoRa -->
                <div class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200/80">
                    <div>
                        <label for="lora_red_id" class="font-semibold text-xs text-slate-800 block mb-1">
                            LoRa Red ID (Network)
                        </label>
                        <input type="number" id="lora_red_id" name="lora_red_id" min="1" max="255" class="app-input w-full font-mono text-sm" placeholder="18">
                    </div>
                    <div>
                        <label for="lora_id_gateway" class="font-semibold text-xs text-slate-800 block mb-1">
                            LoRa Gateway Addr
                        </label>
                        <input type="number" id="lora_id_gateway" name="lora_id_gateway" min="1" max="65535" class="app-input w-full font-mono text-sm" placeholder="100">
                    </div>
                </div>
            </div>
        </div>

        <!-- PANEL: GESTIÓN DE NODOS LoRa DE AULAS -->
        <div class="app-panel p-6 mt-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gray-100 mb-6 gap-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <span>📡</span> Nodos LoRa de Aulas Conectadas (RYLR998)
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Administración centralizada de nodos en aulas. Los datos aquí guardados son consultados dinámicamente por el Gateway y cargados a cada ESP32 vía su portal cautivo.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="cargarNodosLora()" class="btn btn-secondary text-xs py-1.5 px-3 flex items-center gap-1.5">
                        <span>🔄</span> Actualizar
                    </button>
                    <button type="button" onclick="abrirModalNuevoNodo()" class="btn btn-primary text-xs py-1.5 px-3 flex items-center gap-1.5">
                        <span>➕</span> Añadir Nodo de Aula
                    </button>
                </div>
            </div>

            <!-- Tabla de Nodos LoRa -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-slate-50">
                            <th class="py-3 px-4">Aula Vinculada</th>
                            <th class="py-3 px-4">Ubicación / Piso</th>
                            <th class="py-3 px-4 text-center">Red LoRa</th>
                            <th class="py-3 px-4 text-center">Dirección LoRa</th>
                            <th class="py-3 px-4 text-center">Código RF</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla-nodos-body" class="divide-y divide-gray-100">
                        <tr>
                            <td colspan="7" class="py-6 text-center text-gray-400 text-xs">
                                <span class="animate-spin inline-block mr-1">⏳</span> Cargando nodos LoRa...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 p-3 bg-amber-50/70 border border-amber-200/80 rounded-xl flex items-start gap-2.5 text-xs text-amber-800">
                <span class="text-base leading-none">💡</span>
                <div class="leading-relaxed">
                    <strong>Paso a paso para instalar un nuevo nodo de aula:</strong>
                    Primero registra el aula aquí asignándole un <strong>LoRa ID</strong> y un <strong>Código de Aula</strong>.
                    Luego, en el aula física, enciende el ESP32, conéctate con tu teléfono a su red Wi-Fi temporal <code>SISCO-AULA-CONFIG</code> e ingresa exactamente estos mismos parámetros en su portal cautivo seguro.
                </div>
            </div>
        </div>

        <!-- PANEL 3: Alta Disponibilidad & Estrategia Cloud -->
        <div class="app-panel p-6 mt-8">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <span>☁️</span> Estrategia de Alta Disponibilidad & Conectividad Remota
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        C&oacute;mo independizar el frontend y el gateway de cambios en la IP local del desarrollador o del colegio.
                    </p>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    Arquitectura
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Opción 1: Base de Datos en la Nube -->
                <div class="p-4 rounded-xl border border-indigo-100 bg-indigo-50/40">
                    <div class="flex items-center gap-2 text-indigo-900 font-bold text-sm mb-2">
                        <span>1.</span> Base de Datos Cloud (Aiven / Railway)
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-3">
                        Aloja MySQL en la nube para que m&uacute;ltiples laptops y el frontend consuman la misma base de datos sin depender de una m&aacute;quina local.
                    </p>
                    <div class="p-2.5 rounded bg-white border border-indigo-100 font-mono text-[11px] text-slate-700 space-y-1">
                        <div>// config.local.php</div>
                        <div>putenv('DB_HOST=mysql-cloud.aivencloud.com');</div>
                        <div>putenv('DB_PORT=25060');</div>
                        <div>putenv('DB_SSL=true');</div>
                    </div>
                </div>

                <!-- Opción 2: Cloudflare Tunnel / Ngrok -->
                <div class="p-4 rounded-xl border border-amber-100 bg-amber-50/40">
                    <div class="flex items-center gap-2 text-amber-900 font-bold text-sm mb-2">
                        <span>2.</span> T&uacute;nel HTTPS (Cloudflare Tunnel)
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-3">
                        Publica el servidor Apache local a trav&eacute;s de un dominio seguro con TLS sin abrir puertos en el router. El Gateway y el Frontend apuntan a este dominio fijo.
                    </p>
                    <div class="p-2.5 rounded bg-white border border-amber-100 font-mono text-[11px] text-slate-700 space-y-1">
                        <div>cloudflared tunnel run sisco-edu</div>
                        <div>URL: https://sisco.tu-dominio.com</div>
                        <div>&rarr; Cero cambios de IP al moverte</div>
                    </div>
                </div>

                <!-- Opción 3: Nombre Local mDNS -->
                <div class="p-4 rounded-xl border border-emerald-100 bg-emerald-50/40">
                    <div class="flex items-center gap-2 text-emerald-900 font-bold text-sm mb-2">
                        <span>3.</span> Resoluci&oacute;n Local por mDNS (.local)
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed mb-3">
                        Para pruebas locales sin Internet, los ESP32 pueden resolver el servidor v&iacute;a mDNS (<code>http://sisco-servidor.local</code>) sin importar la IP que el router DHCP entregue.
                    </p>
                    <div class="p-2.5 rounded bg-white border border-emerald-100 font-mono text-[11px] text-slate-700 space-y-1">
                        <div>WiFi.setHostname("sisco-registrador");</div>
                        <div>MDNS.begin("sisco-servidor");</div>
                        <div>&rarr; Independiente de la subred</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción al Pie -->
        <div class="mt-8 flex items-center justify-end gap-4">
            <button type="button" onclick="cargarConfiguraciones()" class="btn btn-secondary text-sm">
                Descartar / Recargar
            </button>
            <button type="submit" class="btn btn-primary text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span>Guardar Par&aacute;metros</span>
            </button>
        </div>
    </form>

    <!-- MODAL AÑADIR / EDITAR NODO LORA -->
    <div id="modal-nodo" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 border border-slate-200 relative">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                <h3 id="modal-nodo-titulo" class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <span>📡</span> Añadir Nodo de Aula LoRa
                </h3>
                <button type="button" onclick="cerrarModalNodo()" class="text-gray-400 hover:text-gray-600 font-bold text-lg leading-none">&times;</button>
            </div>

            <form id="form-nodo" onsubmit="event.preventDefault(); guardarNodo();">
                <input type="hidden" id="modal_id_nodo" value="">

                <!-- PANEL DE FILTROS INTUITIVOS PARA INSTITUCIONES CON MUCHAS AULAS -->
                <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-xl mb-4 space-y-2.5">
                    <div class="text-[11px] font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <span>🔍</span> Filtros Rápidos de Aula
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-0.5">Bloque / Edificio</label>
                            <select id="filtro_modal_bloque" onchange="filtrarAulasDisponiblesModal()" class="app-input w-full text-xs py-1.5 px-2">
                                <option value="">Todos los bloques</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-0.5">Piso / Nivel</label>
                            <select id="filtro_modal_piso" onchange="filtrarAulasDisponiblesModal()" class="app-input w-full text-xs py-1.5 px-2">
                                <option value="">Todos los pisos</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <input type="text" id="filtro_modal_buscar" oninput="filtrarAulasDisponiblesModal()" placeholder="Buscar aula por nombre o código (ej. BTI, A-P1, 7MO)..." class="app-input w-full text-xs py-1.5 px-2.5">
                    </div>
                </div>

                <!-- Aula Física a Vincular -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1">
                        <label for="modal_id_aula" class="font-semibold text-xs text-slate-700">
                            Aula Física a Vincular *
                        </label>
                        <span id="texto-aulas-disponibles-count" class="text-[11px] text-slate-400">0 disponibles</span>
                    </div>
                    <select id="modal_id_aula" class="app-input w-full text-sm font-medium" required onchange="alCambiarAulaModal(this)">
                        <option value="">-- Selecciona el aula --</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <!-- Red LoRa -->
                    <div>
                        <label for="modal_lora_red_id" class="font-semibold text-xs text-slate-700 block mb-1">
                            Red LoRa (Network ID) *
                        </label>
                        <input type="number" id="modal_lora_red_id" min="1" max="255" value="18" class="app-input w-full font-mono text-sm" required>
                        <p class="text-[11px] text-slate-500 mt-1">Debe coincidir con la red del Gateway (18).</p>
                    </div>

                    <!-- Dirección LoRa -->
                    <div>
                        <label for="modal_lora_id" class="font-semibold text-xs text-slate-700 block mb-1">
                            Dirección LoRa (Addr) *
                        </label>
                        <input type="number" id="modal_lora_id" min="1" max="65535" class="app-input w-full font-mono text-sm" placeholder="111" required>
                        <p class="text-[11px] text-slate-500 mt-1">Dirección única RYLR998 para esta aula.</p>
                    </div>
                </div>

                <!-- Código RF del Aula (Autocompletado) -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1">
                        <label for="modal_room_id" class="font-semibold text-xs text-slate-700">
                            Código RF de Aula (Filtro Antena y OLED) *
                        </label>
                        <span id="badge-auto-rf" class="text-[10px] text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded font-bold border border-emerald-200">
                            ⚡ Autocompletado del Aula
                        </span>
                    </div>
                    <input type="text" id="modal_room_id" class="app-input w-full font-mono text-sm font-bold text-indigo-700 bg-slate-50" placeholder="ej. A-P1-01" required readonly>
                    <p class="text-[11px] text-slate-500 mt-1">Heredado del aula para garantizar sincronización perfecta entre base de datos y antena.</p>
                </div>

                <div class="p-3 bg-blue-50 border border-blue-200/80 rounded-xl text-[11px] text-blue-800 mb-5 leading-relaxed">
                    ℹ️ <strong>Importante:</strong> Al guardar este nodo, el Gateway lo incluirá automáticamente en las sincronizaciones biométricas y la antena filtrará paquetes usando este código de aula exacto.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" onclick="cerrarModalNodo()" class="btn btn-secondary text-xs py-2 px-3">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-guardar-nodo" class="btn btn-primary text-xs py-2 px-4 flex items-center gap-1.5 font-bold">
                        <span>💾</span> Guardar Nodo
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

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

    contenedor.className = "mb-6 p-4 rounded-xl border text-sm flex items-start gap-3 transition-all duration-200";

    if (tipo === 'success') {
        contenedor.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-800');
        icono.textContent = "✅";
    } else if (tipo === 'error') {
        contenedor.classList.add('bg-rose-50', 'border-rose-200', 'text-rose-800');
        icono.textContent = "❌";
    } else {
        contenedor.classList.add('bg-blue-50', 'border-blue-200', 'text-blue-800');
        icono.textContent = "ℹ️";
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
            // Actualizar la variable de entorno JS en la página viva
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

document.getElementById('btn-guardar-todo').addEventListener('click', () => {
    guardarConfiguraciones();
});

async function probarConexion(tipo) {
    const ipInput = document.getElementById(tipo === 'registrador' ? 'ip_registrador' : 'ip_gateway');
    const statusDiv = document.getElementById(tipo === 'registrador' ? 'status-registrador' : 'status-gateway');
    const btn = document.getElementById(tipo === 'registrador' ? 'btn-test-registrador' : 'btn-test-gateway');

    const ip = ipInput ? ipInput.value.trim() : '';

    statusDiv.className = "mt-3 text-xs p-2.5 rounded-lg border font-mono bg-blue-50 text-blue-700 border-blue-200 block";
    statusDiv.innerHTML = `<span class="inline-block animate-spin mr-1">⏳</span> Conectando con ${ip || 'dispositivo'}...`;
    btn.disabled = true;

    try {
        const url = `${API_TEST}?tipo=${encodeURIComponent(tipo)}&ip=${encodeURIComponent(ip)}`;
        const resp = await fetch(url);
        const res = await resp.json();

        if (res.status === 'success') {
            const latencia = res.data?.latencia_ms ? ` (${res.data.latencia_ms} ms)` : '';
            statusDiv.className = "mt-3 text-xs p-2.5 rounded-lg border font-mono bg-emerald-50 text-emerald-800 border-emerald-200 block";
            statusDiv.innerHTML = `🟢 <strong>ONLINE${latencia}:</strong> ${res.message}`;
        } else {
            statusDiv.className = "mt-3 text-xs p-2.5 rounded-lg border font-mono bg-rose-50 text-rose-800 border-rose-200 block";
            statusDiv.innerHTML = `🔴 <strong>OFFLINE:</strong> ${res.message}`;
        }
    } catch (e) {
        statusDiv.className = "mt-3 text-xs p-2.5 rounded-lg border font-mono bg-rose-50 text-rose-800 border-rose-200 block";
        statusDiv.innerHTML = `🔴 <strong>ERROR:</strong> ${e.message}`;
    } finally {
        btn.disabled = false;
    }
}

async function cargarNodosLora() {
    const tbody = document.getElementById('tabla-nodos-body');
    try {
        const resp = await fetch(API_NODOS_LISTAR);
        const res = await resp.json();

        if (res.status === 'success' && res.data) {
            listaNodosCache = res.data.nodos || [];
            aulasDisponiblesCache = res.data.aulas_disponibles || [];
            siguienteLoraIdSugerido = res.data.siguiente_lora_id || 101;
            renderizarTablaNodos(listaNodosCache);
            actualizarFiltrosModalCampus();
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="py-4 text-center text-rose-500 text-xs">${res.message || 'Error al cargar nodos'}</td></tr>`;
        }
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="7" class="py-4 text-center text-rose-500 text-xs">Error de conexión: ${e.message}</td></tr>`;
    }
}

function renderizarTablaNodos(nodos) {
    const tbody = document.getElementById('tabla-nodos-body');
    if (!nodos.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-gray-400 text-xs">No hay nodos LoRa registrados. Haz clic en "➕ Añadir Nodo de Aula" para vincular uno.</td></tr>`;
        return;
    }

    tbody.innerHTML = nodos.map(n => `
        <tr class="hover:bg-slate-50/70 transition-colors">
            <td class="py-3 px-4 font-semibold text-slate-800">
                ${n.nombre_aula ? `${n.nombre_aula} <span class="text-xs text-slate-400 font-normal">(${n.codigo_sala || n.room_id})</span>` : '<span class="text-slate-400 italic">Sin aula asignada</span>'}
            </td>
            <td class="py-3 px-4 text-slate-500 text-xs">
                ${n.ubicacion || n.piso || '-'}
            </td>
            <td class="py-3 px-4 text-center">
                <span class="inline-block px-2 py-0.5 rounded-full font-mono text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    Red ${n.lora_red_id || 18}
                </span>
            </td>
            <td class="py-3 px-4 text-center">
                <span class="inline-block px-2.5 py-0.5 rounded-full font-mono text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    ID: ${n.lora_id}
                </span>
            </td>
            <td class="py-3 px-4 text-center font-mono text-xs text-slate-700">
                <code class="px-1.5 py-0.5 bg-slate-100 rounded border border-slate-200">${n.room_id || n.codigo_sala || '-'}</code>
            </td>
            <td class="py-3 px-4 text-center">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Activo
                </span>
            </td>
            <td class="py-3 px-4 text-right space-x-1.5">
                <button type="button" onclick="abrirModalEditarNodo(${n.id_nodo})" class="text-xs btn btn-secondary py-1 px-2" title="Editar parámetros del nodo">
                    ✏️ Editar
                </button>
                <button type="button" onclick="eliminarNodo(${n.id_nodo}, '${n.nombre_aula || n.room_id}')" class="text-xs py-1 px-2 text-rose-600 hover:text-rose-800 font-semibold" title="Desvincular nodo">
                    🗑️ Desvincular
                </button>
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
    const selectAula = document.getElementById('modal_id_aula');
    const contador = document.getElementById('texto-aulas-disponibles-count');

    const fBloque = selBloque ? selBloque.value : '';
    const fPiso = selPiso ? selPiso.value : '';
    const fBuscar = inputBuscar ? inputBuscar.value.toLowerCase().trim() : '';

    let lista = [...aulasDisponiblesCache];

    // Si estamos editando, incluir el aula actualmente asignada
    if (nodoEditandoActual && nodoEditandoActual.id_aula) {
        const yaEsta = lista.some(a => a.id_aula == nodoEditandoActual.id_aula);
        if (!yaEsta) {
            lista.unshift({
                id_aula: nodoEditandoActual.id_aula,
                nombre: nodoEditandoActual.nombre_aula || `Aula ${nodoEditandoActual.id_aula}`,
                codigo: nodoEditandoActual.room_id || nodoEditandoActual.codigo_sala || '',
                codigo_sala: nodoEditandoActual.room_id || nodoEditandoActual.codigo_sala || '',
                bloque: nodoEditandoActual.bloque || 'Bloque A',
                piso: nodoEditandoActual.piso || '',
                esActual: true
            });
        }
    }

    const filtradas = lista.filter(a => {
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
        contador.textContent = `${filtradas.length} aulas disponibles`;
    }

    const valorSeleccionadoPrevio = selectAula.value;
    selectAula.innerHTML = '<option value="">-- Selecciona el aula física --</option>';

    if (!filtradas.length) {
        selectAula.innerHTML = '<option value="">Sin aulas para estos filtros</option>';
        document.getElementById('modal_room_id').value = '';
        return;
    }

    filtradas.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.id_aula;
        const cod = a.codigo || a.codigo_sala || '';
        opt.dataset.codigo = cod;
        opt.textContent = `[${cod || 'S/C'}] ${a.nombre} — ${a.bloque || ''} ${a.piso ? `(${a.piso})` : ''} ${a.esActual ? '★ Actual' : ''}`;
        if (String(a.id_aula) === String(valorSeleccionadoPrevio)) {
            opt.selected = true;
        }
        selectAula.appendChild(opt);
    });

    // Si solo hay un resultado con el filtro, seleccionarlo automáticamente
    if (filtradas.length === 1 && fBuscar.length >= 2) {
        selectAula.selectedIndex = 1;
        alCambiarAulaModal(selectAula);
    } else {
        alCambiarAulaModal(selectAula);
    }
}

function abrirModalNuevoNodo() {
    nodoEditandoActual = null;
    document.getElementById('modal-nodo-titulo').innerHTML = '<span>📡</span> Añadir Nodo de Aula LoRa';
    document.getElementById('modal_id_nodo').value = '';
    document.getElementById('modal_lora_red_id').value = '18';
    document.getElementById('modal_lora_id').value = siguienteLoraIdSugerido;
    document.getElementById('modal_room_id').value = '';

    const selBloque = document.getElementById('filtro_modal_bloque');
    const selPiso = document.getElementById('filtro_modal_piso');
    const inputBuscar = document.getElementById('filtro_modal_buscar');
    if (selBloque) selBloque.value = '';
    if (selPiso) selPiso.value = '';
    if (inputBuscar) inputBuscar.value = '';

    actualizarFiltrosModalCampus();
    filtrarAulasDisponiblesModal();

    document.getElementById('modal-nodo').classList.remove('hidden');
}

function abrirModalEditarNodo(idNodo) {
    const nodo = listaNodosCache.find(n => n.id_nodo == idNodo);
    if (!nodo) return;

    nodoEditandoActual = nodo;
    document.getElementById('modal-nodo-titulo').innerHTML = '<span>✏️</span> Editar Nodo de Aula LoRa';
    document.getElementById('modal_id_nodo').value = nodo.id_nodo;
    document.getElementById('modal_lora_red_id').value = nodo.lora_red_id || 18;
    document.getElementById('modal_lora_id').value = nodo.lora_id;
    document.getElementById('modal_room_id').value = nodo.room_id || '';

    const selBloque = document.getElementById('filtro_modal_bloque');
    const selPiso = document.getElementById('filtro_modal_piso');
    const inputBuscar = document.getElementById('filtro_modal_buscar');
    if (selBloque) selBloque.value = '';
    if (selPiso) selPiso.value = '';
    if (inputBuscar) inputBuscar.value = '';

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
    if (opt && opt.dataset && opt.dataset.codigo) {
        inputRoom.value = opt.dataset.codigo;
        inputRoom.classList.add('bg-emerald-50');
        setTimeout(() => inputRoom.classList.remove('bg-emerald-50'), 400);
    } else if (!opt || !opt.value) {
        if (!nodoEditandoActual) {
            inputRoom.value = '';
        }
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
            alert(res.message || 'Error al guardar el nodo');
        }
    } catch (e) {
        alert('Fallo de comunicación: ' + e.message);
    } finally {
        btn.disabled = false;
    }
}

async function eliminarNodo(idNodo, nombreAula) {
    if (!confirm(`¿Estás seguro de que deseas desvincular el nodo LoRa de "${nombreAula}"?`)) {
        return;
    }

    try {
        const resp = await fetch(API_NODOS_ELIMINAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_nodo: idNodo })
        });
        const res = await resp.json();

        if (res.status === 'success') {
            mostrarAlerta('success', res.message || 'Nodo desvinculado.');
            cargarNodosLora();
        } else {
            mostrarAlerta('error', res.message || 'No se pudo desvincular el nodo.');
        }
    } catch (e) {
        mostrarAlerta('error', 'Error al desvincular nodo: ' + e.message);
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
