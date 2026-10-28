<?php
declare(strict_types=1);

$titulo = "Procedimientos e Instrumentos de Evaluación (Normativa MEC Paraguay)";

require_once __DIR__ . '/../../../src/config/app.php';
require_once __DIR__ . '/../../../src/middleware/auth.php';

verificarRol(['SuperAdmin', 'Administrador', 'Coordinador', 'Profesor']);

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';

$rolUsuario = $_SESSION['rol'] ?? '';
$idProfesorSesion = $_SESSION['id_profesor'] ?? null;
?>

<main class="app-main flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full space-y-6 text-base">

    <!-- Encabezado Principal y Acción de Creación -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-3 border-b border-slate-200 dark:border-slate-800">
        <div>
            <div class="flex items-center gap-3">
                <span class="p-2.5 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-2xl font-bold shrink-0 border border-indigo-200 dark:border-indigo-800">
                    📋
                </span>
                <div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Procedimientos e Instrumentos de Evaluación
                    </h2>
                    <p class="text-base text-slate-600 dark:text-slate-400 mt-1">
                        Banco de plantillas curriculares según la normativa del Ministerio de Educación y Ciencias (MEC) del Paraguay.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <button type="button" onclick="abrirModalCrearPlantilla()" class="btn btn-primary text-base font-bold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2 shadow-sm" title="Diseñar un nuevo instrumento evaluativo">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Nuevo Instrumento</span>
            </button>

            <?php if ($rolUsuario === 'Profesor'): ?>
                <a href="<?= base_url('mvc/views/profesores/sesion_clase.php') ?>" class="btn btn-secondary text-base font-semibold py-2.5 px-4 min-h-[44px] inline-flex items-center gap-2">
                    <span>Ir a Mi Sesión de Clase →</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Guía Curricular Oficial MEC (Banner Informativo) -->
    <div class="p-5 rounded-3xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-900/60 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-indigo-200 dark:bg-indigo-900 text-indigo-900 dark:text-indigo-200">
                    Marco Curricular MEC Paraguay
                </span>
                <span class="text-sm font-semibold text-slate-500">•</span>
                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Resolución Ministerial de Evaluación de los Aprendizajes</span>
            </div>
            <p class="text-sm sm:text-base text-slate-700 dark:text-slate-300 leading-relaxed">
                Los <strong>Procedimientos</strong> (Observación, Informe, Prueba) definen la técnica de recolección de evidencias, mientras que los <strong>Instrumentos</strong> (Lista de Cotejo, RSA, Rúbrica, Pruebas) contienen los criterios, indicadores y descriptores con su puntaje respectivo.
            </p>
        </div>
        <div class="shrink-0 flex items-center gap-2 text-sm font-bold text-indigo-700 dark:text-indigo-300 bg-white/90 dark:bg-slate-900/90 px-4 py-2 rounded-2xl border border-indigo-200 dark:border-indigo-800">
            <span>Plantillas Reutilizables Activas:</span>
            <span id="badge-total-plantillas" class="text-lg font-black text-indigo-600 dark:text-indigo-400">0</span>
        </div>
    </div>

    <!-- Filtros de Procedimientos e Instrumentos -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-5 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
        <div>
            <label for="filtro-instrumento-tipo" class="block text-sm sm:text-base font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                Filtrar por Instrumento
            </label>
            <select id="filtro-instrumento-tipo" class="app-input w-full text-base font-semibold py-2.5 px-3 rounded-xl min-h-[46px]" onchange="filtrarPlantillasLocales()">
                <option value="">Todos los instrumentos</option>
            </select>
        </div>

        <div>
            <label for="filtro-origen-plantilla" class="block text-sm sm:text-base font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                Tipo de Plantilla
            </label>
            <select id="filtro-origen-plantilla" class="app-input w-full text-base font-semibold py-2.5 px-3 rounded-xl min-h-[46px]" onchange="filtrarPlantillasLocales()">
                <option value="">Todas (MEC y Personalizadas)</option>
                <option value="institucional">Solo Estándar MEC</option>
                <option value="personal">Mis Plantillas Personalizadas</option>
            </select>
        </div>

        <div class="flex items-end">
            <input type="text" id="busqueda-plantillas" placeholder="Buscar por título, contenido o criterio..." 
                   class="app-input w-full py-2.5 px-3 text-base rounded-xl min-h-[46px]" oninput="filtrarPlantillasLocales()">
        </div>
    </div>

    <!-- Grilla de Tarjetas de Instrumentos Evaluativos -->
    <div id="contenedor-tarjetas-plantillas" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="p-8 text-center text-base text-slate-500 font-semibold col-span-full">
            Cargando catálogo de instrumentos evaluativos...
        </div>
    </div>

    <!-- Modal: Crear / Personalizar Nuevo Instrumento -->
    <div id="modal-crear-plantilla" class="app-modal hidden" role="dialog" aria-modal="true" aria-labelledby="mcp-titulo">
        <div class="app-modal-dialog max-w-3xl bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-6 max-h-[92vh] overflow-y-auto">
            
            <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">📐</span>
                    <div>
                        <h3 id="mcp-titulo" class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">
                            Nuevo Instrumento de Evaluación
                        </h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            Configure el procedimiento, instrumento y criterios acordes a los indicadores del MEC.
                        </p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalCrearPlantilla()" class="app-modal-close text-2xl font-bold p-2 text-slate-400 hover:text-slate-700 dark:hover:text-white">
                    ×
                </button>
            </div>

            <form id="form-crear-plantilla" class="space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="form-id-procedimiento" class="block text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            1. Procedimiento Evaluativo *
                        </label>
                        <select id="form-id-procedimiento" name="id_procedimiento" required class="app-input w-full text-base font-semibold py-2.5 px-3 rounded-xl min-h-[46px]">
                            <!-- Opciones dinámicas -->
                        </select>
                    </div>

                    <div>
                        <label for="form-id-instrumento" class="block text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            2. Instrumento Evaluativo *
                        </label>
                        <select id="form-id-instrumento" name="id_instrumento" required class="app-input w-full text-base font-semibold py-2.5 px-3 rounded-xl min-h-[46px]">
                            <!-- Opciones dinámicas -->
                        </select>
                    </div>
                </div>

                <div>
                    <label for="form-titulo" class="block text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Título Descriptivo del Instrumento *
                    </label>
                    <input type="text" id="form-titulo" name="titulo" required placeholder="Ej: Rúbrica de Resolución de Problemas en Taller de Informática" class="app-input w-full text-base font-semibold py-2.5 px-3.5 rounded-xl min-h-[46px]">
                </div>

                <div>
                    <label for="form-descripcion" class="block text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Orientación Pedagógica / Descripción
                    </label>
                    <textarea id="form-descripcion" name="descripcion" rows="2" placeholder="Instrucciones para la aplicación del instrumento..." class="app-input w-full text-base py-2 px-3 rounded-xl"></textarea>
                </div>

                <!-- Criterios e Indicadores Dinámicos -->
                <div class="space-y-3 pt-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Criterios / Indicadores de Desempeño
                        </label>
                        <button type="button" onclick="agregarFilaCriterio()" class="text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1">
                            <span>+ Añadir Criterio</span>
                        </button>
                    </div>

                    <div id="contenedor-filas-criterios" class="space-y-2.5">
                        <!-- Filas de criterios dinámicas -->
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-sm font-bold text-slate-800 dark:text-slate-200">
                        <span>Puntaje Total Máximo Calculado:</span>
                        <span id="label-puntaje-total-calc" class="text-lg font-black text-indigo-600 dark:text-indigo-400">0.00 pts</span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                    <button type="button" onclick="cerrarModalCrearPlantilla()" class="btn btn-secondary text-base font-semibold py-2.5 px-5 min-h-[44px] w-full sm:w-auto">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-submit-plantilla" class="btn btn-primary text-base font-bold py-2.5 px-6 min-h-[44px] w-full sm:w-auto">
                        Guardar Plantilla
                    </button>
                </div>
            </form>

        </div>
    </div>

