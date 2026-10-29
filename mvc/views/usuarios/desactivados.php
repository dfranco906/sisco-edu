<?php
declare(strict_types=1);

$titulo = "Usuarios Desactivados";

require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full space-y-6 text-base">

    <!-- Navegación de Migas de Pan (Breadcrumbs) -->
    <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 font-medium" aria-label="Breadcrumb">
        <a href="<?= base_url('mvc/views/dashboard/index.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Inicio</a>
        <span>/</span>
        <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Administración</a>
        <span>/</span>
        <a href="<?= base_url('mvc/views/usuarios/index.php') ?>" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">Gestión de Usuarios</a>
        <span>/</span>
        <span class="text-slate-900 dark:text-slate-100 font-bold" aria-current="page">Desactivados</span>
    </nav>

    <!-- Encabezado Principal y Barra de Acciones Accesibles -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-3">
                <span class="p-3 rounded-2xl bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 text-2xl font-bold shrink-0 border border-rose-200 dark:border-rose-800 shadow-xs">
                    🚫
                </span>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Usuarios Desactivados
                    </h1>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1">
                        Historial de cuentas suspendidas o dadas de baja en el sistema escolar.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <a href="<?= base_url('mvc/views/usuarios/index.php') ?>" class="btn btn-primary text-base font-bold py-2.5 px-5 min-h-[44px] inline-flex items-center gap-2 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>Volver a Usuarios Activos</span>
            </a>
        </div>
    </div>

    <!-- Tira de KPIs de Resumen de Cuentas Inactivas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm text-base">
        <div class="p-4 rounded-2xl bg-rose-50/70 dark:bg-rose-950/40 border border-rose-100 dark:border-rose-900/50 flex items-center justify-between">
            <div>
                <span class="text-sm font-bold uppercase tracking-wider text-rose-900 dark:text-rose-300 block">
                    Total Desactivados
                </span>
                <span id="kpi-total-desactivados" class="text-2xl sm:text-3xl font-black text-rose-800 dark:text-rose-200 block mt-0.5">
                    0
                </span>
            </div>
            <div class="text-3xl">🚫</div>
        </div>

        <div class="p-4 rounded-2xl bg-purple-50/70 dark:bg-purple-950/40 border border-purple-100 dark:border-purple-900/50 flex items-center justify-between">
            <div>
                <span class="text-sm font-bold uppercase tracking-wider text-purple-900 dark:text-purple-300 block">
                    Directivos & Admins
                </span>
                <span id="kpi-admins-desactivados" class="text-2xl sm:text-3xl font-black text-purple-800 dark:text-purple-200 block mt-0.5">
                    0
                </span>
            </div>
            <div class="text-3xl">🛡️</div>
        </div>

        <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/50 flex items-center justify-between">
            <div>
                <span class="text-sm font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-300 block">
                    Personal Docente
                </span>
                <span id="kpi-docentes-desactivados" class="text-2xl sm:text-3xl font-black text-emerald-800 dark:text-emerald-200 block mt-0.5">
                    0
                </span>
            </div>
            <div class="text-3xl">👨‍🏫</div>
        </div>

        <div class="p-4 rounded-2xl bg-cyan-50/70 dark:bg-cyan-950/40 border border-cyan-100 dark:border-cyan-900/50 flex items-center justify-between">
            <div>
                <span class="text-sm font-bold uppercase tracking-wider text-cyan-900 dark:text-cyan-300 block">
                    Alumnos & Tutores
                </span>
                <span id="kpi-alumnos-tutores-desactivados" class="text-2xl sm:text-3xl font-black text-cyan-800 dark:text-cyan-200 block mt-0.5">
                    0
                </span>
            </div>
            <div class="text-3xl">🎓</div>
        </div>
    </div>

    <!-- Pestañas por Rol en el Sistema -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 border-b border-slate-200 dark:border-slate-800" role="tablist" aria-label="Filtrar por rol">
        <button type="button" role="tab" aria-selected="true" data-rol="" onclick="seleccionarPestanaRolDesactivado('', this)" 
                class="tab-rol-desactivado px-4 py-2.5 rounded-xl font-bold text-base transition-colors shrink-0 bg-rose-600 text-white shadow-xs">
            Todos <span id="tab-des-cnt-todos" class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-rose-800 text-white font-black">0</span>
        </button>
        <button type="button" role="tab" aria-selected="false" data-rol="SuperAdmin" onclick="seleccionarPestanaRolDesactivado('SuperAdmin', this)" 
                class="tab-rol-desactivado px-4 py-2.5 rounded-xl font-semibold text-base transition-colors shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
            SuperAdmin <span id="tab-des-cnt-superadmin" class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-slate-200 dark:bg-slate-700 font-bold">0</span>
        </button>
        <button type="button" role="tab" aria-selected="false" data-rol="Administrador" onclick="seleccionarPestanaRolDesactivado('Administrador', this)" 
                class="tab-rol-desactivado px-4 py-2.5 rounded-xl font-semibold text-base transition-colors shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
            Administradores <span id="tab-des-cnt-administrador" class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-slate-200 dark:bg-slate-700 font-bold">0</span>
        </button>
        <button type="button" role="tab" aria-selected="false" data-rol="Coordinador" onclick="seleccionarPestanaRolDesactivado('Coordinador', this)" 
                class="tab-rol-desactivado px-4 py-2.5 rounded-xl font-semibold text-base transition-colors shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
            Coordinadores <span id="tab-des-cnt-coordinador" class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-slate-200 dark:bg-slate-700 font-bold">0</span>
        </button>
        <button type="button" role="tab" aria-selected="false" data-rol="Profesor" onclick="seleccionarPestanaRolDesactivado('Profesor', this)" 
                class="tab-rol-desactivado px-4 py-2.5 rounded-xl font-semibold text-base transition-colors shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
            Profesores <span id="tab-des-cnt-profesor" class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-slate-200 dark:bg-slate-700 font-bold">0</span>
        </button>
        <button type="button" role="tab" aria-selected="false" data-rol="Estudiante" onclick="seleccionarPestanaRolDesactivado('Estudiante', this)" 
                class="tab-rol-desactivado px-4 py-2.5 rounded-xl font-semibold text-base transition-colors shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
            Estudiantes <span id="tab-des-cnt-estudiante" class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-slate-200 dark:bg-slate-700 font-bold">0</span>
        </button>
        <button type="button" role="tab" aria-selected="false" data-rol="Tutor" onclick="seleccionarPestanaRolDesactivado('Tutor', this)" 
                class="tab-rol-desactivado px-4 py-2.5 rounded-xl font-semibold text-base transition-colors shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700">
            Tutores <span id="tab-des-cnt-tutor" class="ml-1.5 px-2 py-0.5 rounded-full text-xs bg-slate-200 dark:bg-slate-700 font-bold">0</span>
        </button>
    </div>

    <!-- Barra de búsqueda y conteo accesible -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative flex-1 max-w-lg">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input type="text" id="busqueda-desactivados" oninput="filtrarUsuariosDesactivados()" placeholder="Buscar por nombre, apellido, usuario/CI, email o rol..."
                   class="app-input w-full pl-11 pr-10 py-3 text-base rounded-2xl min-h-[48px]">
            <button type="button" id="btn-limpiar-busqueda-des" onclick="limpiarBusquedaDesactivados()" class="hidden absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer" aria-label="Limpiar búsqueda">
                ✕
            </button>
        </div>

        <div class="text-base text-slate-700 dark:text-slate-300 font-semibold">
            Mostrando <span id="contador-des-filtrados" class="font-extrabold text-rose-700 dark:text-rose-300">0</span> de <span id="contador-des-total" class="font-extrabold text-slate-900 dark:text-white">0</span> cuentas desactivadas
        </div>
    </div>

    <!-- Tabla Accesible de Usuarios Desactivados -->
    <div class="app-table-wrap rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-base">
                <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800 text-sm sm:text-base font-bold uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-4 px-5 text-left">Usuario / Persona</th>
                        <th scope="col" class="py-4 px-5 text-left">Rol Institucional</th>
                        <th scope="col" class="py-4 px-5 text-left">Contacto</th>
                        <th scope="col" class="py-4 px-5 text-left">Estado</th>
                        <th scope="col" class="py-4 px-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-desactivados-body" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr>
                        <td colspan="5" class="p-8 text-center text-base text-slate-500 font-semibold">
                            Cargando usuarios desactivados...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
