<?php
declare(strict_types=1);
?>
<!-- Modal Accesible: Editar Usuario del Sistema -->
<div id="modal-editar-usuario" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-editar-usuario-titulo">
    <div class="relative w-full max-w-xl max-h-[92vh] bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-5 sm:p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/80">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 flex items-center justify-center text-2xl font-bold shadow-xs shrink-0 border border-amber-200 dark:border-amber-800">
                    <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <div>
                    <h2 id="modal-editar-usuario-titulo" class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Editar Usuario
                    </h2>
                    <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 mt-0.5">
                        Modificá datos personales, rol o restablecé la contraseña.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalEditarUsuario()" class="w-10 h-10 rounded-xl text-slate-400 hover:text-slate-800 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800 flex items-center justify-center text-xl font-bold transition focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer" aria-label="Cerrar modal editar usuario">
                ✕
            </button>
        </div>

        <!-- Formulario -->
        <form id="form-editar-usuario" onsubmit="guardarEdicionUsuario(event)" class="flex flex-col flex-1 overflow-hidden">
            <input type="hidden" id="editar-id-usuario" name="id_usuario">

            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1 text-base">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="editar-nombre" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                            Nombre <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="editar-nombre" name="nombre" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]" required autocomplete="given-name">
                    </div>
                    <div>
                        <label for="editar-apellido" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                            Apellido <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="editar-apellido" name="apellido" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]" required autocomplete="family-name">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="editar-usuario" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                            Nombre de Usuario / CI <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="editar-usuario" name="usuario" class="app-input w-full text-base font-mono font-bold py-2.5 px-3.5 rounded-xl min-h-[46px]" required autocomplete="username">
                    </div>

                    <div>
                        <label for="editar-rol" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                            Rol en el Sistema <span class="text-rose-500">*</span>
                        </label>
                        <select id="editar-rol" name="rol" class="app-input w-full text-base font-semibold py-2.5 px-3.5 rounded-xl min-h-[46px]" required>
                            <option value="SuperAdmin">SuperAdmin (Acceso Total)</option>
                            <option value="Administrador">Administrador (Gestión General)</option>
                            <option value="Coordinador">Coordinador Académico</option>
                            <option value="Profesor">Profesor (Docente de Aula)</option>
                            <option value="Estudiante">Estudiante (Solo Lectura / Portal)</option>
                            <option value="Tutor">Tutor / Padre de Familia</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="editar-email" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                            Correo Electrónico
                        </label>
                        <input type="email" id="editar-email" name="email" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]" autocomplete="email">
                    </div>
                    <div>
                        <label for="editar-celular" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                            Teléfono Celular
                        </label>
                        <input type="tel" id="editar-celular" name="celular" class="app-input w-full text-base font-mono py-2.5 px-3.5 rounded-xl min-h-[46px]" autocomplete="tel">
                    </div>
                </div>

                <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
                    <label for="editar-password" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                        Restablecer Contraseña (Opcional)
                    </label>
                    <div class="relative">
                        <input type="password" id="editar-password" name="password" class="app-input w-full text-base py-2.5 px-3.5 pr-12 rounded-xl min-h-[46px]" placeholder="Dejar en blanco para conservar la actual" minlength="6" autocomplete="new-password">
                        <button type="button" onclick="alternarVisibilidadPass('editar-password', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer" aria-label="Mostrar u ocultar contraseña">
                            <span class="text-lg">👁️</span>
                        </button>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Completá este campo únicamente si deseás cambiar o blanquear la contraseña del usuario.
                    </p>
                </div>

            </div>

            <!-- Footer con Acciones -->
            <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 p-4 sm:p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/80">
                <button type="button" onclick="cerrarModalEditarUsuario()" class="btn btn-secondary w-full sm:w-auto text-base font-semibold py-2.5 px-5 min-h-[46px]">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-editar-usuario" class="btn btn-primary w-full sm:w-auto text-base font-bold py-2.5 px-6 min-h-[46px] inline-flex items-center justify-center gap-2 shadow-sm">
                    <span id="txt-submit-editar-usuario">Guardar Cambios</span>
                </button>
            </div>
        </form>

    </div>
</div>