</main>

<script>
let catalogoPlantillas = [];
let catalogoProcedimientos = [];
let catalogoInstrumentos = [];

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

async function cargarDatosPlantillas() {
    const contenedor = document.getElementById('contenedor-tarjetas-plantillas');
    if (contenedor) {
        contenedor.innerHTML = '<div class="p-8 text-center text-base text-slate-500 font-semibold col-span-full">Cargando catálogo de instrumentos evaluativos...</div>';
    }

    try {
        const res = await fetch(`${getBaseUrl()}/src/api/Evaluaciones/leer_plantillas.php`);
        const json = await res.json();

        if (!res.ok || json.status !== 'success') {
            throw new Error(json.message || "Error al obtener plantillas");
        }

        catalogoPlantillas = json.data.plantillas || [];
        catalogoProcedimientos = json.data.procedimientos || [];
        catalogoInstrumentos = json.data.instrumentos || [];

        poblarSelectoresFiltroYModal();
        filtrarPlantillasLocales();

        document.getElementById('badge-total-plantillas').textContent = String(catalogoPlantillas.length);
    } catch (e) {
        console.error("Error al cargar plantillas:", e);
        if (contenedor) {
            contenedor.innerHTML = `<div class="p-8 text-center text-rose-600 font-bold col-span-full">Error al cargar plantillas: ${escapeHtml(e.message)}</div>`;
        }
    }
}

