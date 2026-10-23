<?php
$titulo = "Aulas";
$api = "src/api/Aula/leer_aulas.php";
$columnas = ["id_aula", "nombre", "codigo", "bloque", "piso", "ubicacion", "activo"];

$filtros = [
    "buscar" => ["nombre", "codigo", "bloque", "piso", "ubicacion"],
    "placeholder" => "Buscar aula por nombre, código o bloque...",
    "pestanas" => [
        "campo" => "bloque",
        "todosLabel" => "Todos los bloques"
    ]
];

$formCrear = [
    "api" => "src/api/Aula/crear_aula.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre del Aula / Espacio (ej. 1° Curso BTI, Laboratorio 1)", "type" => "text"],
        [
            "name" => "bloque",
            "label" => "Bloque / Edificio / Campus",
            "type" => "select",
            "options" => [
                ["value" => "Bloque A", "label" => "Bloque A"],
                ["value" => "Bloque B", "label" => "Bloque B"]
            ]
        ],
        [
            "name" => "piso",
            "label" => "Piso / Nivel",
            "type" => "select",
            "options" => [
                ["value" => "Planta Baja", "label" => "Planta Baja"],
                ["value" => "1° Piso", "label" => "1° Piso"],
                ["value" => "2° Piso", "label" => "2° Piso"],
                ["value" => "3° Piso", "label" => "3° Piso"]
            ]
        ],
        [
            "name" => "codigo",
            "label" => "Código de Sala (Autogenerado estándar ej. A-PB-01)",
            "type" => "text"
        ],
        ["name" => "ubicacion", "label" => "Ubicación detallada (opcional)", "type" => "text", "required" => false]
    ]
];

$apiActualizar = "src/api/Aula/actualizar_aula.php";
$apiDesactivar = "src/api/Aula/desactivar_aula.php";
$urlDesactivados = "mvc/views/aulas/desactivadas.php";
$urlVolver = "mvc/views/administracion/index.php";
$idCampo = "id_aula";
$camposEditar = ["nombre", "bloque", "piso", "codigo", "ubicacion"];

$toolbarExtraHtml = '
<button type="button" onclick="abrirModalCampus()" class="btn btn-secondary flex items-center gap-1.5 text-sm font-semibold">
    <span>🏢</span> Configurar Bloques y Pisos
</button>
';

