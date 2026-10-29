<!-- Submodal Accesible: Diagnóstico del Sistema y Estado IoT -->
<div id="modal-diagnostico-dashboard" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-diag-titulo">
    <div class="relative w-full max-w-2xl max-h-[90vh] bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/80">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 flex items-center justify-center text-2xl font-bold shadow-xs shrink-0">
                    🔍
                </div>
                <div>
                    <h2 id="modal-diag-titulo" class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Diagnóstico del Sistema & Conectividad
                    </h2>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-0.5">
                        Estado operativo de la base de datos, sesión y enlace IoT
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalDiagnostico()" class="w-12 h-12 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800 flex items-center justify-center text-2xl font-bold transition focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <!-- Contenido -->
        <div class="p-6 sm:p-8 space-y-6 overflow-y-auto flex-1">
            
            <!-- Base de Datos -->
            <div class="p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border-2 border-emerald-200 dark:border-emerald-800/70 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 flex items-center justify-center text-xl shrink-0 font-bold">
                    ✓
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Base de Datos PostgreSQL (Supabase)</h3>
                    <p class="text-base text-slate-700 dark:text-slate-300 mt-1 leading-relaxed">
                        Conexión activa y autenticada. Transacciones PDO con sentencias preparadas y restricciones CHECK de integridad biométrica DY50 activas.
                    </p>
                </div>
            </div>

            <!-- Hardware y Nodos IoT con Diagnóstico en Tiempo Real -->
            <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border-2 border-slate-200 dark:border-slate-700/80 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-300 flex items-center justify-center text-xl shrink-0 font-bold">
                            📡
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Nodos de Hardware e Infraestructura IoT</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Estado de comunicación con los microcontroladores ESP32 en red local</p>
                        </div>
                    </div>
                    <button type="button" id="btn-diag-test-todos" onclick="ejecutarDiagnosticoHardwareModal()" class="min-h-[42px] px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl shadow-xs transition flex items-center justify-center gap-2 cursor-pointer self-start sm:self-auto">
                        <span>⚡</span>
                        <span>Probar Conexiones Ahora</span>
                    </button>
                </div>

                <!-- Tarjeta 1: ESP32 Registrador -->
                <div id="card-diag-registrador" class="p-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 transition-all space-y-2">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">👆</span>
                            <span class="font-extrabold text-base text-slate-900 dark:text-white">ESP32 Registrador (DY50 Secretaría)</span>
                        </div>
                        <span id="badge-diag-registrador" class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700">
                            Pendiente de prueba
                        </span>
                    </div>
                    <div class="text-sm font-mono text-slate-600 dark:text-slate-400">
                        IP configurada: <strong class="text-slate-900 dark:text-white font-bold" id="diag-ip-reg"><?= defined('IP_ESP32_REGISTRADOR') ? IP_ESP32_REGISTRADOR : '192.168.100.39' ?></strong>:80
                    </div>
                    <div id="msg-diag-registrador" class="text-sm font-medium text-slate-500 dark:text-slate-400">
                        Haga clic en "Probar Conexiones Ahora" para verificar si el nodo está encendido y accesible.
                    </div>
                </div>

                <!-- Tarjeta 2: ESP32 Gateway LoRa -->
                <div id="card-diag-gateway" class="p-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 transition-all space-y-2">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">📶</span>
                            <span class="font-extrabold text-base text-slate-900 dark:text-white">ESP32 Gateway LoRa (Concentrador RYLR998)</span>
                        </div>
                        <span id="badge-diag-gateway" class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700">
                            Pendiente de prueba
                        </span>
                    </div>
                    <div class="text-sm font-mono text-slate-600 dark:text-slate-400">
                        IP configurada: <strong class="text-slate-900 dark:text-white font-bold" id="diag-ip-gw"><?= defined('IP_ESP32_GATEWAY') ? IP_ESP32_GATEWAY : '192.168.100.137' ?></strong>:80
                    </div>
                    <div id="msg-diag-gateway" class="text-sm font-medium text-slate-500 dark:text-slate-400">
                        Haga clic en "Probar Conexiones Ahora" para verificar si el concentrador LoRa está en línea.
                    </div>
                </div>

                <div class="pt-1 flex justify-end">
                    <a href="<?= base_url('mvc/views/configuracion/index.php') ?>" class="inline-flex items-center gap-1.5 text-sm font-bold text-blue-700 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 hover:underline">
                        <span>Configurar parámetros de red y nodos LoRa</span>
                        <span>→</span>
                    </a>
                </div>
            </div>

            <!-- Información de Sesión -->
            <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border-2 border-slate-200 dark:border-slate-700/80 space-y-3">
                <h3 class="text-base font-extrabold uppercase tracking-wider text-slate-600 dark:text-slate-400">Sesión Activa</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-base">
                    <div>
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Usuario:</span>
                        <strong class="text-slate-900 dark:text-white ml-1"><?= htmlspecialchars($_SESSION['usuario'] ?? 'Invitado', ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Rol:</span>
                        <span class="px-2.5 py-0.5 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-300 font-bold text-sm ml-1 border border-emerald-200 dark:border-emerald-800">
                            <?= htmlspecialchars($_SESSION['rol'] ?? 'Invitado', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Fecha del Servidor:</span>
                        <strong class="text-slate-900 dark:text-white ml-1"><?= date('d/m/Y') ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-500 dark:text-slate-400 font-medium">Hora Local:</span>
                        <strong class="text-slate-900 dark:text-white ml-1"><?= date('H:i') ?> hs</strong>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="p-5 sm:p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 flex justify-end">
            <button type="button" onclick="cerrarModalDiagnostico()" class="min-h-[48px] px-8 text-base font-bold rounded-xl border border-slate-300 dark:border-slate-700 bg-white hover:bg-slate-100 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 transition-colors cursor-pointer">
                Cerrar Diagnóstico
            </button>
        </div>

    </div>
</div>

<script>
function abrirModalDiagnostico() {
    const modal = document.getElementById('modal-diagnostico-dashboard');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
}

function cerrarModalDiagnostico() {
    const modal = document.getElementById('modal-diagnostico-dashboard');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

async function ejecutarDiagnosticoHardwareModal() {
    const btn = document.getElementById('btn-diag-test-todos');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>⏳</span><span>Probando...</span>';
    }

    const testNodo = async (tipo, cardId, badgeId, msgId) => {
        const card = document.getElementById(cardId);
        const badge = document.getElementById(badgeId);
        const msg = document.getElementById(msgId);

        if (badge) {
            badge.className = "px-3 py-1 rounded-full text-xs font-mono font-bold bg-blue-100 text-blue-900 border border-blue-300 animate-pulse";
            badge.textContent = "Conectando...";
        }
        if (msg) {
            msg.className = "text-sm font-medium text-slate-500 dark:text-slate-400";
            msg.textContent = "Verificando disponibilidad de socket en puerto 80...";
        }

        try {
            const baseUrl = (window.BASE_URL || '/').replace(/\/+$/, '');
            const resp = await fetch(`${baseUrl}/src/api/Configuracion/probar_conexion.php?tipo=${encodeURIComponent(tipo)}`);
            const res = await resp.json();

            const estaOnline = Boolean(res.online === true || (res.status === 'success' && res.data?.online === true));

            if (estaOnline) {
                const latencia = (res.latencia_ms || res.data?.latencia_ms) ? ` (${res.latencia_ms || res.data?.latencia_ms} ms)` : '';
                if (card) {
                    card.className = "p-4 rounded-xl border-2 border-emerald-400 dark:border-emerald-700 bg-emerald-50/70 dark:bg-emerald-950/30 transition-all space-y-2";
                }
                if (badge) {
                    badge.className = "px-3 py-1 rounded-full text-xs font-mono font-black bg-emerald-100 text-emerald-950 dark:bg-emerald-900/60 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-700";
                    badge.innerHTML = `🟢 ONLINE${latencia}`;
                }
                if (msg) {
                    msg.className = "text-sm font-semibold text-emerald-900 dark:text-emerald-200";
                    msg.textContent = res.message || 'Nodo activo y respondiendo.';
                }
            } else {
                if (card) {
                    card.className = "p-4 rounded-xl border-2 border-rose-400 dark:border-rose-700 bg-rose-50/70 dark:bg-rose-950/30 transition-all space-y-2";
                }
                if (badge) {
                    badge.className = "px-3 py-1 rounded-full text-xs font-mono font-black bg-rose-100 text-rose-950 dark:bg-rose-900/60 dark:text-rose-200 border border-rose-300 dark:border-rose-700";
                    badge.innerHTML = "🔴 OFFLINE";
                }
                if (msg) {
                    msg.className = "text-sm font-medium text-rose-900 dark:text-rose-200";
                    msg.textContent = res.message || 'Dispositivo fuera de línea o sin respuesta.';
                }
            }
        } catch (e) {
            if (card) {
                card.className = "p-4 rounded-xl border-2 border-rose-400 dark:border-rose-700 bg-rose-50/70 dark:bg-rose-950/30 transition-all space-y-2";
            }
            if (badge) {
                badge.className = "px-3 py-1 rounded-full text-xs font-mono font-black bg-rose-100 text-rose-950 dark:bg-rose-900/60 dark:text-rose-200 border border-rose-300 dark:border-rose-700";
                badge.innerHTML = "🔴 ERROR";
            }
            if (msg) {
                msg.className = "text-sm font-medium text-rose-900 dark:text-rose-200";
                msg.textContent = "Error al contactar la API: " + e.message;
            }
        }
    };

    await Promise.all([
        testNodo('registrador', 'card-diag-registrador', 'badge-diag-registrador', 'msg-diag-registrador'),
        testNodo('gateway', 'card-diag-gateway', 'badge-diag-gateway', 'msg-diag-gateway')
    ]);

    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<span>🔄</span><span>Repetir Prueba</span>';
    }
}
</script>
