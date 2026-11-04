<?php
declare(strict_types=1);
?>
<!-- Submodal 1: Ficha Integral del Docente con Monitoreo en Tiempo Real (Paraguay) -->
<div id="modal-detalle-profesor" class="app-modal modal-ficha-wrapper fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 lg:p-6 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="mdp-nombre-docente" onclick="if (event.target === this) cerrarModalDetalleProfesor();">
    <div class="app-modal-dialog modal-ficha-wide bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 w-[96vw] sm:w-[92vw] lg:w-[80vw] max-w-[96vw] lg:max-w-[80vw] h-[96vh] sm:h-auto max-h-[96vh] lg:max-h-[90vh] flex flex-col overflow-hidden p-0 animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Encabezado de la Ficha con Avatar Grande y Nombre -->
        <div class="p-5 sm:p-6 lg:p-7 border-b border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-800/90 flex items-start justify-between gap-4 shrink-0">
            <div class="flex items-center gap-4 min-w-0">
                <div id="mdp-avatar" class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 flex items-center justify-center text-2xl font-black shrink-0 border border-purple-200 dark:border-purple-800 shadow-2xs">
                    --
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h3 id="mdp-nombre-docente" class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900 dark:text-white tracking-tight truncate">
                            Nombre del Profesor
                        </h3>
                        <span id="mdp-badge-estado" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                            ✓ Activo
                        </span>
                    </div>
                    <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                        <span>Cédula de Identidad: <strong id="mdp-cedula-docente" class="text-slate-900 dark:text-white font-mono font-bold text-base">---</strong></span>
                        <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">•</span>
                        <span id="mdp-badge-huella-resumen" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-700 dark:text-slate-300">
                            Huella: Verificando...
                        </span>
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalDetalleProfesor()" class="min-w-[44px] min-h-[44px] w-11 h-11 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer hover:bg-slate-200/70 dark:hover:bg-slate-800 transition-colors shrink-0" aria-label="Cerrar ficha">
                &times;
            </button>
        </div>

        <!-- Cuerpo con Scroll Vertical Autónomo -->
        <div class="p-4 sm:p-6 lg:p-8 space-y-6 overflow-y-auto flex-1 text-base">

            <!-- BANNER DE MONITOREO EN TIEMPO REAL: HORARIO DE PARAGUAY & PRESENCIA BIOMÉTRICA -->
            <div id="mdp-banner-tiempo-real" class="p-4 sm:p-5 lg:p-6 rounded-2xl border transition-all duration-300 bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-start gap-3.5">
                        <span id="mdp-realtime-icono" class="text-3xl shrink-0 mt-0.5">⏱️</span>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    Monitoreo en Vivo (Hora Oficial Paraguay)
                                </span>
                                <span id="mdp-realtime-hora-servidor" class="text-xs font-mono font-bold px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200">
                                    --:--:--
                                </span>
                            </div>
                            <h5 id="mdp-realtime-titulo" class="text-base sm:text-lg lg:text-xl font-black text-slate-900 dark:text-white mt-1">
                                Consultando disponibilidad y horario...
                            </h5>
                            <p id="mdp-realtime-detalle" class="text-sm sm:text-base font-medium text-slate-600 dark:text-slate-300 mt-0.5 leading-relaxed">
                                Verificando asignaciones lectivas del día de hoy...
                            </p>
                        </div>
                    </div>

                    <div id="mdp-realtime-badge-asistencia" class="shrink-0 self-start sm:self-auto">
                        <!-- Badge dinámico de huella -->
                    </div>
                </div>
            </div>

            <!-- Bloque 1: Canales de Contacto y Acceso al Sistema -->
            <div class="space-y-3">
                <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    <span>Contacto y Cuenta de Acceso</span>
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Teléfono Celular -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                            Teléfono Móvil
                        </span>
                        <div id="mdp-celular-wrap" class="flex items-center justify-between gap-2 pt-1">
                            <span id="mdp-celular-texto" class="text-base font-mono font-bold text-slate-900 dark:text-white truncate">
                                Sin registro
                            </span>
                            <div id="mdp-celular-acciones" class="flex items-center gap-1.5 hidden shrink-0">
                                <a id="mdp-link-whatsapp" href="#" target="_blank" rel="noopener noreferrer" class="p-2 min-h-[40px] min-w-[40px] flex items-center justify-center rounded-xl bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 font-bold text-base transition-colors" title="Enviar WhatsApp">
                                    💬
                                </a>
                                <a id="mdp-link-tel" href="#" class="p-2 min-h-[40px] min-w-[40px] flex items-center justify-center rounded-xl bg-purple-100 hover:bg-purple-200 dark:bg-purple-950/60 dark:hover:bg-purple-900/60 text-purple-800 dark:text-purple-300 font-bold text-base transition-colors" title="Llamar">
                                    📞
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Correo Electrónico -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                            Correo Electrónico
                        </span>
                        <div class="pt-1">
                            <a id="mdp-email-link" href="#" class="text-base font-semibold text-purple-700 dark:text-purple-300 hover:underline break-all block truncate">
                                Sin correo registrado
                            </a>
                        </div>
                    </div>

                    <!-- Usuario del Sistema -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 space-y-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                            Usuario en SISCO-EDU
                        </span>
                        <div class="pt-1 flex items-center justify-between gap-2">
                            <span id="mdp-usuario-texto" class="text-base font-mono font-bold text-slate-900 dark:text-white truncate">
                                ---
                            </span>
                            <span class="px-2.5 py-0.5 rounded-lg text-xs font-black uppercase tracking-wider bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300 shrink-0">
                                Docente
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bloque 2: Carga Horaria y Materias Asignadas -->
            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        <span>Distribución Curricular y Materias</span>
                    </h4>
                    <div class="text-sm font-bold text-slate-600 dark:text-slate-400">
                        Total: <span id="mdp-total-materias-cnt" class="text-purple-600 dark:text-purple-400 font-extrabold text-base">0</span> asignaciones
                    </div>
                </div>

                <!-- Tabla Detallada de Materias con Estado en Vivo -->
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-base">
                            <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800 font-bold text-xs sm:text-sm uppercase tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-4 text-left w-1/4">Curso / Grado</th>
                                    <th class="py-3.5 px-4 text-left w-1/4">Materia Asignada</th>
                                    <th class="py-3.5 px-4 text-center w-1/6">Carga Horaria</th>
                                    <th class="py-3.5 px-4 text-left w-1/3">Horario & Estado Actual (Paraguay)</th>
                                </tr>
                            </thead>
                            <tbody id="mdp-tabla-materias-body" class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr>
                                    <td colspan="4" class="p-6 text-center text-slate-500 font-medium">
                                        Cargando distribución de materias y horarios en vivo...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Bloque 3: Acciones de Identidad Biométrica IoT (Centralizadas en la Ficha) -->
            <div class="p-5 sm:p-6 rounded-2xl bg-purple-50/70 dark:bg-purple-950/30 border border-purple-200/70 dark:border-purple-900/40 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <span class="text-xs font-black uppercase tracking-wider text-purple-900 dark:text-purple-300 block">
                        Gestión Biométrica de Hardware (ESP32 / DY50 / LoRa)
                    </span>
                    <span id="mdp-user-id-global" class="font-mono text-base sm:text-lg font-black text-slate-900 dark:text-white block mt-0.5">
                        PROF_-----
                    </span>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                        Capture la huella en el sensor de secretaría y elija a qué aulas transmitirla vía radiofrecuencia LoRa.
                    </p>
                </div>
                <div class="flex items-center gap-3 flex-wrap shrink-0">
                    <!-- Botón 1: Capturar Huella en el Registrador -->
                    <button type="button" id="mdp-btn-abrir-biometria" class="btn btn-secondary text-base font-bold py-2.5 px-5 min-h-[46px] inline-flex items-center gap-2 cursor-pointer shadow-xs">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.99 7.132"></path></svg>
                        <span>Configurar Huella</span>
                    </button>

                    <!-- Botón 2: Enviar a Aulas Selectivas -->
                    <button type="button" id="mdp-btn-enviar-aulas-ficha" class="btn bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-5 min-h-[46px] inline-flex items-center gap-2 shadow-xs transition-colors cursor-pointer">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                        <span>Enviar a Aulas</span>
                    </button>
                </div>
            </div>

        </div>

        <!-- Acciones Inferiores del Modal -->
        <div class="p-4 sm:p-5 lg:p-6 bg-slate-50/90 dark:bg-slate-800/90 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shrink-0">
            <div class="w-full sm:w-auto">
                <button type="button" id="mdp-btn-editar-desde-ficha" class="btn btn-secondary text-base font-bold py-2.5 px-5 min-h-[46px] w-full sm:w-auto inline-flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    <span>Modificar Datos Personales</span>
                </button>
            </div>
            <div class="w-full sm:w-auto flex justify-end">
                <button type="button" onclick="cerrarModalDetalleProfesor()" class="btn btn-primary text-base font-bold py-2.5 px-6 min-h-[46px] w-full sm:w-auto cursor-pointer">
                    Entendido / Cerrar
                </button>
            </div>
        </div>

    </div>
</div>
