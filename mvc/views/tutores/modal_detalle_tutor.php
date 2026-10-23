<?php
declare(strict_types=1);
?>
<!-- Submodal: Ficha Integral del Tutor / Familia (Información Secundaria) -->
<div id="modal-detalle-tutor" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="mdt-nombre-tutor">
    <div class="app-modal-dialog max-w-3xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-4">
                <div id="mdt-avatar" class="w-16 h-16 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-2xl font-black shrink-0 border border-indigo-200 dark:border-indigo-800">
                    --
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 id="mdt-nombre-tutor" class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            Nombre del Tutor
                        </h3>
                        <span id="mdt-badge-estado" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                            ✓ Activo
                        </span>
                    </div>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-2">
                        <span>Cédula de Identidad: <strong id="mdt-cedula-tutor" class="text-slate-900 dark:text-white font-mono font-bold text-base">---</strong></span>
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalDetalleTutor()" class="app-modal-close text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl font-bold p-2" aria-label="Cerrar ficha">
                ×
            </button>
        </div>

        <!-- Bloque 1: Canales de Contacto y Ubicación -->
        <div class="space-y-3">
            <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                <span>Canales de Contacto y Domicilio</span>
            </h4>
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Teléfono Celular -->
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 space-y-1">
                    <span class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                        Celular / WhatsApp
                    </span>
                    <div class="flex items-center justify-between gap-2 pt-1">
                        <span id="mdt-celular-texto" class="text-base font-mono font-bold text-slate-900 dark:text-white">
                            Sin registro
                        </span>
                        <div id="mdt-celular-acciones" class="flex items-center gap-1 hidden">
                            <a id="mdt-link-whatsapp" href="#" target="_blank" rel="noopener noreferrer" class="p-2 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 font-bold text-sm" title="Enviar WhatsApp">
                                💬
                            </a>
                            <a id="mdt-link-tel" href="#" class="p-2 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-950/40 dark:text-indigo-300 font-bold text-sm" title="Llamar">
                                📞
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Correo Electrónico -->
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 space-y-1">
                    <span class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                        Correo Electrónico
                    </span>
                    <div class="pt-1">
                        <a id="mdt-email-link" href="#" class="text-base font-semibold text-indigo-700 dark:text-indigo-300 hover:underline break-all block">
                            Sin correo registrado
                        </a>
                    </div>
                </div>

                <!-- Dirección Domiciliaria -->
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-800 space-y-1">
                    <span class="text-sm font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                        Dirección Domiciliaria
                    </span>
                    <span id="mdt-direccion-texto" class="text-base font-medium text-slate-800 dark:text-slate-200 block pt-1">
                        Sin dirección registrada
                    </span>
                </div>
            </div>
        </div>

        <!-- Bloque 2: Estudiantes e Hijos a Cargo -->
        <div class="space-y-3 pt-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span>Estudiantes / Hijos Vinculados</span>
                </h4>
                <button type="button" id="mdt-btn-vincular-mas" class="btn btn-secondary text-sm font-bold py-1.5 px-3.5 min-h-[38px] inline-flex items-center gap-1.5 self-start sm:self-auto">
                    <span>+ Vincular Otro Alumno</span>
                </button>
            </div>

            <!-- Tabla de Hijos Vinculados -->
            <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-base">
                        <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800 font-bold text-sm uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-4 text-left">Estudiante</th>
                                <th class="py-3 px-4 text-left">Cédula</th>
                                <th class="py-3 px-4 text-left">Curso / Grado</th>
                                <th class="py-3 px-4 text-left">Parentesco</th>
                                <th class="py-3 px-4 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="mdt-tabla-hijos-body" class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr>
                                <td colspan="5" class="p-6 text-center text-slate-500 font-medium">
                                    Cargando hijos vinculados...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Acciones Inferiores del Modal -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
            <div class="w-full sm:w-auto">
                <button type="button" id="mdt-btn-editar-desde-ficha" class="btn btn-secondary text-base font-bold py-2.5 px-5 min-h-[44px] w-full sm:w-auto inline-flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    <span>Modificar Datos del Tutor</span>
                </button>
            </div>
            <div class="w-full sm:w-auto flex justify-end">
                <button type="button" onclick="cerrarModalDetalleTutor()" class="btn btn-primary text-base font-bold py-2.5 px-6 min-h-[44px] w-full sm:w-auto">
                    Entendido / Cerrar
                </button>
            </div>
        </div>

    </div>
</div>