$modalExtraHtml = '
<!-- MODAL CONFIGURACIÓN DE ESTRUCTURA EDILICIA (BLOQUES Y PISOS) -->
<div id="modal-campus" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 border border-slate-200 relative">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <div>
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <span>🏢</span> Estructura Institucional (Bloques y Pisos)
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Define los edificios y niveles de la institución para la asignación y generación automática de códigos de aula.
                </p>
            </div>
            <button type="button" onclick="cerrarModalCampus()" class="text-gray-400 hover:text-gray-600 font-bold text-xl leading-none">&times;</button>
        </div>

        <div id="mensaje-campus" class="hidden mb-4 p-3 rounded-xl text-xs font-semibold"></div>

        <div class="space-y-6">
            <!-- SECCIÓN 1: BLOQUES O EDIFICIOS -->
            <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                        <span>🏛️</span> Bloques / Campus / Edificios
                    </h4>
                    <span class="text-xs text-slate-500" id="contador-bloques">0 bloques</span>
                </div>

                <div id="lista-bloques" class="flex flex-wrap gap-2 mb-3 min-h-[40px] p-2 bg-white rounded-lg border border-slate-200">
                    <span class="text-xs text-slate-400 italic">Cargando bloques...</span>
                </div>

                <div class="flex items-center gap-2">
                    <input type="text" id="nuevo_bloque_nombre" placeholder="Nombre (ej. Bloque C o Ciencias)" class="app-input flex-1 text-xs">
                    <input type="text" id="nuevo_bloque_id" placeholder="Prefijo (ej. C)" maxlength="4" class="app-input w-24 text-xs uppercase font-mono">
                    <button type="button" onclick="agregarBloque()" class="btn btn-primary text-xs py-2 px-3 whitespace-nowrap">
                        + Agregar Bloque
                    </button>
                </div>
            </div>

            <!-- SECCIÓN 2: PISOS O NIVELES -->
            <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-xl">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                        <span>📶</span> Pisos / Plantas / Niveles
                    </h4>
                    <span class="text-xs text-slate-500" id="contador-pisos">0 pisos</span>
                </div>

                <!-- GENERADOR RÁPIDO -->
                <div class="p-3 bg-indigo-50/70 border border-indigo-200/70 rounded-lg mb-3">
                    <div class="text-xs font-semibold text-indigo-900 mb-1.5 flex items-center gap-1">
                        <span>⚡</span> Generador Rápido de Pisos:
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-700">
                        <label class="flex items-center gap-1">
                            Pisos superiores:
                            <input type="number" id="gen_cantidad_pisos" value="3" min="1" max="20" class="app-input w-16 py-1 px-2 text-center text-xs">
                        </label>
                        <label class="flex items-center gap-1 cursor-pointer">
                            <input type="checkbox" id="gen_incluir_pb" checked class="rounded border-slate-300 text-indigo-600">
                            Incluir Planta Baja (PB)
                        </label>
                        <label class="flex items-center gap-1 cursor-pointer">
                            <input type="checkbox" id="gen_incluir_ss" class="rounded border-slate-300 text-indigo-600">
                            Incluir Subsuelo (SS)
                        </label>
                        <button type="button" onclick="generarPisosRapido()" class="btn btn-secondary text-xs py-1 px-2.5">
                            Generar Estructura
                        </button>
                    </div>
                </div>

                <div id="lista-pisos" class="flex flex-wrap gap-2 mb-3 min-h-[40px] p-2 bg-white rounded-lg border border-slate-200">
                    <span class="text-xs text-slate-400 italic">Cargando pisos...</span>
                </div>

                <div class="flex items-center gap-2">
                    <input type="text" id="nuevo_piso_nombre" placeholder="Nombre (ej. Terraza o 4° Piso)" class="app-input flex-1 text-xs">
                    <input type="text" id="nuevo_piso_id" placeholder="Prefijo (ej. P4)" maxlength="4" class="app-input w-24 text-xs uppercase font-mono">
                    <button type="button" onclick="agregarPiso()" class="btn btn-primary text-xs py-2 px-3 whitespace-nowrap">
                        + Agregar Piso
                    </button>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-4 mt-5 border-t border-gray-100">
            <button type="button" onclick="cerrarModalCampus()" class="btn btn-secondary text-xs py-2 px-4">
                Cancelar
            </button>
            <button type="button" id="btn-guardar-campus" onclick="guardarEstructuraCampus()" class="btn btn-success text-xs py-2 px-4 flex items-center gap-1.5 font-bold">
                <span>💾</span> Guardar Estructura
            </button>
        </div>
    </div>
</div>

<script>
// Estado en memoria de bloques y pisos para el modal
let campusBloquesActuales = [];
let campusPisosActuales = [];

const API_ESTRUCTURA_OBTENER = "<?= base_url("src/api/Aula/obtener_estructura_campus.php") ?>";
const API_ESTRUCTURA_GUARDAR = "<?= base_url("src/api/Aula/guardar_estructura_campus.php") ?>";
const API_GENERAR_CODIGO = "<?= base_url("src/api/Aula/generar_codigo_aula.php") ?>";

async function cargarEstructuraCampus() {
    try {
        const resp = await fetch(API_ESTRUCTURA_OBTENER);
        const res = await resp.json();
        if (res.status === "success" && res.data) {
            campusBloquesActuales = res.data.bloques || [];
            campusPisosActuales = res.data.pisos || [];
            actualizarSelectsFormulario();
            renderizarListasCampus();
        }
    } catch (e) {
        console.error("Error al cargar estructura del campus:", e);
    }
}

