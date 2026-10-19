<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="flex-1 p-6 md:p-10 overflow-y-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Planes Anuales Pedagógicos</h2>
            <p class="text-sm text-slate-500 mt-1">
                Estructura curricular MEC Paraguay (1 Hora Cátedra = 40 minutos).
            </p>
        </div>

        <div class="flex items-center gap-3">
            <select id="select-plan" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-sm text-slate-700 font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Cargando planes...</option>
            </select>

            <button id="btn-nuevo-tema" type="button" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors flex items-center gap-2">
                <span>➕</span> Agregar Tema / Lección
            </button>
        </div>
    </div>

    <!-- Encabezado Institucional del Plan -->
    <div id="plan-header-card" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-8 hidden">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 pb-6 border-b border-slate-100">
            <div>
                <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Institución</span>
                <p id="info-institucion" class="text-sm font-bold text-slate-800 mt-0.5">-</p>
            </div>
            <div>
                <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Materia y Curso</span>
                <p id="info-materia-curso" class="text-sm font-bold text-indigo-700 mt-0.5">-</p>
            </div>
            <div>
                <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Profesor Encargado</span>
                <p id="info-profesor" class="text-sm font-bold text-slate-800 mt-0.5">-</p>
            </div>
            <div>
                <span class="text-xs uppercase tracking-wider text-slate-400 font-semibold">Carga Horaria / Turno</span>
                <p id="info-carga-turno" class="text-sm font-bold text-slate-800 mt-0.5">-</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6">
            <div class="p-4 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-bold text-indigo-900 uppercase tracking-wide">Competencia General</span>
                <p id="info-comp-general" class="text-xs text-slate-600 mt-1 leading-relaxed">-</p>
            </div>
            <div class="p-4 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-bold text-indigo-900 uppercase tracking-wide">Competencia Específica</span>
                <p id="info-comp-especifica" class="text-xs text-slate-600 mt-1 leading-relaxed">-</p>
            </div>
        </div>
    </div>

    <!-- Contenido de Unidades y Temas -->
    <div id="plan-contenido-container" class="space-y-6">
        <div class="p-12 text-center text-slate-400 bg-white rounded-xl border border-slate-200">
            <span class="text-3xl block mb-2">📖</span>
            Selecciona un plan anual en el selector superior para ver su estructura curricular.
        </div>
    </div>
</main>

