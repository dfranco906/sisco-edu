<!-- Modal Accesible: Asignación Granular de Alcance Operativo (Turnos, Niveles, Bachilleratos y Cursos) -->
<div id="modal-alcance-usuario" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-alcance-titulo">
    <div class="relative w-full max-w-4xl max-h-[92vh] bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header -->
        <div class="flex items-center justify-between p-5 sm:p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/80">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-2xl font-bold shadow-xs shrink-0">
                    🎯
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 id="modal-alcance-titulo" class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            Alcance Operativo de Usuario
                        </h2>
                        <span id="alcance-usuario-rol" class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-300">
                            Rol
                        </span>
                    </div>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">
                        Definí los turnos, niveles, bachilleratos y cursos que <strong id="alcance-usuario-nombre" class="text-slate-900 dark:text-slate-200">---</strong> tiene autorizados para gestionar.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalAlcanceUsuario()" class="w-10 h-10 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800 flex items-center justify-center text-xl font-bold transition focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <!-- Banner informativo explicativo -->
        <div class="px-6 py-3 bg-amber-50 dark:bg-amber-950/30 border-b border-amber-200/80 dark:border-amber-900/40 text-xs sm:text-sm text-amber-900 dark:text-amber-200 flex items-start gap-2.5">
            <span class="text-base shrink-0">🛡️</span>
            <div>
                <strong>Segmentación Granular de Responsabilidades:</strong>
                El usuario solo podrá visualizar y operar (asistencias, clases en vivo, calificaciones, planes anuales y estudiantes) en los cursos marcados aquí. Los administradores globales sin restricción y el SuperAdmin conservan acceso general.
            </div>
        </div>

        <!-- Cuerpo del Modal -->
        <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1">
            
            <!-- Barra de Filtros en Lote y Búsqueda Rápida -->
            <div class="space-y-3 p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            🔍
                        </span>
                        <input type="text" id="alcance-filtro-busqueda" placeholder="Filtrar cursos por nombre, sección, turno o bachillerato..." 
                               class="w-full pl-9 pr-3 py-2 text-sm rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button type="button" onclick="marcarTodosLosCursos(true)" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 hover:bg-emerald-200 transition">
                            ✓ Marcar Todos
                        </button>
                        <button type="button" onclick="marcarTodosLosCursos(false)" class="px-3 py-1.5 text-xs font-bold rounded-lg bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-800 hover:bg-rose-200 transition">
                            ✕ Desmarcar Todos
                        </button>
                    </div>
                </div>

                <!-- Botones de Marcado en Bloque por Turno, Nivel y Bachillerato -->
                <div class="space-y-2 pt-2 border-t border-slate-200 dark:border-slate-700/60 text-xs">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-slate-600 dark:text-slate-400">Turnos:</span>
                        <div id="alcance-botones-turnos" class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-slate-400 italic">Cargando...</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-slate-600 dark:text-slate-400">Niveles:</span>
                        <div id="alcance-botones-niveles" class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-slate-400 italic">Cargando...</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-bold text-slate-600 dark:text-slate-400">Bachilleratos:</span>
                        <div id="alcance-botones-bachilleratos" class="flex items-center gap-1.5 flex-wrap">
                            <span class="text-slate-400 italic">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenedor de Cursos con Estado de Carga -->
            <div id="alcance-estado-cargando" class="py-12 text-center text-slate-500">
                <div class="inline-block animate-spin text-3xl mb-2">⏳</div>
                <p class="text-sm font-semibold">Cargando estructura institucional de cursos y alcances...</p>
            </div>

            <div id="alcance-lista-cursos" class="hidden grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <!-- Se inyectan dinámicamente las tarjetas de cursos -->
            </div>

            <div id="alcance-sin-resultados" class="hidden py-8 text-center text-slate-500 bg-slate-50 dark:bg-slate-800/20 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700">
                <span class="text-3xl block mb-2">🔍</span>
                <p class="text-sm font-medium">No se encontraron cursos que coincidan con la búsqueda.</p>
            </div>

        </div>

        <!-- Footer / Botones de Acción -->
        <div class="p-4 sm:p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-800/90 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
                <span>Asignados: <strong id="alcance-contador-seleccionados" class="text-indigo-600 dark:text-indigo-400 font-extrabold text-base">0</strong> de <span id="alcance-contador-total">0</span> cursos institucionales</span>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button type="button" onclick="cerrarModalAlcanceUsuario()" class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold text-sm transition">
                    Cancelar
                </button>
                <button type="button" id="btn-guardar-alcance" onclick="guardarAlcanceUsuario()" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md shadow-indigo-600/30 transition flex items-center justify-center gap-2 cursor-pointer">
                    <span id="alcance-btn-icono">💾</span>
                    <span id="alcance-btn-texto">Guardar Alcance Operativo</span>
                </button>
            </div>
        </div>

    </div>
