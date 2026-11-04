<?php
declare(strict_types=1);
?>
<!-- Submodal: Envío Selectivo de Huella a Aulas con Detección de Estado -->
<div id="modal-enviar-huella-aulas" class="app-modal modal-ficha-wrapper fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 lg:p-6 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="meha-titulo" onclick="if (event.target === this) cerrarModalEnviarHuellaAulas();">
    <div class="app-modal-dialog modal-biometria-wide bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-[96vw] sm:w-[92vw] lg:w-[80vw] max-w-[96vw] lg:max-w-[80vw] flex flex-col p-5 sm:p-6 lg:p-7 space-y-4 sm:space-y-5 max-h-[92vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Encabezado del Modal -->
        <div class="flex items-start justify-between gap-4 pb-3.5 border-b border-slate-200 dark:border-slate-800 shrink-0">
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-2xl font-bold border border-indigo-200 dark:border-indigo-800 shrink-0 shadow-2xs">
                    📡
                </div>
                <div class="min-w-0">
                    <h3 id="meha-titulo" class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight truncate">
                        Enviar Huella a Aulas (LoRa)
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-0.5 truncate">
                        Docente: <strong id="meha-nombre-docente" class="text-slate-900 dark:text-white font-bold">---</strong>
                        (CI: <span id="meha-cedula-docente" class="font-mono text-indigo-600 dark:text-indigo-400 font-semibold">---</span>)
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalEnviarHuellaAulas()" class="min-w-[40px] min-h-[40px] rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-2xl font-bold p-1 cursor-pointer" aria-label="Cerrar ventana">
                ×
            </button>
        </div>

        <!-- Banner de Resumen de Sincronización -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center shrink-0">
            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500 block">Total Aulas</span>
                <span id="meha-total-aulas" class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-0.5 block">0</span>
            </div>
            <div class="p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 block">En Sensor DY50</span>
                <span id="meha-total-sincronizadas" class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5 block">0</span>
            </div>
            <div class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800">
                <span class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400 block">En Cola LoRa</span>
                <span id="meha-total-pendientes" class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400 mt-0.5 block">0</span>
            </div>
            <div class="p-3 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800">
                <span class="text-xs font-bold uppercase tracking-wider text-rose-700 dark:text-rose-400 block">Faltantes</span>
                <span id="meha-total-faltantes" class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 mt-0.5 block">0</span>
            </div>
        </div>

        <!-- Barra de Acciones y Selección Rápida -->
        <div class="flex flex-wrap items-center justify-between gap-3 pt-1 shrink-0">
            <div class="flex items-center gap-2">
                <button type="button" onclick="marcarTodasAulasEnvio(true)" class="btn btn-secondary text-xs sm:text-sm font-bold py-1.5 px-3 min-h-[36px] cursor-pointer">
                    Seleccionar Todas
                </button>
                <button type="button" onclick="marcarTodasAulasEnvio(false)" class="btn btn-secondary text-xs sm:text-sm font-bold py-1.5 px-3 min-h-[36px] cursor-pointer">
                    Deseleccionar
                </button>
                <button type="button" onclick="seleccionarSoloFaltantesAulas()" class="btn btn-secondary text-xs sm:text-sm font-bold py-1.5 px-3 min-h-[36px] text-indigo-700 dark:text-indigo-300 cursor-pointer">
                    Solo Faltantes
                </button>
            </div>

            <label class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                <input type="checkbox" id="meha-check-forzar" class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                <span>Forzar reenvío a aulas ya sincronizadas</span>
            </label>
        </div>

        <!-- Lista de Aulas con Checkboxes -->
        <div class="space-y-2 max-h-[340px] overflow-y-auto pr-1">
            <div id="meha-lista-aulas-contenedor" class="divide-y divide-slate-100 dark:divide-slate-800 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
                <div class="p-8 text-center text-base text-slate-500 font-semibold">
                    Cargando aulas asignadas y estado biométrico...
                </div>
            </div>
        </div>

        <!-- Feedback de Envío / Spinner -->
        <div id="meha-feedback-box" class="hidden p-3.5 rounded-2xl text-center space-y-2">
            <div id="meha-feedback-spinner" class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-indigo-600 border-t-transparent mx-auto"></div>
            <p id="meha-feedback-msg" class="text-sm font-bold text-slate-800 dark:text-slate-200 whitespace-pre-line"></p>
        </div>

        <!-- Botones Inferiores -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3.5 border-t border-slate-200 dark:border-slate-800 shrink-0">
            <button type="button" onclick="cerrarModalEnviarHuellaAulas()" class="btn btn-secondary text-sm sm:text-base font-semibold py-2.5 px-5 min-h-[44px] w-full sm:w-auto cursor-pointer">
                Cancelar
            </button>
            <button type="button" id="meha-btn-enviar-seleccionadas" onclick="ejecutarEnvioAulasSeleccionadas()" class="btn btn-primary text-sm sm:text-base font-bold py-2.5 px-6 min-h-[44px] w-full sm:w-auto inline-flex items-center justify-center gap-2 shadow-sm cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                <span>Transmitir a Aulas Seleccionadas</span>
            </button>
        </div>

    </div>
</div>
