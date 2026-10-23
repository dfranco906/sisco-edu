<?php
declare(strict_types=1);
?>
<!-- Modal Editar Tutor (Rediseñado con Tipografía Grande y Accesibilidad) -->
<div id="modal-editar-tutor" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="met-titulo">
    <div class="app-modal-dialog max-w-xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-2xl font-bold shrink-0 border border-indigo-200 dark:border-indigo-800">
                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </div>
                <div>
                    <h3 id="met-titulo" class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Modificar Datos del Tutor
                    </h3>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-0.5">
                        Actualice los medios de contacto, cédula o dirección domiciliaria.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalEditarTutor()" class="app-modal-close text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl font-bold p-2" aria-label="Cerrar modal editar">
                ×
            </button>
        </div>

        <form id="form-editar-tutor" class="space-y-4 text-base">
            <input type="hidden" name="id_tutor" id="edit-tut-id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Nombre *</label>
                    <input type="text" name="nombre" id="edit-tut-nombre" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]" required>
                </div>
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Apellido *</label>
                    <input type="text" name="apellido" id="edit-tut-apellido" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]" required>
                </div>
            </div>

            <div>
                <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Cédula de Identidad *</label>
                <input type="text" name="cedula_identidad" id="edit-tut-cedula" class="app-input w-full text-base font-mono font-bold py-2.5 px-3.5 rounded-xl min-h-[46px]" required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Celular / WhatsApp *</label>
                    <input type="text" name="celular" id="edit-tut-celular" class="app-input w-full text-base font-mono py-2.5 px-3.5 rounded-xl min-h-[46px]" required>
                </div>
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Correo Electrónico</label>
                    <input type="email" name="email" id="edit-tut-email" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]">
                </div>
            </div>

            <div>
                <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Dirección Domiciliaria</label>
                <input type="text" name="direccion" id="edit-tut-direccion" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]">
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="cerrarModalEditarTutor()" class="btn btn-muted text-base font-bold py-2.5 px-5 min-h-[46px] w-full sm:w-auto">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-editar-tut" class="btn btn-primary text-base font-extrabold py-2.5 px-6 min-h-[46px] w-full sm:w-auto shadow-sm">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