</div>

<script>
(function() {
    let usuarioActualId = 0;
    let datosAlcance = null;

    window.abrirModalAlcanceUsuario = async function(idUsuario, nombreCompleto, rol) {
        usuarioActualId = idUsuario;
        const modal = document.getElementById('modal-alcance-usuario');
        if (!modal) return;

        // Reset UI
        document.getElementById('alcance-usuario-nombre').textContent = nombreCompleto || 'Usuario #' + idUsuario;
        document.getElementById('alcance-usuario-rol').textContent = rol || 'Usuario';
        document.getElementById('alcance-filtro-busqueda').value = '';
        document.getElementById('alcance-estado-cargando').classList.remove('hidden');
        document.getElementById('alcance-lista-cursos').classList.add('hidden');
        document.getElementById('alcance-sin-resultados').classList.add('hidden');
        document.getElementById('alcance-contador-seleccionados').textContent = '0';
        document.getElementById('alcance-contador-total').textContent = '0';

        modal.classList.remove('hidden');

        try {
            const url = window.BASE_URL ? window.BASE_URL + 'src/api/Usuario/obtener_alcance.php?id_usuario=' + idUsuario : 'src/api/Usuario/obtener_alcance.php?id_usuario=' + idUsuario;
            const res = await fetch(url);
            const respuesta = await res.json();

            if (respuesta.status !== 'success' || !respuesta.data) {
                throw new Error(respuesta.message || 'Error al obtener alcance del usuario.');
            }

            datosAlcance = respuesta.data;
            renderizarEstructuraAlcance(datosAlcance);
        } catch (err) {
            console.error('Error al abrir modal de alcance:', err);
            alert('⚠️ ' + err.message);
            cerrarModalAlcanceUsuario();
        }
    };

    window.cerrarModalAlcanceUsuario = function() {
        const modal = document.getElementById('modal-alcance-usuario');
        if (modal) modal.classList.add('hidden');
        usuarioActualId = 0;
        datosAlcance = null;
    };

    function renderizarEstructuraAlcance(data) {
        document.getElementById('alcance-estado-cargando').classList.add('hidden');
        const container = document.getElementById('alcance-lista-cursos');
        container.innerHTML = '';
        container.classList.remove('hidden');

        const turnosDiv = document.getElementById('alcance-botones-turnos');
        const nivelesDiv = document.getElementById('alcance-botones-niveles');
        const bachiDiv = document.getElementById('alcance-botones-bachilleratos');

        // Render Botones Turnos
        turnosDiv.innerHTML = '';
        (data.turnos || []).forEach(t => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'px-2 py-0.5 rounded-md bg-slate-200 dark:bg-slate-700 hover:bg-indigo-100 hover:text-indigo-800 text-slate-700 dark:text-slate-300 font-medium transition cursor-pointer';
            btn.textContent = t.nombre;
            btn.title = 'Alternar todos los cursos de: ' + t.nombre;
            btn.onclick = () => alternarGrupoGrados(t.grados);
            turnosDiv.appendChild(btn);
        });

        // Render Botones Niveles
        nivelesDiv.innerHTML = '';
        (data.niveles || []).forEach(n => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'px-2 py-0.5 rounded-md bg-slate-200 dark:bg-slate-700 hover:bg-indigo-100 hover:text-indigo-800 text-slate-700 dark:text-slate-300 font-medium transition cursor-pointer';
            btn.textContent = n.nombre;
            btn.title = 'Alternar todos los cursos de: ' + n.nombre;
            btn.onclick = () => alternarGrupoGrados(n.grados);
            nivelesDiv.appendChild(btn);
        });

        // Render Botones Bachilleratos
        bachiDiv.innerHTML = '';
        (data.bachilleratos || []).forEach(b => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'px-2 py-0.5 rounded-md bg-slate-200 dark:bg-slate-700 hover:bg-indigo-100 hover:text-indigo-800 text-slate-700 dark:text-slate-300 font-medium transition cursor-pointer';
            btn.textContent = b.nombre;
            btn.title = 'Alternar todos los cursos de: ' + b.nombre;
            btn.onclick = () => alternarGrupoGrados(b.grados);
            bachiDiv.appendChild(btn);
        });

        // Render Tarjetas de Cursos
        const cursos = data.grados || [];
        document.getElementById('alcance-contador-total').textContent = cursos.length;

        cursos.forEach(c => {
            const card = document.createElement('label');
            card.className = `curso-card flex items-start gap-3 p-3 rounded-xl border-2 transition cursor-pointer select-none ${
                c.asignado 
                    ? 'border-indigo-500 bg-indigo-50/60 dark:bg-indigo-950/30' 
                    : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700'
            }`;
            card.dataset.idGrado = c.id_grado;
            card.dataset.turno = (c.turno_nombre || '').toLowerCase();
            card.dataset.nivel = (c.nivel_nombre || '').toLowerCase();
            card.dataset.bachi = (c.bachillerato_nombre || '').toLowerCase();
            card.dataset.nombre = (c.grado_nombre + ' ' + (c.seccion || '') + ' ' + (c.aula_nombre || '')).toLowerCase();

            card.innerHTML = `
                <input type="checkbox" value="${c.id_grado}" ${c.asignado ? 'checked' : ''} 
                       class="check-grado mt-1 h-4 w-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-1">
                        <strong class="text-sm font-bold text-slate-900 dark:text-white truncate">
                            ${c.grado_nombre} "${c.seccion || 'U'}"
                        </strong>
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-mono font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                            ${c.aula_nombre || 'Sin aula'}
                        </span>
                    </div>
                    <div class="mt-1 flex items-center gap-1.5 flex-wrap text-[11px]">
                        <span class="px-1.5 py-0.2 rounded font-medium bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/40">
                            🌅 ${c.turno_nombre || 'Sin turno'}
                        </span>
                        <span class="px-1.5 py-0.2 rounded font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            📚 ${c.bachillerato_nombre || 'General'}
                        </span>
                    </div>
                </div>
            `;

            const chk = card.querySelector('.check-grado');
            chk.addEventListener('change', () => {
                actualizarEstiloTarjeta(card, chk.checked);
                actualizarContador();
            });

            container.appendChild(card);
        });

        actualizarContador();
    }

    function actualizarEstiloTarjeta(card, isChecked) {
        if (isChecked) {
            card.classList.remove('border-slate-200', 'dark:border-slate-800', 'bg-white', 'dark:bg-slate-900');
            card.classList.add('border-indigo-500', 'bg-indigo-50/60', 'dark:bg-indigo-950/30');
        } else {
            card.classList.remove('border-indigo-500', 'bg-indigo-50/60', 'dark:bg-indigo-950/30');
            card.classList.add('border-slate-200', 'dark:border-slate-800', 'bg-white', 'dark:bg-slate-900');
        }
    }

    function actualizarContador() {
        const seleccionados = document.querySelectorAll('.check-grado:checked').length;
        document.getElementById('alcance-contador-seleccionados').textContent = seleccionados;
    }

    window.marcarTodosLosCursos = function(marcar) {
        const cards = document.querySelectorAll('.curso-card');
        cards.forEach(card => {
            // Si está visible por el filtro de búsqueda
            if (card.style.display !== 'none') {
                const chk = card.querySelector('.check-grado');
                if (chk) {
                    chk.checked = marcar;
                    actualizarEstiloTarjeta(card, marcar);
                }
            }
        });
        actualizarContador();
    };

    function alternarGrupoGrados(idsGradosGrupo) {
        if (!Array.isArray(idsGradosGrupo) || idsGradosGrupo.length === 0) return;
        
        // Ver si la mayoría ya están marcados para decidir si marcamos o desmarcamos
        let marcados = 0;
        idsGradosGrupo.forEach(id => {
            const chk = document.querySelector(`.curso-card[data-id-grado="${id}"] .check-grado`);
            if (chk && chk.checked) marcados++;
        });

        const nuevoEstado = (marcados < idsGradosGrupo.length);

        idsGradosGrupo.forEach(id => {
            const card = document.querySelector(`.curso-card[data-id-grado="${id}"]`);
            if (card) {
                const chk = card.querySelector('.check-grado');
                if (chk) {
                    chk.checked = nuevoEstado;
                    actualizarEstiloTarjeta(card, nuevoEstado);
                }
            }
        });
        actualizarContador();
    }

    // Buscador en tiempo real de cursos
    document.addEventListener('DOMContentLoaded', () => {
        const inputBusqueda = document.getElementById('alcance-filtro-busqueda');
        if (inputBusqueda) {
            inputBusqueda.addEventListener('input', function() {
                const query = this.value.trim().toLowerCase();
                const cards = document.querySelectorAll('.curso-card');
                let visibles = 0;

                cards.forEach(card => {
                    const match = !query || 
                                  card.dataset.nombre.includes(query) || 
                                  card.dataset.turno.includes(query) || 
                                  card.dataset.nivel.includes(query) || 
                                  card.dataset.bachi.includes(query);
                    card.style.display = match ? 'flex' : 'none';
                    if (match) visibles++;
                });

                const sinRes = document.getElementById('alcance-sin-resultados');
                if (sinRes) {
                    sinRes.classList.toggle('hidden', visibles > 0);
                }
            });
        }
    });

    // Guardar Asignación
    window.guardarAlcanceUsuario = async function() {
        if (!usuarioActualId) return;

        const btn = document.getElementById('btn-guardar-alcance');
        const txt = document.getElementById('alcance-btn-texto');
        const ico = document.getElementById('alcance-btn-icono');

        const checkboxes = document.querySelectorAll('.check-grado:checked');
        const idsGrados = Array.from(checkboxes).map(c => parseInt(c.value, 10));

        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-not-allowed');
        ico.textContent = '⏳';
        txt.textContent = 'Guardando cambios...';

        try {
            const url = window.BASE_URL ? window.BASE_URL + 'src/api/Usuario/guardar_alcance.php' : 'src/api/Usuario/guardar_alcance.php';
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_usuario: usuarioActualId,
                    ids_grados: idsGrados
                })
            });

            const data = await res.json();
            if (data.status !== 'success') {
                throw new Error(data.message || 'Error al guardar alcance.');
            }

            alert(`✅ ${data.message} (${data.data.total_asignados} cursos asignados)`);
            cerrarModalAlcanceUsuario();
            
            // Si existe la función de recargar tabla, la ejecutamos
            if (typeof window.recargarTabla === 'function') {
                window.recargarTabla();
            }
        } catch (err) {
            console.error('Error al guardar alcance:', err);
            alert('❌ ' + err.message);
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-70', 'cursor-not-allowed');
            ico.textContent = '💾';
            txt.textContent = 'Guardar Alcance Operativo';
        }
    };
})();
</script>