function poblarSelectoresFiltroYModal() {
    // Filtro por Instrumento
    const selFiltroInst = document.getElementById('filtro-instrumento-tipo');
    if (selFiltroInst) {
        selFiltroInst.innerHTML = '<option value="">Todos los instrumentos</option>' +
            catalogoInstrumentos.map(i => `<option value="${i.id_instrumento}">${escapeHtml(i.nombre)}</option>`).join('');
    }

    // Modal Crear: Procedimientos
    const selProc = document.getElementById('form-id-procedimiento');
    if (selProc) {
        selProc.innerHTML = '<option value="">Seleccione procedimiento...</option>' +
            catalogoProcedimientos.map(p => `<option value="${p.id_procedimiento}">${escapeHtml(p.nombre)}</option>`).join('');
    }

    // Modal Crear: Instrumentos
    const selInst = document.getElementById('form-id-instrumento');
    if (selInst) {
        selInst.innerHTML = '<option value="">Seleccione instrumento...</option>' +
            catalogoInstrumentos.map(i => `<option value="${i.id_instrumento}">${escapeHtml(i.nombre)}</option>`).join('');
    }
}

function filtrarPlantillasLocales() {
    const fInst = document.getElementById('filtro-instrumento-tipo')?.value || '';
    const fOrigen = document.getElementById('filtro-origen-plantilla')?.value || '';
    const q = (document.getElementById('busqueda-plantillas')?.value || '').trim().toLowerCase();

    let filtradas = catalogoPlantillas;

    if (fInst) {
        filtradas = filtradas.filter(p => String(p.id_instrumento) === String(fInst));
    }

    if (fOrigen === 'institucional') {
        filtradas = filtradas.filter(p => parseInt(p.es_institucional || 0) === 1);
    } else if (fOrigen === 'personal') {
        filtradas = filtradas.filter(p => parseInt(p.es_institucional || 0) === 0);
    }

    if (q) {
        filtradas = filtradas.filter(p => {
            const tit = (p.titulo || '').toLowerCase();
            const desc = (p.descripcion || '').toLowerCase();
            const inst = (p.instrumento_nombre || '').toLowerCase();
            const proc = (p.procedimiento_nombre || '').toLowerCase();
            return tit.includes(q) || desc.includes(q) || inst.includes(q) || proc.includes(q);
        });
    }

    renderizarTarjetasPlantillas(filtradas);
}

