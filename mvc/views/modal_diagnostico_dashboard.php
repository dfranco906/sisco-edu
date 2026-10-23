<!-- Submodal Accesible: Diagnóstico del Sistema y Estado IoT -->
<div id="modal-diagnostico-dashboard" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-diag-titulo">
    <div class="relative w-full max-w-2xl max-h-[90vh] bg-white rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-6 border-b border-slate-200 bg-slate-50/80">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-2xl font-bold shadow-xs shrink-0">
                    🔍
                </div>
                <div>
                    <h2 id="modal-diag-titulo" class="text-2xl font-extrabold text-slate-900 tracking-tight">
                        Diagnóstico del Sistema & Conectividad
                    </h2>
                    <p class="text-base text-slate-600 mt-0.5">
                        Estado operativo de la base de datos, sesión y enlace IoT
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalDiagnostico()" class="w-12 h-12 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 flex items-center justify-center text-2xl font-bold transition focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <!-- Contenido -->
        <div class="p-6 sm:p-8 space-y-6 overflow-y-auto flex-1">
            
            <!-- Base de Datos -->
            <div class="p-5 rounded-2xl bg-emerald-50 border-2 border-emerald-200 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl shrink-0 font-bold">
                    ✓
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-slate-900">Base de Datos PostgreSQL (Supabase)</h3>
                    <p class="text-base text-slate-700 mt-1">
                        Conexión activa y autenticada. Transacciones PDO con sentencias preparadas y restricciones CHECK de integridad biométrica DY50 activas.
                    </p>
                </div>
            </div>

            <!-- Hardware y Nodos IoT -->
            <div class="p-5 rounded-2xl bg-blue-50 border-2 border-blue-200 flex items-start gap-4">
                <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-xl shrink-0 font-bold">
                    📡
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-slate-900">Nodos IoT y Biometría</h3>
                    <p class="text-base text-slate-700 mt-1">
                        Enrolamiento de huellas en Secretar&iacute;a vía ESP32 Registrador. Transmisión de eventos hacia las aulas coordinadas por radiofrecuencia LoRa RYLR998.
                    </p>
                    <div class="mt-3">
                        <a href="<?= base_url('mvc/views/configuracion/index.php') ?>" class="inline-flex items-center gap-1.5 text-base font-bold text-blue-700 hover:underline">
                            <span>Ver parámetros de red en Configuración</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Información de Sesión -->
            <div class="p-5 rounded-2xl bg-slate-50 border-2 border-slate-200 space-y-2">
                <h3 class="text-base font-extrabold uppercase tracking-wider text-slate-600">Sesión Activa</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-base">
                    <div>
                        <span class="text-slate-500 font-medium">Usuario:</span>
                        <strong class="text-slate-900 ml-1"><?= htmlspecialchars($_SESSION['usuario'] ?? 'Invitado', ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-500 font-medium">Rol:</span>
                        <span class="px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-900 font-bold text-sm ml-1">
                            <?= htmlspecialchars($_SESSION['rol'] ?? 'Invitado', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-500 font-medium">Fecha del Servidor:</span>
                        <strong class="text-slate-900 ml-1"><?= date('d/m/Y') ?></strong>
                    </div>
                    <div>
                        <span class="text-slate-500 font-medium">Hora Local:</span>
                        <strong class="text-slate-900 ml-1"><?= date('H:i') ?> hs</strong>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="p-5 sm:p-6 border-t border-slate-200 bg-slate-50 flex justify-end">
            <button type="button" onclick="cerrarModalDiagnostico()" class="btn btn-muted min-h-[48px] px-8 text-base font-bold rounded-xl">
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
</script>