<!-- Modal: Agregar Tema / Lección -->
<div id="modal-tema" class="fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-xl overflow-hidden animate-scale-up">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <h3 class="font-bold text-slate-800 text-base">Planificar Nueva Lección / Tema</h3>
            <button id="modal-tema-close" type="button" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
        </div>

        <form id="form-nuevo-tema" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Capacidad MEC Asociada</label>
                <select id="modal-capacidad-id" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">Seleccione capacidad...</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tema / Contenido de la Lección</label>
                <input type="text" id="modal-tema-titulo" required placeholder="Ej: 1.1 El rol del administrador financiero en la era digital" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                        Horas Cátedra
                        <span class="text-indigo-600 font-normal">(1 HC = 40 min)</span>
                    </label>
                    <input type="number" id="modal-tema-horas" min="1" max="10" step="1" value="2" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Fecha Programada</label>
                    <input type="date" id="modal-tema-fecha" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">
                    Indicadores de Logro
                    <span class="text-slate-400 font-normal">(uno por línea)</span>
                </label>
                <textarea id="modal-tema-indicadores" rows="3" placeholder="- Reconoce los conceptos básicos&#10;- Aplica las fórmulas financieras&#10;- Cumple con la entrega en fecha" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-indigo-500 focus:outline-none font-mono text-xs"></textarea>
            </div>

            <div class="pt-2 flex justify-end gap-3 border-t border-slate-100">
                <button type="button" id="modal-tema-cancel" class="px-4 py-2 border border-slate-300 rounded-lg text-sm text-slate-700 hover:bg-slate-50 font-medium">Cancelar</button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm">Guardar Lección</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const API_LISTAR = `${window.BASE_URL}src/api/PlanesAnuales/listar.php`;
    const API_OBTENER = `${window.BASE_URL}src/api/PlanesAnuales/obtener.php`;
    const API_GUARDAR_TEMA = `${window.BASE_URL}src/api/PlanesAnuales/guardar_tema.php`;

    const selectPlan = document.getElementById('select-plan');
    const headerCard = document.getElementById('plan-header-card');
    const contenidoContainer = document.getElementById('plan-contenido-container');

    const modalTema = document.getElementById('modal-tema');
    const btnNuevoTema = document.getElementById('btn-nuevo-tema');
    const modalTemaClose = document.getElementById('modal-tema-close');
    const modalTemaCancel = document.getElementById('modal-tema-cancel');
    const formNuevoTema = document.getElementById('form-nuevo-tema');
    const selectCapacidad = document.getElementById('modal-capacidad-id');

    let currentPlanData = null;

    // 1. Cargar lista de planes
    fetch(API_LISTAR)
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success' && res.data && res.data.length > 0) {
                selectPlan.innerHTML = res.data.map(p => `
                    <option value="${p.id_plan}">
                        ${p.materia_nombre || p.materia_fuente} - ${p.grado_nombre || p.curso_fuente || ''} (${p.profesor_nombre ? p.profesor_nombre + ' ' + p.profesor_apellido : p.profesor_fuente || ''}) [${p.anio}]
                    </option>
                `).join('');
                cargarPlan(res.data[0].id_plan);
            } else {
                selectPlan.innerHTML = '<option value="">No hay planes anuales cargados</option>';
            }
        })
        .catch(e => console.error(e));

    selectPlan.addEventListener('change', () => {
        if (selectPlan.value) cargarPlan(selectPlan.value);
    });

    // 2. Cargar detalle del plan
    function cargarPlan(idPlan) {
        contenidoContainer.innerHTML = '<div class="p-8 text-center text-slate-400">⏳ Cargando estructura curricular...</div>';
        fetch(`${API_OBTENER}?id_plan=${idPlan}`)
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success' && res.data) {
                    currentPlanData = res.data;
                    renderizarPlan(res.data);
                } else {
                    contenidoContainer.innerHTML = `<div class="p-8 text-center text-rose-500 font-semibold">${res.message}</div>`;
                }
            })
            .catch(e => {
                contenidoContainer.innerHTML = `<div class="p-8 text-center text-rose-500 font-semibold">Error al cargar plan: ${e.message}</div>`;
            });
    }

    function renderizarPlan(plan) {
        headerCard.classList.remove('hidden');
        document.getElementById('info-institucion').textContent = plan.institucion_fuente || 'COLEGIO PRESBITERIANO SAN PABLO';
        document.getElementById('info-materia-curso').textContent = `${plan.materia_nombre || plan.materia_fuente || 'Materia'} — ${plan.grado_nombre || plan.curso_fuente || 'Curso'}`;
        document.getElementById('info-profesor').textContent = plan.profesor_nombre ? `${plan.profesor_nombre} ${plan.profesor_apellido}` : (plan.profesor_fuente || '-');
        
        const cargaHC = plan.carga_horaria || 2;
        const duracionMin = cargaHC * 40;
        document.getElementById('info-carga-turno').textContent = `${cargaHC} HC (${duracionMin} min) / ${plan.turno_fuente || 'MAÑANA'}`;
        document.getElementById('info-comp-general').textContent = plan.competencia_general || 'Competencia general orientada a la formación integral técnica y académica.';
        document.getElementById('info-comp-especifica').textContent = plan.competencia_especifica || 'Aplica los conceptos, procedimientos e instrumentos correspondientes al ámbito de estudio.';

        // Llenar selector de capacidades del modal
        selectCapacidad.innerHTML = '<option value="">Seleccione capacidad...</option>';
        if (plan.unidades) {
            plan.unidades.forEach(u => {
                if (u.capacidades) {
                    u.capacidades.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id_capacidad;
                        opt.textContent = `${u.nombre ? u.nombre + ': ' : ''}${c.descripcion.substring(0, 80)}...`;
                        selectCapacidad.appendChild(opt);
                    });
                }
            });
        }

        // Renderizar tabla/acordeón pedagógico
        if (!plan.unidades || plan.unidades.length === 0) {
            contenidoContainer.innerHTML = '<div class="p-8 text-center text-slate-400 bg-white rounded-xl border border-slate-200">El plan no tiene unidades cargadas todavía.</div>';
            return;
        }

        let html = '';
        plan.unidades.forEach(u => {
            html += `
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-slate-50 to-indigo-50/30 p-4 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">Unidad Temática</span>
                            <h3 class="text-base font-bold text-slate-800">${u.nombre || 'Unidad de Aprendizaje'}</h3>
                        </div>
                        <div class="text-xs text-slate-500 font-medium">
                            ${u.tiempo_texto ? `<span>📅 ${u.tiempo_texto}</span>` : ''}
                        </div>
                    </div>

                    <div class="p-5 space-y-6">
            `;

            if (u.capacidades && u.capacidades.length > 0) {
                u.capacidades.forEach(c => {
                    html += `
                        <div class="border-l-4 border-indigo-500 pl-4 space-y-3">
                            <div>
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Capacidad MEC</span>
                                <p class="text-sm font-semibold text-slate-800 leading-snug">${c.descripcion}</p>
                            </div>

                            <!-- Tabla de temas/lecciones -->
                            <div class="overflow-x-auto rounded-lg border border-slate-200">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                                        <tr>
                                            <th class="py-2.5 px-3 font-semibold">Tema / Contenido</th>
                                            <th class="py-2.5 px-3 font-semibold w-28">Duración</th>
                                            <th class="py-2.5 px-3 font-semibold w-32">Fecha</th>
                                            <th class="py-2.5 px-3 font-semibold">Indicadores</th>
                                            <th class="py-2.5 px-3 font-semibold w-36">Evaluación</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                    `;

                    if (c.temas && c.temas.length > 0) {
                        c.temas.forEach(t => {
                            const hc = parseFloat(t.horas_catedra || 1);
                            const min = hc * 40;
                            const fecha = t.fecha_inicio ? t.fecha_inicio : (t.fecha_texto || 'Por definir');

                            const indHtml = t.indicadores && t.indicadores.length > 0
                                ? `<ul class="list-disc pl-4 space-y-0.5 text-slate-600">${t.indicadores.map(i => `<li>${i.descripcion}</li>`).join('')}</ul>`
                                : '<span class="text-slate-400 italic">Sin indicadores cargados</span>';

                            const evalHtml = [
                                ...(t.procedimientos || []),
                                ...(t.instrumentos || [])
                            ].join(', ') || '<span class="text-slate-400">RSA / Observación</span>';

                            html += `
                                <tr class="hover:bg-slate-50/50">
                                    <td class="py-3 px-3 font-medium text-slate-800">${t.titulo}</td>
                                    <td class="py-3 px-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700">
                                            ${hc} HC (${min}m)
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-600 font-mono text-[11px]">
                                        ${fecha}
                                    </td>
                                    <td class="py-3 px-3">${indHtml}</td>
                                    <td class="py-3 px-3 text-slate-500 text-[11px]">${evalHtml}</td>
                                </tr>
                            `;
                        });
                    } else {
                        html += `<tr><td colspan="5" class="py-3 px-3 text-center text-slate-400 italic">No hay temas programados para esta capacidad</td></tr>`;
                    }

                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                });
            } else {
                html += `<p class="text-xs text-slate-400 italic">No hay capacidades registradas en esta unidad.</p>`;
            }

            html += `
                    </div>
                </div>
            `;
        });

        contenidoContainer.innerHTML = html;
    }

    // Modal controls
    btnNuevoTema.addEventListener('click', () => {
        document.getElementById('modal-tema-fecha').value = new Date().toISOString().substring(0, 10);
        modalTema.classList.remove('hidden');
    });

    const cerrarModal = () => modalTema.classList.add('hidden');
    modalTemaClose.addEventListener('click', cerrarModal);
    modalTemaCancel.addEventListener('click', cerrarModal);

    // Guardar nuevo tema
    formNuevoTema.addEventListener('submit', (e) => {
        e.preventDefault();
        const indRaw = document.getElementById('modal-tema-indicadores').value;
        const indicadores = indRaw.split('\n').map(s => s.replace(/^[-*•]\s*/, '').trim()).filter(s => s.length > 0);

        const payload = {
            id_capacidad: parseInt(selectCapacidad.value),
            titulo: document.getElementById('modal-tema-titulo').value.trim(),
            horas_catedra: parseFloat(document.getElementById('modal-tema-horas').value),
            fecha_inicio: document.getElementById('modal-tema-fecha').value,
            fecha_fin: document.getElementById('modal-tema-fecha').value,
            indicadores: indicadores
        };

        fetch(API_GUARDAR_TEMA, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                cerrarModal();
                formNuevoTema.reset();
                if (selectPlan.value) cargarPlan(selectPlan.value);
            } else {
                alert(`Error: ${res.message}`);
            }
        })
        .catch(e => alert(`Error de conexión: ${e.message}`));
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
