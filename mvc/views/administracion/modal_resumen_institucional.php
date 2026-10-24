<!-- Submodal Accesible: Resumen y Estadísticas Institucionales -->
<div id="modal-resumen-institucional" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-resumen-titulo">
    <div class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Cabecera del Submodal -->
        <div class="flex items-center justify-between p-6 sm:p-7 border-b border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-800/60">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 flex items-center justify-center text-2xl font-bold shadow-xs">
                    📊
                </div>
                <div>
                    <h2 id="modal-resumen-titulo" class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Resumen General del Establecimiento
                    </h2>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-0.5">
                        Consolidado de entidades activas en el ciclo lectivo
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalResumen()" class="w-12 h-12 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800 flex items-center justify-center text-2xl font-bold transition focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <!-- Cuerpo del Submodal con scroll -->
        <div class="p-6 sm:p-8 overflow-y-auto space-y-8 flex-1">
            
            <!-- Bloque 1: Alumnado y Comunidad Escolar -->
            <div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-indigo-600"></span>
                    Comunidad Escolar
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800/60 flex flex-col justify-between">
                        <div>
                            <span class="text-sm font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300">Estudiantes</span>
                            <div class="text-4xl font-black text-slate-900 dark:text-white mt-2"><?= (int)$totales['total_estudiantes'] ?></div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Alumnos matriculados activos</p>
                        </div>
                        <a href="<?= base_url('mvc/views/estudiantes/index.php') ?>" class="mt-4 inline-flex items-center justify-between text-sm font-bold text-indigo-700 dark:text-indigo-300 hover:text-indigo-900 dark:hover:text-indigo-200 pt-2 border-t border-indigo-200/80 dark:border-indigo-800/60">
                            <span>Ver nómina</span>
                            <span>→</span>
                        </a>
                    </div>

                    <div class="p-5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/60 flex flex-col justify-between">
                        <div>
                            <span class="text-sm font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-300">Tutores y Familias</span>
                            <div class="text-4xl font-black text-slate-900 dark:text-white mt-2"><?= (int)$totales['total_tutores'] ?></div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Responsables registrados</p>
                        </div>
                        <a href="<?= base_url('mvc/views/tutores/index.php') ?>" class="mt-4 inline-flex items-center justify-between text-sm font-bold text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 dark:hover:text-emerald-200 pt-2 border-t border-emerald-200/80 dark:border-emerald-800/60">
                            <span>Ver familias</span>
                            <span>→</span>
                        </a>
                    </div>

                    <div class="p-5 rounded-2xl bg-blue-50/70 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800/60 flex flex-col justify-between">
                        <div>
                            <span class="text-sm font-bold uppercase tracking-wider text-blue-700 dark:text-blue-300">Personal y Cuentas</span>
                            <div class="text-4xl font-black text-slate-900 dark:text-white mt-2"><?= (int)$totales['total_usuarios'] ?></div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Docentes, coordinadores y admin</p>
                        </div>
                        <a href="<?= base_url('mvc/views/usuarios/index.php') ?>" class="mt-4 inline-flex items-center justify-between text-sm font-bold text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-200 pt-2 border-t border-blue-200/80 dark:border-blue-800/60">
                            <span>Ver usuarios</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bloque 2: Oferta Académica y Curricular -->
            <div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-blue-600"></span>
                    Estructura Curricular y Carga Docente
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-center">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Turnos</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white mt-1"><?= (int)$totales['total_turnos'] ?></div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Jornadas activas</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-center">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Niveles</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white mt-1"><?= (int)$totales['total_niveles'] ?></div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ciclos formativos</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-center">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Bachilleratos</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white mt-1"><?= (int)$totales['total_bachilleratos'] ?></div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Especialidades</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-center">
                        <span class="text-xs sm:text-sm font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">Cursos / Grados</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white mt-1"><?= (int)$totales['total_grados'] ?></div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Secciones habilitadas</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4">
                    <div class="p-4 rounded-2xl bg-purple-50/70 dark:bg-purple-950/50 border border-purple-200 dark:border-purple-800/60">
                        <span class="text-sm font-bold uppercase tracking-wider text-purple-700 dark:text-purple-300">Materias de la Malla</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white mt-1"><?= (int)$totales['total_materias'] ?></div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">Asignaturas científicas y técnicas</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-teal-50/70 dark:bg-teal-950/50 border border-teal-200 dark:border-teal-800/60">
                        <span class="text-sm font-bold uppercase tracking-wider text-teal-700 dark:text-teal-300">Asignaciones Docentes</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white mt-1"><?= (int)$totales['total_asignaciones'] ?></div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">Docentes con aula y materia asignada</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-rose-50/70 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800/60">
                        <span class="text-sm font-bold uppercase tracking-wider text-rose-700 dark:text-rose-300">Aulas Físicas</span>
                        <div class="text-3xl font-black text-slate-900 dark:text-white mt-1"><?= (int)$totales['total_aulas'] ?></div>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">Salas de clase y laboratorios</p>
                    </div>
                </div>
            </div>

            <!-- Pipeline Visual Accesible -->
            <div class="p-6 rounded-2xl bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-3">
                <h4 class="text-base font-bold text-slate-900 dark:text-white">Principio de Organización en Cascada</h4>
                <p class="text-base text-slate-700 dark:text-slate-300 leading-relaxed">
                    Cada materia pertenece a un <strong>Grado</strong>, el cual se ubica en un <strong>Bachillerato</strong> (o Ciclo Básico), dentro de un <strong>Nivel Académico</strong> y operando en un <strong>Turno</strong> específico. Respetar este orden garantiza que los profesores y coordinadores visualicen sus listas sin duplicidad.
                </p>
            </div>

        </div>

        <!-- Pie del Submodal -->
        <div class="p-5 sm:p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 flex justify-end">
            <button type="button" onclick="cerrarModalResumen()" class="btn btn-muted min-h-[48px] px-8 text-base font-bold rounded-xl">
                Entendido / Cerrar
            </button>
        </div>

    </div>
</div>

<script>
function abrirModalResumen() {
    const modal = document.getElementById('modal-resumen-institucional');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
}

function cerrarModalResumen() {
    const modal = document.getElementById('modal-resumen-institucional');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

// Cerrar con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalResumen();
    }
});
</script>
