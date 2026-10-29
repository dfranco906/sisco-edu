<!-- Modal Accesible: Asignación Granular de Alcance Operativo (Turnos, Niveles, Bachilleratos y Cursos) -->
<div id="modal-alcance-usuario" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-alcance-titulo">
    <div class="relative w-full max-w-5xl max-h-[92vh] bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Header Accesible -->
        <div class="flex items-center justify-between p-5 sm:p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-800/90">
            <div class="flex items-center gap-3.5">
                <div class="w-14 h-14 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-3xl font-black shadow-xs shrink-0 border border-indigo-200 dark:border-indigo-800">
                    🎯
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 id="modal-alcance-titulo" class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                            Alcance Operativo de Usuario
                        </h2>
                        <span id="alcance-usuario-rol" class="px-3 py-1 rounded-xl text-sm font-bold uppercase tracking-wider bg-indigo-100 text-indigo-800 dark:bg-indigo-900/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                            Rol
                        </span>
                    </div>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1">
                        Definí los turnos, niveles y cursos que <strong id="alcance-usuario-nombre" class="text-slate-900 dark:text-slate-100 font-extrabold">---</strong> tiene autorizados para operar.
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalAlcanceUsuario()" class="min-w-[44px] min-h-[44px] w-11 h-11 rounded-xl text-slate-400 hover:text-slate-800 dark:hover:text-white hover:bg-slate-200/70 dark:hover:bg-slate-800 flex items-center justify-center text-2xl font-bold transition focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer" aria-label="Cerrar modal alcance">
                ✕
            </button>
        </div>

        <!-- Banner informativo explicativo con tipografía grande y contraste -->
        <div class="p-4 sm:p-5 bg-amber-50 dark:bg-amber-950/30 border-b border-amber-200 dark:border-amber-900/40 text-base text-amber-950 dark:text-amber-200 flex items-start gap-3.5">
            <span class="text-2xl shrink-0">🛡️</span>
            <div>
                <strong class="font-extrabold text-amber-900 dark:text-amber-100">Segmentación Granular de Responsabilidades:</strong>
                <span>El usuario solo podrá visualizar y operar (asistencias, clases en vivo, calificaciones, planes anuales y expedientes de estudiantes) en los cursos marcados aquí. Los administradores globales y el SuperAdmin conservan acceso general.</span>
            </div>
        </div>

        <!-- Cuerpo del Modal -->
        <div class="p-5 sm:p-6 space-y-5 overflow-y-auto flex-1 text-base">
            
            <!-- Barra de Filtros en Lote y Búsqueda Rápida -->
            <div class="space-y-4 p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border-2 border-slate-200 dark:border-slate-800">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-lg">
                            🔍
                        </span>
                        <input type="text" id="alcance-filtro-busqueda" placeholder="Filtrar cursos por nombre, sección, turno, aula o bachillerato..." 
                               class="app-input w-full pl-11 pr-4 py-3 text-base rounded-2xl min-h-[48px] border-2 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                        <button type="button" onclick="marcarTodosLosCursos(true)" class="min-h-[44px] px-4 py-2 text-base font-bold rounded-xl bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/80 text-emerald-900 dark:text-emerald-200 border-2 border-emerald-300 dark:border-emerald-800 shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <span>✓</span>
                            <span>Marcar Todos</span>
                        </button>
                        <button type="button" onclick="marcarTodosLosCursos(false)" class="min-h-[44px] px-4 py-2 text-base font-bold rounded-xl bg-rose-100 hover:bg-rose-200 dark:bg-rose-950/60 dark:hover:bg-rose-900/80 text-rose-900 dark:text-rose-200 border-2 border-rose-300 dark:border-rose-800 shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                            <span>✕</span>
                            <span>Desmarcar Todos</span>
                        </button>
                    </div>
                </div>

                <!-- Botones Accesibles de Marcado en Bloque por Turno, Nivel y Bachillerato -->
                <div class="space-y-3 pt-3 border-t-2 border-slate-200 dark:border-slate-700/60 text-base">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="font-extrabold text-slate-700 dark:text-slate-300 min-w-[120px]">Turnos:</span>
                        <div id="alcance-botones-turnos" class="flex items-center gap-2 flex-wrap">
                            <span class="text-slate-400 italic">Cargando turnos...</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="font-extrabold text-slate-700 dark:text-slate-300 min-w-[120px]">Ciclos / Niveles:</span>
                        <div id="alcance-botones-niveles" class="flex items-center gap-2 flex-wrap">
                            <span class="text-slate-400 italic">Cargando niveles...</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="font-extrabold text-slate-700 dark:text-slate-300 min-w-[120px]">Bachilleratos:</span>
                        <div id="alcance-botones-bachilleratos" class="flex items-center gap-2 flex-wrap">
                            <span class="text-slate-400 italic">Cargando bachilleratos...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenedor de Cursos con Estado de Carga -->
            <div id="alcance-estado-cargando" class="py-14 text-center text-slate-500">
                <div class="inline-block animate-spin text-4xl mb-3">⏳</div>
                <p class="text-lg font-bold text-slate-700 dark:text-slate-300">Cargando estructura institucional de cursos y alcances...</p>
            </div>

            <!-- Grid de Cursos (Tarjetas con tipografía grande y contraste WCAG 2.1 AA) -->
            <div id="alcance-lista-cursos" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                <!-- Se inyectan dinámicamente las tarjetas de cursos -->
            </div>

            <!-- Estado Vacío en Búsqueda -->
            <div id="alcance-sin-resultados" class="hidden py-12 text-center text-slate-500 bg-slate-50 dark:bg-slate-800/30 rounded-3xl border-2 border-dashed border-slate-300 dark:border-slate-700">
                <span class="text-4xl block mb-2">🔍</span>
                <p class="text-lg font-bold text-slate-800 dark:text-slate-200">No se encontraron cursos coincidentes</p>
                <p class="text-base text-slate-600 dark:text-slate-400 mt-1">Modificá el término de búsqueda para ver más cursos.</p>
            </div>

        </div>

        <!-- Footer Accesible con Conteo en Vivo y Botones Grandes (min-h-[46px]) -->
        <div class="p-4 sm:p-6 border-t-2 border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-800/90 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="text-base text-slate-700 dark:text-slate-300 flex items-center gap-2.5">
                <span class="w-3 h-3 rounded-full bg-indigo-500 animate-pulse"></span>
                <span>Asignados: <strong id="alcance-contador-seleccionados" class="text-indigo-700 dark:text-indigo-300 font-black text-xl">0</strong> de <span id="alcance-contador-total" class="font-extrabold text-slate-900 dark:text-white">0</span> cursos institucionales</span>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto justify-end flex-wrap">
                <button type="button" onclick="cerrarModalAlcanceUsuario()" class="btn btn-secondary w-full sm:w-auto text-base font-semibold py-2.5 px-6 min-h-[46px]">
                    Cancelar
                </button>
                <button type="button" id="btn-guardar-alcance" onclick="guardarAlcanceUsuario()" class="btn btn-primary w-full sm:w-auto text-base font-bold py-2.5 px-6 min-h-[46px] shadow-sm inline-flex items-center justify-center gap-2 cursor-pointer">
                    <span id="alcance-btn-icono" class="text-lg">💾</span>
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

    function getBaseUrl() {
        return (window.BASE_URL || '').replace(/\/+$/, '');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

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
            const url = `${getBaseUrl()}/src/api/Usuario/obtener_alcance.php?id_usuario=${encodeURIComponent(idUsuario)}`;
            const res = await fetch(url);
            const respuesta = await res.json();

            if (respuesta.status !== 'success' || !respuesta.data) {
                throw new Error(respuesta.message || 'Error al obtener alcance del usuario.');
            }

            datosAlcance = respuesta.data;
            renderizarEstructuraAlcance(datosAlcance);
        } catch (err) {
            console.error('Error al abrir modal de alcance:', err);
            if (window.Toast) {
                window.Toast.error(err.message || 'Error al cargar los alcances.');
            } else {
                alert('⚠️ ' + err.message);
            }
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

        // Render Botones Turnos (Pills accesibles con min-h-[40px] y text-base)
        turnosDiv.innerHTML = '';
        (data.turnos || []).forEach(t => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pill-filtro px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100 hover:text-indigo-900 dark:hover:bg-indigo-950/70 dark:hover:text-indigo-200 text-slate-800 dark:text-slate-200 font-bold text-sm sm:text-base border-2 border-slate-200 dark:border-slate-700 transition cursor-pointer flex items-center gap-1.5';
            btn.innerHTML = `<span>🌅 ${escapeHtml(t.nombre)}</span> <span class="text-xs bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded-md font-mono">${(t.grados || []).length}</span>`;
            btn.title = 'Alternar todos los cursos de: ' + t.nombre;
            btn.onclick = () => alternarGrupoGrados(t.grados);
            turnosDiv.appendChild(btn);
        });

        // Render Botones Niveles
        nivelesDiv.innerHTML = '';
        (data.niveles || []).forEach(n => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pill-filtro px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100 hover:text-indigo-900 dark:hover:bg-indigo-950/70 dark:hover:text-indigo-200 text-slate-800 dark:text-slate-200 font-bold text-sm sm:text-base border-2 border-slate-200 dark:border-slate-700 transition cursor-pointer flex items-center gap-1.5';
            btn.innerHTML = `<span>📚 ${escapeHtml(n.nombre)}</span> <span class="text-xs bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded-md font-mono">${(n.grados || []).length}</span>`;
            btn.title = 'Alternar todos los cursos de: ' + n.nombre;
            btn.onclick = () => alternarGrupoGrados(n.grados);
            nivelesDiv.appendChild(btn);
        });

        // Render Botones Bachilleratos
        bachiDiv.innerHTML = '';
        (data.bachilleratos || []).forEach(b => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'pill-filtro px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-indigo-100 hover:text-indigo-900 dark:hover:bg-indigo-950/70 dark:hover:text-indigo-200 text-slate-800 dark:text-slate-200 font-bold text-sm sm:text-base border-2 border-slate-200 dark:border-slate-700 transition cursor-pointer flex items-center gap-1.5';
            btn.innerHTML = `<span>🎓 ${escapeHtml(b.nombre)}</span> <span class="text-xs bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded-md font-mono">${(b.grados || []).length}</span>`;
            btn.title = 'Alternar todos los cursos de: ' + b.nombre;
            btn.onclick = () => alternarGrupoGrados(b.grados);
            bachiDiv.appendChild(btn);
        });

        // Render Tarjetas de Cursos
        const cursos = data.grados || [];
        document.getElementById('alcance-contador-total').textContent = cursos.length;

        cursos.forEach(c => {
            const card = document.createElement('label');
            card.className = `curso-card flex items-start gap-3.5 p-4 sm:p-5 rounded-2xl border-2 transition-all cursor-pointer select-none ${
                c.asignado 
                    ? 'border-indigo-600 dark:border-indigo-500 bg-indigo-50/80 dark:bg-indigo-950/50 shadow-xs ring-2 ring-indigo-500/20' 
                    : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700'
            }`;
            card.dataset.idGrado = c.id_grado;
            card.dataset.turno = (c.turno_nombre || '').toLowerCase();
            card.dataset.nivel = (c.nivel_nombre || '').toLowerCase();
            card.dataset.bachi = (c.bachillerato_nombre || '').toLowerCase();
            card.dataset.nombre = (c.grado_nombre + ' ' + (c.seccion || '') + ' ' + (c.aula_nombre || '')).toLowerCase();

            card.innerHTML = `
                <input type="checkbox" value="${c.id_grado}" ${c.asignado ? 'checked' : ''} 
                       class="check-grado w-6 h-6 rounded-lg text-indigo-600 border-2 border-slate-300 dark:border-slate-600 focus:ring-indigo-500 cursor-pointer mt-0.5 shrink-0">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2 flex-wrap">
                        <strong class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white truncate">
                            ${escapeHtml(c.grado_nombre)} "${escapeHtml(c.seccion || 'U')}"
                        </strong>
                        <span class="text-sm px-2.5 py-1 rounded-lg font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            🚪 ${escapeHtml(c.aula_nombre || 'Sin aula')}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center gap-2 flex-wrap text-sm">
                        <span class="px-2.5 py-1 rounded-lg font-bold bg-blue-100 dark:bg-blue-950/60 text-blue-900 dark:text-blue-200 border border-blue-200 dark:border-blue-800">
                            🌅 ${escapeHtml(c.turno_nombre || 'Sin turno')}
                        </span>
                        <span class="px-2.5 py-1 rounded-lg font-bold bg-indigo-100 dark:bg-indigo-950/60 text-indigo-900 dark:text-indigo-200 border border-indigo-200 dark:border-indigo-800">
                            📚 ${escapeHtml(c.bachillerato_nombre || 'General')}
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
            card.classList.add('border-indigo-600', 'dark:border-indigo-500', 'bg-indigo-50/80', 'dark:bg-indigo-950/50', 'shadow-xs', 'ring-2', 'ring-indigo-500/20');
        } else {
            card.classList.remove('border-indigo-600', 'dark:border-indigo-500', 'bg-indigo-50/80', 'dark:bg-indigo-950/50', 'shadow-xs', 'ring-2', 'ring-indigo-500/20');
            card.classList.add('border-slate-200', 'dark:border-slate-800', 'bg-white', 'dark:bg-slate-900');
        }
    }

    function actualizarContador() {
        const seleccionados = document.querySelectorAll('.check-grado:checked').length;
        const total = document.querySelectorAll('.check-grado').length;
        document.getElementById('alcance-contador-seleccionados').textContent = seleccionados;
        document.getElementById('alcance-contador-total').textContent = total;
    }

    window.marcarTodosLosCursos = function(marcar) {
        const cards = document.querySelectorAll('.curso-card');
        cards.forEach(card => {
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
            const url = `${getBaseUrl()}/src/api/Usuario/guardar_alcance.php`;
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

            if (window.Toast) {
                window.Toast.success(`${data.message} (${data.data.total_asignados} cursos asignados)`, 'Alcance Guardado');
            } else {
                alert(`✅ ${data.message} (${data.data.total_asignados} cursos asignados)`);
            }

            cerrarModalAlcanceUsuario();
            
            // Si existe la función de recargar tabla, la ejecutamos
            if (typeof window.recargarTabla === 'function') {
                window.recargarTabla();
            }
        } catch (err) {
            console.error('Error al guardar alcance:', err);
            if (window.Toast) {
                window.Toast.error(err.message || 'No se pudo guardar el alcance.', 'Error');
            } else {
                alert('❌ ' + err.message);
            }
        } finally {
            btn.disabled = false;
            btn.classList.remove('opacity-70', 'cursor-not-allowed');
            ico.textContent = '💾';
            txt.textContent = 'Guardar Alcance Operativo';
        }
    };
})();
</script>
