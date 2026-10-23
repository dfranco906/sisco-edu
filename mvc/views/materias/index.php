<?php
require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/middleware/auth.php';
verificarRol(['SuperAdmin', 'Administrador', 'Administracion']);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-10">
    <div class="max-w-7xl mx-auto space-y-8">

        <!-- Breadcrumb Accesible -->
        <nav class="flex items-center gap-2 text-base text-slate-600 font-medium" aria-label="Migas de pan">
            <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="hover:text-blue-600 transition flex items-center gap-1.5 focus:outline-none focus:underline">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Inicio
            </a>
            <span class="text-slate-400">/</span>
            <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="hover:text-blue-600 transition font-medium focus:outline-none focus:underline">
                Administración
            </a>
            <span class="text-slate-400">/</span>
            <span class="text-slate-900 font-bold">Materias</span>
        </nav>

        <!-- Cabecera de Página Accesible -->
        <div class="app-page-header p-6 sm:p-8 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span class="px-3.5 py-1 text-sm font-extrabold rounded-full bg-blue-100 text-blue-900 border border-blue-200 uppercase tracking-wide">
                        Malla Curricular
                    </span>
                    <span class="text-base text-slate-600 font-medium hidden sm:inline">
                        Catálogo de Asignaturas Institucionales
                    </span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Gestión de Materias
                </h1>
                <p class="text-base sm:text-lg text-slate-700 mt-2 max-w-3xl leading-relaxed">
                    Administrá las materias y áreas formativas del colegio. Las cargas horarias semanales se definen de forma diferenciada en las asignaciones de cada curso.
                </p>
            </div>

            <!-- Botonera de Acción Primaria -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <button type="button" onclick="abrirModalCrearMateria()" class="min-h-[48px] px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-base font-bold rounded-2xl shadow-sm transition flex items-center gap-2.5 focus:outline-none focus:ring-4 focus:ring-blue-200">
                    <span class="text-xl">+</span>
                    <span>Registrar Materia</span>
                </button>
                <a href="<?= base_url('mvc/views/materias/desactivadas.php') ?>" class="btn btn-muted min-h-[48px] px-5 py-2.5 text-base font-semibold rounded-2xl border border-slate-300">
                    <span>🗑️</span>
                    <span>Materias Inactivas</span>
                </a>
                <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="btn btn-muted min-h-[48px] px-5 py-2.5 text-base font-semibold rounded-2xl">
                    ← Volver
                </a>
            </div>
        </div>

        <!-- Franja de Indicadores Métricos (KPI Strip de Materias) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="p-6 rounded-3xl bg-blue-50/70 border-2 border-blue-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-blue-900">Total Materias Activas</span>
                    <div id="kpi-total-materias" class="text-4xl font-black text-slate-900 mt-2">0</div>
                    <p class="text-sm text-slate-600 mt-0.5">En la malla curricular institucional</p>
                </div>
                <span class="text-4xl">📚</span>
            </div>

            <div class="p-6 rounded-3xl bg-emerald-50/70 border-2 border-emerald-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-emerald-900">Con Cursos Asignados</span>
                    <div id="kpi-materias-asignadas" class="text-4xl font-black text-slate-900 mt-2">0</div>
                    <p class="text-sm text-slate-600 mt-0.5">Vinculadas con docentes y horarios</p>
                </div>
                <span class="text-4xl">🧑‍🏫</span>
            </div>

            <div class="p-6 rounded-3xl bg-amber-50/70 border-2 border-amber-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-amber-900">Pendientes de Asignar</span>
                    <div id="kpi-materias-sin-asignar" class="text-4xl font-black text-slate-900 mt-2">0</div>
                    <p class="text-sm text-slate-600 mt-0.5">Sin carga horaria en cursos aún</p>
                </div>
                <span class="text-4xl">⏳</span>
            </div>
        </div>

        <!-- Barra de Búsqueda Predictiva en Tiempo Real -->
        <div class="bg-white p-5 rounded-2xl border-2 border-slate-200/90 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="relative w-full md:max-w-xl">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" id="input-busqueda-materia" placeholder="Buscar materia por nombre o área formativa..."
                       class="w-full min-h-[48px] pl-12 pr-10 py-3 text-base text-slate-900 font-semibold bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                       aria-label="Buscar materia">
                <button type="button" id="btn-limpiar-busqueda-materia" onclick="limpiarBusquedaMateria()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-700 hidden text-lg font-bold" aria-label="Limpiar búsqueda">
                    ✕
                </button>
            </div>
            <div class="flex items-center gap-2 self-start md:self-auto text-base text-slate-700 font-bold">
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                <span id="contador-materias-visibles">Cargando materias...</span>
            </div>
        </div>

        <!-- Tabla Accesible de Materias (Sin la columna de carga horaria semanal) -->
        <div class="overflow-x-auto rounded-3xl border-2 border-slate-200/90 bg-white shadow-sm">
            <table class="w-full text-left text-base" id="tabla-materias">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-sm border-b-2 border-slate-200">
                    <tr>
                        <th class="p-5">Materia / Asignatura</th>
                        <th class="p-5">Descripción Pedagógica</th>
                        <th class="p-5">Cursos Asignados</th>
                        <th class="p-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-materias" class="divide-y divide-slate-200 text-slate-800">
                    <tr>
                        <td colspan="4" class="p-12 text-center text-slate-500 font-medium">
                            Cargando materias de la malla curricular...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Estado Vacío cuando no hay coincidencias -->
        <div id="empty-busqueda-materias" class="hidden p-14 text-center rounded-3xl bg-white border-2 border-slate-200 shadow-sm space-y-4">
            <span class="text-5xl block">🔍</span>
            <h3 class="text-2xl font-bold text-slate-900">No encontramos materias con esa búsqueda</h3>
            <p class="text-base text-slate-600 max-w-md mx-auto">
                Probá escribiendo otra palabra clave o limpiá el filtro para ver todo el catálogo.
            </p>
            <button type="button" onclick="limpiarBusquedaMateria()" class="btn btn-primary min-h-[46px] px-6 text-base font-bold rounded-2xl">
                Restablecer lista completa
            </button>
        </div>

    </div>
