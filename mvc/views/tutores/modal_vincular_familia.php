<!-- Modal Gestor Inteligente de Familias y Vínculos Accesible -->
<div id="modal-gestor-familias" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="gf-titulo">
    <div class="relative w-full max-w-3xl max-h-[90vh] bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header Accesible -->
        <div class="flex items-start justify-between gap-4 p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/60">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-2xl font-bold shadow-xs shrink-0">
                    👨‍👩‍👧‍👦
                </div>
                <div>
                    <h2 id="gf-titulo" class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight">
                        Gestor de Familias y Vínculos
                    </h2>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-0.5">
                        Búsqueda predictiva de tutores y vinculación directa de estudiantes y hermanos.
                    </p>
                </div>
            </div>
            <button type="button" onclick="GestorFamilias.cerrar()" class="w-12 h-12 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800 flex items-center justify-center text-2xl font-bold transition focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <div class="p-6 sm:p-8 space-y-6 overflow-y-auto flex-1">

            <!-- PASO 1: SELECCIONAR O CREAR TUTOR -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2.5 text-sm font-extrabold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-black">1</span>
                        Tutor / Responsable Familiar
                    </label>
                    <button type="button" id="gf-btn-toggle-nuevo-tutor" onclick="GestorFamilias.toggleNuevoTutor()" class="text-base text-indigo-700 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-bold hover:underline">
                        + Registrar Nuevo Tutor
                    </button>
                </div>

                <!-- Buscador de Tutores (visible cuando NO hay tutor seleccionado) -->
                <div id="gf-box-busqueda-tutor" class="relative">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" id="gf-input-tutor" placeholder="Escribí CI, nombre, apellido o celular del tutor..."
                               class="w-full min-h-[48px] pl-12 pr-10 py-3 text-base text-slate-900 dark:text-white font-medium bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition" autocomplete="off">
                        <button type="button" id="gf-btn-limpiar-tutor-busqueda" onclick="GestorFamilias.limpiarBusquedaTutor()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hidden text-lg font-bold" aria-label="Limpiar búsqueda">
                            ✕
                        </button>
                    </div>

                    <!-- Lista desplegable de resultados tutores -->
                    <div id="gf-results-tutor" class="hidden absolute z-30 mt-1 w-full bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border-2 border-slate-200 dark:border-slate-700 max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 text-base">
                        <!-- Dinámico -->
                    </div>
                </div>

                <!-- Tarjeta de Tutor Seleccionado -->
                <div id="gf-card-tutor-seleccionado" class="hidden p-5 rounded-2xl bg-indigo-50/90 dark:bg-indigo-950/60 border-2 border-indigo-200 dark:border-indigo-800 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4 min-w-0">
                        <div id="gf-tutor-avatar" class="w-12 h-12 rounded-2xl bg-indigo-600 text-white font-bold flex items-center justify-center text-lg shrink-0 shadow-sm">
                            TO
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <span id="gf-tutor-nombre" class="font-extrabold text-slate-900 dark:text-white text-lg truncate">Nombre Tutor</span>
                                <span id="gf-tutor-ci" class="px-3 py-1 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-900 dark:text-indigo-300 font-mono text-sm font-bold border border-indigo-200 dark:border-indigo-800">CI: 0000000</span>
                            </div>
                            <div class="flex items-center gap-3 mt-1 text-sm font-semibold text-slate-600 dark:text-slate-400 flex-wrap">
                                <span id="gf-tutor-celular">📱 Sin celular</span>
                                <span>•</span>
                                <span id="gf-tutor-hijos-badge" class="truncate font-bold text-emerald-700 dark:text-emerald-400">Sin hijos vinculados</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="GestorFamilias.quitarTutor()" class="min-h-[40px] px-3.5 py-1.5 text-sm rounded-xl font-bold text-rose-700 dark:text-rose-300 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800 transition shrink-0">
                        Cambiar
                    </button>
                </div>

                <!-- Micro-formulario Colapsable: Registrar Nuevo Tutor en el acto -->
                <div id="gf-box-nuevo-tutor" class="hidden p-6 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border-2 border-slate-200 dark:border-slate-700 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-extrabold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider">
                            Registrar Nuevo Tutor en el Sistema
                        </span>
                        <button type="button" onclick="GestorFamilias.toggleNuevoTutor()" class="text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white text-base font-bold">✕ Cancelar</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input type="text" id="gf-nuevo-nombre" placeholder="Nombre(s) *" class="w-full min-h-[46px] px-3.5 py-2.5 text-base bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl" required>
                        <input type="text" id="gf-nuevo-apellido" placeholder="Apellido(s) *" class="w-full min-h-[46px] px-3.5 py-2.5 text-base bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl" required>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <input type="text" id="gf-nuevo-ci" placeholder="Cédula de Identidad *" class="w-full min-h-[46px] px-3.5 py-2.5 text-base bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl" required>
                        <input type="text" id="gf-nuevo-celular" placeholder="Celular / WhatsApp (opcional)" class="w-full min-h-[46px] px-3.5 py-2.5 text-base bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl">
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" onclick="GestorFamilias.toggleNuevoTutor()" class="btn btn-muted min-h-[44px] text-base px-5 font-bold rounded-xl">
                            Cancelar
                        </button>
                        <button type="button" id="gf-btn-guardar-nuevo-tutor" onclick="GestorFamilias.crearNuevoTutor()" class="btn btn-primary min-h-[44px] text-base px-6 font-bold rounded-xl">
                            Guardar y Seleccionar
                        </button>
                    </div>
                </div>
            </div>

            <!-- PASO 2: ESTUDIANTES A VINCULAR (MULTIPLE / HERMANOS) -->
            <div class="space-y-4 pt-4 border-t-2 border-slate-100 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2.5 text-sm font-extrabold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                        <span class="w-6 h-6 rounded-full bg-indigo-600 text-white flex items-center justify-center text-xs font-black">2</span>
                        Estudiantes a Cargo / Hermanos (<span id="gf-cant-estudiantes">0</span>)
                    </label>
                    <span class="text-sm font-semibold text-slate-500 dark:text-slate-400">Podés sumar varios hermanos</span>
                </div>

                <!-- Buscador para agregar estudiantes a la lista -->
                <div class="relative">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" id="gf-input-estudiante" placeholder="Escribí CI o nombre del estudiante para sumarlo a la lista..."
                               class="w-full min-h-[48px] pl-12 pr-10 py-3 text-base text-slate-900 dark:text-white font-medium bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-slate-800 transition" autocomplete="off">
                        <button type="button" id="gf-btn-limpiar-est-busqueda" onclick="GestorFamilias.limpiarBusquedaEstudiante()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-700 dark:hover:text-slate-300 hidden text-lg font-bold" aria-label="Limpiar búsqueda">
                            ✕
                        </button>
                    </div>

                    <!-- Lista desplegable de resultados estudiantes -->
                    <div id="gf-results-estudiante" class="hidden absolute z-20 mt-1 w-full bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border-2 border-slate-200 dark:border-slate-700 max-h-72 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800 text-base">
                        <!-- Dinámico -->
                    </div>
                </div>

                <!-- Contenedor / Tabla de Estudiantes Seleccionados -->
                <div id="gf-box-lista-estudiantes" class="space-y-3">
                    <div id="gf-empty-estudiantes" class="p-8 text-center rounded-2xl border-2 border-dashed border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 text-base">
                        <p class="font-bold text-slate-800 dark:text-slate-200">No hay estudiantes seleccionados.</p>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Buscá arriba por nombre o cédula para agregar hijos o hermanos a vincular.</p>
                    </div>

                    <div id="gf-lista-estudiantes" class="space-y-3 max-h-64 overflow-y-auto pr-1">
                        <!-- Items dinámicos -->
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer / Botones de Acción Accesibles -->
        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-4 p-5 sm:p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60">
            <span id="gf-resumen-estado" class="text-base text-slate-600 dark:text-slate-400 font-medium text-center sm:text-left">
                Seleccioná un tutor y al menos un estudiante.
            </span>
            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                <button type="button" onclick="GestorFamilias.cerrar()" class="btn btn-muted min-h-[46px] text-base py-2.5 px-6 font-bold flex-1 sm:flex-none rounded-xl">
                    Cancelar
                </button>
                <button type="button" id="gf-btn-guardar" onclick="GestorFamilias.guardar()" disabled class="btn btn-success min-h-[46px] text-base py-2.5 px-7 font-bold flex-1 sm:flex-none disabled:opacity-50 disabled:cursor-not-allowed rounded-xl shadow-xs">
                    Guardar Vínculos Familiares
                </button>
            </div>
        </div>

    </div>
