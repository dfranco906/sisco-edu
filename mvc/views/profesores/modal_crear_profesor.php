<?php
declare(strict_types=1);
?>
<!-- Modal Crear Profesor (Rediseñado con Tipografía Grande y Accesibilidad) -->
<div id="modal-crear-profesor" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="mcp-titulo">
    <div class="app-modal-dialog max-w-xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-purple-100 dark:bg-purple-900/50 text-purple-700 dark:text-purple-300 flex items-center justify-center text-2xl font-bold shrink-0 border border-purple-200 dark:border-purple-800">
                    <svg class="w-7 h-7 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                </div>
                <div>
                    <h3 id="mcp-titulo" class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Registrar Nuevo Docente
                    </h3>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-0.5">
                        Ficha personal y credenciales de acceso al sistema escolar.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalCrearProfesor()" class="app-modal-close text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl font-bold p-2" aria-label="Cerrar modal crear">
                ×
            </button>
        </div>

        <form id="form-crear-profesor" class="space-y-4 text-base">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Nombre *</label>
                    <input type="text" name="nombre" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl" placeholder="Ej: Carlos Alberto" required>
                </div>
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Apellido *</label>
                    <input type="text" name="apellido" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl" placeholder="Ej: González" required>
                </div>
            </div>

            <div>
                <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Cédula de Identidad *</label>
                <input type="text" name="cedula_identidad" placeholder="Ej: 3456789 (sin puntos)" class="app-input w-full text-base font-mono font-bold py-2.5 px-3.5 rounded-xl" required>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 font-medium">
                    Servirá como usuario y contraseña inicial para el panel del profesor.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Correo Electrónico (Opcional)</label>
                    <input type="email" name="email" placeholder="docente@colegio.edu.py" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl">
                </div>
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Teléfono Celular (Opcional)</label>
                    <input type="text" name="celular" placeholder="0981 123 456" class="app-input w-full text-base font-mono py-2.5 px-3.5 rounded-xl">
                </div>
            </div>

            <div class="border-t border-slate-200 dark:border-slate-800 pt-4 space-y-3">
                <span class="font-extrabold text-sm uppercase tracking-wider text-purple-700 dark:text-purple-300 block">
                    Asignación Inicial de Cátedra (Opcional)
                </span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-1.5 font-semibold text-base text-slate-800 dark:text-slate-200">Grado o Curso</label>
                        <select name="id_grado" id="modal-crear-grado-select" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl">
                            <option value="">Sin asignar aún</option>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-1.5 font-semibold text-base text-slate-800 dark:text-slate-200">Materia</label>
                        <select name="id_materia" id="modal-crear-materia-select" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl">
                            <option value="">Sin asignar aún</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="cerrarModalCrearProfesor()" class="btn btn-muted text-base font-bold py-2.5 px-5 min-h-[46px] w-full sm:w-auto">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-crear-prof" class="btn btn-success text-base font-extrabold py-2.5 px-6 min-h-[46px] w-full sm:w-auto shadow-sm">
                    Guardar Nuevo Profesor
                </button>
            </div>
        </form>
    </div>
</div>
