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
        <div class="space-y-4">
            <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-blue-600 text-white text-sm font-bold flex items-center justify-center shrink-0">i</span>
                <span>Proceso biométrico en 2 pasos independientes:</span>
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-base text-slate-700 dark:text-slate-300">
                <div class="flex flex-col gap-2 p-4 rounded-2xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30">
                    <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300 font-extrabold text-base">
                        <span class="w-7 h-7 rounded-full bg-blue-200 dark:bg-blue-900 flex items-center justify-center text-sm font-bold">1</span>
                        <span>Momento 1: Registro en Base de Datos</span>
                    </div>
                    <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        Inicie la captura y solicite al estudiante apoyar su dedo 2 veces en el lector registrador (<strong class="font-mono text-blue-700 dark:text-blue-300" id="mbe-ip-nodo">Consultando...</strong>). La huella se valida y almacena localmente de forma segura.
                    </p>
                </div>

                <div class="flex flex-col gap-2 p-4 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30">
                    <div class="flex items-center gap-2 text-indigo-700 dark:text-indigo-300 font-extrabold text-base">
                        <span class="w-7 h-7 rounded-full bg-indigo-200 dark:bg-indigo-900 flex items-center justify-center text-sm font-bold">2</span>
                        <span>Momento 2: Enviar al Aula</span>
                    </div>
                    <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                        Presione <strong class="text-indigo-700 dark:text-indigo-300">"Enviar al Aula"</strong> para encolar la huella hacia el nodo del aula del estudiante vía LoRa. Si el aula está apagada, la huella ya queda a salvo para sincronizar después.
                    </p>
                </div>
            </div>
        </div>

        <!-- Área de Estado y Feedback de Captura -->
        <div id="mbe-feedback-box" class="hidden p-4 rounded-2xl border text-center space-y-2">
            <div id="mbe-feedback-spinner" class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto hidden"></div>
            <p id="mbe-feedback-msg" class="text-base font-bold"></p>
        </div>

        <!-- Botones de Acción (Separación Explícita de Registro y Envío al Aula) -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
            <button type="button" onclick="cerrarModalBiometriaEstudiante()" class="btn btn-muted text-base font-bold py-2.5 px-5 min-h-[46px]">
                Cerrar
            </button>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <button type="button" id="mbe-btn-iniciar-captura" class="btn btn-primary text-base font-extrabold py-3 px-5 min-h-[48px] inline-flex items-center justify-center gap-2 shadow-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.99 7.132"></path></svg>
                    <span>1. Registrar Huella</span>
                </button>
                <button type="button" id="mbe-btn-enviar-aulas" class="btn bg-indigo-600 hover:bg-indigo-700 text-white text-base font-extrabold py-3 px-5 min-h-[48px] inline-flex items-center justify-center gap-2 shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                    <span>2. Enviar al Aula</span>
                </button>
            </div>
        </div>

    </div>
</div>
