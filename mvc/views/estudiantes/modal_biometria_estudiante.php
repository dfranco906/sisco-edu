<?php
declare(strict_types=1);
?>
<!-- Submodal 2: Asistente y Gestión de Huella Biométrica Estudiantil (ESP32 / DY50) -->
<div id="modal-biometria-estudiante" class="app-modal modal-ficha-wrapper fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 lg:p-6 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="mbe-titulo" onclick="if (event.target === this) cerrarModalBiometriaEstudiante();">
    <div class="app-modal-dialog modal-biometria-wide bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-[96vw] sm:w-[92vw] lg:w-[80vw] max-w-[96vw] lg:max-w-[80vw] flex flex-col p-5 sm:p-6 lg:p-7 space-y-4 sm:space-y-5 max-h-[92vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 pb-3.5 border-b border-slate-200 dark:border-slate-800 shrink-0">
            <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 flex items-center justify-center text-3xl font-bold shrink-0 border border-blue-200 dark:border-blue-800 shadow-2xs">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.99 7.132"></path></svg>
                </div>
                <div class="min-w-0">
                    <h3 id="mbe-titulo" class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight truncate">
                        Vinculación Biométrica del Estudiante
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-0.5 truncate">
                        Captura y registro de huella en el sensor registrador DY50 / AS608.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalBiometriaEstudiante()" class="min-w-[40px] min-h-[40px] rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-2xl font-bold p-1 cursor-pointer" aria-label="Cerrar modal biometría">
                ×
            </button>
        </div>

        <!-- Cuerpo del Modal en 2 Columnas para aprovechamiento horizontal (80vw) sin scroll -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 items-stretch">
            
            <!-- Columna Izquierda: Datos del Estudiante y Selector de Conexión -->
            <div class="lg:col-span-5 flex flex-col gap-3.5 justify-between">
                
                <!-- Ficha Resumida del Alumno -->
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-800 flex flex-col gap-2.5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                                Estudiante Seleccionado
                            </span>
                            <span id="mbe-nombre-estudiante" class="text-lg sm:text-xl font-black text-slate-900 dark:text-white block mt-0.5 truncate">
                                ---
                            </span>
                        </div>
                        <div id="mbe-badge-estado-wrap" class="shrink-0">
                            <span id="mbe-badge-estado" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                Consultando...
                            </span>
                        </div>
                    </div>
                    <div class="text-sm text-slate-600 dark:text-slate-300 font-mono flex flex-wrap items-center gap-2 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                        <span>Cédula: <strong id="mbe-cedula-estudiante" class="text-slate-900 dark:text-white font-bold">---</strong></span>
                        <span class="text-slate-300 dark:text-slate-600">•</span>
                        <span>Curso: <strong id="mbe-grado-estudiante" class="text-blue-600 dark:text-blue-400 font-bold">---</strong></span>
                    </div>
                </div>

                <!-- Selector y Estado de Conexión del Sensor Registrador (USB vs Wi-Fi) -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wide">Canal:</span>
                        <span id="mbe-badge-canal" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            📶 Modo Wi-Fi
                        </span>
                    </div>
                    <button type="button" id="mbe-btn-conectar-usb" onclick="conectarUsbRegistrador('estudiante')" class="btn btn-secondary text-xs sm:text-sm font-bold py-1.5 px-3 min-h-[36px] inline-flex items-center gap-2 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 shadow-2xs transition-all cursor-pointer">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span id="mbe-btn-conectar-usb-txt">Conectar por Cable USB</span>
                    </button>
                </div>

                <!-- Área de Estado y Feedback de Captura -->
                <div id="mbe-feedback-box" class="hidden p-3.5 rounded-2xl border text-center space-y-2">
                    <div id="mbe-feedback-spinner" class="w-6 h-6 border-3 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto hidden"></div>
                    <p id="mbe-feedback-msg" class="text-sm font-bold leading-snug"></p>
                </div>

            </div>

            <!-- Columna Derecha: Guía de los 2 Momentos de Enrolamiento -->
            <div class="lg:col-span-7 flex flex-col gap-3 justify-between">
                <div>
                    <h4 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2 mb-2.5">
                        <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-xs font-bold flex items-center justify-center shrink-0">i</span>
                        <span>Proceso biométrico en 2 pasos independientes:</span>
                    </h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-700 dark:text-slate-300">
                        <div class="flex flex-col gap-1.5 p-3.5 rounded-2xl bg-blue-50/70 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/30">
                            <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300 font-extrabold text-sm sm:text-base">
                                <span class="w-6 h-6 rounded-full bg-blue-200 dark:bg-blue-900 flex items-center justify-center text-xs font-bold shrink-0">1</span>
                                <span>Momento 1: Registro en BD</span>
                            </div>
                            <p class="text-xs sm:text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                Inicie la captura y solicite al estudiante apoyar su dedo 2 veces en el lector registrador (<strong class="font-mono text-blue-700 dark:text-blue-300" id="mbe-ip-nodo">Consultando...</strong>). La huella se valida y almacena localmente de forma segura.
                            </p>
                        </div>

                        <div class="flex flex-col gap-1.5 p-3.5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30">
                            <div class="flex items-center gap-2 text-indigo-700 dark:text-indigo-300 font-extrabold text-sm sm:text-base">
                                <span class="w-6 h-6 rounded-full bg-indigo-200 dark:bg-indigo-900 flex items-center justify-center text-xs font-bold shrink-0">2</span>
                                <span>Momento 2: Enviar al Aula</span>
                            </div>
                            <p class="text-xs sm:text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                Presione <strong class="text-indigo-700 dark:text-indigo-300">"Enviar al Aula"</strong> para encolar la huella hacia el nodo del aula del estudiante vía LoRa. Si el aula está apagada, la huella ya queda a salvo para sincronizar después.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-xs text-slate-500 dark:text-slate-400 italic">
                    💡 Podrá enrolar todas las huellas de forma continuada en secretaría y enviarlas a los nodos de aula en cualquier momento.
                </div>
            </div>

        </div>

        <!-- Botones de Acción (Separación Explícita de Registro y Envío al Aula) -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3.5 border-t border-slate-200 dark:border-slate-800 shrink-0">
            <button type="button" onclick="cerrarModalBiometriaEstudiante()" class="btn btn-muted text-sm sm:text-base font-bold py-2.5 px-5 min-h-[44px] cursor-pointer">
                Cerrar
            </button>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <button type="button" id="mbe-btn-iniciar-captura" class="btn btn-primary text-sm sm:text-base font-extrabold py-2.5 sm:py-3 px-5 sm:px-6 min-h-[46px] inline-flex items-center justify-center gap-2 shadow-md cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.99 7.132"></path></svg>
                    <span>1. Registrar Huella</span>
                </button>
                <button type="button" id="mbe-btn-enviar-aulas" class="btn bg-indigo-600 hover:bg-indigo-700 text-white text-sm sm:text-base font-extrabold py-2.5 sm:py-3 px-5 sm:px-6 min-h-[46px] inline-flex items-center justify-center gap-2 shadow-md cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                    <span>2. Enviar al Aula</span>
                </button>
            </div>
        </div>

    </div>
</div>
