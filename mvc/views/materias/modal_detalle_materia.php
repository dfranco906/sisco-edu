<!-- Submodal Accesible: Detalle y Asignaciones de la Materia -->
<div id="modal-detalle-materia" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-det-mat-titulo">
    <div class="relative w-full max-w-3xl max-h-[90vh] bg-white rounded-3xl shadow-2xl border-2 border-slate-200 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Cabecera del Submodal -->
        <div class="flex items-center justify-between p-6 sm:p-7 border-b border-slate-200 bg-slate-50/80">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-indigo-100 text-indigo-700 flex items-center justify-center text-2xl font-bold shadow-xs shrink-0">
                    📖
                </div>
                <div>
                    <h2 id="modal-det-mat-titulo" class="text-2xl font-extrabold text-slate-900 tracking-tight">
                        Detalles de la Materia
                    </h2>
                    <p id="modal-det-mat-subtitulo" class="text-base text-slate-600 mt-0.5">
                        Cursos, docentes y cargas horarias reales asignadas
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalDetalleMateria()" class="w-12 h-12 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-200/60 flex items-center justify-center text-2xl font-bold transition focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <!-- Cuerpo del Submodal con scroll -->
        <div class="p-6 sm:p-8 space-y-6 overflow-y-auto flex-1">
            
            <!-- Resumen de la Asignatura -->
            <div class="p-6 rounded-2xl bg-slate-50 border-2 border-slate-200 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <h3 id="det-mat-nombre" class="text-2xl font-extrabold text-slate-900">Nombre de Materia</h3>
                    <span id="det-mat-estado-badge" class="px-3.5 py-1 rounded-full text-sm font-bold bg-emerald-100 text-emerald-900 border border-emerald-200 self-start sm:self-auto">
                        Activa
                    </span>
                </div>
                <div>
                    <span class="text-xs uppercase font-extrabold text-slate-500 tracking-wider block mb-1">Descripción / Área Pedagógica:</span>
                    <p id="det-mat-desc" class="text-base text-slate-700 leading-relaxed font-normal">
                        Sin descripción registrada.
                    </p>
                </div>
            </div>

            <!-- Tabla de Carga Horaria y Asignaciones Reales en Cursos -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-indigo-600 inline-block"></span>
                            Distribución en Cursos y Cargas Horarias Reales
                        </h4>
                        <p class="text-sm text-slate-600">
                            La carga horaria semanal corresponde a la asignada a cada curso en el plan lectivo:
                        </p>
                    </div>
                    <span id="det-mat-total-cursos" class="text-sm font-bold px-3 py-1 bg-indigo-50 text-indigo-800 rounded-xl border border-indigo-200">
                        0 asignaciones
                    </span>
                </div>

                <!-- Tabla de Asignaciones -->
                <div class="overflow-x-auto rounded-2xl border-2 border-slate-200 shadow-xs">
                    <table class="w-full text-left text-base" id="tabla-det-asignaciones">
                        <thead class="bg-slate-100 text-slate-700 uppercase font-bold text-xs sm:text-sm border-b-2 border-slate-200">
                            <tr>
                                <th class="p-4">Curso / Grado</th>
                                <th class="p-4">Turno</th>
                                <th class="p-4">Docente Responsable</th>
                                <th class="p-4 text-right">Carga Horaria</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-det-asignaciones" class="divide-y divide-slate-200 text-slate-800">
                            <!-- Inyectado vía JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Empty state de asignaciones -->
                <div id="empty-det-asignaciones" class="hidden p-8 text-center rounded-2xl bg-white border-2 border-dashed border-slate-200 space-y-2">
                    <span class="text-3xl block">📋</span>
                    <p class="text-base font-bold text-slate-800">Esta materia no tiene asignaciones docentes activas actualmente.</p>
                    <p class="text-sm text-slate-500 max-w-md mx-auto">
                        Para vincularla con un curso y asignarle su carga horaria semanal, dirigite al módulo de <strong>Asignaciones Docentes</strong>.
                    </p>
                </div>
            </div>

        </div>

        <!-- Pie del Submodal -->
        <div class="p-5 sm:p-6 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
            <a href="<?= base_url('mvc/views/asignaciones/index.php') ?>" class="text-base font-bold text-indigo-700 hover:underline">
                Gestionar en Asignaciones Docentes →
            </a>
            <button type="button" onclick="cerrarModalDetalleMateria()" class="btn btn-muted min-h-[48px] px-8 text-base font-bold rounded-xl">
                Cerrar
            </button>
        </div>

    </div>
