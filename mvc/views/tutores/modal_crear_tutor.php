<?php
declare(strict_types=1);
?>
<!-- Modal Crear Tutor (Rediseñado con Tipografía Grande y Accesibilidad) -->
<div id="modal-crear-tutor" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="mct-titulo">
    <div class="app-modal-dialog max-w-xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto">
        
        <!-- Header -->
        <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-2xl font-bold shrink-0 border border-indigo-200 dark:border-indigo-800">
                    <svg class="w-7 h-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                </div>
                <div>
                    <h3 id="mct-titulo" class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Registrar Nuevo Tutor
                    </h3>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-0.5">
                        Padre, madre o encargado legal de estudiantes del colegio.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalCrearTutor()" class="app-modal-close text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl font-bold p-2" aria-label="Cerrar modal crear">
                ×
            </button>
        </div>

        <form id="form-crear-tutor" class="space-y-4 text-base">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Nombre *</label>
                    <input type="text" name="nombre" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]" placeholder="Ej: María Elena" required>
                </div>
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Apellido *</label>
                    <input type="text" name="apellido" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]" placeholder="Ej: Benítez" required>
                </div>
            </div>

            <div>
                <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Cédula de Identidad *</label>
                <input type="text" name="cedula_identidad" placeholder="Ej: 3456789 (sin puntos)" class="app-input w-full text-base font-mono font-bold py-2.5 px-3.5 rounded-xl min-h-[46px]" required>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 font-medium">
                    Servirá como usuario y contraseña inicial para el panel de familias.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Celular / WhatsApp *</label>
                    <input type="text" name="celular" placeholder="0981 123 456" class="app-input w-full text-base font-mono py-2.5 px-3.5 rounded-xl min-h-[46px]" required>
                </div>
                <div>
                    <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Correo Electrónico (Opcional)</label>
                    <input type="email" name="email" placeholder="tutor@gmail.com" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]">
                </div>
            </div>

            <div>
                <label class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">Dirección Domiciliaria (Opcional)</label>
                <input type="text" name="direccion" placeholder="Barrio, calle, número de casa..." class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]">
            </div>

            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                <button type="button" onclick="cerrarModalCrearTutor()" class="btn btn-muted text-base font-bold py-2.5 px-5 min-h-[46px] w-full sm:w-auto">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-crear-tut" class="btn btn-success text-base font-extrabold py-2.5 px-6 min-h-[46px] w-full sm:w-auto shadow-sm">
                    Guardar Tutor
                </button>
            </div>
        </form>
    </div>
</div>
