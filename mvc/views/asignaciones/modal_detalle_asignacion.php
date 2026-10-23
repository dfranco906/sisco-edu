<!-- Submodal Accesible: Detalle Integral de Asignación Docente -->
<div id="modal-detalle-asignacion" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-det-asig-titulo">
    <div class="relative w-full max-w-3xl max-h-[90vh] bg-white rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Cabecera del Submodal -->
        <div class="flex items-center justify-between p-6 sm:p-7 border-b border-slate-200 bg-slate-50/80">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl font-bold shadow-xs shrink-0">
                    📋
                </div>
                <div>
                    <h2 id="modal-det-asig-titulo" class="text-2xl font-extrabold text-slate-900 tracking-tight">
                        Detalle de Asignación Docente
                    </h2>
                    <p id="modal-det-asig-subtitulo" class="text-base text-slate-600 mt-0.5">
                        Carga horaria, distribución semanal y estudiantes matriculados
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalDetalleAsignacion()" class="w-12 h-12 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 flex items-center justify-center text-2xl font-bold transition focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <!-- Cuerpo del Submodal con scroll -->
        <div class="p-6 sm:p-8 space-y-6 overflow-y-auto flex-1">
            
            <!-- Resumen de Asignatura y Docente -->
            <div class="p-6 rounded-2xl bg-slate-50 border-2 border-slate-200 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <span class="text-sm font-extrabold uppercase tracking-wider text-blue-700 block mb-1">Materia / Asignatura</span>
                        <h3 id="det-asig-materia" class="text-2xl sm:text-3xl font-black text-slate-900">Cargando materia...</h3>
                    </div>
                    <span id="det-asig-anio-badge" class="px-4 py-1.5 rounded-full text-base font-extrabold bg-blue-100 text-blue-900 border border-blue-200 self-start sm:self-auto">
                        Año Lectivo 2026
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-200/80">
                    <div>
                        <span class="text-xs uppercase font-extrabold text-slate-500 tracking-wider block mb-1">Docente Responsable</span>
                        <p id="det-asig-profesor" class="text-lg font-bold text-slate-800">Cargando docente...</p>
                        <p id="det-asig-profesor-ci" class="text-sm text-slate-600 font-medium mt-0.5">C.I. ---</p>
                    </div>
                    <div>
                        <span class="text-xs uppercase font-extrabold text-slate-500 tracking-wider block mb-1">Curso y Turno</span>
                        <p id="det-asig-grado" class="text-lg font-bold text-slate-800">Cargando curso...</p>
                        <p id="det-asig-aula" class="text-sm text-slate-600 font-medium mt-0.5">Aula: ---</p>
                    </div>
                </div>
            </div>

            <!-- Franja de Métricas de la Cátedra (3 Tarjetas) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Horas Cátedra Semanales -->
                <div class="p-5 rounded-2xl bg-blue-50/70 border-2 border-blue-200 text-center">
                    <span class="text-xs uppercase font-extrabold text-blue-900 tracking-wider block">Carga Semanal</span>
                    <div id="det-asig-horas" class="text-3xl font-black text-slate-900 mt-1.5">0</div>
                    <p class="text-xs text-slate-600 font-medium mt-0.5">Horas cátedra semanales</p>
                </div>

                <!-- Alumnos Matriculados -->
                <div class="p-5 rounded-2xl bg-emerald-50/70 border-2 border-emerald-200 text-center">
                    <span class="text-xs uppercase font-extrabold text-emerald-900 tracking-wider block">Alumnos Activos</span>
                    <div id="det-asig-alumnos" class="text-3xl font-black text-slate-900 mt-1.5">0</div>
                    <p class="text-xs text-slate-600 font-medium mt-0.5">Matriculados en lista</p>
                </div>

                <!-- Plan Anual Curricular -->
                <div class="p-5 rounded-2xl bg-indigo-50/70 border-2 border-indigo-200 text-center">
                    <span class="text-xs uppercase font-extrabold text-indigo-900 tracking-wider block">Plan Anual</span>
                    <div id="det-asig-plan-estado" class="text-lg font-extrabold text-indigo-900 mt-2">
                        <span class="inline-block px-3 py-1 bg-white rounded-xl border border-indigo-200 shadow-2xs">Sin plan</span>
                    </div>
                    <p class="text-xs text-slate-600 font-medium mt-0.5">Estado pedagógico</p>
                </div>
            </div>

            <!-- Grilla / Lista de Horarios Semanales -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-indigo-600 inline-block"></span>
                            Distribución de Clases Semanales
                        </h4>
                        <p class="text-sm text-slate-600">Días, horarios y aulas en los que se dicta esta cátedra:</p>
                    </div>
                    <span id="det-asig-cant-bloques" class="text-sm font-bold px-3 py-1 bg-slate-100 text-slate-700 rounded-xl border border-slate-200">
                        0 bloques
                    </span>
                </div>

                <!-- Tabla de Horarios -->
                <div class="overflow-x-auto rounded-2xl border-2 border-slate-200 shadow-xs" id="box-tabla-horarios">
                    <table class="w-full text-left text-base" id="tabla-det-horarios">
                        <thead class="bg-slate-100 text-slate-700 uppercase font-bold text-xs sm:text-sm border-b-2 border-slate-200">
                            <tr>
                                <th class="p-4">Día de la Semana</th>
                                <th class="p-4">Horario Inicio - Fin</th>
                                <th class="p-4">Aula Asignada</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-det-horarios" class="divide-y divide-slate-200 text-slate-800">
                            <!-- Inyectado vía JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Empty State de Horarios -->
                <div id="empty-det-horarios" class="hidden p-8 text-center rounded-2xl bg-white border-2 border-dashed border-slate-200 space-y-2">
                    <span class="text-3xl block">⏰</span>
                    <p class="text-base font-bold text-slate-800">Esta asignación aún no tiene bloques de horario programados.</p>
                    <p class="text-sm text-slate-500 max-w-md mx-auto">
                        Los horarios se definen en el módulo de <strong>Horarios Institucionales</strong> para evitar superposiciones con otras materias.
                    </p>
                </div>
            </div>

        </div>

        <!-- Pie del Submodal -->
        <div class="p-5 sm:p-6 border-t border-slate-200 bg-slate-50 flex flex-wrap items-center justify-between gap-3">
            <button type="button" id="btn-det-editar-acceso" class="text-base font-bold text-amber-700 hover:text-amber-800 hover:underline flex items-center gap-1.5 focus:outline-none">
                <span>✏️</span>
                <span>Modificar esta Asignación</span>
            </button>
            <button type="button" onclick="cerrarModalDetalleAsignacion()" class="btn btn-muted min-h-[48px] px-8 text-base font-bold rounded-xl">
                Cerrar
            </button>
        </div>

    </div>
