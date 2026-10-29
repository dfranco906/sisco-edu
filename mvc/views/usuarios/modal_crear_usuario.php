<?php
declare(strict_types=1);
?>
<!-- Modal Accesible: Crear Usuario del Sistema -->
<div id="modal-crear-usuario" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-crear-usuario-titulo">
    <div class="relative w-full max-w-xl max-h-[92vh] bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-5 sm:p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/80">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-2xl font-bold shadow-xs shrink-0 border border-indigo-200 dark:border-indigo-800">
                    <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <div>
                    <h2 id="modal-crear-usuario-titulo" class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Nuevo Usuario
                    </h2>
                    <p class="text-sm sm:text-base text-slate-600 dark:text-slate-400 mt-0.5">
                        Registrá una nueva cuenta con rol y credenciales seguras.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalCrearUsuario()" class="w-10 h-10 rounded-xl text-slate-400 hover:text-slate-800 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800 flex items-center justify-center text-xl font-bold transition focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer" aria-label="Cerrar modal nuevo usuario">
                ✕
            </button>
        </div>

        <!-- Formulario -->
        <form id="form-crear-usuario" onsubmit="guardarNuevoUsuario(event)" class="flex flex-col flex-1 overflow-hidden">
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1 text-base">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="crear-nombre" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                            Nombre <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="crear-nombre" name="nombre" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]" placeholder="Ej: Carlos Alberto" required autocomplete="given-name">
                    </div>
                    <div>
                        <label for="crear-apellido" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                            Apellido <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="crear-apellido" name="apellido" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]" placeholder="Ej: González Benítez" required autocomplete="family-name">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="crear-usuario" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                            Nombre de Usuario / CI <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="crear-usuario" name="usuario" class="app-input w-full text-base font-mono font-bold py-2.5 px-3.5 rounded-xl min-h-[46px]" placeholder="Ej: 3456789 o cgonzalez" required autocomplete="username">
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Identificador único para el inicio de sesión.</p>
                    </div>

                    <div>
                        <label for="crear-rol" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                            Rol en el Sistema <span class="text-rose-500">*</span>
                        </label>
                        <select id="crear-rol" name="rol" class="app-input w-full text-base font-semibold py-2.5 px-3.5 rounded-xl min-h-[46px]" required>
                            <option value="">Seleccione un rol...</option>
                            <option value="SuperAdmin">SuperAdmin (Acceso Total)</option>
                            <option value="Administrador">Administrador (Gestión General)</option>
                            <option value="Coordinador">Coordinador Académico</option>
                            <option value="Profesor">Profesor (Docente de Aula)</option>
                            <option value="Estudiante">Estudiante (Solo Lectura / Portal)</option>
                            <option value="Tutor">Tutor / Padre de Familia</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="crear-password" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                        Contraseña Inicial <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" id="crear-password" name="password" class="app-input w-full text-base py-2.5 px-3.5 pr-12 rounded-xl min-h-[46px]" placeholder="Mínimo 6 caracteres" required minlength="6" autocomplete="new-password">
                        <button type="button" onclick="alternarVisibilidadPass('crear-password', this)" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer" aria-label="Mostrar u ocultar contraseña">
                            <span class="text-lg">👁️</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="crear-email" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                            Correo Electrónico
                        </label>
                        <input type="email" id="crear-email" name="email" class="app-input w-full text-base py-2.5 px-3.5 rounded-xl min-h-[46px]" placeholder="usuario@colegio.edu.py" autocomplete="email">
                    </div>
                    <div>
                        <label for="crear-celular" class="block mb-1.5 font-bold text-base text-slate-900 dark:text-white">
                            Teléfono Celular
                        </label>
                        <input type="tel" id="crear-celular" name="celular" class="app-input w-full text-base font-mono py-2.5 px-3.5 rounded-xl min-h-[46px]" placeholder="0981 123 456" autocomplete="tel">
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-800 text-xs sm:text-sm text-slate-600 dark:text-slate-400 flex items-start gap-2.5">
                    <span class="text-base shrink-0">💡</span>
                    <span>Si el rol seleccionado es <strong>Profesor</strong>, <strong>Estudiante</strong> o <strong>Tutor</strong>, el sistema vinculará o creará automáticamente su perfil correspondiente según el número de Cédula/Usuario.</span>
                </div>

            </div>

            <!-- Footer con Acciones -->
            <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 p-4 sm:p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/80">
                <button type="button" onclick="cerrarModalCrearUsuario()" class="btn btn-secondary w-full sm:w-auto text-base font-semibold py-2.5 px-5 min-h-[46px]">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit-crear-usuario" class="btn btn-primary w-full sm:w-auto text-base font-bold py-2.5 px-6 min-h-[46px] inline-flex items-center justify-center gap-2 shadow-sm">
                    <span id="txt-submit-crear-usuario">Registrar Usuario</span>
                </button>
            </div>
        </form>

    </div>
</div>
