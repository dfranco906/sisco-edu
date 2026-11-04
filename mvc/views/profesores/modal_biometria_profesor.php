<?php
declare(strict_types=1);
?>
<!-- Submodal 2: Asistente y Gestión de Huella Biométrica (ESP32 / DY50) -->
<div id="modal-biometria-profesor" class="app-modal modal-ficha-wrapper fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 lg:p-6 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="mbp-titulo" onclick="if (event.target === this) cerrarModalBiometriaProfesor();">
    <div class="app-modal-dialog modal-biometria-wide bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-[96vw] sm:w-[92vw] lg:w-[80vw] max-w-[96vw] lg:max-w-[80vw] flex flex-col p-5 sm:p-6 lg:p-7 space-y-4 sm:space-y-5 max-h-[92vh] overflow-y-auto animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 pb-3.5 border-b border-slate-200 dark:border-slate-800 shrink-0">
            <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 flex items-center justify-center text-3xl font-bold shrink-0 border border-purple-200 dark:border-purple-800 shadow-2xs">
                    <svg class="w-7 h-7 sm:w-8 sm:h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.99 7.132"></path></svg>
                </div>
                <div class="min-w-0">
                    <h3 id="mbp-titulo" class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight truncate">
                        Vinculación Biométrica Docente
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-0.5 truncate">
                        Registro y sincronización de huella con el sensor óptico DY50 / AS608.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalBiometriaProfesor()" class="min-w-[40px] min-h-[40px] rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-2xl font-bold p-1 cursor-pointer" aria-label="Cerrar modal biometría">
                ×
            </button>
        </div>

        <!-- Cuerpo del Modal en 2 Columnas para aprovechamiento horizontal (80vw) sin scroll -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 items-stretch">
            
            <!-- Columna Izquierda: Datos del Docente y Selector de Conexión -->
            <div class="lg:col-span-5 flex flex-col gap-3.5 justify-between">
                
                <!-- Ficha Resumida del Docente -->
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/70 border border-slate-200 dark:border-slate-800 flex flex-col gap-2.5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                                Docente Seleccionado
                            </span>
                            <span id="mbp-nombre-docente" class="text-lg sm:text-xl font-black text-slate-900 dark:text-white block mt-0.5 truncate">
                                ---
                            </span>
                        </div>
                        <div id="mbp-badge-estado-wrap" class="shrink-0">
                            <span id="mbp-badge-estado" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                Consultando...
                            </span>
                        </div>
                    </div>
                    <div class="text-sm text-slate-600 dark:text-slate-300 font-mono flex flex-wrap items-center gap-2 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                        <span>Cédula: <strong id="mbp-cedula-docente" class="text-slate-900 dark:text-white font-bold">---</strong></span>
                    </div>
                </div>

                <!-- Selector y Estado de Conexión del Sensor Registrador (USB vs Wi-Fi) -->
                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-600 dark:text-slate-400 uppercase tracking-wide">Canal:</span>
                        <span id="mbp-badge-canal" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            📶 Modo Wi-Fi
                        </span>
                    </div>
                    <button type="button" id="mbp-btn-conectar-usb" onclick="conectarUsbRegistrador('profesor')" class="btn btn-secondary text-xs sm:text-sm font-bold py-1.5 px-3 min-h-[36px] inline-flex items-center gap-2 border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 shadow-2xs transition-all cursor-pointer">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span id="mbp-btn-conectar-usb-txt">Conectar por Cable USB</span>
                    </button>
                </div>

                <!-- Área de Estado y Feedback de Captura -->
                <div id="mbp-feedback-box" class="hidden p-3.5 rounded-2xl border text-center space-y-2">
                    <div id="mbp-feedback-spinner" class="w-6 h-6 border-3 border-purple-600 border-t-transparent rounded-full animate-spin mx-auto hidden"></div>
                    <p id="mbp-feedback-msg" class="text-sm font-bold leading-snug"></p>
                </div>

            </div>

            <!-- Columna Derecha: Guía de los 2 Momentos de Enrolamiento -->
            <div class="lg:col-span-7 flex flex-col gap-3 justify-between">
                <div>
                    <h4 class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-2 mb-2.5">
                        <span class="w-5 h-5 rounded-full bg-purple-600 text-white text-xs font-bold flex items-center justify-center shrink-0">i</span>
                        <span>Proceso biométrico en 2 pasos independientes:</span>
                    </h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-700 dark:text-slate-300">
                        <div class="flex flex-col gap-1.5 p-3.5 rounded-2xl bg-purple-50/70 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/30">
                            <div class="flex items-center gap-2 text-purple-700 dark:text-purple-300 font-extrabold text-sm sm:text-base">
                                <span class="w-6 h-6 rounded-full bg-purple-200 dark:bg-purple-900 flex items-center justify-center text-xs font-bold shrink-0">1</span>
                                <span>Momento 1: Registro en BD</span>
                            </div>
                            <p class="text-xs sm:text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                Inicie la captura y pida al profesor apoyar su dedo 2 veces en el lector registrador (<strong class="font-mono text-purple-700 dark:text-purple-300" id="mbp-ip-nodo">Consultando...</strong>). La huella se valida y almacena de forma segura.
                            </p>
                        </div>

                        <div class="flex flex-col gap-1.5 p-3.5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30">
                            <div class="flex items-center gap-2 text-indigo-700 dark:text-indigo-300 font-extrabold text-sm sm:text-base">
                                <span class="w-6 h-6 rounded-full bg-indigo-200 dark:bg-indigo-900 flex items-center justify-center text-xs font-bold shrink-0">2</span>
                                <span>Momento 2: Enviar a las Aulas</span>
                            </div>
                            <p class="text-xs sm:text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                Presione <strong class="text-indigo-700 dark:text-indigo-300">"Enviar Huella a las Aulas"</strong> cuando los nodos estén listos. Si hay problemas de red o nodos apagados, la huella ya está a salvo en el servidor.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="text-xs text-slate-500 dark:text-slate-400 italic">
                    💡 Podrá enrolar las huellas de los profesores en secretaría y posteriormente seleccionar a qué aulas sincronizarlas vía LoRa.
                </div>
            </div>

        </div>

        <!-- Botones de Acción (Separación Explícita de Registro y Envío a Aulas) -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3.5 border-t border-slate-200 dark:border-slate-800 shrink-0">
            <button type="button" onclick="cerrarModalBiometriaProfesor()" class="btn btn-muted text-sm sm:text-base font-bold py-2.5 px-5 min-h-[44px] cursor-pointer">
                Cerrar
            </button>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <button type="button" id="mbp-btn-iniciar-captura" class="btn btn-primary text-sm sm:text-base font-extrabold py-2.5 sm:py-3 px-5 sm:px-6 min-h-[46px] inline-flex items-center justify-center gap-2 shadow-md cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.99 7.132"></path></svg>
                    <span>1. Registrar Huella</span>
                </button>
                <button type="button" id="mbp-btn-enviar-aulas" class="btn bg-indigo-600 hover:bg-indigo-700 text-white text-sm sm:text-base font-extrabold py-2.5 sm:py-3 px-5 sm:px-6 min-h-[46px] inline-flex items-center justify-center gap-2 shadow-md cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                    <span>2. Enviar a las Aulas</span>
                </button>
            </div>
        </div>

    </div>
</div>