</div>

<script>
let asignacionActualDetalle = null;

async function abrirModalDetalleAsignacion(idAsignacion) {
    const modal = document.getElementById('modal-detalle-asignacion');
    if (!modal) return;

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    // Estado inicial de carga
    document.getElementById('det-asig-materia').textContent = 'Cargando cátedra...';
    document.getElementById('det-asig-profesor').textContent = 'Cargando...';
    document.getElementById('det-asig-profesor-ci').textContent = 'C.I. ---';
    document.getElementById('det-asig-grado').textContent = 'Cargando...';
    document.getElementById('det-asig-aula').textContent = 'Aula: ---';
    document.getElementById('det-asig-horas').textContent = '-';
    document.getElementById('det-asig-alumnos').textContent = '-';
    document.getElementById('det-asig-plan-estado').innerHTML = '<span class="inline-block px-3 py-1 bg-white rounded-xl border border-indigo-200 text-sm">Consultando...</span>';

    const tbodyHorarios = document.getElementById('tbody-det-horarios');
    const boxTabla = document.getElementById('box-tabla-horarios');
    const emptyHorarios = document.getElementById('empty-det-horarios');
    const cantBloques = document.getElementById('det-asig-cant-bloques');

    if (tbodyHorarios) {
        tbodyHorarios.innerHTML = '<tr><td colspan="3" class="p-6 text-center text-slate-500 font-medium">Consultando bloques horarios...</td></tr>';
    }

    try {
        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Asignaciones/obtener_asignacion.php?id_asignacion=' + idAsignacion;
        const res = await fetch(url);
        const json = await res.json();

        if (json.status !== 'success' || !json.data) {
            if (window.Toast) Toast.error(json.message || 'No se pudo obtener el detalle de la asignación docente.', 'Aviso');
            else alert(json.message || 'No se pudo obtener el detalle de la asignación docente.');
            cerrarModalDetalleAsignacion();
            return;
        }

        const data = json.data;
        asignacionActualDetalle = data;

        // Inyectar datos principales
        document.getElementById('det-asig-materia').textContent = data.materia_nombre || 'Materia';
        document.getElementById('det-asig-profesor').textContent = ((data.profesor_nombre || '') + ' ' + (data.profesor_apellido || '')).trim() || 'Docente no asignado';
        document.getElementById('det-asig-profesor-ci').textContent = data.profesor_ci ? `C.I. ${data.profesor_ci}` : 'Sin cédula registrada';
        
        const cursoTxt = (data.grado_nombre || 'Curso') + (data.seccion ? ' "' + data.seccion + '"' : '') + (data.turno_nombre ? ' (' + data.turno_nombre + ')' : '');
        document.getElementById('det-asig-grado').textContent = cursoTxt;
        document.getElementById('det-asig-aula').textContent = `Aula: ${data.aula_nombre || 'Sin aula fija'}`;
        document.getElementById('det-asig-anio-badge').textContent = `Año Lectivo ${data.anho_lectivo || 2026}`;

        // Métricas
        document.getElementById('det-asig-horas').textContent = data.carga_horaria || 0;
        document.getElementById('det-asig-alumnos').textContent = data.total_alumnos || 0;

        // Plan Anual
        const planEl = document.getElementById('det-asig-plan-estado');
        if (data.plan_anual && data.plan_anual.estado) {
            const est = data.plan_anual.estado.toLowerCase();
            let badgeClass = 'bg-slate-100 text-slate-800 border-slate-300';
            let label = data.plan_anual.estado;

            if (est.includes('aprob')) {
                badgeClass = 'bg-emerald-100 text-emerald-900 border-emerald-300';
            } else if (est.includes('revis') || est.includes('proceso') || est.includes('borrador')) {
                badgeClass = 'bg-amber-100 text-amber-900 border-amber-300';
            } else if (est.includes('pend')) {
                badgeClass = 'bg-rose-100 text-rose-900 border-rose-300';
            }

            planEl.innerHTML = `<span class="inline-block px-3 py-1 rounded-xl text-sm font-bold border ${badgeClass}">${label}</span>`;
        } else {
            planEl.innerHTML = '<span class="inline-block px-3 py-1 bg-slate-100 text-slate-700 rounded-xl text-sm font-bold border border-slate-200">Sin plan entregado</span>';
        }

        // Horarios
        const horarios = Array.isArray(data.horarios) ? data.horarios : [];
        if (cantBloques) cantBloques.textContent = `${horarios.length} bloques`;

        if (horarios.length === 0) {
            if (boxTabla) boxTabla.classList.add('hidden');
            if (emptyHorarios) emptyHorarios.classList.remove('hidden');
        } else {
            if (boxTabla) boxTabla.classList.remove('hidden');
            if (emptyHorarios) emptyHorarios.classList.add('hidden');

            tbodyHorarios.innerHTML = horarios.map(h => {
                const dia = h.dia_semana ? h.dia_semana.charAt(0).toUpperCase() + h.dia_semana.slice(1).toLowerCase() : 'Día';
                const inicio = (h.hora_inicio || '').slice(0, 5);
                const fin = (h.hora_fin || '').slice(0, 5);

                return `
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="p-4 font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            ${dia}
                        </td>
                        <td class="p-4 font-semibold text-slate-700">
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-800 rounded-lg text-sm font-bold font-mono">
                                ${inicio} - ${fin}
                            </span>
                        </td>
                        <td class="p-4 text-slate-600 font-medium">
                            ${h.aula || 'Aula fija del grado'}
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Botón editar acceso rápido
        const btnEditAcceso = document.getElementById('btn-det-editar-acceso');
        if (btnEditAcceso) {
            btnEditAcceso.onclick = () => {
                cerrarModalDetalleAsignacion();
                abrirModalEditarAsignacion(data);
            };
        }

    } catch (e) {
        console.error("Error al consultar asignación:", e);
        if (window.Toast) Toast.error('No se pudo conectar con el servidor para obtener los datos de la cátedra.', 'Error de red');
        else alert('No se pudo conectar con el servidor para obtener los datos de la cátedra.');
        cerrarModalDetalleAsignacion();
    }
}

function cerrarModalDetalleAsignacion() {
    const modal = document.getElementById('modal-detalle-asignacion');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}
</script>