(() => {
    "use strict";

    let desactivadosCargados = [];
    let rolFiltroActivo = '';

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

    function obtenerIniciales(nombre, apellido) {
        const n = (nombre || '').trim().charAt(0).toUpperCase();
        const a = (apellido || '').trim().charAt(0).toUpperCase();
        return (n + a) || 'US';
    }

    function normalizarRol(rol) {
        const r = (rol || '').trim();
        if (r.toLowerCase() === 'administracion') return 'Administrador';
        return r;
    }

    function obtenerEstiloRol(rol) {
        const r = normalizarRol(rol).toLowerCase();
        switch (r) {
            case 'superadmin':
                return {
                    badge: 'bg-purple-100 dark:bg-purple-950/60 text-purple-800 dark:text-purple-300 border-purple-200 dark:border-purple-800',
                    label: '🛡️ SuperAdmin'
                };
            case 'administrador':
                return {
                    badge: 'bg-blue-100 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                    label: '💼 Administrador'
                };
            case 'coordinador':
                return {
                    badge: 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-800 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
                    label: '📋 Coordinador'
                };
            case 'profesor':
                return {
                    badge: 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                    label: '👨‍🏫 Profesor'
                };
            case 'estudiante':
                return {
                    badge: 'bg-cyan-100 dark:bg-cyan-950/60 text-cyan-800 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800',
                    label: '🎓 Estudiante'
                };
            case 'tutor':
                return {
                    badge: 'bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                    label: '👨‍👩‍👦 Tutor'
                };
            default:
                return {
                    badge: 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border-slate-200 dark:border-slate-700',
                    label: escapeHtml(rol || 'Usuario')
                };
        }
    }

    // 1. Cargar Usuarios Desactivados
    async function cargarUsuariosDesactivados() {
        const tbody = document.getElementById('tabla-desactivados-body');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-base text-slate-500 font-semibold">Cargando usuarios desactivados...</td></tr>';

        try {
            const res = await fetch(`${getBaseUrl()}/src/api/Usuario/leer_usuarios_desactivados.php`);
            const json = await res.json();
            desactivadosCargados = json.data || [];

            actualizarKpisYTabs();
            filtrarUsuariosDesactivados();
        } catch (err) {
            console.error("Error al cargar usuarios desactivados:", err);
            tbody.innerHTML = '<tr><td colspan="5" class="p-8 text-center text-base text-rose-600 font-bold">Error al cargar la lista de usuarios desactivados.</td></tr>';
        }
    }

    // 2. Actualizar KPIs y Contadores
    function actualizarKpisYTabs() {
        const total = desactivadosCargados.length;
        let admins = 0;
        let docentes = 0;
        let alumnosTutores = 0;

        let cntSuperAdmin = 0;
        let cntAdmin = 0;
        let cntCoord = 0;
        let cntProf = 0;
        let cntEst = 0;
        let cntTut = 0;

        desactivadosCargados.forEach(u => {
            const r = normalizarRol(u.rol).toLowerCase();
            if (r === 'superadmin') {
                cntSuperAdmin++;
                admins++;
            } else if (r === 'administrador') {
                cntAdmin++;
                admins++;
            } else if (r === 'coordinador') {
                cntCoord++;
                docentes++;
            } else if (r === 'profesor') {
                cntProf++;
                docentes++;
            } else if (r === 'estudiante') {
                cntEst++;
                alumnosTutores++;
            } else if (r === 'tutor') {
                cntTut++;
                alumnosTutores++;
            }
        });

        const kpiTotal = document.getElementById('kpi-total-desactivados');
        const kpiAdmins = document.getElementById('kpi-admins-desactivados');
        const kpiDocentes = document.getElementById('kpi-docentes-desactivados');
        const kpiAlumnosTutores = document.getElementById('kpi-alumnos-tutores-desactivados');

        if (kpiTotal) kpiTotal.textContent = String(total);
        if (kpiAdmins) kpiAdmins.textContent = String(admins);
        if (kpiDocentes) kpiDocentes.textContent = String(docentes);
        if (kpiAlumnosTutores) kpiAlumnosTutores.textContent = String(alumnosTutores);

        // Pestañas
        document.getElementById('tab-des-cnt-todos').textContent = String(total);
        document.getElementById('tab-des-cnt-superadmin').textContent = String(cntSuperAdmin);
        document.getElementById('tab-des-cnt-administrador').textContent = String(cntAdmin);
        document.getElementById('tab-des-cnt-coordinador').textContent = String(cntCoord);
        document.getElementById('tab-des-cnt-profesor').textContent = String(cntProf);
        document.getElementById('tab-des-cnt-estudiante').textContent = String(cntEst);
        document.getElementById('tab-des-cnt-tutor').textContent = String(cntTut);
    }

    // 3. Filtrar
    function filtrarUsuariosDesactivados() {
        const queryInput = document.getElementById('busqueda-desactivados');
        const btnLimpiar = document.getElementById('btn-limpiar-busqueda-des');
        const query = (queryInput ? queryInput.value : '').trim().toLowerCase();

        if (btnLimpiar) {
            btnLimpiar.classList.toggle('hidden', query.length === 0);
        }

        const filtrados = desactivadosCargados.filter(u => {
            if (rolFiltroActivo) {
                const userRol = normalizarRol(u.rol).toLowerCase();
                const targetRol = normalizarRol(rolFiltroActivo).toLowerCase();
                if (userRol !== targetRol) return false;
            }

            if (!query) return true;

            const nombreCompleto = `${u.nombre || ''} ${u.apellido || ''}`.toLowerCase();
            const usuarioStr = String(u.usuario || '').toLowerCase();
            const emailStr = String(u.email || '').toLowerCase();
            const rolStr = String(u.rol || '').toLowerCase();
            const celularStr = String(u.celular || '').toLowerCase();

            return nombreCompleto.includes(query) ||
                   usuarioStr.includes(query) ||
                   emailStr.includes(query) ||
                   rolStr.includes(query) ||
                   celularStr.includes(query);
        });

        renderizarTablaDesactivados(filtrados);
    }

    // 4. Renderizar Filas
    function renderizarTablaDesactivados(lista) {
        const tbody = document.getElementById('tabla-desactivados-body');
        const cntFiltrados = document.getElementById('contador-des-filtrados');
        const cntTotal = document.getElementById('contador-des-total');

        if (cntFiltrados) cntFiltrados.textContent = String(lista.length);
        if (cntTotal) cntTotal.textContent = String(desactivadosCargados.length);

        if (!tbody) return;

        if (lista.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="p-12 text-center text-slate-500">
                        <div class="text-4xl mb-3">✅</div>
                        <p class="font-extrabold text-lg text-slate-800 dark:text-slate-200">No hay usuarios desactivados</p>
                        <p class="text-base mt-1">Todas las cuentas están actualmente activas en el sistema.</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = lista.map(u => {
            const iniciales = obtenerIniciales(u.nombre, u.apellido);
            const estilo = obtenerEstiloRol(u.rol);
            const nombreCompleto = `${u.nombre || ''} ${u.apellido || ''}`.trim() || 'Sin Nombre';
            const email = (u.email && u.email.trim() !== '') ? escapeHtml(u.email) : 'Sin correo registrado';
            const celular = (u.celular && u.celular.trim() !== '' && u.celular !== '0') ? escapeHtml(u.celular) : 'Sin celular';

            return `
                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors opacity-90">
                    <td class="py-4 px-5">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-slate-300 dark:bg-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center font-extrabold text-base shadow-xs shrink-0 tracking-wider">
                                ${iniciales}
                            </div>
                            <div class="min-w-0">
                                <div class="font-extrabold text-slate-800 dark:text-slate-200 text-base truncate">
                                    ${escapeHtml(nombreCompleto)}
                                </div>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                        @${escapeHtml(u.usuario || '---')}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <td class="py-4 px-5">
                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-sm font-bold border ${estilo.badge}">
                            ${estilo.label}
                        </span>
                    </td>

                    <td class="py-4 px-5">
                        <div class="text-sm sm:text-base text-slate-700 dark:text-slate-300 space-y-0.5">
                            <div class="flex items-center gap-1.5 truncate max-w-xs" title="${email}">
                                <span class="text-slate-400 text-xs shrink-0">✉️</span>
                                <span class="truncate">${email}</span>
                            </div>
                            <div class="flex items-center gap-1.5 font-mono text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                                <span class="text-slate-400 text-xs shrink-0">📱</span>
                                <span>${celular}</span>
                            </div>
                        </div>
                    </td>

                    <td class="py-4 px-5">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-900/50">
                            🚫 Suspendido
                        </span>
                    </td>

                    <td class="py-4 px-5 text-right">
                        <div class="inline-flex items-center justify-end gap-2 flex-wrap">
                            <button type="button" onclick="confirmarRestaurarUsuario(${u.id_usuario}, '${escapeHtml(nombreCompleto)}')" 
                                    class="btn btn-success text-sm font-bold py-2 px-3 min-h-[44px] inline-flex items-center gap-1.5 shadow-xs" 
                                    title="Restablecer acceso al usuario">
                                <span>♻️</span>
                                <span>Restaurar</span>
                            </button>
                            <button type="button" onclick="confirmarEliminarUsuario(${u.id_usuario}, '${escapeHtml(nombreCompleto)}')" 
                                    class="btn btn-danger text-sm font-bold py-2 px-3 min-h-[44px] inline-flex items-center gap-1.5 shadow-xs" 
                                    title="Eliminar usuario permanentemente de la base de datos">
                                <span>🗑️</span>
                                <span>Eliminar Permanente</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // 5. Pestañas
    window.seleccionarPestanaRolDesactivado = function(rol, btn) {
        rolFiltroActivo = rol;
        document.querySelectorAll('.tab-rol-desactivado').forEach(b => {
            b.classList.remove('bg-rose-600', 'text-white', 'shadow-xs');
            b.classList.add('bg-slate-100', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
            b.setAttribute('aria-selected', 'false');
        });

        if (btn) {
            btn.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'text-slate-700', 'dark:text-slate-300');
            btn.classList.add('bg-rose-600', 'text-white', 'shadow-xs');
            btn.setAttribute('aria-selected', 'true');
        }

        filtrarUsuariosDesactivados();
    };

    window.filtrarUsuariosDesactivados = filtrarUsuariosDesactivados;

    window.limpiarBusquedaDesactivados = function() {
        const queryInput = document.getElementById('busqueda-desactivados');
        if (queryInput) {
            queryInput.value = '';
            queryInput.focus();
        }
        filtrarUsuariosDesactivados();
    };

    // 6. Restaurar Usuario
    window.confirmarRestaurarUsuario = async function(idUsuario, nombreCompleto) {
        let confirmado = false;
        if (window.SISCO && typeof window.SISCO.confirm === 'function') {
            confirmado = await window.SISCO.confirm({
                title: '¿Restaurar cuenta de usuario?',
                message: `La cuenta de ${nombreCompleto} será reactivada inmediatamente con todos sus permisos anteriores. Podrá volver a iniciar sesión con sus credenciales habituales.`,
                confirmText: 'Sí, Restaurar',
                cancelText: 'Cancelar',
                type: 'primary'
            });
        } else {
            confirmado = confirm(`¿Estás seguro de restaurar al usuario "${nombreCompleto}"?`);
        }

        if (!confirmado) return;

        try {
            const res = await fetch(`${getBaseUrl()}/src/api/Usuario/restaurar_usuario.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_usuario: idUsuario })
            });

            const json = await res.json();
            if (json.status !== 'success') {
                throw new Error(json.message || 'Error al restaurar el usuario.');
            }

            if (window.Toast) {
                window.Toast.success(json.message || 'Usuario restaurado exitosamente.');
            }
            await cargarUsuariosDesactivados();
        } catch (err) {
            console.error("Error al restaurar usuario:", err);
            if (window.Toast) {
                window.Toast.error(err.message || 'No se pudo restaurar el usuario.');
            }
        }
    };

    // 7. Eliminar Usuario Definitivamente
    window.confirmarEliminarUsuario = async function(idUsuario, nombreCompleto) {
        let confirmado = false;
        if (window.SISCO && typeof window.SISCO.confirm === 'function') {
            confirmado = await window.SISCO.confirm({
                title: '⚠️ ¿Eliminar permanentemente?',
                message: `ADVERTENCIA: Esta acción es irreversible. Se eliminará por completo la cuenta de ${nombreCompleto} de la base de datos. Si tiene registros vinculados a asistencias o notas, la eliminación podría ser bloqueada por integridad referencial.`,
                confirmText: 'Sí, Eliminar Definitivamente',
                cancelText: 'Cancelar',
                type: 'danger'
            });
        } else {
            confirmado = confirm(`ADVERTENCIA: ¿Eliminar definitivamente al usuario "${nombreCompleto}"? Esta acción no se puede deshacer.`);
        }

        if (!confirmado) return;

        try {
            const res = await fetch(`${getBaseUrl()}/src/api/Usuario/eliminar_usuario.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_usuario: idUsuario })
            });

            const json = await res.json();
            if (json.status !== 'success') {
                throw new Error(json.message || 'No se pudo eliminar el usuario.');
            }

            if (window.Toast) {
                window.Toast.success(json.message || 'Usuario eliminado permanentemente.');
            }
            await cargarUsuariosDesactivados();
        } catch (err) {
            console.error("Error al eliminar usuario:", err);
            if (window.Toast) {
                window.Toast.error(err.message || 'No se pudo eliminar el usuario.');
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        cargarUsuariosDesactivados();
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