</div>
            <span id="gf-resumen-estado" class="text-base text-slate-600 font-medium text-center sm:text-left">
                Seleccioná un tutor y al menos un estudiante.
            </span>
            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                <button type="button" onclick="GestorFamilias.cerrar()" class="btn btn-muted min-h-[46px] text-base py-2.5 px-6 font-bold flex-1 sm:flex-none rounded-xl">
                    Cancelar
                </button>
                <button type="button" id="gf-btn-guardar" onclick="GestorFamilias.guardar()" disabled class="btn btn-success min-h-[46px] text-base py-2.5 px-7 font-bold flex-1 sm:flex-none disabled:opacity-50 disabled:cursor-not-allowed rounded-xl shadow-xs">
                    Guardar Vínculos Familiares
                </button>
            </div>
        </div>

    </div>
</div>

<script>
window.GestorFamilias = (function() {
    let modal = null;
    let tutorSeleccionado = null;
    let estudiantesSeleccionados = new Map();
    let timerBusquedaTutor = null;
    let timerBusquedaEst = null;
    let onSuccessCallback = null;

    function getBaseUrl() {
        const u = window.BASE_URL || '';
        return u.replace(/\/+$/, '');
    }

    function init() {
        modal = document.getElementById('modal-gestor-familias');
        if (!modal) return;

        const inputTutor = document.getElementById('gf-input-tutor');
        if (inputTutor) {
            inputTutor.addEventListener('input', (e) => {
                clearTimeout(timerBusquedaTutor);
                const q = e.target.value.trim();
                const btnLimpiar = document.getElementById('gf-btn-limpiar-tutor-busqueda');
                if (btnLimpiar) btnLimpiar.classList.toggle('hidden', q.length === 0);

                if (q.length < 1) {
                    cerrarResultadosTutor();
                    return;
                }
                timerBusquedaTutor = setTimeout(() => buscarTutores(q), 200);
            });

            inputTutor.addEventListener('focus', () => {
                const q = inputTutor.value.trim();
                if (q.length >= 1) buscarTutores(q);
            });
        }

        const inputEst = document.getElementById('gf-input-estudiante');
        if (inputEst) {
            inputEst.addEventListener('input', (e) => {
                clearTimeout(timerBusquedaEst);
                const q = e.target.value.trim();
                const btnLimpiar = document.getElementById('gf-btn-limpiar-est-busqueda');
                if (btnLimpiar) btnLimpiar.classList.toggle('hidden', q.length === 0);

                if (q.length < 1) {
                    cerrarResultadosEst();
                    return;
                }
                timerBusquedaEst = setTimeout(() => buscarEstudiantes(q), 200);
            });

            inputEst.addEventListener('focus', () => {
                const q = inputEst.value.trim();
                if (q.length >= 1) buscarEstudiantes(q);
            });
        }

        // Cerrar dropdowns si se hace clic afuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#gf-box-busqueda-tutor')) {
                cerrarResultadosTutor();
            }
            if (!e.target.closest('#gf-results-estudiante') && e.target !== inputEst) {
                cerrarResultadosEst();
            }
        });

        // Cerrar modal con tecla Esc
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                cerrar();
            }
        });
    }

    async function buscarTutores(q) {
        const boxRes = document.getElementById('gf-results-tutor');
        if (!boxRes) return;

        boxRes.classList.remove('hidden');
        boxRes.innerHTML = '<div class="p-5 text-center text-slate-500 text-base">Buscando tutores...</div>';

        try {
            const url = `${getBaseUrl()}/src/api/Tutor/buscar_tutores.php?q=${encodeURIComponent(q)}&limit=15`;
            const res = await fetch(url);
            const json = await res.json();
            const lista = json.data || [];

            if (lista.length === 0) {
                boxRes.innerHTML = `
                    <div class="p-6 text-center space-y-3">
                        <p class="text-slate-700 font-semibold text-base">No se encontró ningún tutor con "${q}".</p>
                        <button type="button" onclick="GestorFamilias.toggleNuevoTutor(true)" class="btn btn-secondary text-sm py-2 px-4 rounded-xl font-bold">
                            + Registrar a esta persona como Tutor
                        </button>
                    </div>`;
                return;
            }

            boxRes.innerHTML = lista.map(t => {
                const iniciales = (t.nombre.charAt(0) + (t.apellido ? t.apellido.charAt(0) : '')).toUpperCase();
                const totalHijos = parseInt(t.total_hijos || 0);
                const hijosText = totalHijos > 0 ? `Hijos: ${t.hijos_resumen || ''}` : 'Sin hijos vinculados';
                const badgeHijos = totalHijos > 0 
                    ? `<span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-bold text-xs truncate max-w-[260px] inline-block border border-emerald-200">${hijosText}</span>`
                    : `<span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 font-semibold text-xs border border-slate-200">Sin hijos</span>`;

                const jsonStr = JSON.stringify(t).replace(/"/g, '&quot;');
                return `
                    <div onclick="GestorFamilias.seleccionarTutor(${jsonStr})"
                         class="p-4 hover:bg-indigo-50/80 cursor-pointer flex items-center justify-between gap-4 transition">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center text-sm shrink-0 border border-indigo-200">
                                ${iniciales}
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <span class="font-extrabold text-slate-900 text-base">${t.nombre} ${t.apellido}</span>
                                    <span class="font-mono text-sm font-bold text-slate-500">CI: ${t.cedula_identidad}</span>
                                </div>
                                <div class="flex items-center gap-3 mt-1 text-sm text-slate-600">
                                    ${t.celular ? `<span>📱 ${t.celular}</span>` : ''}
                                    ${badgeHijos}
                                </div>
                            </div>
                        </div>
                        <span class="text-indigo-700 font-extrabold text-sm shrink-0">Elegir →</span>
                    </div>
                `;
            }).join('');
        } catch (e) {
            console.error("Error buscando tutores:", e);
            boxRes.innerHTML = '<div class="p-4 text-rose-600 font-bold text-center text-base">Error al consultar tutores</div>';
        }
    }

    async function buscarEstudiantes(q) {
        const boxRes = document.getElementById('gf-results-estudiante');
        if (!boxRes) return;

        boxRes.classList.remove('hidden');
        boxRes.innerHTML = '<div class="p-5 text-center text-slate-500 text-base">Buscando estudiantes...</div>';

        try {
            const url = `${getBaseUrl()}/src/api/Estudiante/buscar_estudiantes.php?q=${encodeURIComponent(q)}&limit=15`;
            const res = await fetch(url);
            const json = await res.json();
            const lista = json.data || [];

            if (lista.length === 0) {
                boxRes.innerHTML = `<div class="p-6 text-center text-slate-600 text-base font-semibold">No se encontraron estudiantes con "${q}".</div>`;
                return;
            }

            boxRes.innerHTML = lista.map(est => {
                const yaAgregado = estudiantesSeleccionados.has(est.id_estudiante);
                const totalTut = parseInt(est.total_tutores || 0);
                let badgeTutores = '';
                if (totalTut === 0) {
                    badgeTutores = `<span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 font-bold text-xs border border-amber-200">⚠ Sin tutor</span>`;
                } else {
                    badgeTutores = `<span class="px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-900 font-bold text-xs border border-blue-200 truncate max-w-[220px] inline-block" title="${est.tutores_resumen || ''}">👤 ${est.tutores_resumen || totalTut + ' Tutores'}</span>`;
                }

                const jsonStr = JSON.stringify(est).replace(/"/g, '&quot;');
                return `
                    <div onclick="GestorFamilias.agregarEstudiante(${jsonStr})"
                         class="p-4 hover:bg-slate-50 cursor-pointer flex items-center justify-between gap-4 transition ${yaAgregado ? 'opacity-60 bg-slate-50' : ''}">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <span class="font-extrabold text-slate-900 text-base">${est.apellido}, ${est.nombre}</span>
                                <span class="font-mono text-sm font-bold text-slate-500">CI: ${est.cedula_identidad}</span>
                                <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 font-bold text-xs text-slate-700 border border-slate-200">${est.grado_nombre || 'Sin grado'}</span>
                            </div>
                            <div class="mt-1">
                                ${badgeTutores}
                            </div>
                        </div>
                        <span class="text-sm font-bold ${yaAgregado ? 'text-emerald-700' : 'text-indigo-700'} shrink-0">
                            ${yaAgregado ? '✓ Agregado' : '+ Agregar'}
                        </span>
                    </div>
                `;
            }).join('');
        } catch (e) {
            console.error("Error buscando estudiantes:", e);
            boxRes.innerHTML = '<div class="p-4 text-rose-600 font-bold text-center text-base">Error al consultar estudiantes</div>';
        }
    }

    function cerrarResultadosTutor() {
        const box = document.getElementById('gf-results-tutor');
        if (box) box.classList.add('hidden');
    }

    function cerrarResultadosEst() {
        const box = document.getElementById('gf-results-estudiante');
        if (box) box.classList.add('hidden');
    }

    function seleccionarTutor(tutor) {
        tutorSeleccionado = tutor;
        cerrarResultadosTutor();

        const boxBusqueda = document.getElementById('gf-box-busqueda-tutor');
        const cardSel = document.getElementById('gf-card-tutor-seleccionado');
        const boxNuevo = document.getElementById('gf-box-nuevo-tutor');

        if (boxBusqueda) boxBusqueda.classList.add('hidden');
        if (boxNuevo) boxNuevo.classList.add('hidden');

        if (cardSel) {
            cardSel.classList.remove('hidden');
            const iniciales = (tutor.nombre.charAt(0) + (tutor.apellido ? tutor.apellido.charAt(0) : '')).toUpperCase();
            document.getElementById('gf-tutor-avatar').textContent = iniciales;
            document.getElementById('gf-tutor-nombre').textContent = `${tutor.nombre} ${tutor.apellido}`;
            document.getElementById('gf-tutor-ci').textContent = `CI: ${tutor.cedula_identidad}`;
            document.getElementById('gf-tutor-celular').textContent = tutor.celular ? `📱 ${tutor.celular}` : '📱 Sin celular';

            const totalHijos = parseInt(tutor.total_hijos || 0);
            const hijosBadge = document.getElementById('gf-tutor-hijos-badge');
            if (hijosBadge) {
                hijosBadge.textContent = totalHijos > 0 ? `Hijos: ${tutor.hijos_resumen || ''}` : 'Sin hijos vinculados';
            }
        }

        actualizarEstado();
    }

    function quitarTutor() {
        tutorSeleccionado = null;
        const boxBusqueda = document.getElementById('gf-box-busqueda-tutor');
        const cardSel = document.getElementById('gf-card-tutor-seleccionado');
        const inputTutor = document.getElementById('gf-input-tutor');

        if (cardSel) cardSel.classList.add('hidden');
        if (boxBusqueda) boxBusqueda.classList.remove('hidden');
        if (inputTutor) {
            inputTutor.value = '';
            inputTutor.focus();
        }
        document.getElementById('gf-btn-limpiar-tutor-busqueda')?.classList.add('hidden');
        actualizarEstado();
    }

    function agregarEstudiante(est, parentescoDefault = 'Tutor Legal', esPrincipalDefault = 1) {
        if (!est || !est.id_estudiante) return;

        if (estudiantesSeleccionados.has(est.id_estudiante)) {
            if (window.Toast) Toast.info(`"${est.nombre}" ya está en la lista de asignación`);
            cerrarResultadosEst();
            return;
        }

        estudiantesSeleccionados.set(est.id_estudiante, {
            id_estudiante: est.id_estudiante,
            nombre: est.nombre,
            apellido: est.apellido,
            cedula_identidad: est.cedula_identidad,
            grado_nombre: est.grado_nombre || est.grado || 'Sin grado',
            parentesco: parentescoDefault,
            es_principal: esPrincipalDefault
        });

        cerrarResultadosEst();
        limpiarBusquedaEstudiante();
        renderListaEstudiantes();
        actualizarEstado();
    }

    function quitarEstudiante(idEstudiante) {
        estudiantesSeleccionados.delete(idEstudiante);
        renderListaEstudiantes();
        actualizarEstado();
    }

    function renderListaEstudiantes() {
        const boxEmpty = document.getElementById('gf-empty-estudiantes');
        const contLista = document.getElementById('gf-lista-estudiantes');
        const cantSpan = document.getElementById('gf-cant-estudiantes');

        if (!contLista) return;

        const total = estudiantesSeleccionados.size;
        if (cantSpan) cantSpan.textContent = String(total);

        if (total === 0) {
            if (boxEmpty) boxEmpty.classList.remove('hidden');
            contLista.innerHTML = '';
            return;
        }

        if (boxEmpty) boxEmpty.classList.add('hidden');

        let html = '';
        estudiantesSeleccionados.forEach((item, id) => {
            const opcionesParentesco = ['Padre', 'Madre', 'Tutor Legal', 'Encargado/a', 'Abuelo/a', 'Tío/a'].map(opt => 
                `<option value="${opt}" ${item.parentesco === opt ? 'selected' : ''}>${opt}</option>`
            ).join('');

            html += `
                <div class="p-4 rounded-2xl bg-slate-50 border-2 border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-base">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span class="font-extrabold text-slate-900">${item.apellido ? item.apellido + ', ' : ''}${item.nombre}</span>
                            ${item.cedula_identidad ? `<span class="font-mono text-sm font-bold text-slate-500">CI: ${item.cedula_identidad}</span>` : ''}
                            ${item.grado_nombre ? `<span class="px-2.5 py-0.5 rounded-lg bg-indigo-100 text-indigo-900 font-bold text-xs border border-indigo-200">${item.grado_nombre}</span>` : ''}
                        </div>
                    </div>
                    <div class="flex items-center gap-3 self-end sm:self-auto shrink-0 flex-wrap">
                        <div class="flex items-center gap-1.5">
                            <label class="text-xs font-bold text-slate-600 uppercase">Rol:</label>
                            <select onchange="GestorFamilias.cambiarParentesco(${id}, this.value)"
                                    class="min-h-[40px] py-1.5 px-3 text-sm font-bold bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-500">
                                ${opcionesParentesco}
                            </select>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer text-sm font-bold text-slate-700 ml-1">
                            <input type="checkbox" onchange="GestorFamilias.cambiarPrincipal(${id}, this.checked)"
                                   ${item.es_principal ? 'checked' : ''}
                                   class="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-0">
                            <span>Principal</span>
                        </label>
                        <button type="button" onclick="GestorFamilias.quitarEstudiante(${id})"
                                class="w-9 h-9 rounded-xl flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition ml-1"
                                title="Quitar estudiante de la asignación" aria-label="Quitar">
                            ✕
                        </button>
                    </div>
                </div>
            `;
        });

        contLista.innerHTML = html;
    }

    function cambiarParentesco(idEstudiante, valor) {
        if (estudiantesSeleccionados.has(idEstudiante)) {
            const item = estudiantesSeleccionados.get(idEstudiante);
            item.parentesco = valor;
            estudiantesSeleccionados.set(idEstudiante, item);
        }
    }

    function cambiarPrincipal(idEstudiante, checked) {
        if (estudiantesSeleccionados.has(idEstudiante)) {
            const item = estudiantesSeleccionados.get(idEstudiante);
            item.es_principal = checked ? 1 : 0;
            estudiantesSeleccionados.set(idEstudiante, item);
        }
    }

    function actualizarEstado() {
        const btnGuardar = document.getElementById('gf-btn-guardar');
        const resumenSpan = document.getElementById('gf-resumen-estado');

        const tieneTutor = tutorSeleccionado !== null;
        const cantEst = estudiantesSeleccionados.size;

        if (btnGuardar) {
            btnGuardar.disabled = !(tieneTutor && cantEst > 0);
        }

        if (resumenSpan) {
            if (!tieneTutor && cantEst === 0) {
                resumenSpan.textContent = 'Seleccioná un tutor y al menos un estudiante.';
            } else if (!tieneTutor) {
                resumenSpan.textContent = 'Falta seleccionar el tutor responsable.';
            } else if (cantEst === 0) {
                resumenSpan.textContent = 'Falta agregar al menos un estudiante a cargo.';
            } else {
                resumenSpan.innerHTML = `Listo para vincular <strong class="text-indigo-700 font-bold">${cantEst} estudiante(s)</strong> con <strong>${tutorSeleccionado.nombre} ${tutorSeleccionado.apellido}</strong>.`;
            }
        }
    }

    function toggleNuevoTutor(abrir = null) {
        const box = document.getElementById('gf-box-nuevo-tutor');
        const boxBusqueda = document.getElementById('gf-box-busqueda-tutor');
        if (!box) return;

        const debeAbrir = abrir !== null ? abrir : box.classList.contains('hidden');
        if (debeAbrir) {
            box.classList.remove('hidden');
            if (boxBusqueda) boxBusqueda.classList.add('hidden');
            cerrarResultadosTutor();
            document.getElementById('gf-nuevo-nombre')?.focus();
        } else {
            box.classList.add('hidden');
            if (!tutorSeleccionado && boxBusqueda) boxBusqueda.classList.remove('hidden');
        }
    }

    async function crearNuevoTutor() {
        const nombre = document.getElementById('gf-nuevo-nombre')?.value.trim();
        const apellido = document.getElementById('gf-nuevo-apellido')?.value.trim();
        const ci = document.getElementById('gf-nuevo-ci')?.value.trim();
        const celular = document.getElementById('gf-nuevo-celular')?.value.trim();

        if (!nombre || !apellido || !ci) {
            window.Toast ? Toast.warning("Nombre, apellido y cédula son obligatorios") : alert("Complete los campos obligatorios");
            return;
        }

        const btn = document.getElementById('gf-btn-guardar-nuevo-tutor');
        if (btn) btn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('nombre', nombre);
            formData.append('apellido', apellido);
            formData.append('cedula_identidad', ci);
            formData.append('celular', celular);

            const res = await fetch(`${getBaseUrl()}/src/api/Tutor/crear_tutor.php`, {
                method: 'POST',
                body: formData
            });
            const json = await res.json();

            if (json.status === 'success' && json.data && json.data.id_tutor) {
                if (window.Toast) Toast.success("Tutor registrado y seleccionado");
                const nuevoTutorObj = {
                    id_tutor: json.data.id_tutor,
                    nombre: nombre,
                    apellido: apellido,
                    cedula_identidad: ci,
                    celular: celular,
                    total_hijos: 0,
                    hijos_resumen: ''
                };
                toggleNuevoTutor(false);
                seleccionarTutor(nuevoTutorObj);
            } else {
                if (window.Toast) Toast.error(json.message || "Error al crear tutor");
                else alert(json.message);
            }
        } catch (e) {
            console.error("Error al crear tutor:", e);
            if (window.Toast) Toast.error("Error de comunicación al registrar tutor");
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    async function guardar() {
        if (!tutorSeleccionado || estudiantesSeleccionados.size === 0) {
            return;
        }

        const btn = document.getElementById('gf-btn-guardar');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = 'Guardando vínculos...';
        }

        const payload = {
            id_tutor: tutorSeleccionado.id_tutor,
            estudiantes: Array.from(estudiantesSeleccionados.values()).map(e => ({
                id_estudiante: e.id_estudiante,
                parentesco: e.parentesco,
                es_principal: e.es_principal
            }))
        };

        try {
            const res = await fetch(`${getBaseUrl()}/src/api/Tutor/vincular_hijo.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();

            if (json.status === 'success') {
                if (window.Toast) Toast.success(json.message || "Vínculos familiares registrados correctamente");
                cerrar();
                if (typeof onSuccessCallback === 'function') {
                    onSuccessCallback(json);
                } else {
                    setTimeout(() => location.reload(), 600);
                }
            } else {
                if (window.Toast) Toast.error(json.message || "Error al registrar vínculos");
                else alert(json.message);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Guardar Vínculos Familiares';
                }
            }
        } catch (e) {
            console.error("Error al guardar vínculos:", e);
            if (window.Toast) Toast.error("Error de red al guardar vínculos familiares");
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = 'Guardar Vínculos Familiares';
            }
        }
    }

    function limpiarBusquedaTutor() {
        const inp = document.getElementById('gf-input-tutor');
        if (inp) {
            inp.value = '';
            inp.focus();
        }
        document.getElementById('gf-btn-limpiar-tutor-busqueda')?.classList.add('hidden');
        cerrarResultadosTutor();
    }

    function limpiarBusquedaEstudiante() {
        const inp = document.getElementById('gf-input-estudiante');
        if (inp) {
            inp.value = '';
        }
        document.getElementById('gf-btn-limpiar-est-busqueda')?.classList.add('hidden');
        cerrarResultadosEst();
    }

    function abrir(opciones = {}) {
        if (!modal) init();
        if (!modal) return;

        // Reset
        tutorSeleccionado = null;
        estudiantesSeleccionados.clear();
        onSuccessCallback = opciones.onSuccess || null;

        quitarTutor();
        toggleNuevoTutor(false);
        limpiarBusquedaTutor();
        limpiarBusquedaEstudiante();

        // Si viene con tutor inicial
        if (opciones.tutorInicial) {
            seleccionarTutor(opciones.tutorInicial);
        }

        // Si viene con estudiante inicial (ej. desde aula_detalle)
        if (opciones.estudianteInicial) {
            agregarEstudiante(
                opciones.estudianteInicial,
                opciones.parentesco || 'Tutor Legal',
                opciones.es_principal !== undefined ? opciones.es_principal : 1
            );
        } else {
            renderListaEstudiantes();
        }

        actualizarEstado();

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Enfocar primer campo relevante
        setTimeout(() => {
            if (opciones.tutorInicial) {
                document.getElementById('gf-input-estudiante')?.focus();
            } else {
                document.getElementById('gf-input-tutor')?.focus();
            }
        }, 100);
    }

    function cerrar() {
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        cerrarResultadosTutor();
        cerrarResultadosEst();
    }

    return {
        init,
        abrir,
        cerrar,
        seleccionarTutor,
        quitarTutor,
        agregarEstudiante,
        quitarEstudiante,
        cambiarParentesco,
        cambiarPrincipal,
        toggleNuevoTutor,
        crearNuevoTutor,
        guardar,
        limpiarBusquedaTutor,
        limpiarBusquedaEstudiante
    };
})();

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', () => {
    GestorFamilias.init();
});

// Función de compatibilidad global para llamadas existentes desde botones
function abrirModalVincular(idEstudiante = null, nombreEstudiante = '', apellidoEstudiante = '', ciEstudiante = '', gradoNombre = '') {
    if (idEstudiante) {
        GestorFamilias.abrir({
            estudianteInicial: {
                id_estudiante: parseInt(idEstudiante),
                nombre: nombreEstudiante,
                apellido: apellidoEstudiante || '',
                cedula_identidad: ciEstudiante || '',
                grado_nombre: gradoNombre || ''
            }
        });
    } else {
        GestorFamilias.abrir();
    }
}
function cerrarModalVincular() {
    GestorFamilias.cerrar();
}
</script>