function renderizarListasCampus() {
    const contBloques = document.getElementById("lista-bloques");
    const contPisos = document.getElementById("lista-pisos");
    if (!contBloques || !contPisos) return;

    document.getElementById("contador-bloques").textContent = `${campusBloquesActuales.length} bloques`;
    document.getElementById("contador-pisos").textContent = `${campusPisosActuales.length} pisos`;

    if (!campusBloquesActuales.length) {
        contBloques.innerHTML = `<span class="text-xs text-rose-500">Sin bloques definidos. Agrega al menos uno.</span>`;
    } else {
        contBloques.innerHTML = campusBloquesActuales.map((b, i) => `
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                <span>${b.nombre}</span>
                <span class="font-mono text-[10px] text-indigo-500 uppercase">(${b.id || b.nombre})</span>
                <button type="button" onclick="eliminarBloque(${i})" class="text-indigo-400 hover:text-rose-600 font-bold ml-1 text-sm leading-none" title="Eliminar">&times;</button>
            </span>
        `).join("");
    }

    if (!campusPisosActuales.length) {
        contPisos.innerHTML = `<span class="text-xs text-rose-500">Sin pisos definidos. Agrega al menos uno.</span>`;
    } else {
        contPisos.innerHTML = campusPisosActuales.map((p, i) => `
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span>${p.nombre}</span>
                <span class="font-mono text-[10px] text-emerald-600 uppercase">(${p.id || p.nombre})</span>
                <button type="button" onclick="eliminarPiso(${i})" class="text-emerald-400 hover:text-rose-600 font-bold ml-1 text-sm leading-none" title="Eliminar">&times;</button>
            </span>
        `).join("");
    }
}

function agregarBloque() {
    const inputNom = document.getElementById("nuevo_bloque_nombre");
    const inputId = document.getElementById("nuevo_bloque_id");
    const nombre = inputNom.value.trim();
    if (!nombre) return;
    const id = inputId.value.trim().toUpperCase() || nombre.replace(/[^A-Za-z0-9]/g, "").substring(0, 3).toUpperCase();

    campusBloquesActuales.push({ id, nombre });
    inputNom.value = "";
    inputId.value = "";
    renderizarListasCampus();
}

function eliminarBloque(idx) {
    if (campusBloquesActuales.length <= 1) {
        alert("Debe existir al menos un bloque.");
        return;
    }
    campusBloquesActuales.splice(idx, 1);
    renderizarListasCampus();
}

function agregarPiso() {
    const inputNom = document.getElementById("nuevo_piso_nombre");
    const inputId = document.getElementById("nuevo_piso_id");
    const nombre = inputNom.value.trim();
    if (!nombre) return;
    const id = inputId.value.trim().toUpperCase() || (nombre.match(/\\d+/) ? "P" + nombre.match(/\\d+/)[0] : "P");

    campusPisosActuales.push({ id, nombre, numero: campusPisosActuales.length });
    inputNom.value = "";
    inputId.value = "";
    renderizarListasCampus();
}

function eliminarPiso(idx) {
    if (campusPisosActuales.length <= 1) {
        alert("Debe existir al menos un piso.");
        return;
    }
    campusPisosActuales.splice(idx, 1);
    renderizarListasCampus();
}

function generarPisosRapido() {
    const cant = parseInt(document.getElementById("gen_cantidad_pisos").value, 10) || 1;
    const incPb = document.getElementById("gen_incluir_pb").checked;
    const incSs = document.getElementById("gen_incluir_ss").checked;

    const lista = [];
    if (incSs) {
        lista.push({ id: "SS", nombre: "Subsuelo", numero: -1 });
    }
    if (incPb) {
        lista.push({ id: "PB", nombre: "Planta Baja", numero: 0 });
    }
    for (let i = 1; i <= cant; i++) {
        lista.push({ id: `P${i}`, nombre: `${i}° Piso`, numero: i });
    }

    campusPisosActuales = lista;
    renderizarListasCampus();
}

function abrirModalCampus() {
    document.getElementById("modal-campus").classList.remove("hidden");
    document.body.classList.add("modal-open");
    renderizarListasCampus();
}

function cerrarModalCampus() {
    document.getElementById("modal-campus").classList.add("hidden");
    document.body.classList.remove("modal-open");
    document.getElementById("mensaje-campus").className = "hidden";
}

async function guardarEstructuraCampus() {
    const btn = document.getElementById("btn-guardar-campus");
    const msg = document.getElementById("mensaje-campus");
    btn.disabled = true;

    try {
        const resp = await fetch(API_ESTRUCTURA_GUARDAR, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                bloques: campusBloquesActuales,
                pisos: campusPisosActuales
            })
        });
        const res = await resp.json();

        msg.classList.remove("hidden");
        if (res.status === "success") {
            msg.className = "mb-4 p-3 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 block";
            msg.innerHTML = `✅ ${res.message || "Estructura actualizada correctamente."}`;
            actualizarSelectsFormulario();
            setTimeout(() => {
                cerrarModalCampus();
            }, 1200);
        } else {
            msg.className = "mb-4 p-3 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 block";
            msg.innerHTML = `❌ ${res.message || "Error al guardar estructura."}`;
        }
    } catch (e) {
        msg.classList.remove("hidden");
        msg.className = "mb-4 p-3 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 block";
        msg.innerHTML = `❌ Error de red: ${e.message}`;
    } finally {
        btn.disabled = false;
    }
}

