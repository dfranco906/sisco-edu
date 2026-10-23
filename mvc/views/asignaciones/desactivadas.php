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
            <a href="<?= base_url('mvc/views/asignaciones/index.php') ?>" class="hover:text-blue-600 transition font-medium focus:outline-none focus:underline">
                Asignaciones Docentes
            </a>
            <span class="text-slate-400">/</span>
            <span class="text-slate-900 font-bold">Inactivas</span>
        </nav>

        <!-- Cabecera de Página Accesible -->
        <div class="app-page-header p-6 sm:p-8 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span class="px-3.5 py-1 text-sm font-extrabold rounded-full bg-rose-100 text-rose-900 border border-rose-200 uppercase tracking-wide">
                        Papelera de Cátedras
                    </span>
                    <span class="text-base text-slate-600 font-medium hidden sm:inline">
                        Asignaciones Fuera de Servicio
                    </span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Asignaciones Inactivas
                </h1>
                <p class="text-base sm:text-lg text-slate-700 mt-2 max-w-3xl leading-relaxed">
                    Lista de cátedras docentes que fueron dadas de baja. Podés restaurarlas a la lista activa o eliminarlas definitivamente si no tienen horarios asociados.
                </p>
            </div>

            <!-- Botonera de Retorno -->
            <div class="flex items-center gap-3 shrink-0">
                <a href="<?= base_url('mvc/views/asignaciones/index.php') ?>" class="btn btn-primary min-h-[48px] px-6 py-2.5 text-base font-bold rounded-2xl shadow-sm">
                    ← Volver a Asignaciones Activas
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
                <input type="text" id="input-busqueda-desactivadas" placeholder="Buscar por docente, materia o curso..."
                       class="w-full min-h-[48px] pl-12 pr-10 py-3 text-base text-slate-900 font-semibold bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                       aria-label="Buscar asignación desactivada">
                <button type="button" id="btn-limpiar-busqueda-desactivadas" onclick="limpiarBusquedaDesactivadas()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-700 hidden text-lg font-bold" aria-label="Limpiar búsqueda">
                    ✕
                </button>
            </div>
            <div class="flex items-center gap-2 self-start md:self-auto text-base text-slate-700 font-bold">
                <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                <span id="contador-desactivadas-visibles">Cargando asignaciones inactivas...</span>
            </div>
        </div>

        <!-- Tabla Accesible de Asignaciones Inactivas -->
        <div class="overflow-x-auto rounded-3xl border-2 border-slate-200/90 bg-white shadow-sm">
            <table class="w-full text-left text-base" id="tabla-desactivadas">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-sm border-b-2 border-slate-200">
                    <tr>
                        <th class="p-5">Docente & Materia</th>
                        <th class="p-5">Curso / Grado & Turno</th>
                        <th class="p-5">Carga Horaria</th>
                        <th class="p-5">Año Lectivo</th>
                        <th class="p-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-desactivadas" class="divide-y divide-slate-200 text-slate-800">
                    <tr>
                        <td colspan="5" class="p-12 text-center text-slate-500 font-medium">
                            Consultando asignaciones inactivas...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Estado Vacío cuando no hay asignaciones inactivas o no hay coincidencias -->
        <div id="empty-desactivadas" class="hidden p-14 text-center rounded-3xl bg-white border-2 border-slate-200 shadow-sm space-y-4">
            <span class="text-5xl block">🎉</span>
            <h3 class="text-2xl font-bold text-slate-900">No hay asignaciones docentes inactivas</h3>
            <p class="text-base text-slate-600 max-w-md mx-auto">
                Todas las cátedras y asignaciones se encuentran activas en el período escolar actual.
            </p>
            <a href="<?= base_url('mvc/views/asignaciones/index.php') ?>" class="btn btn-primary inline-flex items-center min-h-[46px] px-6 text-base font-bold rounded-2xl">
                Volver a Asignaciones Activas
            </a>
        </div>

    </div>