</div>

<script>
async function abrirModalDetalleMateria(idMateria) {
    const modal = document.getElementById('modal-detalle-materia');
    if (!modal) return;

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    const nombreEl = document.getElementById('det-mat-nombre');
    const descEl = document.getElementById('det-mat-desc');
    const tbody = document.getElementById('tbody-det-asignaciones');
    const emptyBox = document.getElementById('empty-det-asignaciones');
    const tabla = document.getElementById('tabla-det-asignaciones');
    const totalSpan = document.getElementById('det-mat-total-cursos');

    if (nombreEl) nombreEl.textContent = 'Cargando información...';
    if (descEl) descEl.textContent = '...';
    if (tbody) tbody.innerHTML = '<tr><td colspan="4" class="p-6 text-center text-slate-400">Consultando asignaciones...</td></tr>';

    try {
        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + `/src/api/Materia/obtener_materia.php?id_materia=${idMateria}`;
        const res = await fetch(url);
        const json = await res.json();

        if (json.status !== 'success' || !json.data) {
            if (nombreEl) nombreEl.textContent = 'Error al cargar';
            if (descEl) descEl.textContent = json.message || 'No se pudo obtener el detalle de la materia.';
            return;
        }

        const mat = json.data;
        if (nombreEl) nombreEl.textContent = mat.nombre;
        if (descEl) descEl.textContent = mat.descripcion || 'Sin descripción registrada.';

        const asignaciones = mat.asignaciones || [];
        if (totalSpan) totalSpan.textContent = `${asignaciones.length} curso(s) vinculado(s)`;

        if (asignaciones.length === 0) {
            if (tabla) tabla.classList.add('hidden');
            if (emptyBox) emptyBox.classList.remove('hidden');
        } else {
            if (tabla) tabla.classList.remove('hidden');
            if (emptyBox) emptyBox.classList.add('hidden');

            if (tbody) {
                tbody.innerHTML = asignaciones.map(a => `
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="p-4 font-bold text-slate-900">
                            ${escapeHtml(a.grado_nombre)} ${a.seccion ? ' • Secc. ' + escapeHtml(a.seccion) : ''}
                        </td>
                        <td class="p-4 font-semibold text-slate-700">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-xs font-bold uppercase">
                                ${escapeHtml(a.turno_nombre || 'Regular')}
                            </span>
                        </td>
                        <td class="p-4 font-medium text-slate-800">
                            ${escapeHtml(a.profesor_nombre)} ${escapeHtml(a.profesor_apellido)}
                        </td>
                        <td class="p-4 text-right">
                            <span class="inline-block px-3.5 py-1 rounded-xl bg-blue-100 text-blue-900 font-extrabold text-base border border-blue-200">
                                ${a.carga_horaria} hs cátedra
                            </span>
                        </td>
                    </tr>
                `).join('');
            }
        }
    } catch (e) {
        console.error("Error obteniendo detalle de materia:", e);
        if (nombreEl) nombreEl.textContent = 'Error de conexión';
        if (descEl) descEl.textContent = 'No se pudo establecer comunicación con el servidor.';
    }
}

function cerrarModalDetalleMateria() {
    const modal = document.getElementById('modal-detalle-materia');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>
