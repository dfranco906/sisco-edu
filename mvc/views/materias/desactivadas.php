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
            <a href="<?= base_url('mvc/views/materias/index.php') ?>" class="hover:text-blue-600 transition font-medium focus:outline-none focus:underline">
                Materias
            </a>
            <span class="text-slate-400">/</span>
            <span class="text-slate-900 font-bold">Desactivadas</span>
        </nav>

        <!-- Cabecera de Página Accesible -->
        <div class="app-page-header p-6 sm:p-8 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span class="px-3.5 py-1 text-sm font-extrabold rounded-full bg-rose-100 text-rose-900 border border-rose-200 uppercase tracking-wide">
                        Papelera de Materias
                    </span>
                    <span class="text-base text-slate-600 font-medium hidden sm:inline">
                        Asignaturas Fuera de Servicio
                    </span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Materias Desactivadas
                </h1>
                <p class="text-base sm:text-lg text-slate-700 mt-2 max-w-3xl leading-relaxed">
                    Lista de materias que fueron dadas de baja. Podés restaurarlas a la malla activa o eliminarlas permanentemente si no tienen registros vinculados.
                </p>
            </div>

            <!-- Botonera de Retorno -->
            <div class="flex items-center gap-3 shrink-0">
                <a href="<?= base_url('mvc/views/materias/index.php') ?>" class="btn btn-primary min-h-[48px] px-6 py-2.5 text-base font-bold rounded-2xl shadow-sm">
                    ← Volver a Materias Activas
                </a>
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
                <input type="text" id="input-busqueda-desactivadas" placeholder="Buscar materia desactivada por nombre..."
                       class="w-full min-h-[48px] pl-12 pr-10 py-3 text-base text-slate-900 font-semibold bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                       aria-label="Buscar materia desactivada">
                <button type="button" id="btn-limpiar-busqueda-desactivadas" onclick="limpiarBusquedaDesactivadas()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-700 hidden text-lg font-bold" aria-label="Limpiar búsqueda">
                    ✕
                </button>
            </div>
            <div class="flex items-center gap-2 self-start md:self-auto text-base text-slate-700 font-bold">
                <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                <span id="contador-desactivadas-visibles">Cargando materias inactivas...</span>
            </div>
        </div>

        <!-- Tabla Accesible de Materias Desactivadas (Sin columna de carga horaria semanal) -->
        <div class="overflow-x-auto rounded-3xl border-2 border-slate-200/90 bg-white shadow-sm">
            <table class="w-full text-left text-base" id="tabla-desactivadas">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-sm border-b-2 border-slate-200">
                    <tr>
                        <th class="p-5">Materia / Asignatura</th>
                        <th class="p-5">Descripción Pedagógica</th>
                        <th class="p-5">Estado</th>
                        <th class="p-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-desactivadas" class="divide-y divide-slate-200 text-slate-800">
                    <tr>
                        <td colspan="4" class="p-12 text-center text-slate-500 font-medium">
                            Consultando materias inactivas...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Estado Vacío cuando no hay materias desactivadas o no hay coincidencias -->
        <div id="empty-desactivadas" class="hidden p-14 text-center rounded-3xl bg-white border-2 border-slate-200 shadow-sm space-y-4">
            <span class="text-5xl block">🎉</span>
            <h3 class="text-2xl font-bold text-slate-900">No hay materias desactivadas</h3>
            <p class="text-base text-slate-600 max-w-md mx-auto">
                Todas las asignaturas registradas se encuentran activas en la malla curricular institucional.
            </p>
            <div class="pt-2">
                <a href="<?= base_url('mvc/views/materias/index.php') ?>" class="btn btn-primary min-h-[46px] px-6 text-base font-bold rounded-2xl inline-flex items-center gap-2">
                    Volver a Materias Activas
                </a>
            </div>
        </div>

    </div>
</main>

<script>
let listaDesactivadasGlobal = [];

document.addEventListener('DOMContentLoaded', function() {
    cargarMateriasDesactivadas();

    const inputBuscar = document.getElementById('input-busqueda-desactivadas');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', filtrarDesactivadas);
    }
});

