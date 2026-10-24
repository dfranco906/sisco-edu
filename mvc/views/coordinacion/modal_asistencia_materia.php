<!-- Submodal Accesible: Detalle de Asistencia de Alumnos por Materia -->
<div id="modal-asistencia-materia" class="app-modal fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm hidden" role="dialog" aria-modal="true" aria-labelledby="modal-mat-asist-titulo">
    <div class="relative w-full max-w-4xl max-h-[90vh] bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-200">
        
        <!-- Cabecera del Submodal -->
        <div class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/60">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-2xl font-bold shadow-xs">
                    📊
                </div>
                <div>
                    <h2 id="modal-mat-asist-titulo" class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Asistencias por Estudiante
                    </h2>
                    <p id="modal-mat-asist-subtitulo" class="text-base text-slate-600 dark:text-slate-400 mt-0.5">
                        Materia y docente responsable
                    </p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalAsistenciaMateria()" class="w-12 h-12 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-200/60 dark:hover:bg-slate-800 flex items-center justify-center text-2xl font-bold transition focus:outline-none focus:ring-2 focus:ring-indigo-500" aria-label="Cerrar modal">
                ✕
            </button>
        </div>

        <!-- Filtro Rápido en Modal -->
        <div class="p-4 sm:px-6 bg-slate-50 dark:bg-slate-800/40 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">🔍</span>
                <input type="text" id="input-buscar-alumno-materia" placeholder="Filtrar estudiante por nombre..."
                       class="w-full pl-10 pr-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl text-base text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <span id="conteo-alumnos-materia-modal" class="text-sm font-bold text-slate-600 dark:text-slate-400 whitespace-nowrap">
                0 estudiantes
            </span>
        </div>

        <!-- Cuerpo del Submodal con Tabla Accesible -->
        <div class="p-6 overflow-y-auto flex-1">
            <div class="overflow-x-auto rounded-2xl border-2 border-slate-200 dark:border-slate-800 shadow-xs">
                <table class="w-full text-left" id="tabla-modal-asistencia-materia">
                    <thead class="bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 uppercase font-bold text-sm border-b-2 border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="p-4 sm:p-5">Estudiante</th>
                            <th class="p-4 text-center">Presentes</th>
                            <th class="p-4 text-center">Tardanzas</th>
                            <th class="p-4 text-center">Ausentes</th>
                            <th class="p-4 text-right">% Asistencia</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-modal-asistencia-materia" class="divide-y divide-slate-200 dark:divide-slate-800 text-base">
                        <!-- Rellenado dinámicamente -->
                    </tbody>
                </table>
            </div>

            <div id="empty-modal-asistencia-materia" class="hidden p-8 text-center space-y-2">
                <p class="text-lg font-bold text-slate-800 dark:text-slate-200">No se encontraron estudiantes con ese nombre.</p>
                <p class="text-sm text-slate-500 dark:text-slate-400">Probá limpiando el texto de búsqueda.</p>
            </div>
        </div>

        <!-- Pie del Submodal -->
        <div class="p-5 sm:p-6 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 flex items-center justify-between">
            <p class="text-sm text-slate-500 dark:text-slate-400 hidden sm:block">Datos sincronizados con las marcaciones biométricas DY50.</p>
            <button type="button" onclick="cerrarModalAsistenciaMateria()" class="btn btn-muted min-h-[48px] px-8 text-base font-bold rounded-xl ml-auto">
                Cerrar
            </button>
        </div>

    </div>
</div>

<script>
let listaAlumnosMateriaActual = [];

function abrirModalAsistenciaMateria(materiaNombre, profNombre, alumnosJson) {
    const modal = document.getElementById('modal-asistencia-materia');
    const subtitulo = document.getElementById('modal-mat-asist-subtitulo');
    const inputBuscar = document.getElementById('input-buscar-alumno-materia');

    if (!modal) return;

    if (subtitulo) {
        subtitulo.textContent = `${materiaNombre} • Docente: ${profNombre}`;
    }

    try {
        listaAlumnosMateriaActual = (typeof alumnosJson === 'string') ? JSON.parse(alumnosJson) : alumnosJson;
    } catch (e) {
        console.error("Error al parsear alumnosJson", e);
        listaAlumnosMateriaActual = [];
    }

    if (inputBuscar) {
        inputBuscar.value = '';
    }

    renderizarAlumnosMateria(listaAlumnosMateriaActual);

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function renderizarAlumnosMateria(lista) {
    const tbody = document.getElementById('tbody-modal-asistencia-materia');
    const conteo = document.getElementById('conteo-alumnos-materia-modal');
    const emptyBox = document.getElementById('empty-modal-asistencia-materia');
    const tabla = document.getElementById('tabla-modal-asistencia-materia');

    if (!tbody) return;

    if (conteo) {
        conteo.textContent = `${lista.length} estudiante(s)`;
    }

    if (lista.length === 0) {
        tbody.innerHTML = '';
        if (tabla) tabla.classList.add('hidden');
        if (emptyBox) emptyBox.classList.remove('hidden');
        return;
    }

    if (tabla) tabla.classList.remove('hidden');
    if (emptyBox) emptyBox.classList.add('hidden');

    tbody.innerHTML = lista.map(p => {
        const pct = parseInt(p.porcentaje) || 0;
        const colorClass = pct >= 80 ? 'text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 border-emerald-200 dark:border-emerald-800' : 'text-amber-800 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 border-amber-200 dark:border-amber-800';
        return `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <td class="p-4 sm:p-5 font-bold text-slate-900 dark:text-white">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-sm border border-indigo-200 dark:border-indigo-800">
                            ${(p.nombre_completo || 'A').charAt(0).toUpperCase()}
                        </div>
                        <span>${escapeHtml(p.nombre_completo)}</span>
                    </div>
                </td>
                <td class="p-4 text-center">
                    <span class="inline-block px-3 py-1 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-300 font-bold text-base border border-emerald-200 dark:border-emerald-800">
                        ${p.presentes}
                    </span>
                </td>
                <td class="p-4 text-center">
                    <span class="inline-block px-3 py-1 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-900 dark:text-amber-300 font-bold text-base border border-amber-200 dark:border-amber-800">
                        ${p.tardanzas}
                    </span>
                </td>
                <td class="p-4 text-center">
                    <span class="inline-block px-3 py-1 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-900 dark:text-rose-300 font-bold text-base border border-rose-200 dark:border-rose-800">
                        ${p.ausentes}
                    </span>
                </td>
                <td class="p-4 text-right">
                    <span class="inline-block px-3.5 py-1 rounded-xl font-extrabold text-base border ${colorClass}">
                        ${pct}%
                    </span>
                </td>
            </tr>
        `;
    }).join('');
}

function cerrarModalAsistenciaMateria() {
    const modal = document.getElementById('modal-asistencia-materia');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const inputBuscar = document.getElementById('input-buscar-alumno-materia');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            const filtrados = listaAlumnosMateriaActual.filter(a => {
                return (a.nombre_completo || '').toLowerCase().includes(q);
            });
            renderizarAlumnosMateria(filtrados);
        });
    }
});

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
