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
            <span class="text-slate-900 font-bold">Asignaciones Docentes</span>
        </nav>

        <!-- Cabecera de Página Accesible -->
        <div class="app-page-header p-6 sm:p-8 rounded-3xl bg-white border-2 border-slate-200/90 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <span class="px-3.5 py-1 text-sm font-extrabold rounded-full bg-blue-100 text-blue-900 border border-blue-200 uppercase tracking-wide">
                        Malla & Cátedras
                    </span>
                    <span class="text-base text-slate-600 font-medium hidden sm:inline">
                        Distribución de Carga Horaria y Docencia
                    </span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                    Gestión de Asignaciones Docentes
                </h1>
                <p class="text-base sm:text-lg text-slate-700 mt-2 max-w-3xl leading-relaxed">
                    Asigná docentes a cada materia y curso, definí las horas cátedra semanales y controlá la carga horaria institucional.
                </p>
            </div>

            <!-- Botonera de Acción Primaria -->
            <div class="flex flex-wrap items-center gap-3 shrink-0">
                <button type="button" onclick="abrirModalCrearAsignacion()" class="min-h-[48px] px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-base font-bold rounded-2xl shadow-sm transition flex items-center gap-2.5 focus:outline-none focus:ring-4 focus:ring-blue-200">
                    <span class="text-xl">+</span>
                    <span>Registrar Asignación</span>
                </button>
                <a href="<?= base_url('mvc/views/asignaciones/desactivadas.php') ?>" class="btn btn-muted min-h-[48px] px-5 py-2.5 text-base font-semibold rounded-2xl border border-slate-300">
                    <span>🗑️</span>
                    <span>Asignaciones Inactivas</span>
                </a>
                <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="btn btn-muted min-h-[48px] px-5 py-2.5 text-base font-semibold rounded-2xl">
                    ← Volver
                </a>
            </div>
        </div>

        <!-- Franja de Indicadores Métricos (KPI Strip de Asignaciones) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Total Asignaciones -->
            <div class="p-6 rounded-3xl bg-blue-50/70 border-2 border-blue-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-blue-900">Asignaciones Activas</span>
                    <div id="kpi-total-asignaciones" class="text-4xl font-black text-slate-900 mt-2">0</div>
                    <p class="text-sm text-slate-600 mt-0.5">Cátedras en funcionamiento</p>
                </div>
                <span class="text-4xl">📋</span>
            </div>

            <!-- Horas Cátedra Totales -->
            <div class="p-6 rounded-3xl bg-indigo-50/70 border-2 border-indigo-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-indigo-900">Horas Cátedra Semanales</span>
                    <div id="kpi-total-horas" class="text-4xl font-black text-slate-900 mt-2">0</div>
                    <p class="text-sm text-slate-600 mt-0.5">Carga horaria total sumada</p>
                </div>
                <span class="text-4xl">⏱️</span>
            </div>

            <!-- Docentes Asignados -->
            <div class="p-6 rounded-3xl bg-emerald-50/70 border-2 border-emerald-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-emerald-900">Docentes con Cátedra</span>
                    <div id="kpi-total-profesores" class="text-4xl font-black text-slate-900 mt-2">0</div>
                    <p class="text-sm text-slate-600 mt-0.5">Profesores titulares asignados</p>
                </div>
                <span class="text-4xl">🧑‍🏫</span>
            </div>

            <!-- Cursos Cubiertos -->
            <div class="p-6 rounded-3xl bg-amber-50/70 border-2 border-amber-200 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-sm font-extrabold uppercase tracking-wider text-amber-900">Cursos / Secciones</span>
                    <div id="kpi-total-cursos" class="text-4xl font-black text-slate-900 mt-2">0</div>
                    <p class="text-sm text-slate-600 mt-0.5">Cursos con materias activas</p>
                </div>
                <span class="text-4xl">🏫</span>
            </div>
        </div>

        <!-- Barra de Búsqueda Predictiva y Filtros en Tiempo Real -->
        <div class="bg-white p-5 rounded-2xl border-2 border-slate-200/90 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
            
            <!-- Campo de Texto de Búsqueda -->
            <div class="relative w-full md:max-w-md">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" id="input-busqueda-asignacion" placeholder="Buscar por docente, materia o curso..."
                       class="w-full min-h-[48px] pl-12 pr-10 py-3 text-base text-slate-900 font-semibold bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                       aria-label="Buscar asignación">
                <button type="button" id="btn-limpiar-busqueda-asignacion" onclick="limpiarBusquedaAsignacion()" class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-700 hidden text-lg font-bold" aria-label="Limpiar búsqueda">
                    ✕
                </button>
            </div>

            <!-- Filtros Rápidos (Curso y Año) -->
            <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                <select id="filtro-curso-asignacion" onchange="filtrarAsignaciones()"
                        class="min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-semibold bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos los cursos</option>
                </select>

                <select id="filtro-anio-asignacion" onchange="filtrarAsignaciones()"
                        class="min-h-[48px] px-4 py-2.5 text-base text-slate-900 font-semibold bg-slate-50 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos los años</option>
                    <option value="2026" selected>Año 2026</option>
                    <option value="2025">Año 2025</option>
                    <option value="2024">Año 2024</option>
                </select>

                <div class="flex items-center gap-2 text-base text-slate-700 font-bold ml-auto md:ml-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    <span id="contador-asignaciones-visibles">Cargando...</span>
                </div>
            </div>

        </div>

        <!-- Tabla Accesible de Asignaciones Docentes (5 Columnas) -->
        <div class="overflow-x-auto rounded-3xl border-2 border-slate-200/90 bg-white shadow-sm">
            <table class="w-full text-left text-base" id="tabla-asignaciones">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-sm border-b-2 border-slate-200">
                    <tr>
                        <th class="p-5">Docente & Materia</th>
                        <th class="p-5">Curso / Grado & Turno</th>
                        <th class="p-5">Carga Horaria</th>
                        <th class="p-5">Año Lectivo</th>
                        <th class="p-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbody-asignaciones" class="divide-y divide-slate-200 text-slate-800">
                    <tr>
                        <td colspan="5" class="p-12 text-center text-slate-500 font-medium">
                            Cargando asignaciones docentes...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Estado Vacío cuando no hay coincidencias -->
        <div id="empty-busqueda-asignaciones" class="hidden p-14 text-center rounded-3xl bg-white border-2 border-slate-200 shadow-sm space-y-4">
            <span class="text-5xl block">🔍</span>
            <h3 class="text-2xl font-bold text-slate-900">No encontramos asignaciones con esos criterios</h3>
            <p class="text-base text-slate-600 max-w-md mx-auto">
                Probá modificando la búsqueda, seleccionando otro curso o limpiá los filtros para ver todas las asignaciones.
            </p>
            <button type="button" onclick="limpiarBusquedaAsignacion()" class="btn btn-primary min-h-[46px] px-6 text-base font-bold rounded-2xl">
                Restablecer lista completa
            </button>
        </div>

    </div>
