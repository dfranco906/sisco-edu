<?php
declare(strict_types=1);

$titulo = "Tutores y Familias";

require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full space-y-6 text-base">

    <!-- Encabezado Principal y Barra de Acciones Accesibles -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-3">
                <span class="p-2.5 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-2xl font-bold shrink-0 border border-indigo-200 dark:border-indigo-800">
                    👨‍👩‍👧‍👦
                </span>
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Tutores y Familias
                    </h2>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1">
                        Directorio de padres, madres y encargados legales con vinculación escolar.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button type="button" onclick="abrirModalCrearTutor()" class="btn btn-success text-base font-bold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2 shadow-sm" title="Registrar un nuevo tutor">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Nuevo Tutor</span>
            </button>

            <button type="button" onclick="GestorFamilias.abrir()" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2" title="Asistente para vincular tutores con alumnos o hermanos">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <span>+ Vincular Hijos</span>
            </button>

            <button type="button" onclick="abrirModalImportarTutores()" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2" title="Importar tutores desde archivo CSV / Excel">
                <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span>Importar</span>
            </button>

            <a href="<?= base_url('src/api/Tutor/exportar_tutores.php') ?>" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2" title="Exportar directorio de tutores a CSV / Excel">
                <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Exportar</span>
            </a>

            <a href="<?= base_url('mvc/views/tutores/desactivados.php') ?>" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2" title="Consultar o restaurar tutores dados de baja">
                <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <span>Desactivados</span>
            </a>

            <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="btn btn-primary text-base font-semibold py-2.5 px-4 min-h-[44px]">
                Volver
            </a>
        </div>
    </div>

    <!-- Tira de Métricas y Resumen de Familias -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm text-base">
        <div class="p-4 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 flex items-center justify-between">
            <div>
                <span class="text-sm font-bold uppercase tracking-wider text-indigo-900 dark:text-indigo-300 block">
                    Total de Tutores
                </span>
                <span id="kpi-total-tutores" class="text-2xl sm:text-3xl font-black text-indigo-800 dark:text-indigo-200 block mt-0.5">
                    0
                </span>
            </div>
            <div class="text-3xl">👥</div>
        </div>

        <div class="p-4 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-100 dark:border-emerald-900/50 flex items-center justify-between">
            <div>
                <span class="text-sm font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-300 block">
                    Con Hijos Asignados
                </span>
                <span id="kpi-con-hijos" class="text-2xl sm:text-3xl font-black text-emerald-800 dark:text-emerald-200 block mt-0.5">
                    0
                </span>
            </div>
            <div class="text-3xl">👨‍👩‍👦</div>
        </div>

        <div class="p-4 rounded-2xl bg-amber-50/70 dark:bg-amber-950/40 border border-amber-100 dark:border-amber-900/50 flex items-center justify-between">
            <div>
                <span class="text-sm font-bold uppercase tracking-wider text-amber-900 dark:text-amber-300 block">
                    Sin Estudiantes
                </span>
                <span id="kpi-sin-hijos" class="text-2xl sm:text-3xl font-black text-amber-800 dark:text-amber-200 block mt-0.5">
                    0
                </span>
            </div>
            <div class="text-3xl">⚠️</div>
        </div>
    </div>

    <!-- Barra de búsqueda y conteo accesible -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="relative flex-1 max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </span>
            <input type="text" id="busqueda-tutores-filtro" placeholder="Buscar por nombre, CI, teléfono o hijos..."
                   class="app-input w-full pl-11 pr-4 py-3 text-base rounded-2xl min-h-[46px]">
        </div>

        <div class="text-base text-slate-700 dark:text-slate-300 font-semibold">
            Mostrando <span id="contador-filtrados" class="font-extrabold text-indigo-700 dark:text-indigo-300">0</span> de <span id="contador-total" class="font-extrabold text-slate-900 dark:text-white">0</span> tutores
        </div>
    </div>

    <!-- Tabla Rediseñada: Fuentes Grandes (>= 16px / 12 pt) y Revelación Progresiva -->
    <div class="app-table-wrap rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-base">
                <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border-b border-slate-200 dark:border-slate-800 text-sm sm:text-base font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-4 px-5 text-left">Tutor / Responsable</th>
                        <th class="py-4 px-5 text-left">Estudiantes Vinculados</th>
                        <th class="py-4 px-5 text-left">Contacto & Teléfono</th>
                        <th class="py-4 px-5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-tutores-body" class="divide-y divide-slate-100 dark:divide-slate-800">
                    <tr>
                        <td colspan="4" class="p-8 text-center text-base text-slate-500 font-semibold">
                            Cargando directorio de tutores...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modales Modulares Incluidos -->
    <?php require_once __DIR__ . '/modal_detalle_tutor.php'; ?>
    <?php require_once __DIR__ . '/modal_crear_tutor.php'; ?>
    <?php require_once __DIR__ . '/modal_editar_tutor.php'; ?>
    <?php require_once __DIR__ . '/modal_importar_tutores.php'; ?>
    <?php require_once __DIR__ . '/modal_vincular_familia.php'; ?>