async function cargarMateriasDesactivadas() {
    const tbody = document.getElementById('tbody-desactivadas');
    
    try {
        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Materia/leer_materias_desactivadas.php';
        const res = await fetch(url);
        const json = await res.json();

        if (json.status !== 'success' || !Array.isArray(json.data)) {
            tbody.innerHTML = `<tr><td colspan="4" class="p-10 text-center text-rose-600 font-bold">Error al cargar materias: ${json.message || 'Respuesta inválida'}</td></tr>`;
            return;
        }

        listaDesactivadasGlobal = json.data;
        filtrarDesactivadas();

    } catch (e) {
        console.error("Error cargando materias desactivadas:", e);
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="4" class="p-10 text-center text-rose-600 font-bold">Error de conexión al cargar materias inactivas.</td></tr>`;
        }
    }
}

function filtrarDesactivadas() {
    const inputBuscar = document.getElementById('input-busqueda-desactivadas');
    const btnLimpiar = document.getElementById('btn-limpiar-busqueda-desactivadas');
    const tbody = document.getElementById('tbody-desactivadas');
    const labelContador = document.getElementById('contador-desactivadas-visibles');
    const emptyBox = document.getElementById('empty-desactivadas');
    const tabla = document.getElementById('tabla-desactivadas');

    const q = (inputBuscar ? inputBuscar.value : '').trim().toLowerCase();

    if (btnLimpiar) {
        btnLimpiar.classList.toggle('hidden', q.length === 0);
    }

    const filtradas = listaDesactivadasGlobal.filter(m => {
        const n = (m.nombre || '').toLowerCase();
        const d = (m.descripcion || '').toLowerCase();
        return !q || n.includes(q) || d.includes(q);
    });

    if (labelContador) {
        labelContador.textContent = `${filtradas.length} materia(s) inactiva(s)`;
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

        return `
            <tr class="hover:bg-slate-50 transition-colors">
                <!-- Materia -->
                <td class="p-5">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-800 font-black flex items-center justify-center text-lg border border-rose-200 shrink-0">
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

                <!-- Estado -->
                <td class="p-5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-100 text-rose-900 text-sm font-bold border border-rose-200">
                        Inactiva
                    </span>
                </td>

                <!-- Acciones -->
                <td class="p-5 text-right">
                    <div class="inline-flex items-center gap-2 justify-end">
                        <button type="button" onclick="restaurarMateria(${m.id_materia}, '${escapeJs(m.nombre)}')"
                                class="min-h-[44px] px-4 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-600 text-emerald-800 hover:text-white font-bold text-sm border border-emerald-200 transition flex items-center gap-1.5"
                                title="Restaurar a la malla activa">
                            <span>♻️</span>
                            <span>Restaurar</span>
                        </button>
                        <button type="button" onclick="eliminarMateriaDefinitiva(${m.id_materia}, '${escapeJs(m.nombre)}')"
                                class="min-h-[44px] px-4 py-2 rounded-xl bg-slate-100 hover:bg-rose-600 text-slate-700 hover:text-white font-bold text-sm border border-slate-300 hover:border-rose-600 transition flex items-center gap-1.5"
                                title="Eliminar permanentemente">
                            <span>🗑️</span>
                            <span>Eliminar</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function limpiarBusquedaDesactivadas() {
    const inputBuscar = document.getElementById('input-busqueda-desactivadas');
    if (inputBuscar) {
        inputBuscar.value = '';
        filtrarDesactivadas();
        inputBuscar.focus();
    }
}

async function restaurarMateria(idMateria, nombreMateria) {
    if (!confirm(`¿Desea restaurar la materia "${nombreMateria}" a la malla activa?`)) return;

    try {
        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Materia/restaurar_materia.php';
        const formData = new FormData();
        formData.append('id_materia', idMateria);

        const res = await fetch(url, { method: 'POST', body: formData });
        const json = await res.json();

        if (json.status === 'success') {
            if (window.Toast) Toast.success(json.message);
            cargarMateriasDesactivadas();
        } else {
            if (window.Toast) Toast.error(json.message || "Error al restaurar");
            else alert(json.message);
        }
    } catch (e) {
        console.error("Error al restaurar:", e);
        if (window.Toast) Toast.error("Error de comunicación");
    }
}

async function eliminarMateriaDefinitiva(idMateria, nombreMateria) {
    const msg = `⚠️ ADVERTENCIA DE ELIMINACIÓN PERMANENTE:\n\n¿Está seguro de eliminar definitivamente la materia "${nombreMateria}"?\n\nEsta acción no se puede deshacer. Si la materia tiene asignaciones históricas, la base de datos impedirá su eliminación para proteger los registros escolares.`;
    if (!confirm(msg)) return;

    try {
        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Materia/eliminar_materia.php';
        const formData = new FormData();
        formData.append('id_materia', idMateria);

        const res = await fetch(url, { method: 'POST', body: formData });
        const json = await res.json();

        if (json.status === 'success') {
            if (window.Toast) Toast.success(json.message);
            cargarMateriasDesactivadas();
        } else {
            if (window.Toast) Toast.error(json.message || "Error al eliminar");
            else alert(json.message);
        }
    } catch (e) {
        console.error("Error al eliminar:", e);
        if (window.Toast) Toast.error("Error de comunicación");
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
