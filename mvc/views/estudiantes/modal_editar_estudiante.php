<?php
declare(strict_types=1);
?>
<!-- Modal Editar Estudiante (Rediseñado con Tipografía Grande y Accesibilidad) -->
<div id="modal-editar-estudiante" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="mee-titulo">
    <div class="app-modal-dialog max-w-xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 flex items-center justify-center text-2xl font-bold shrink-0 border border-blue-200 dark:border-blue-800">
                    <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                </div>
                <div>
                    <h3 id="mee-titulo" class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Modificar Datos del Estudiante
                    </h3>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-0.5">
                        Actualice la filiación, cédula o reasigne el grado correspondiente.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalEditarEstudiante()" class="app-modal-close text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl font-bold p-2" aria-label="Cerrar modal editar">
                ×
            </button>
        </div>

        <form id="form-editar-estudiante" class="space-y-4 text-base">
            <input type="hidden" name="id_estudiante" id="edit-id-estudiante">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Nombre *</label>
                    <input type="text" name="nombre" id="edit-nombre" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]" required>
                </div>
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Apellido *</label>
                    <input type="text" name="apellido" id="edit-apellido" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]" required>
                </div>
            </div>

            <div>
                <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Cédula de Identidad *</label>
                <input type="text" name="cedula_identidad" id="edit-cedula" class="app-input w-full text-base font-mono font-bold py-2.5 px-3.5 rounded-xl min-h-[46px]" required>
            </div>

            <div>
                <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Grado / Curso</label>
                <select name="id_grado" id="edit-grado" class="app-input w-full text-base font-bold py-2.5 px-3.5 rounded-xl min-h-[46px]">
                    <option value="">Seleccione grado o curso...</option>
                </select>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 font-medium">
                    Al cambiar de curso, se actualizarán las matrículas y materias del alumno.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="cerrarModalEditarEstudiante()" class="btn btn-muted text-base font-bold py-2.5 px-5 min-h-[46px] w-full sm:w-auto">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-editar-est" class="btn btn-primary text-base font-extrabold py-2.5 px-6 min-h-[46px] w-full sm:w-auto shadow-sm">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