</main>

<script>
let tutoresCargados = [];
let tutorSeleccionadoActivo = null;

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
    return (n + a) || 'TU';
}

// 1. Cargar Directorio de Tutores
async function cargarTutores() {
    const tbody = document.getElementById('tabla-tutores-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-base text-slate-500 font-semibold">Cargando directorio de tutores...</td></tr>';

    try {
        const res = await fetch(`${getBaseUrl()}/src/api/Tutor/leer_tutores.php`);
        const json = await res.json();
        tutoresCargados = json.data || [];

        // Métricas
        const total = tutoresCargados.length;
        const conHijos = tutoresCargados.filter(t => parseInt(t.total_hijos || 0) > 0).length;
        const sinHijos = total - conHijos;

        document.getElementById('kpi-total-tutores').textContent = String(total);
        document.getElementById('kpi-con-hijos').textContent = String(conHijos);
        document.getElementById('kpi-sin-hijos').textContent = String(sinHijos);

        renderizarTablaTutores(tutoresCargados);
    } catch (err) {
        console.error("Error cargando tutores:", err);
        tbody.innerHTML = '<tr><td colspan="4" class="p-8 text-center text-base text-rose-600 font-bold">Error al cargar la lista de tutores.</td></tr>';
    }
}

// 2. Renderizar Tabla de Tutores (Tipografía >= 16px)
function renderizarTablaTutores(lista) {
    const tbody = document.getElementById('tabla-tutores-body');
    const cntFiltrados = document.getElementById('contador-filtrados');
    const cntTotal = document.getElementById('contador-total');

    if (cntFiltrados) cntFiltrados.textContent = String(lista.length);
    if (cntTotal) cntTotal.textContent = String(tutoresCargados.length);

    if (!tbody) return;

    if (lista.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="p-10 text-center text-slate-500">
                    <p class="font-extrabold text-lg text-slate-800 dark:text-slate-200">No se encontraron tutores</p>
                    <p class="text-base mt-1">Pruebe ajustando el término de búsqueda o registre un nuevo tutor.</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = lista.map(t => {
        const totalHijos = parseInt(t.total_hijos || 0);
        let hijosHtml = '';
        if (totalHijos > 0) {
            hijosHtml = `
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-100/80 text-emerald-900 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-bold text-sm sm:text-base">
                        👨‍👩‍👧 ${totalHijos} ${totalHijos === 1 ? 'hijo vinculado' : 'hijos vinculados'}
                    </span>
                    <button type="button" onclick="abrirModalDetalleTutor(${t.id_tutor})" class="block text-sm font-bold text-indigo-700 dark:text-indigo-400 hover:underline">
                        Ver ficha y expedientes →
                    </button>
                </div>
            `;
        } else {
            hijosHtml = `
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 text-sm font-semibold">
                        Sin hijos a cargo
                    </span>
                    <button type="button" onclick="abrirVincularParaTutor(${t.id_tutor}, '${escapeHtml(t.nombre)}', '${escapeHtml(t.apellido)}', '${escapeHtml(t.cedula_identidad)}')" class="btn btn-secondary text-sm font-bold py-1 px-3 min-h-[36px]">
                        + Vincular
                    </button>
                </div>
            `;
        }

        const iniciales = obtenerIniciales(t.nombre, t.apellido);
        const jsonTut = JSON.stringify(t).replace(/"/g, '&quot;');

        const celularTxt = t.celular
            ? `<div class="text-base font-mono font-bold text-slate-900 dark:text-white">📱 ${escapeHtml(t.celular)}</div>`
            : '<span class="text-slate-400 text-sm">Sin teléfono registrado</span>';

        const emailTxt = t.email
            ? `<div class="text-sm font-medium text-slate-600 dark:text-slate-400 truncate max-w-[200px]">${escapeHtml(t.email)}</div>`
            : '';

        return `
            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition-colors">
                <!-- Columna 1: Tutor & Cédula -->
                <td class="py-4 px-5">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200 flex items-center justify-center font-black text-lg border border-indigo-200 dark:border-indigo-800 shrink-0">
                            ${iniciales}
                        </div>
                        <div>
                            <div class="font-extrabold text-base sm:text-lg text-slate-900 dark:text-white leading-tight">
                                ${escapeHtml(t.nombre)} ${escapeHtml(t.apellido)}
                            </div>
                            <div class="text-base text-slate-700 dark:text-slate-300 font-mono mt-0.5">
                                CI: <strong class="text-indigo-700 dark:text-indigo-300 font-bold">${escapeHtml(t.cedula_identidad)}</strong>
                            </div>
                        </div>
                    </div>
                </td>

                <!-- Columna 2: Estudiantes a Cargo -->
                <td class="py-4 px-5">
                    ${hijosHtml}
                </td>

                <!-- Columna 3: Contacto -->
                <td class="py-4 px-5">
                    ${celularTxt}
                    ${emailTxt}
                </td>

                <!-- Columna 4: Acciones Principales con Objetivos Táctiles Grandes -->
                <td class="py-4 px-5 text-right whitespace-nowrap">
                    <div class="inline-flex items-center gap-2 justify-end">
                        <button type="button" onclick="abrirModalDetalleTutor(${t.id_tutor})"
                                class="btn btn-primary text-base font-bold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-1.5 shadow-xs"
                                title="Ver ficha integral y detalle de hijos">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span>Ver Ficha</span>
                        </button>

                        <button type="button" onclick="abrirModalEditarTutor(${jsonTut})"
                                class="btn btn-secondary text-base font-semibold py-2.5 px-3.5 min-h-[44px] inline-flex items-center gap-1"
                                title="Editar datos del tutor">
                            <svg class="w-5 h-5 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            <span>Editar</span>
                        </button>

                        <button type="button" onclick="desactivarTutor(${t.id_tutor}, '${escapeHtml(t.nombre)} ${escapeHtml(t.apellido)}')"
                                class="btn btn-danger text-base font-semibold py-2.5 px-3 min-h-[44px] inline-flex items-center justify-center"
                                title="Desactivar tutor">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

// 3. Búsqueda local predictiva
document.addEventListener('DOMContentLoaded', () => {
    cargarTutores();

    const inputBusqueda = document.getElementById('busqueda-tutores-filtro');
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', (e) => {
            const q = (e.target.value || '').trim().toLowerCase();
            if (!q) {
                renderizarTablaTutores(tutoresCargados);
                return;
            }

            const filtrados = tutoresCargados.filter(t => {
                const nom = (t.nombre || '').toLowerCase();
                const ape = (t.apellido || '').toLowerCase();
                const ci = (t.cedula_identidad || '').toLowerCase();
                const cel = (t.celular || '').toLowerCase();
                const hij = (t.hijos_resumen || '').toLowerCase();
                return nom.includes(q) || ape.includes(q) || ci.includes(q) || cel.includes(q) || hij.includes(q);
            });

            renderizarTablaTutores(filtrados);
        });
    }

    // Configurar Formulario Crear Tutor
    const formCrear = document.getElementById('form-crear-tutor');
    if (formCrear) {
        formCrear.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-crear-tut');
            if (btn) btn.disabled = true;

            try {
                const formData = new FormData(formCrear);
                const res = await fetch(`${getBaseUrl()}/src/api/Tutor/crear_tutor.php`, {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (!res.ok || json.status !== 'success') {
                    throw new Error(json.message || "Error al registrar tutor.");
                }

                cerrarModalCrearTutor();
                formCrear.reset();
                cargarTutores();
            } catch (err) {
                alert("Error al registrar tutor: " + err.message);
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    }

    // Configurar Formulario Editar Tutor
    const formEditar = document.getElementById('form-editar-tutor');
    if (formEditar) {
        formEditar.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-editar-tut');
            if (btn) btn.disabled = true;

            try {
                const formData = new FormData(formEditar);
                const res = await fetch(`${getBaseUrl()}/src/api/Tutor/actualizar_tutor.php`, {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (!res.ok || json.status !== 'success') {
                    throw new Error(json.message || "Error al actualizar tutor.");
                }

                cerrarModalEditarTutor();
                cargarTutores();
            } catch (err) {
                alert("Error al actualizar tutor: " + err.message);
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    }
});

// ==========================================
// CONTROLADORES DE SUBMODALES
// ==========================================

// Submodal: Ficha Integral del Tutor
async function abrirModalDetalleTutor(idTutor) {
    const tut = tutoresCargados.find(t => parseInt(t.id_tutor) === parseInt(idTutor));
    if (!tut) return;

    tutorSeleccionadoActivo = tut;
    const modal = document.getElementById('modal-detalle-tutor');
    if (!modal) return;

    // Encabezado
    document.getElementById('mdt-nombre-tutor').textContent = `${tut.nombre} ${tut.apellido}`;
    document.getElementById('mdt-cedula-tutor').textContent = tut.cedula_identidad || 'Sin CI';
    document.getElementById('mdt-avatar').textContent = obtenerIniciales(tut.nombre, tut.apellido);

    // Contacto: Celular
    const celularTexto = document.getElementById('mdt-celular-texto');
    const celularAcciones = document.getElementById('mdt-celular-acciones');
    const linkWhatsApp = document.getElementById('mdt-link-whatsapp');
    const linkTel = document.getElementById('mdt-link-tel');

    if (tut.celular && tut.celular.trim()) {
        celularTexto.textContent = tut.celular;
        const limpio = tut.celular.replace(/\D/g, '');
        if (linkWhatsApp) linkWhatsApp.href = `https://wa.me/595${limpio.startsWith('0') ? limpio.substring(1) : limpio}`;
        if (linkTel) linkTel.href = `tel:${tut.celular}`;
        celularAcciones.classList.remove('hidden');
    } else {
        celularTexto.textContent = 'Sin teléfono registrado';
        celularAcciones.classList.add('hidden');
    }

    // Contacto: Email
    const emailLink = document.getElementById('mdt-email-link');
    if (emailLink) {
        if (tut.email && tut.email.trim()) {
            emailLink.textContent = tut.email;
            emailLink.href = `mailto:${tut.email}`;
            emailLink.classList.remove('text-slate-400', 'pointer-events-none');
        } else {
            emailLink.textContent = 'Sin correo registrado';
            emailLink.removeAttribute('href');
            emailLink.classList.add('text-slate-400', 'pointer-events-none');
        }
    }

    // Dirección
    const dirTexto = document.getElementById('mdt-direccion-texto');
    if (dirTexto) {
        dirTexto.textContent = tut.direccion && tut.direccion.trim() ? tut.direccion : 'Sin dirección registrada';
    }

    // Botón Vincular Más Hijos
    const btnVincular = document.getElementById('mdt-btn-vincular-mas');
    if (btnVincular) {
        btnVincular.onclick = () => {
            cerrarModalDetalleTutor();
            abrirVincularParaTutor(tut.id_tutor, tut.nombre, tut.apellido, tut.cedula_identidad);
        };
    }

    // Botón Editar
    const btnEditar = document.getElementById('mdt-btn-editar-desde-ficha');
    if (btnEditar) {
        btnEditar.onclick = () => {
            cerrarModalDetalleTutor();
            abrirModalEditarTutor(tut);
        };
    }

    modal.classList.remove('hidden');

    // Cargar hijos detallados vía API
    await cargarHijosFichaTutor(tut.id_tutor);
}

function cerrarModalDetalleTutor() {
    const modal = document.getElementById('modal-detalle-tutor');
    if (modal) modal.classList.add('hidden');
}

async function cargarHijosFichaTutor(idTutor) {
    const tbody = document.getElementById('mdt-tabla-hijos-body');
    if (!tbody) return;

    tbody.innerHTML = '<tr><td colspan="5" class="p-6 text-center text-slate-500 font-medium">Cargando hijos vinculados...</td></tr>';

    try {
        const res = await fetch(`${getBaseUrl()}/src/api/Tutor/hijos_del_tutor.php?id_tutor=${encodeURIComponent(idTutor)}`);
        const json = await res.json();
        const hijos = json.data || [];

        if (hijos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="p-8 text-center text-slate-500">
                        <p class="font-bold text-base text-slate-700 dark:text-slate-300">Este tutor no tiene estudiantes vinculados actualmente.</p>
                        <p class="text-sm mt-1 text-slate-500">Puede asociar a sus hijos o hermanos usando el botón "+ Vincular Otro Alumno".</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = hijos.map(h => `
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors">
                <td class="py-3.5 px-4 font-bold text-base text-slate-900 dark:text-white">
                    ${escapeHtml(h.apellido)}, ${escapeHtml(h.nombre)}
                </td>
                <td class="py-3.5 px-4 font-mono font-bold text-base text-indigo-700 dark:text-indigo-300">
                    ${escapeHtml(h.cedula_identidad)}
                </td>
                <td class="py-3.5 px-4 text-base font-semibold text-slate-800 dark:text-slate-200">
                    ${escapeHtml(h.grado_nombre || 'Sin curso')}
                </td>
                <td class="py-3.5 px-4 text-base text-slate-700 dark:text-slate-300">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-800 dark:text-indigo-300 font-bold text-sm">
                        ${escapeHtml(h.parentesco || 'Tutor')}
                    </span>
                </td>
                <td class="py-3.5 px-4 text-right">
                    <a href="${getBaseUrl()}/mvc/views/tutores/expediente_estudiante.php?id_estudiante=${h.id_estudiante}"
                       class="btn btn-secondary text-sm font-bold py-1.5 px-3 min-h-[38px] inline-flex items-center gap-1.5"
                       title="Ver expediente académico y asistencias">
                        <span>Expediente</span>
                    </a>
                </td>
            </tr>
        `).join('');

    } catch (e) {
        console.error("Error cargando hijos de tutor:", e);
        tbody.innerHTML = '<tr><td colspan="5" class="p-6 text-center text-rose-500 font-bold">Error al cargar estudiantes vinculados.</td></tr>';
    }
}

function abrirVincularParaTutor(idTutor, nombre, apellido, ci) {
    if (window.GestorFamilias && typeof GestorFamilias.abrir === 'function') {
        GestorFamilias.abrir({
            tutorInicial: {
                id_tutor: parseInt(idTutor),
                nombre: nombre,
                apellido: apellido,
                cedula_identidad: ci
            },
            onSuccess: () => cargarTutores()
        });
    }
}

// Modales Crear y Editar Tutor
function abrirModalCrearTutor() {
    const modal = document.getElementById('modal-crear-tutor');
    const form = document.getElementById('form-crear-tutor');
    if (form) form.reset();
    if (modal) modal.classList.remove('hidden');
}

function cerrarModalCrearTutor() {
    const modal = document.getElementById('modal-crear-tutor');
    if (modal) modal.classList.add('hidden');
}

function abrirModalEditarTutor(tut) {
    const modal = document.getElementById('modal-editar-tutor');
    if (!modal || !tut) return;

    document.getElementById('edit-tut-id').value = tut.id_tutor;
    document.getElementById('edit-tut-nombre').value = tut.nombre || '';
    document.getElementById('edit-tut-apellido').value = tut.apellido || '';
    document.getElementById('edit-tut-cedula').value = tut.cedula_identidad || '';
    document.getElementById('edit-tut-celular').value = tut.celular || '';
    document.getElementById('edit-tut-email').value = tut.email || '';
    document.getElementById('edit-tut-direccion').value = tut.direccion || '';

    modal.classList.remove('hidden');
}

function cerrarModalEditarTutor() {
    const modal = document.getElementById('modal-editar-tutor');
    if (modal) modal.classList.add('hidden');
}

async function desactivarTutor(idTutor, nombreCompleto) {
    if (!confirm(`¿Está seguro de que desea desactivar al tutor "${nombreCompleto}"?`)) return;

    try {
        const formData = new FormData();
        formData.append('id_tutor', idTutor);

        const res = await fetch(`${getBaseUrl()}/src/api/Tutor/desactivar_tutor.php`, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.status === 'success') {
            cargarTutores();
        } else {
            alert(json.message || "Error al desactivar tutor.");
        }
    } catch (e) {
        console.error("Error desactivando tutor:", e);
        alert("Error de comunicación al desactivar tutor.");
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
