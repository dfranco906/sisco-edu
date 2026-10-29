<?php
declare(strict_types=1);
?>
<!-- Submodal 1: Ficha Integral del Estudiante (Información Secundaria) -->
<div id="modal-detalle-estudiante" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="mde-nombre-estudiante">
    <div class="app-modal-dialog max-w-3xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto">
        
        <!-- Encabezado con Avatar Grande y Nombre -->
        <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-4">
                <div id="mde-avatar" class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 flex items-center justify-center text-2xl font-black shrink-0 border border-blue-200 dark:border-blue-800">
                    --
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 id="mde-nombre-estudiante" class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            Nombre del Estudiante
                        </h3>
                        <span id="mde-badge-estado" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                            ✓ Matriculado
                        </span>
                    </div>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                        <span>Cédula de Identidad: <strong id="mde-cedula-estudiante" class="text-slate-900 dark:text-white font-mono font-bold text-base">---</strong></span>
                        <span class="text-slate-300 dark:text-slate-700">•</span>
                        <span id="mde-badge-huella-resumen" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-700 dark:text-slate-300">
                            Huella: Verificando...
                        </span>
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalDetalleEstudiante()" class="app-modal-close text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl font-bold p-2" aria-label="Cerrar ficha">
                ×
            </button>
        </div>

        <!-- Bloque 1: Grado Académico y Materias Matriculadas -->
        <div class="space-y-3">
            <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
                <span>Situación Académica y Matriculación</span>
            </h4>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 space-y-1">
                    <span class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                        Curso Asignado
                    </span>
                    <span id="mde-grado-texto" class="text-base font-extrabold text-slate-900 dark:text-white block pt-1">
                        Sin curso
                    </span>
                </div>

                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 space-y-1">
                    <span class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                        Materias Matriculadas
                    </span>
                    <span id="mde-materias-texto" class="text-base font-extrabold text-blue-600 dark:text-blue-400 block pt-1">
                        0 asignaturas activas
                    </span>
                </div>

                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 space-y-1">
                    <span class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                        Año Lectivo
                    </span>
                    <span class="text-base font-extrabold text-slate-900 dark:text-white block pt-1">
                        2026 (En Curso)
                    </span>
                </div>
            </div>
        </div>

        <!-- Bloque 2: Tutores y Vínculos Familiares -->
        <div class="space-y-3 pt-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>Tutores y Responsables Familiares</span>
                </h4>
                <button type="button" id="mde-btn-vincular-tutor" class="btn btn-secondary text-sm font-bold py-1.5 px-3.5 min-h-[38px] inline-flex items-center gap-1.5 self-start sm:self-auto">
                    <span>+ Vincular Tutor / Familiar</span>
                </button>
            </div>

            <div id="mde-tutores-contenedor" class="p-4 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30 space-y-2">
                <p id="mde-tutores-texto" class="text-base text-slate-800 dark:text-slate-200 font-medium">
                    Sin tutores vinculados.
                </p>
            </div>
        </div>

        <!-- Bloque 3: Datos de Identidad Biométrica IoT -->
        <div class="p-4 rounded-2xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200/70 dark:border-blue-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <span class="text-sm font-bold uppercase tracking-wider text-blue-900 dark:text-blue-300 block">
                    Identificador Biométrico de Hardware (ESP32 / DY50)
                </span>
                <span id="mde-user-id-global" class="font-mono text-base font-bold text-slate-900 dark:text-white block mt-0.5">
                    EST_-----
                </span>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <button type="button" id="mde-btn-abrir-biometria" class="btn btn-secondary text-base font-bold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0a8 8 0 00.99 7.132"></path></svg>
                    <span>Gestionar Huella</span>
                </button>
                <button type="button" id="mde-btn-enviar-aula" class="btn bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2 shadow-xs transition-colors hidden" title="Enviar huella al aula asignada vía LoRa">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path></svg>
                    <span>Enviar a Aula</span>
                </button>
            </div>
        </div>

        <!-- Acciones Inferiores del Modal -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-2 flex-wrap w-full sm:w-auto">
                <a id="mde-link-expediente" href="#" class="btn btn-secondary text-base font-bold py-2.5 px-4 min-h-[44px] inline-flex items-center justify-center gap-2 w-full sm:w-auto">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Ver Expediente Completo</span>
                </a>
                <button type="button" id="mde-btn-editar-desde-ficha" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] w-full sm:w-auto inline-flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    <span>Editar Datos</span>
                </button>
            </div>
            <div class="w-full sm:w-auto flex justify-end">
                <button type="button" onclick="cerrarModalDetalleEstudiante()" class="btn btn-primary text-base font-bold py-2.5 px-6 min-h-[44px] w-full sm:w-auto">
                    Entendido / Cerrar
                </button>
            </div>
        </div>

    </div>
</div>