</main>

<script>
let listaDesactivadasGlobal = [];

document.addEventListener('DOMContentLoaded', function() {
    cargarAsignacionesDesactivadas();

    const inputBuscar = document.getElementById('input-busqueda-desactivadas');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', filtrarDesactivadas);
    }
});

async function cargarAsignacionesDesactivadas() {
    const tbody = document.getElementById('tbody-desactivadas');
    const labelContador = document.getElementById('contador-desactivadas-visibles');
    const emptyBox = document.getElementById('empty-desactivadas');
    const tabla = document.getElementById('tabla-desactivadas');

    try {
        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Asignaciones/leer_asignaciones_desactivadas.php';
        const res = await fetch(url);
        const json = await res.json();

        if (json.status !== 'success' || !Array.isArray(json.data)) {
            tbody.innerHTML = `<tr><td colspan="5" class="p-10 text-center text-rose-600 font-bold">Error al consultar asignaciones: ${json.message || 'Respuesta inválida'}</td></tr>`;
            return;
        }

        listaDesactivadasGlobal = json.data;

        if (listaDesactivadasGlobal.length === 0) {
            tbody.innerHTML = '';
            if (tabla) tabla.classList.add('hidden');
            if (emptyBox) emptyBox.classList.remove('hidden');
            if (labelContador) labelContador.textContent = '0 asignaciones inactivas';
            return;
        }

        filtrarDesactivadas();

    } catch (e) {
        console.error("Error cargando asignaciones inactivas:", e);
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="5" class="p-10 text-center text-rose-600 font-bold">Error de conexión al obtener asignaciones inactivas.</td></tr>`;
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

    const filtradas = listaDesactivadasGlobal.filter(a => {
        const prof = (a.profesor || '').toLowerCase();
        const mat = (a.materia || '').toLowerCase();
        const grad = (a.grado || '').toLowerCase();
        const anio = String(a.anho_lectivo || a['año_lectivo'] || '');
        return !q || prof.includes(q) || mat.includes(q) || grad.includes(q) || anio.includes(q);
    });

    if (labelContador) {
        labelContador.textContent = `${filtradas.length} de ${listaDesactivadasGlobal.length} inactivas`;
    }

    if (filtradas.length === 0) {
        tbody.innerHTML = '';
        if (tabla) tabla.classList.add('hidden');
        if (emptyBox) emptyBox.classList.remove('hidden');
        return;
    }

    if (tabla) tabla.classList.remove('hidden');
    if (emptyBox) emptyBox.classList.add('hidden');

    tbody.innerHTML = filtradas.map(a => {
        const profNombre = a.profesor || 'Docente no asignado';
        const profCi = a.profesor_ci ? `C.I. ${a.profesor_ci}` : '';
        const matNombre = a.materia || 'Materia';
        const gradoNombre = a.grado || 'Curso no definido';
        const seccion = a.seccion ? ` "${a.seccion}"` : '';
        const turno = a.turno ? a.turno : '';
        const carga = a.carga_horaria || 0;
        const anio = a.anho_lectivo || a['año_lectivo'] || 2026;

        return `
            <tr class="hover:bg-slate-50/90 transition-colors opacity-90">
                <!-- Columna 1: Docente & Materia -->
                <td class="p-5">
                    <div class="space-y-1">
                        <span class="text-lg font-bold text-slate-800 block">
                            ${profNombre}
                        </span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 text-sm font-bold">
                                <span>📚</span>
                                <span>${matNombre}</span>
                            </span>
                            ${profCi ? `<span class="text-sm text-slate-500">${profCi}</span>` : ''}
                        </div>
                    </div>
                </td>

                <!-- Columna 2: Curso / Grado & Turno -->
                <td class="p-5">
                    <div class="space-y-1">
                        <span class="text-base font-bold text-slate-700 block">
                            ${gradoNombre}${seccion}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-lg text-sm font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                            ${turno || 'Turno Regular'}
                        </span>
                    </div>
                </td>

                <!-- Columna 3: Carga Horaria -->
                <td class="p-5">
                    <span class="text-base font-bold text-slate-700">
                        ${carga} hs cátedra
                    </span>
                </td>

                <!-- Columna 4: Año Lectivo -->
                <td class="p-5">
                    <span class="text-base font-bold font-mono text-slate-600">
                        ${anio}
                    </span>
                </td>

                <!-- Columna 5: Acciones de Papelera -->
                <td class="p-5 text-right">
                    <div class="flex items-center justify-end gap-2.5 flex-wrap">
                        <!-- Restaurar -->
                        <button type="button" onclick="restaurarAsignacion(${a.id_asignacion}, '${profNombre} - ${matNombre}')"
                                class="min-h-[44px] px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-900 text-base font-bold rounded-xl border border-emerald-300 shadow-2xs transition flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <span>♻️</span>
                            <span>Restaurar</span>
                        </button>

                        <!-- Eliminar Definitivamente -->
                        <button type="button" onclick="eliminarAsignacionDefinitivamente(${a.id_asignacion}, '${profNombre} - ${matNombre}')"
                                class="min-h-[44px] px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-900 text-base font-bold rounded-xl border border-rose-300 shadow-2xs transition flex items-center gap-2 focus:outline-none focus:ring-2 focus:ring-rose-500">
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
    }
}

