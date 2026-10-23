<?php
declare(strict_types=1);
?>
<!-- Submodal 2: Asistente y Gestión de Huella Biométrica Estudiantil (ESP32 / DY50) -->
<div id="modal-biometria-estudiante" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="mbe-titulo">
    <div class="app-modal-dialog max-w-2xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 flex items-center justify-center text-3xl font-bold shrink-0 border border-blue-200 dark:border-blue-800">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.99 7.132"></path></svg>
                </div>
                <div>
                    <h3 id="mbe-titulo" class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Vinculación Biométrica del Estudiante
                    </h3>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-0.5">
                        Captura y registro de huella en el sensor registrador DY50 / AS608.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalBiometriaEstudiante()" class="app-modal-close text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl font-bold p-2" aria-label="Cerrar modal biometría">
                ×
            </button>
        </div>

        <!-- Ficha Resumida del Alumno -->
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                    Estudiante Seleccionado
                </span>
                <span id="mbe-nombre-estudiante" class="text-lg font-bold text-slate-900 dark:text-white block mt-0.5">
                    ---
                </span>
                <span class="text-base text-slate-600 dark:text-slate-300 font-mono">
                    CI: <strong id="mbe-cedula-estudiante" class="text-slate-900 dark:text-white font-bold">---</strong>
                    <span class="mx-1">•</span>
                    Curso: <strong id="mbe-grado-estudiante" class="text-blue-600 dark:text-blue-400 font-bold">---</strong>
                </span>
            </div>
            <div id="mbe-badge-estado-wrap">
                <span id="mbe-badge-estado" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-base font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    Consultando...
                </span>
            </div>
        </div>

        <!-- Instrucciones en Tipografía Grande (Mínimo 16px) -->
        <div class="space-y-3">
            <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-sm font-bold flex items-center justify-center shrink-0">i</span>
                <span>Pasos para la captura en el hardware:</span>
            </h4>
            
            <div class="grid grid-cols-1 gap-2.5 text-base text-slate-700 dark:text-slate-300">
                <div class="flex items-start gap-3 p-3.5 rounded-xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30">
                    <span class="font-extrabold text-blue-700 dark:text-blue-300 text-lg">1.</span>
                    <p class="leading-relaxed">
                        Verifique que el sensor registrador esté encendido y conectado en la red del colegio (<strong class="font-mono text-blue-700 dark:text-blue-300" id="mbe-ip-nodo">Consultando red...</strong>).
                    </p>
                </div>
                <div class="flex items-start gap-3 p-3.5 rounded-xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30">
                    <span class="font-extrabold text-blue-700 dark:text-blue-300 text-lg">2.</span>
                    <p class="leading-relaxed">
                        Presione el botón <strong class="text-slate-900 dark:text-white">"Iniciar Captura de Huella"</strong> a continuación.
                    </p>
                </div>
                <div class="flex items-start gap-3 p-3.5 rounded-xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30">
                    <span class="font-extrabold text-blue-700 dark:text-blue-300 text-lg">3.</span>
                    <p class="leading-relaxed">
                        Pida al estudiante apoyar su dedo índice dos veces consecutivas en el lector óptico hasta escuchar la confirmación sonora.
                    </p>
                </div>
            </div>
        </div>

        <!-- Área de Estado y Feedback de Captura -->
        <div id="mbe-feedback-box" class="hidden p-4 rounded-2xl border text-center space-y-2">
            <div id="mbe-feedback-spinner" class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto hidden"></div>
            <p id="mbe-feedback-msg" class="text-base font-bold"></p>
        </div>

        <!-- Botones de Acción -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
            <button type="button" onclick="cerrarModalBiometriaEstudiante()" class="btn btn-muted text-base font-bold py-2.5 px-5 min-h-[46px] w-full sm:w-auto">
                Cancelar
            </button>
            <button type="button" id="mbe-btn-iniciar-captura" class="btn btn-primary text-base font-extrabold py-3 px-6 min-h-[48px] w-full sm:w-auto inline-flex items-center justify-center gap-2 shadow-md">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.99 7.132"></path></svg>
                <span>Iniciar Captura de Huella</span>
            </button>
        </div>

    </div>
</div>
