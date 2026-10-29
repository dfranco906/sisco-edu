<?php
require_once __DIR__ . '/../../../src/config/app.php';

$titulo = "Aulas y Espacios";
$icono = "🏢";
$subtitulo = "Gestión de espacios físicos, pabellones, pisos y aulas equipadas con sensores biométricos.";
$tituloBotonCrear = "Aula";
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
<button type="button" id="btn-configurar-campus" onclick="window.abrirModalCampus()" class="btn btn-secondary min-h-[46px] px-5 py-2.5 text-base font-bold rounded-2xl flex items-center gap-2 cursor-pointer active:scale-95 shadow-sm">
    <span>🏢</span>
    <span>Configurar Bloques y Pisos</span>
</button>
';

ob_start();
?>
<!-- MODAL CONFIGURACIÓN DE ESTRUCTURA EDILICIA (BLOQUES Y PISOS) -->
<div id="modal-campus" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="modal-campus-titulo">
    <div class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border-2 border-slate-200/90 dark:border-slate-800 max-w-2xl w-full max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <!-- Header -->
        <div class="p-5 sm:p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/90 dark:bg-slate-800/90 flex items-start justify-between gap-4 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 flex items-center justify-center text-2xl font-bold shrink-0 border border-indigo-200 dark:border-indigo-800 shadow-2xs">
                    🏢
                </div>
                <div class="min-w-0">
                    <h3 id="modal-campus-titulo" class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white truncate">
                        Estructura Institucional (Bloques y Pisos)
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                        Define edificios y plantas para la nomenclatura y códigos de aula.
                    </p>
                </div>
            </div>
            <button type="button" onclick="window.cerrarModalCampus()" class="min-w-[44px] min-h-[44px] w-11 h-11 flex items-center justify-center rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 text-2xl font-bold cursor-pointer hover:bg-slate-200/70 dark:hover:bg-slate-800 transition-colors shrink-0" aria-label="Cerrar modal">&times;</button>
        </div>

        <div id="mensaje-campus" class="hidden mx-6 mt-4 p-3 rounded-xl text-sm font-semibold"></div>

        <!-- Body con scroll -->
        <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-6 text-base">
            <!-- SECCIÓN 1: BLOQUES O EDIFICIOS -->
            <div class="p-5 bg-slate-50 dark:bg-slate-800/50 border-2 border-slate-200 dark:border-slate-700 rounded-2xl">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-base font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                        <span>🏛️</span> Bloques / Campus / Edificios
                    </h4>
                    <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800" id="contador-bloques">0 bloques</span>
                </div>

                <div id="lista-bloques" class="flex flex-wrap gap-2 mb-4 min-h-[44px] p-3 bg-white dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800">
                    <span class="text-sm text-slate-400 italic">Cargando bloques...</span>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <input type="text" id="nuevo_bloque_nombre" placeholder="Nombre (ej. Bloque C o Ciencias)" class="w-full min-h-[46px] px-4 py-2 text-base text-slate-900 dark:text-white bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <input type="text" id="nuevo_bloque_id" placeholder="Prefijo (ej. C)" maxlength="4" class="w-full sm:w-28 min-h-[46px] px-3 py-2 text-base uppercase font-mono font-bold text-center text-slate-900 dark:text-white bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <button type="button" onclick="window.agregarBloque()" class="btn btn-primary min-h-[46px] px-4 py-2 text-sm font-bold rounded-xl whitespace-nowrap flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>+</span> <span>Agregar Bloque</span>
                    </button>
                </div>
            </div>

            <!-- SECCIÓN 2: PISOS O NIVELES -->
            <div class="p-5 bg-slate-50 dark:bg-slate-800/50 border-2 border-slate-200 dark:border-slate-700 rounded-2xl">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-base font-extrabold text-slate-800 dark:text-white flex items-center gap-2">
                        <span>📶</span> Pisos / Plantas / Niveles
                    </h4>
                    <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800" id="contador-pisos">0 pisos</span>
                </div>

                <!-- GENERADOR RÁPIDO -->
                <div class="p-3.5 bg-indigo-50/80 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/60 rounded-xl mb-4">
                    <div class="text-sm font-extrabold text-indigo-900 dark:text-indigo-300 mb-2 flex items-center gap-1.5">
                        <span>⚡</span> Generador Rápido de Pisos:
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-slate-700 dark:text-slate-300 font-medium">
                        <label class="flex items-center gap-2">
                            <span>Pisos superiores:</span>
                            <input type="number" id="gen_cantidad_pisos" value="3" min="1" max="20" class="min-h-[40px] w-20 px-2 py-1 text-center font-bold rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white">
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="gen_incluir_pb" checked class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Incluir Planta Baja (PB)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="gen_incluir_ss" class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Incluir Subsuelo (SS)</span>
                        </label>
                        <button type="button" onclick="window.generarPisosRapido()" class="btn btn-secondary min-h-[40px] px-3.5 py-1.5 text-xs font-bold rounded-xl cursor-pointer">
                            Generar Estructura
                        </button>
                    </div>
                </div>

                <div id="lista-pisos" class="flex flex-wrap gap-2 mb-4 min-h-[44px] p-3 bg-white dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800">
                    <span class="text-sm text-slate-400 italic">Cargando pisos...</span>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                    <input type="text" id="nuevo_piso_nombre" placeholder="Nombre (ej. Terraza o 4° Piso)" class="w-full min-h-[46px] px-4 py-2 text-base text-slate-900 dark:text-white bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <input type="text" id="nuevo_piso_id" placeholder="Prefijo (ej. P4)" maxlength="4" class="w-full sm:w-28 min-h-[46px] px-3 py-2 text-base uppercase font-mono font-bold text-center text-slate-900 dark:text-white bg-white dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <button type="button" onclick="window.agregarPiso()" class="btn btn-primary min-h-[46px] px-4 py-2 text-sm font-bold rounded-xl whitespace-nowrap flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>+</span> <span>Agregar Piso</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer fijo -->
        <div class="p-4 sm:p-6 bg-slate-50/90 dark:bg-slate-800/90 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3 shrink-0">
            <button type="button" onclick="window.cerrarModalCampus()" class="btn btn-secondary min-h-[46px] px-5 py-2.5 rounded-xl text-base font-semibold cursor-pointer">
                Cancelar
            </button>
            <button type="button" id="btn-guardar-campus" onclick="window.guardarEstructuraCampus()" class="btn btn-success min-h-[46px] px-6 py-2.5 rounded-xl text-base font-bold shadow-sm flex items-center gap-2 cursor-pointer active:scale-95">
                <span>💾</span> Guardar Estructura
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    let campusBloquesActuales = [];
    let campusPisosActuales = [];

    const API_ESTRUCTURA_OBTENER = "<?= base_url('src/api/Aula/obtener_estructura_campus.php') ?>";
    const API_ESTRUCTURA_GUARDAR = "<?= base_url('src/api/Aula/guardar_estructura_campus.php') ?>";
    const API_GENERAR_CODIGO = "<?= base_url('src/api/Aula/generar_codigo_aula.php') ?>";

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

        const countB = document.getElementById("contador-bloques");
        const countP = document.getElementById("contador-pisos");
        if (countB) countB.textContent = `${campusBloquesActuales.length} bloques`;
        if (countP) countP.textContent = `${campusPisosActuales.length} pisos`;

        if (!campusBloquesActuales.length) {
            contBloques.innerHTML = '<span class="text-sm font-semibold text-rose-500">Sin bloques definidos. Agrega al menos uno.</span>';
        } else {
            contBloques.innerHTML = campusBloquesActuales.map((b, i) => `
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shadow-2xs">
                    <span>${b.nombre}</span>
                    <span class="font-mono text-xs text-indigo-500 uppercase">(${b.id || b.nombre})</span>
                    <button type="button" onclick="window.eliminarBloque(${i})" class="text-indigo-400 hover:text-rose-600 font-bold ml-1 text-base leading-none cursor-pointer" title="Eliminar">&times;</button>
                </span>
            `).join("");
        }

        if (!campusPisosActuales.length) {
            contPisos.innerHTML = '<span class="text-sm font-semibold text-rose-500">Sin pisos definidos. Agrega al menos uno.</span>';
        } else {
            contPisos.innerHTML = campusPisosActuales.map((p, i) => `
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shadow-2xs">
                    <span>${p.nombre}</span>
                    <span class="font-mono text-xs text-emerald-600 dark:text-emerald-400 uppercase">(${p.id || p.nombre})</span>
                    <button type="button" onclick="window.eliminarPiso(${i})" class="text-emerald-400 hover:text-rose-600 font-bold ml-1 text-base leading-none cursor-pointer" title="Eliminar">&times;</button>
                </span>
            `).join("");
        }
    }

    window.agregarBloque = function() {
        const inputNom = document.getElementById("nuevo_bloque_nombre");
        const inputId = document.getElementById("nuevo_bloque_id");
        if (!inputNom) return;
        const nombre = inputNom.value.trim();
        if (!nombre) return;
        const id = (inputId ? inputId.value.trim().toUpperCase() : "") || nombre.replace(/[^A-Za-z0-9]/g, "").substring(0, 3).toUpperCase();

        campusBloquesActuales.push({ id, nombre });
        inputNom.value = "";
        if (inputId) inputId.value = "";
        renderizarListasCampus();
    };

    window.eliminarBloque = function(idx) {
        if (campusBloquesActuales.length <= 1) {
            alert("Debe existir al menos un bloque.");
            return;
        }
        campusBloquesActuales.splice(idx, 1);
        renderizarListasCampus();
    };

    window.agregarPiso = function() {
        const inputNom = document.getElementById("nuevo_piso_nombre");
        const inputId = document.getElementById("nuevo_piso_id");
        if (!inputNom) return;
        const nombre = inputNom.value.trim();
        if (!nombre) return;
        const match = nombre.match(/\d+/);
        const id = (inputId ? inputId.value.trim().toUpperCase() : "") || (match ? "P" + match[0] : "P");

        campusPisosActuales.push({ id, nombre, numero: campusPisosActuales.length });
        inputNom.value = "";
        if (inputId) inputId.value = "";
        renderizarListasCampus();
    };

    window.eliminarPiso = function(idx) {
        if (campusPisosActuales.length <= 1) {
            alert("Debe existir al menos un piso.");
            return;
        }
        campusPisosActuales.splice(idx, 1);
        renderizarListasCampus();
    };

    window.generarPisosRapido = function() {
        const cantElem = document.getElementById("gen_cantidad_pisos");
        const cant = cantElem ? (parseInt(cantElem.value, 10) || 1) : 1;
        const incPb = document.getElementById("gen_incluir_pb") ? document.getElementById("gen_incluir_pb").checked : true;
        const incSs = document.getElementById("gen_incluir_ss") ? document.getElementById("gen_incluir_ss").checked : false;

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
    };

    window.abrirModalCampus = function() {
        const modal = document.getElementById("modal-campus");
        if (modal) {
            modal.classList.remove("hidden");
            document.body.classList.add("modal-open");
            renderizarListasCampus();
        }
    };

    window.cerrarModalCampus = function() {
        const modal = document.getElementById("modal-campus");
        if (modal) {
            modal.classList.add("hidden");
            document.body.classList.remove("modal-open");
        }
        const msg = document.getElementById("mensaje-campus");
        if (msg) msg.className = "hidden";
    };

    window.guardarEstructuraCampus = async function() {
        const btn = document.getElementById("btn-guardar-campus");
        const msg = document.getElementById("mensaje-campus");
        if (btn) btn.disabled = true;

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

            if (msg) {
                msg.classList.remove("hidden");
                if (res.status === "success") {
                    msg.className = "mb-4 p-3 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 block";
                    msg.innerHTML = `✅ ${res.message || "Estructura actualizada correctamente."}`;
                    actualizarSelectsFormulario();
                    setTimeout(() => {
                        window.cerrarModalCampus();
                    }, 1200);
                } else {
                    msg.className = "mb-4 p-3 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 block";
                    msg.innerHTML = `❌ ${res.message || "Error al guardar estructura."}`;
                }
            }
        } catch (e) {
            if (msg) {
                msg.classList.remove("hidden");
                msg.className = "mb-4 p-3 rounded-xl text-xs font-semibold bg-rose-50 text-rose-800 border border-rose-200 block";
                msg.innerHTML = `❌ Error de red: ${e.message}`;
            }
        } finally {
            if (btn) btn.disabled = false;
        }
    };

    function actualizarSelectsFormulario() {
        const selectsBloque = document.querySelectorAll("select[name='bloque']");
        const selectsPiso = document.querySelectorAll("select[name='piso']");

        selectsBloque.forEach(s => {
            const valActual = s.value;
            s.innerHTML = '<option value="">Seleccione bloque</option>' + campusBloquesActuales.map(b => `
                <option value="${b.nombre}" ${b.nombre === valActual ? "selected" : ""}>${b.nombre}</option>
            `).join("");
            if (!valActual && campusBloquesActuales.length) {
                s.value = campusBloquesActuales[0].nombre;
            }
        });

        selectsPiso.forEach(s => {
            const valActual = s.value;
            s.innerHTML = '<option value="">Seleccione piso</option>' + campusPisosActuales.map(p => `
                <option value="${p.nombre}" ${p.nombre === valActual ? "selected" : ""}>${p.nombre}</option>
            `).join("");
            if (!valActual && campusPisosActuales.length) {
                s.value = campusPisosActuales[0].nombre;
            }
        });
    }

    async function autogenerarCodigoForm(formElement) {
        if (!formElement) return;
        const selectBloque = formElement.querySelector("select[name='bloque']");
        const selectPiso = formElement.querySelector("select[name='piso']");
        const inputCodigo = formElement.querySelector("input[name='codigo']");
        const inputId = formElement.querySelector("input[name='id_aula']");

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
                inputCodigo.classList.add("bg-indigo-50");
                setTimeout(() => inputCodigo.classList.remove("bg-indigo-50"), 400);
            }
        } catch (e) {
            console.error("Error autogenerando código de aula:", e);
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        cargarEstructuraCampus();

        // Conectar botón de toolbar si no tiene onclick
        const btnToolbar = document.getElementById("btn-configurar-campus");
        if (btnToolbar) {
            btnToolbar.onclick = window.abrirModalCampus;
        }

        // Conectar eventos en el formulario de creación
        const formCrear = document.getElementById("form-crear");
        if (formCrear) {
            const selBloque = formCrear.querySelector("select[name='bloque']");
            const selPiso = formCrear.querySelector("select[name='piso']");
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

        // Observar cuando se cargan campos en el modal de edición
        const contenedorEditar = document.getElementById("campos-editar");
        if (contenedorEditar) {
            const observer = new MutationObserver(() => {
                actualizarSelectsFormulario();
                const formEditar = document.getElementById("form-editar");
                if (formEditar) {
                    const selB = formEditar.querySelector("select[name='bloque']");
                    const selP = formEditar.querySelector("select[name='piso']");
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
})();
</script>
<?php
$modalExtraHtml = ob_get_clean();

require_once __DIR__ . '/../partials/table_page.php';