async function restaurarAsignacion(id, descripcion) {
    const result = await Swal.fire({
        title: '¿Restaurar asignación docente?',
        html: `La cátedra:<br><strong class="text-slate-900 font-bold text-lg mt-1 block">${descripcion}</strong><br><span class="text-sm text-slate-600">Volverá a estar activa y visible en el panel de asignaciones.</span>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, restaurar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
        const formData = new FormData();
        formData.append('id_asignacion', id);

        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Asignaciones/restaurar_asignacion.php';
        const res = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const json = await res.json();

        if (json.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: '¡Asignación Restaurada!',
                text: json.message || 'La cátedra se restauró con éxito.',
                timer: 2000,
                showConfirmButton: false
            });
            cargarAsignacionesDesactivadas();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo restaurar',
                text: json.message || 'Ocurrió un error al restaurar la asignación.',
                confirmButtonColor: '#2563eb'
            });
        }
    } catch (e) {
        console.error("Error restaurando asignación:", e);
        Swal.fire({
            icon: 'error',
            title: 'Error de red',
            text: 'Ocurrió un problema de comunicación con el servidor.',
            confirmButtonColor: '#2563eb'
        });
    }
}

async function eliminarAsignacionDefinitivamente(id, descripcion) {
    const result = await Swal.fire({
        title: '¿Eliminar DEFINITIVAMENTE?',
        html: `Esta acción es <strong>IRREVERSIBLE</strong>.<br>Se borrará la cátedra de:<br><strong class="text-rose-700 font-bold text-lg mt-1 block">${descripcion}</strong><br><span class="text-sm text-slate-600">No podrá eliminarse si tiene bloques de horario programados.</span>`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar para siempre',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
        const formData = new FormData();
        formData.append('id_asignacion', id);

        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Asignaciones/eliminar_asignacion.php';
        const res = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const json = await res.json();

        if (json.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Eliminada con éxito',
                text: json.message || 'El registro fue borrado definitivamente.',
                timer: 2000,
                showConfirmButton: false
            });
            cargarAsignacionesDesactivadas();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo eliminar',
                text: json.message || 'La asignación no pudo ser eliminada.',
                confirmButtonColor: '#2563eb'
            });
        }
    } catch (e) {
        console.error("Error al eliminar asignación:", e);
        Swal.fire({
            icon: 'error',
            title: 'Error de red',
            text: 'Ocurrió un problema de comunicación con el servidor.',
            confirmButtonColor: '#2563eb'
        });
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