</main>

<!-- Submodales de Asignación Docente -->
<?php require_once __DIR__ . '/modal_crear_asignacion.php'; ?>
<?php require_once __DIR__ . '/modal_editar_asignacion.php'; ?>
<?php require_once __DIR__ . '/modal_detalle_asignacion.php'; ?>

<script>
let listaAsignacionesGlobal = [];

document.addEventListener('DOMContentLoaded', function() {
    cargarAsignaciones();

    const inputBuscar = document.getElementById('input-busqueda-asignacion');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', filtrarAsignaciones);
    }
});

async function cargarAsignaciones() {
    const tbody = document.getElementById('tbody-asignaciones');
    const labelContador = document.getElementById('contador-asignaciones-visibles');
    
    try {
        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Asignaciones/leer_asignaciones.php';
        const res = await fetch(url);
        const json = await res.json();

        if (json.status !== 'success' || !Array.isArray(json.data)) {
            tbody.innerHTML = `<tr><td colspan="5" class="p-10 text-center text-rose-600 font-bold">Error al cargar asignaciones: ${json.message || 'Respuesta inválida'}</td></tr>`;
            return;
        }

        listaAsignacionesGlobal = json.data;
        actualizarKPIs(listaAsignacionesGlobal);
        filtrarAsignaciones();

        // Cargar catálogo de grados para el select de filtro si no se cargó
        cargarCatalogosAsignacion();

    } catch (e) {
        console.error("Error cargando asignaciones:", e);
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="5" class="p-10 text-center text-rose-600 font-bold">Error de conexión al cargar la lista de asignaciones.</td></tr>`;
        }
    }
}

function actualizarKPIs(lista) {
    const totalEl = document.getElementById('kpi-total-asignaciones');
    const horasEl = document.getElementById('kpi-total-horas');
    const profesEl = document.getElementById('kpi-total-profesores');
    const cursosEl = document.getElementById('kpi-total-cursos');

    const total = lista.length;
    let totalHoras = 0;
    const profesSet = new Set();
    const cursosSet = new Set();

    lista.forEach(a => {
        totalHoras += (parseInt(a.carga_horaria) || 0);
        if (a.id_profesor) profesSet.add(a.id_profesor);
        if (a.id_grado) cursosSet.add(a.id_grado);
    });

    if (totalEl) totalEl.textContent = total;
    if (horasEl) horasEl.textContent = totalHoras;
    if (profesEl) profesEl.textContent = profesSet.size;
    if (cursosEl) cursosEl.textContent = cursosSet.size;
}

function filtrarAsignaciones() {
    const inputBuscar = document.getElementById('input-busqueda-asignacion');
    const filtroCurso = document.getElementById('filtro-curso-asignacion');
    const filtroAnio = document.getElementById('filtro-anio-asignacion');
    const btnLimpiar = document.getElementById('btn-limpiar-busqueda-asignacion');
    const tbody = document.getElementById('tbody-asignaciones');
    const labelContador = document.getElementById('contador-asignaciones-visibles');
    const emptyBox = document.getElementById('empty-busqueda-asignaciones');
    const tabla = document.getElementById('tabla-asignaciones');

    const q = (inputBuscar ? inputBuscar.value : '').trim().toLowerCase();
    const cursoSel = (filtroCurso ? filtroCurso.value : '').trim().toLowerCase();
    const anioSel = (filtroAnio ? filtroAnio.value : '').trim();

    if (btnLimpiar) {
        btnLimpiar.classList.toggle('hidden', q.length === 0 && !cursoSel && !anioSel);
    }

    const filtradas = listaAsignacionesGlobal.filter(a => {
        const prof = (a.profesor || '').toLowerCase();
        const mat = (a.materia || '').toLowerCase();
        const grad = (a.grado || '').toLowerCase();
        const anio = String(a.anho_lectivo || a['año_lectivo'] || '');

        const matchQ = !q || prof.includes(q) || mat.includes(q) || grad.includes(q) || anio.includes(q);
        const matchCurso = !cursoSel || grad.includes(cursoSel);
        const matchAnio = !anioSel || anio === anioSel;

        return matchQ && matchCurso && matchAnio;
    });

    if (labelContador) {
        labelContador.textContent = `${filtradas.length} de ${listaAsignacionesGlobal.length} asignaciones`;
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
        const asigJson = JSON.stringify(a).replace(/"/g, '&quot;');
        const profNombre = a.profesor || 'Docente no asignado';
        const profCi = a.profesor_ci ? `C.I. ${a.profesor_ci}` : '';
        const matNombre = a.materia || 'Materia';
        const gradoNombre = a.grado || 'Curso no definido';
        const seccion = a.seccion ? ` "${a.seccion}"` : '';
        const turno = a.turno ? a.turno : '';
        const carga = a.carga_horaria || 0;
        const anio = a.anho_lectivo || a['año_lectivo'] || 2026;

        return `
            <tr class="hover:bg-slate-50/90 transition-colors">
                <!-- Columna 1: Docente & Materia -->
                <td class="p-5">
                    <div class="space-y-1">
                        <span class="text-lg sm:text-xl font-black text-slate-900 block leading-snug">
                            ${profNombre}
                        </span>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-blue-50 text-blue-900 border border-blue-200 text-sm font-extrabold">
                                <span>📚</span>
                                <span>${matNombre}</span>
                            </span>
                            ${profCi ? `<span class="text-sm text-slate-500 font-medium">${profCi}</span>` : ''}
                        </div>
                    </div>
                </td>

                <!-- Columna 2: Curso / Grado & Turno -->
                <td class="p-5">
                    <div class="space-y-1">
                        <span class="text-base sm:text-lg font-bold text-slate-800 block">
                            ${gradoNombre}${seccion}
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-lg text-sm font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                ${turno || 'Turno Regular'}
                            </span>
                        </div>
                    </div>
                </td>

                <!-- Columna 3: Carga Horaria -->
                <td class="p-5">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-2xl bg-indigo-50 border border-indigo-200">
                        <span class="text-lg font-black text-indigo-900">${carga} hs</span>
                        <span class="text-xs font-bold text-indigo-700 uppercase">cátedra/sem</span>
                    </div>
                </td>

                <!-- Columna 4: Año Lectivo -->
                <td class="p-5">
                    <span class="px-3.5 py-1.5 rounded-2xl bg-slate-100 text-slate-800 border border-slate-200 text-base font-bold font-mono">
                        ${anio}
                    </span>
                </td>

                <!-- Columna 5: Acciones Accesibles -->
                <td class="p-5 text-right">
                    <div class="flex items-center justify-end gap-2 flex-wrap">
                        <!-- Detalles (Submodal) -->
                        <button type="button" onclick="abrirModalDetalleAsignacion(${a.id_asignacion})"
                                class="min-h-[44px] px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-800 text-base font-bold rounded-xl border border-blue-200 shadow-2xs transition flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                title="Ver detalles y horarios de la asignación">
                            <span>📋</span>
                            <span class="hidden sm:inline">Detalles</span>
                        </button>

                        <!-- Editar -->
                        <button type="button" onclick="abrirModalEditarAsignacion(${asigJson})"
                                class="min-h-[44px] px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-900 text-base font-bold rounded-xl border border-amber-200 shadow-2xs transition flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-amber-400"
                                title="Modificar asignación docente">
                            <span>✏️</span>
                            <span class="hidden sm:inline">Editar</span>
                        </button>

                        <!-- Desactivar -->
                        <button type="button" onclick="desactivarAsignacion(${a.id_asignacion}, '${profNombre} - ${matNombre}')"
                                class="min-h-[44px] px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-800 text-base font-bold rounded-xl border border-rose-200 shadow-2xs transition flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-rose-400"
                                title="Desactivar asignación docente">
                            <span>🗑️</span>
                            <span class="hidden sm:inline">Desactivar</span>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function limpiarBusquedaAsignacion() {
    const inputBuscar = document.getElementById('input-busqueda-asignacion');
    const filtroCurso = document.getElementById('filtro-curso-asignacion');
    const filtroAnio = document.getElementById('filtro-anio-asignacion');

    if (inputBuscar) inputBuscar.value = '';
    if (filtroCurso) filtroCurso.value = '';
    if (filtroAnio) filtroAnio.value = '';

    filtrarAsignaciones();
}

async function desactivarAsignacion(id, descripcion) {
    const result = await Swal.fire({
        title: '¿Desactivar asignación docente?',
        html: `Estás a punto de dar de baja la asignación de:<br><strong class="text-slate-900 font-bold text-lg mt-1 block">${descripcion}</strong><br><span class="text-sm text-slate-600">Podrás reactivarla en cualquier momento desde la sección de asignaciones inactivas.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
        const formData = new FormData();
        formData.append('id_asignacion', id);

        const url = (window.BASE_URL ? window.BASE_URL.replace(/\/+$/, '') : '') + '/src/api/Asignaciones/desactivar_asignacion.php';
        const res = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const json = await res.json();

        if (json.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Asignación desactivada',
                text: json.message || 'La cátedra fue dada de baja correctamente.',
                timer: 2000,
                showConfirmButton: false
            });
            cargarAsignaciones();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error al desactivar',
                text: json.message || 'No se pudo desactivar la asignación.',
                confirmButtonColor: '#2563eb'
            });
        }
    } catch (e) {
        console.error("Error desactivando asignación:", e);
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