function actualizarSelectsFormulario() {
    const selectsBloque = document.querySelectorAll("select[name=\'bloque\']");
    const selectsPiso = document.querySelectorAll("select[name=\'piso\']");

    selectsBloque.forEach(s => {
        const valActual = s.value;
        s.innerHTML = \'<option value="">Seleccione bloque</option>\' + campusBloquesActuales.map(b => `
            <option value="${b.nombre}" ${b.nombre === valActual ? "selected" : ""}>${b.nombre}</option>
        `).join("");
        if (!valActual && campusBloquesActuales.length) {
            s.value = campusBloquesActuales[0].nombre;
        }
    });

    selectsPiso.forEach(s => {
        const valActual = s.value;
        s.innerHTML = \'<option value="">Seleccione piso</option>\' + campusPisosActuales.map(p => `
            <option value="${p.nombre}" ${p.nombre === valActual ? "selected" : ""}>${p.nombre}</option>
        `).join("");
        if (!valActual && campusPisosActuales.length) {
            s.value = campusPisosActuales[0].nombre;
        }
    });
}

async function autogenerarCodigoForm(formElement) {
    if (!formElement) return;
    const selectBloque = formElement.querySelector("select[name=\'bloque\']");
    const selectPiso = formElement.querySelector("select[name=\'piso\']");
    const inputCodigo = formElement.querySelector("input[name=\'codigo\']");
    const inputId = formElement.querySelector("input[name=\'id_aula\']");

    if (!selectBloque || !selectPiso || !inputCodigo) return;

    const b = selectBloque.value || "Bloque A";
    const p = selectPiso.value || "Planta Baja";
    const id = inputId ? inputId.value : "";

    try {
        const url = `${API_GENERAR_CODIGO}?bloque=${encodeURIComponent(b)}&piso=${encodeURIComponent(p)}&id_aula=${id}`;
        const resp = await fetch(url);
        const res = await resp.json();
        if (res.status === "success" && res.data && res.data.codigo) {
            inputCodigo.value = res.data.codigo;
            inputCodigo.classList.add("bg-indigo-50/60");
            setTimeout(() => inputCodigo.classList.remove("bg-indigo-50/60"), 600);
        }
    } catch (e) {
        console.error("Error autogenerando código de aula:", e);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    cargarEstructuraCampus();

    // Conectar eventos en el formulario de creación
    const formCrear = document.getElementById("form-crear");
    if (formCrear) {
        const selBloque = formCrear.querySelector("select[name=\'bloque\']");
        const selPiso = formCrear.querySelector("select[name=\'piso\']");
        if (selBloque) selBloque.addEventListener("change", () => autogenerarCodigoForm(formCrear));
        if (selPiso) selPiso.addEventListener("change", () => autogenerarCodigoForm(formCrear));

        const btnAbrirCrear = document.getElementById("btn-crear");
        if (btnAbrirCrear) {
            btnAbrirCrear.addEventListener("click", () => {
                setTimeout(() => {
                    actualizarSelectsFormulario();
                    autogenerarCodigoForm(formCrear);
                }, 100);
            });
        }
    }

    // Observar cuando se cargan campos en el modal de edición para sincronizar selects de campus
    const contenedorEditar = document.getElementById("campos-editar");
    if (contenedorEditar) {
        const observer = new MutationObserver(() => {
            actualizarSelectsFormulario();
            const formEditar = document.getElementById("form-editar");
            if (formEditar) {
                const selB = formEditar.querySelector("select[name=\'bloque\']");
                const selP = formEditar.querySelector("select[name=\'piso\']");
                if (selB && !selB.dataset.listenerAttached) {
                    selB.dataset.listenerAttached = "true";
                    selB.addEventListener("change", () => autogenerarCodigoForm(formEditar));
                }
                if (selP && !selP.dataset.listenerAttached) {
                    selP.dataset.listenerAttached = "true";
                    selP.addEventListener("change", () => autogenerarCodigoForm(formEditar));
                }
            }
        });
        observer.observe(contenedorEditar, { childList: true, subtree: true });
    }
});
</script>
';

require_once __DIR__ . '/../partials/table_page.php';