</main>

<!-- Submodales de Materia -->
<?php require_once __DIR__ . '/modal_crear_materia.php'; ?>
<?php require_once __DIR__ . '/modal_editar_materia.php'; ?>
<?php require_once __DIR__ . '/modal_detalle_materia.php'; ?>

<script>
let listaMateriasGlobal = [];

document.addEventListener('DOMContentLoaded', function() {
    cargarMaterias();

    const inputBuscar = document.getElementById('input-busqueda-materia');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', filtrarMaterias);
    }
});

async function cargarMaterias() {
    const tbody = document.getElementById('tbody-materias');
    const labelContador = document.getElementById('contador-materias-visibles');
    
    try {
        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Materia/leer_materias.php';
        const res = await fetch(url);
        const json = await res.json();

        if (json.status !== 'success' || !Array.isArray(json.data)) {
            tbody.innerHTML = `<tr><td colspan="4" class="p-10 text-center text-rose-600 font-bold">Error al cargar materias: ${json.message || 'Respuesta inválida'}</td></tr>`;
            return;
        }

        listaMateriasGlobal = json.data;
        actualizarKPIs(listaMateriasGlobal);
        filtrarMaterias();

    } catch (e) {
        console.error("Error cargando materias:", e);
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="4" class="p-10 text-center text-rose-600 font-bold">Error de conexión al cargar la lista de materias.</td></tr>`;
        }
    }
}

function actualizarKPIs(lista) {
    const totalEl = document.getElementById('kpi-total-materias');
    const asigEl = document.getElementById('kpi-materias-asignadas');
    const sinAsigEl = document.getElementById('kpi-materias-sin-asignar');

    const total = lista.length;
    let conAsig = 0;

    lista.forEach(m => {
        const cant = parseInt(m.total_asignaciones) || 0;
        if (cant > 0) conAsig++;
    });

    if (totalEl) totalEl.textContent = total;
    if (asigEl) asigEl.textContent = conAsig;
    if (sinAsigEl) sinAsigEl.textContent = Math.max(0, total - conAsig);
}

function filtrarMaterias() {
    const inputBuscar = document.getElementById('input-busqueda-materia');
    const btnLimpiar = document.getElementById('btn-limpiar-busqueda-materia');
    const tbody = document.getElementById('tbody-materias');
    const labelContador = document.getElementById('contador-materias-visibles');
    const emptyBox = document.getElementById('empty-busqueda-materias');
    const tabla = document.getElementById('tabla-materias');

    const q = (inputBuscar ? inputBuscar.value : '').trim().toLowerCase();

    if (btnLimpiar) {
        btnLimpiar.classList.toggle('hidden', q.length === 0);
    }

    const filtradas = listaMateriasGlobal.filter(m => {
        const n = (m.nombre || '').toLowerCase();
        const d = (m.descripcion || '').toLowerCase();
        return !q || n.includes(q) || d.includes(q);
    });

    if (labelContador) {
        labelContador.textContent = `${filtradas.length} de ${listaMateriasGlobal.length} materias`;
    }

    if (filtradas.length === 0) {
        tbody.innerHTML = '';
        if (tabla) tabla.classList.add('hidden');
        if (emptyBox) emptyBox.classList.remove('hidden');
        return;
    }

    if (tabla) tabla.classList.remove('hidden');
    if (emptyBox) emptyBox.classList.add('hidden');

    tbody.innerHTML = filtradas.map(m => {
        const inicial = (m.nombre || 'M').charAt(0).toUpperCase();
        const cantAsig = parseInt(m.total_asignaciones) || 0;
        const matJson = JSON.stringify(m).replace(/"/g, '&quot;');

        let badgeAsig = '';
        if (cantAsig > 0) {
            badgeAsig = `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-100 text-indigo-900 text-sm font-bold border border-indigo-200">
                🏫 ${cantAsig} curso(s)
            </span>`;
        } else {
            badgeAsig = `<span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-sm font-semibold border border-slate-200">
                Sin asignar
            </span>`;
        }

        return `
            <tr class="hover:bg-slate-50 transition-colors">
                <!-- Materia -->
                <td class="p-5">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-800 font-black flex items-center justify-center text-lg border border-blue-200 shrink-0">
                            ${inicial}
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900 leading-snug">${escapeHtml(m.nombre)}</h3>
                            <span class="text-xs font-mono font-semibold text-slate-500">ID #${m.id_materia}</span>
                        </div>
                    </div>
                </td>

                <!-- Descripción -->
                <td class="p-5 font-medium text-slate-700 max-w-md">
                    ${m.descripcion ? escapeHtml(m.descripcion) : '<span class="text-slate-400 italic">Sin descripción registrada</span>'}
                </td>

                <!-- Cursos Asignados -->
                <td class="p-5">
                    ${badgeAsig}
                </td>

                <!-- Acciones -->
                <td class="p-5 text-right">
                    <div class="inline-flex items-center gap-2 justify-end">
                        <button type="button" onclick="abrirModalDetalleMateria(${m.id_materia})"
                                class="min-h-[44px] px-3.5 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-600 text-indigo-700 hover:text-white font-bold text-sm border border-indigo-200 transition flex items-center gap-1.5"
                                title="Ver detalles y asignaciones">
                            <span>📖</span>
                            <span>Detalles</span>
                        </button>
                        <button type="button" onclick="abrirModalEditarMateria(${matJson})"
                                class="min-h-[44px] px-3.5 py-2 rounded-xl bg-amber-50 hover:bg-amber-600 text-amber-800 hover:text-white font-bold text-sm border border-amber-200 transition flex items-center gap-1.5"
                                title="Editar materia">
                            <span>✏️</span>
                            <span>Editar</span>
                        </button>
                        <button type="button" onclick="desactivarMateria(${m.id_materia}, '${escapeJs(m.nombre)}')"
                                class="min-h-[44px] px-3.5 py-2 rounded-xl bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white font-bold text-sm border border-rose-200 transition flex items-center gap-1.5"
                                title="Desactivar materia">
                            <span>✕</span>
                            <span>Desactivar</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function limpiarBusquedaMateria() {
    const inputBuscar = document.getElementById('input-busqueda-materia');
    if (inputBuscar) {
        inputBuscar.value = '';
        filtrarMaterias();
        inputBuscar.focus();
    }
}

async function desactivarMateria(idMateria, nombreMateria) {
    const msg = `¿Desea desactivar la materia "${nombreMateria}"?\n\nPasará a la lista de inactivas y no podrá ser asignada a nuevos cursos.`;
    if (!confirm(msg)) return;

    try {
        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Materia/desactivar_materia.php';
        const formData = new FormData();
        formData.append('id_materia', idMateria);

        const res = await fetch(url, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.status === 'success') {
            if (window.Toast) Toast.success(json.message);
            cargarMaterias();
        } else {
            if (window.Toast) Toast.error(json.message || "Error al desactivar");
            else alert(json.message);
        }
    } catch (e) {
        console.error("Error al desactivar materia:", e);
        if (window.Toast) Toast.error("Error de comunicación con el servidor");
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

function escapeJs(text) {
    if (!text) return '';
    return String(text).replace(/'/g, "\\'").replace(/"/g, '\\"');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