function renderizarTarjetasPlantillas(lista) {
    const contenedor = document.getElementById('contenedor-tarjetas-plantillas');
    if (!contenedor) return;

    if (lista.length === 0) {
        contenedor.innerHTML = `
            <div class="p-12 text-center rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 col-span-full">
                <span class="text-4xl">🔍</span>
                <h4 class="text-xl font-bold text-slate-800 dark:text-slate-200 mt-3">No se encontraron instrumentos evaluativos</h4>
                <p class="text-sm text-slate-500 mt-1">Pruebe ajustando los filtros superiores o diseñe una nueva plantilla personalizada.</p>
            </div>
        `;
        return;
    }

    contenedor.innerHTML = lista.map(p => {
        const esInst = parseInt(p.es_institucional || 0) === 1;
        const criterios = Array.isArray(p.criterios) ? p.criterios : [];
        const totalCriterios = criterios.length;

        return `
            <div class="p-6 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm hover:border-indigo-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between space-y-4">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wider ${esInst ? 'bg-indigo-100 text-indigo-900 dark:bg-indigo-950/70 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800' : 'bg-purple-100 text-purple-900 dark:bg-purple-950/70 dark:text-purple-300 border border-purple-200 dark:border-purple-800'}">
                            ${esInst ? '🏛️ Estándar MEC' : '👤 Personalizada'}
                        </span>
                        <span class="text-sm font-mono font-bold text-slate-500 dark:text-slate-400">
                            ${p.puntaje_total} pts
                        </span>
                    </div>

                    <h4 class="text-lg font-black text-slate-900 dark:text-white leading-tight">
                        ${escapeHtml(p.titulo)}
                    </h4>

                    <div class="text-xs space-y-1 text-slate-600 dark:text-slate-400">
                        <div><strong>Procedimiento:</strong> ${escapeHtml(p.procedimiento_nombre)}</div>
                        <div><strong>Instrumento:</strong> ${escapeHtml(p.instrumento_nombre)}</div>
                        ${p.descripcion ? `<p class="italic text-slate-500 line-clamp-2 mt-1">"${escapeHtml(p.descripcion)}"</p>` : ''}
                    </div>

                    <!-- Desglose de Criterios (Preview) -->
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 space-y-1.5 text-xs">
                        <span class="font-bold text-slate-700 dark:text-slate-300 block uppercase tracking-wider">
                            ${totalCriterios} Criterio(s) / Indicador(es):
                        </span>
                        <ul class="space-y-1 text-slate-600 dark:text-slate-300 list-disc list-inside">
                            ${criterios.slice(0, 3).map(c => `
                                <li class="truncate">${escapeHtml(c.indicador || c.criterio || 'Criterio')} <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">(${c.puntaje_max || 1}p)</span></li>
                            `).join('')}
                            ${totalCriterios > 3 ? `<li class="text-indigo-600 dark:text-indigo-400 font-bold">+ ${totalCriterios - 3} criterio(s) adicional(es)...</li>` : ''}
                        </ul>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <span class="text-xs text-slate-400 font-medium">
                        ${escapeHtml(p.autor || '')}
                    </span>
                    ${!esInst ? `
                        <button type="button" onclick="eliminarPlantilla(${p.id_plantilla})" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline p-1">
                            Eliminar
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }).join('');
}

// Modal Crear Plantilla
function abrirModalCrearPlantilla() {
    const modal = document.getElementById('modal-crear-plantilla');
    const form = document.getElementById('form-crear-plantilla');
    if (form) form.reset();

    const contenedor = document.getElementById('contenedor-filas-criterios');
    if (contenedor) {
        contenedor.innerHTML = '';
        agregarFilaCriterio("Demuestra comprensión del contenido temático", 1);
        agregarFilaCriterio("Aplica el procedimiento o técnica correctamente", 2);
        agregarFilaCriterio("Presenta resultados con orden y fundamentación", 2);
    }

    if (modal) modal.classList.remove('hidden');
}

function cerrarModalCrearPlantilla() {
    const modal = document.getElementById('modal-crear-plantilla');
    if (modal) modal.classList.add('hidden');
}

function agregarFilaCriterio(nombreDefecto = '', puntosDefecto = 1) {
    const contenedor = document.getElementById('contenedor-filas-criterios');
    if (!contenedor) return;

    const div = document.createElement('div');
    div.className = 'fila-criterio flex items-center gap-2 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700';
    div.innerHTML = `
        <input type="text" class="criterio-nombre app-input flex-1 py-1.5 px-3 text-sm rounded-lg" placeholder="Descripción del criterio / indicador" value="${escapeHtml(nombreDefecto)}" required>
        <div class="flex items-center gap-1 shrink-0">
            <span class="text-xs font-bold text-slate-500">Pts:</span>
            <input type="number" min="0.5" max="100" step="0.5" class="criterio-puntos app-input w-20 py-1.5 px-2 text-sm font-bold text-center rounded-lg" value="${puntosDefecto}" required oninput="calcularPuntajeTotalFormulario()">
        </div>
        <button type="button" onclick="this.closest('.fila-criterio').remove(); calcularPuntajeTotalFormulario();" class="p-1.5 text-slate-400 hover:text-rose-600 text-lg font-bold">
            ×
        </button>
    `;

    contenedor.appendChild(div);
    calcularPuntajeTotalFormulario();
}

function calcularPuntajeTotalFormulario() {
    const inputs = document.querySelectorAll('.criterio-puntos');
    let total = 0;
    inputs.forEach(inp => {
        total += parseFloat(inp.value) || 0;
    });

    const lbl = document.getElementById('label-puntaje-total-calc');
    if (lbl) lbl.textContent = `${total.toFixed(2)} pts`;
}

// Formulario Enviar
document.addEventListener('DOMContentLoaded', () => {
    cargarDatosPlantillas();

    const form = document.getElementById('form-crear-plantilla');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-plantilla');
            if (btn) btn.disabled = true;

            const filas = document.querySelectorAll('.fila-criterio');
            const criterios = [];
            let total = 0;

            filas.forEach(f => {
                const nom = f.querySelector('.criterio-nombre')?.value.trim();
                const pts = parseFloat(f.querySelector('.criterio-puntos')?.value) || 1;
                if (nom) {
                    criterios.push({ indicador: nom, puntaje_max: pts });
                    total += pts;
                }
            });

            if (criterios.length === 0) {
                alert("Debe agregar al menos un criterio o indicador al instrumento.");
                if (btn) btn.disabled = false;
                return;
            }

            try {
                const formData = new FormData(form);
                formData.append('criterios', JSON.stringify(criterios));
                formData.append('puntaje_total', total);

                const res = await fetch(`${getBaseUrl()}/src/api/Evaluaciones/crear_plantilla.php`, {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();

                if (!res.ok || json.status !== 'success') {
                    throw new Error(json.message || "Error al guardar plantilla");
                }

                cerrarModalCrearPlantilla();
                await cargarDatosPlantillas();
            } catch (err) {
                alert("Error: " + err.message);
            } finally {
                if (btn) btn.disabled = false;
            }
        });
    }
});

async function eliminarPlantilla(idPlantilla) {
    if (!confirm("¿Desea eliminar esta plantilla de evaluación personalizada?")) return;

    try {
        const formData = new FormData();
        formData.append('id_plantilla', idPlantilla);

        const res = await fetch(`${getBaseUrl()}/src/api/Evaluaciones/eliminar_plantilla.php`, {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (!res.ok || json.status !== 'success') {
            throw new Error(json.message || "Error al eliminar");
        }

        await cargarDatosPlantillas();
    } catch (e) {
        alert("Error al eliminar plantilla: " + e.message);
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
