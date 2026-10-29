function escaparHtml(valor) {
    return String(valor ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function etiquetaFiltro(item, filtro) {
    let etiqueta;

    if (Array.isArray(filtro.labelFields)) {
        etiqueta = filtro.labelFields
            .map((campo) => item[campo])
            .filter((valor) => valor !== null && valor !== undefined && String(valor).trim() !== "")
            .join(" ");
    } else {
        etiqueta = item[filtro.labelField || filtro.campo];
    }

    etiqueta = String(etiqueta ?? "").trim();

    if (filtro.formato === "titulo") {
        etiqueta = etiqueta
            .toLocaleLowerCase("es")
            .replace(/(^|\s|_)(\p{L})/gu, (coincidencia) =>
                coincidencia.replace("_", " ").toLocaleUpperCase("es")
            );
    }

    return etiqueta;
}

function opcionesFiltro(datos, filtro) {
    const opciones = new Map();

    datos.forEach((item) => {
        const valorOriginal = item[filtro.campo];
        if (valorOriginal === null || valorOriginal === undefined || String(valorOriginal).trim() === "") return;

        const valor = String(valorOriginal);
        const etiqueta = etiquetaFiltro(item, filtro) || valor;
        if (!opciones.has(valor)) opciones.set(valor, etiqueta);
    });

    return [...opciones.entries()].sort((a, b) => {
        const comparacion = a[1].localeCompare(b[1], "es", { numeric: true, sensitivity: "base" });
        return filtro.orden === "desc" ? -comparacion : comparacion;
    });
}

function contenidoCelda(columna, valor, item = null) {
    const texto = String(valor ?? "");

    if (columna === "total_hijos") {
        const cant = parseInt(valor || 0);
        if (cant > 0) {
            const tooltip = item && item.hijos_resumen ? ` title="${escaparHtml(item.hijos_resumen)}"` : '';
            return `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 font-bold text-xs cursor-help"${tooltip}>👨‍👩‍👦 ${cant} hijo(s)</span>`;
        }
        return `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-semibold text-xs">Sin hijos</span>`;
    }

    if (columna === "estado" && texto !== "") {
        const estado = texto.toUpperCase();
        const clase = estado === "PRESENTE" || estado === "CONFIRMADO" || estado === "RECIBIDO"
            ? "status-success"
            : estado === "TARDANZA" || estado === "PENDIENTE" || estado === "ENVIADO"
                ? "status-warning"
                : estado === "AUSENTE" || estado === "ERROR"
                    ? "status-danger"
                    : "status-neutral";
        return `<span class="status-badge ${clase}">${escaparHtml(etiquetaFiltro({ estado: texto }, { campo: "estado", formato: "titulo" }))}</span>`;
    }

    if (columna === "activo" && texto !== "") {
        const activo = texto === "1";
        return `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider ${activo ? "bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800" : "bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700"}">${activo ? "Activo" : "Inactivo"}</span>`;
    }

    if ((columna === "hora_inicio" || columna === "hora_fin") && texto) {
        return `<span class="font-mono font-bold text-sm text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800/80 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700">⏱️ ${escaparHtml(texto.substring(0, 5))}</span>`;
    }

    if (columna === "codigo" && texto) {
        return `<span class="font-mono font-bold text-xs uppercase px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 text-indigo-800 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">${escaparHtml(texto)}</span>`;
    }

    if (columna === "tipo" && texto) {
        const esTecnico = texto.toLowerCase().includes("téc");
        return `<span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-xs font-extrabold ${esTecnico ? "bg-purple-100 dark:bg-purple-950/60 text-purple-900 dark:text-purple-300 border border-purple-200 dark:border-purple-800" : "bg-blue-100 dark:bg-blue-950/60 text-blue-900 dark:text-blue-300 border border-blue-200 dark:border-blue-800"}">${escaparHtml(texto)}</span>`;
    }

    return escaparHtml(texto);
}

async function cargarTabla(api, columnas, filtros = {}) {
    const tbody = document.getElementById("tabla-body");
    const filtrosBox = document.getElementById("filtros-tabla");
    const pestanasBox = document.getElementById("pestanas-tabla");

    let datosOriginales = [];
    let valorPestanaActiva = "";

    window.abrirVincularTutorDesdeTabla = function(idTutor) {
        const item = datosOriginales.find(d => String(d.id_tutor) === String(idTutor));
        if (item && window.GestorFamilias) {
            GestorFamilias.abrir({
                tutorInicial: {
                    id_tutor: item.id_tutor,
                    nombre: item.nombre,
                    apellido: item.apellido,
                    cedula_identidad: item.cedula_identidad,
                    celular: item.celular || "",
                    total_hijos: item.total_hijos || 0,
                    hijos_resumen: item.hijos_resumen || ""
                }
            });
        }
    };

    tbody.innerHTML = `<tr><td colspan="${columnas.length + 1}" class="p-4 text-center">Cargando datos...</td></tr>`;

    try {
        const res = await fetch(api);
        const json = await res.json();
        datosOriginales = json.data ?? json;

        crearPestanas(datosOriginales, filtros.pestanas);
        crearFiltros(datosOriginales, filtros);
        render(datosOriginales);

    } catch (error) {
        console.error(error);
        tbody.innerHTML = `<tr><td colspan="${columnas.length + 1}" class="p-4 text-center text-red-600">Error al cargar datos</td></tr>`;
    }

    function render(datos) {
        tbody.innerHTML = "";

        if (!Array.isArray(datos) || datos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${columnas.length + 1}" class="p-4 text-center text-slate-500">No hay datos registrados</td></tr>`;
            return;
        }

        datos.forEach(item => {
            const idRegistro = window.ID_CAMPO ? item[window.ID_CAMPO] : null;
            let fila = `<tr class="border-b border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">`;

            columnas.forEach(col => {
                fila += `<td class="p-4 text-base">${contenidoCelda(col, item[col], item)}</td>`;
            });

            fila += `
    <td class="p-4 text-center">
        <div class="table-actions flex items-center justify-center gap-2 flex-wrap">
        ${(typeof window.abrirModalAlcanceUsuario === 'function' && item.id_usuario && (item.rol === 'Coordinador' || item.rol === 'Administrador' || item.rol === 'Administracion')) ? `
        <button type="button" onclick="abrirModalAlcanceUsuario(${item.id_usuario}, '${(item.nombre || '').replace(/'/g, "\\'")} ${(item.apellido || '').replace(/'/g, "\\'")}', '${item.rol}')"
                class="min-h-[40px] px-3.5 py-1.5 rounded-xl font-bold text-sm bg-indigo-100 hover:bg-indigo-200 text-indigo-900 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 transition inline-flex items-center gap-1.5 cursor-pointer shadow-2xs" title="Asignar turnos, niveles, bachilleratos y cursos de operación">
            <span>🎯</span> <span>Alcance</span>
        </button>` : ""}
        ${item.id_tutor ? `
        <button type="button" onclick="abrirVincularTutorDesdeTabla(${item.id_tutor})"
                class="min-h-[40px] px-3.5 py-1.5 rounded-xl font-bold text-sm bg-blue-100 hover:bg-blue-200 text-blue-900 dark:bg-blue-950/60 dark:hover:bg-blue-900/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800 transition inline-flex items-center gap-1.5 cursor-pointer shadow-2xs" title="Vincular estudiantes o hermanos a este tutor">
            <span>👨‍👩‍👦</span> <span>+ Hijos</span>
        </button>` : ""}
        ${window.API_ACTUALIZAR && idRegistro ? `
        <button type="button" onclick='editarRegistro(${JSON.stringify(item)})'
                class="min-h-[40px] px-3.5 py-1.5 rounded-xl font-bold text-sm bg-blue-100 hover:bg-blue-200 text-blue-900 dark:bg-blue-950/60 dark:hover:bg-blue-900/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800 transition inline-flex items-center gap-1.5 cursor-pointer shadow-2xs">
            <span>✏️</span> <span>Editar</span>
        </button>` : ""}
        ${window.API_DESACTIVAR && idRegistro ? `
        <button type="button" onclick='desactivarRegistro(${JSON.stringify(idRegistro)})'
                class="min-h-[40px] px-3.5 py-1.5 rounded-xl font-bold text-sm bg-amber-100 hover:bg-amber-200 text-amber-950 dark:bg-amber-950/60 dark:hover:bg-amber-900/60 dark:text-amber-300 border border-amber-300 dark:border-amber-800 transition inline-flex items-center gap-1.5 cursor-pointer shadow-2xs">
            <span>⚠️</span> <span>Desactivar</span>
        </button>` : ""}
        ${window.API_RESTAURAR && idRegistro ? `
        <button type="button" onclick='restaurarRegistro(${JSON.stringify(idRegistro)})'
                class="min-h-[40px] px-3.5 py-1.5 rounded-xl font-bold text-sm bg-emerald-100 hover:bg-emerald-200 text-emerald-950 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 transition inline-flex items-center gap-1.5 cursor-pointer shadow-2xs">
            <span>♻️</span> <span>Restaurar</span>
        </button>` : ""}
        ${window.API_ELIMINAR && idRegistro ? `
        <button type="button" onclick='eliminarRegistro(${JSON.stringify(idRegistro)})'
                class="min-h-[40px] px-3.5 py-1.5 rounded-xl font-bold text-sm bg-rose-100 hover:bg-rose-200 text-rose-950 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 dark:text-rose-300 border border-rose-300 dark:border-rose-800 transition inline-flex items-center gap-1.5 cursor-pointer shadow-2xs">
            <span>🗑️</span> <span>Eliminar</span>
        </button>` : ""}
        ${item.user_id_global && !item.id_profesor ? `
        <button type="button" onclick="descargarYGuardarTemplate('${item.user_id_global}', 'estudiante')"
                class="min-h-[40px] px-3.5 py-1.5 rounded-xl font-bold text-sm bg-purple-100 hover:bg-purple-200 text-purple-900 dark:bg-purple-950/60 dark:hover:bg-purple-900/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800 transition inline-flex items-center gap-1.5 cursor-pointer shadow-2xs">
            <span>👆</span> <span>Huella</span>
        </button>` : ""}

        ${item.user_id_global && item.id_profesor ? `
        <button type="button" onclick="descargarYGuardarTemplate('${item.user_id_global}', 'profesor')"
                class="min-h-[40px] px-3.5 py-1.5 rounded-xl font-bold text-sm bg-purple-100 hover:bg-purple-200 text-purple-900 dark:bg-purple-950/60 dark:hover:bg-purple-900/60 dark:text-purple-300 border border-purple-200 dark:border-purple-800 transition inline-flex items-center gap-1.5 cursor-pointer shadow-2xs">
            <span>👆</span> <span>Huella</span>
        </button>` : ""}
        </div>
    </td>`;

            fila += `</tr>`;
            tbody.innerHTML += fila;
        });
    }

    function crearPestanas(datos, configPestanas) {
        if (!pestanasBox || !configPestanas || !configPestanas.campo) return;

        pestanasBox.innerHTML = "";
        const campo = configPestanas.campo;

        let opciones = [];
        if (Array.isArray(configPestanas.opciones) && configPestanas.opciones.length > 0) {
            opciones = configPestanas.opciones.map(op => ({
                valor: String(op.valor ?? ""),
                label: String(op.label ?? op.valor)
            }));
        } else {
            const valoresUnicos = new Set();
            datos.forEach(item => {
                const val = item[campo];
                if (val !== null && val !== undefined && String(val).trim() !== "") {
                    valoresUnicos.add(String(val).trim());
                }
            });

            const listaOrdenada = Array.from(valoresUnicos).sort((a, b) =>
                a.localeCompare(b, "es", { numeric: true, sensitivity: "base" })
            );

            opciones = [
                { valor: "", label: configPestanas.todosLabel ?? "Todos" },
                ...listaOrdenada.map(v => ({ valor: v, label: v }))
            ];
        }

        const contenedor = document.createElement("div");
        contenedor.className = "flex flex-wrap items-center gap-2 p-1.5 bg-slate-100 dark:bg-slate-800/80 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xs";
        contenedor.setAttribute("role", "tablist");

        opciones.forEach(op => {
            const count = op.valor === ""
                ? datos.length
                : datos.filter(item => String(item[campo] ?? "").trim().toLowerCase() === op.valor.toLowerCase()).length;

            const btn = document.createElement("button");
            btn.type = "button";
            btn.setAttribute("role", "tab");
            btn.setAttribute("data-valor", op.valor);
            const esActivo = valorPestanaActiva.toLowerCase() === op.valor.toLowerCase();

            btn.className = `tab-item inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-150 cursor-pointer ${
                esActivo
                    ? "bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm border border-slate-200/90 dark:border-slate-700 font-extrabold"
                    : "text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/70 dark:hover:bg-slate-700/60"
            }`;
            btn.setAttribute("aria-selected", esActivo ? "true" : "false");

            btn.innerHTML = `
                <span>${escaparHtml(op.label)}</span>
                <span class="tab-badge px-2.5 py-0.5 text-xs rounded-full font-bold transition-colors ${
                    esActivo
                        ? "bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800"
                        : "bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300"
                }">${count}</span>
            `;

            btn.addEventListener("click", () => {
                valorPestanaActiva = op.valor;
                contenedor.querySelectorAll(".tab-item").forEach(b => {
                    const active = b.getAttribute("data-valor").toLowerCase() === valorPestanaActiva.toLowerCase();
                    b.className = `tab-item inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-150 cursor-pointer ${
                        active
                            ? "bg-white dark:bg-slate-900 text-blue-600 dark:text-blue-400 shadow-sm border border-slate-200/90 dark:border-slate-700 font-extrabold"
                            : "text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-200/70 dark:hover:bg-slate-700/60"
                    }`;
                    b.setAttribute("aria-selected", active ? "true" : "false");
                    const badge = b.querySelector(".tab-badge");
                    if (badge) {
                        badge.className = `tab-badge px-2.5 py-0.5 text-xs rounded-full font-bold transition-colors ${
                            active ? "bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800" : "bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300"
                        }`;
                    }
                });

                aplicarFiltros();
            });

            contenedor.appendChild(btn);
        });

        pestanasBox.appendChild(contenedor);
    }

    function crearFiltros(datos, filtros) {
        if (!filtrosBox) return;

        filtrosBox.innerHTML = "";

        if (filtros.buscar) {
            filtrosBox.innerHTML += `
                <div class="relative flex-1 min-w-[220px]">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-base">🔍</span>
                    <input id="buscar-tabla" 
                           type="text" 
                           placeholder="${filtros.placeholder ?? 'Buscar...'}"
                           class="w-full min-h-[46px] pl-10 pr-4 py-2 text-base text-slate-900 dark:text-white font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white dark:focus:bg-slate-900 transition">
                </div>
            `;
        }

        if (filtros.selects) {
            filtros.selects.forEach(f => {
                const opciones = opcionesFiltro(datos, f);
                if (opciones.length === 0) return;

                let select = `
                    <select data-campo="${f.campo}" class="filtro-select min-h-[46px] px-3.5 py-2 text-base text-slate-900 dark:text-white font-semibold bg-slate-50 dark:bg-slate-800/80 border border-slate-300 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <option value="">${escaparHtml(f.label)}</option>
                `;

                opciones.forEach(([valor, etiqueta]) => {
                    select += `<option value="${escaparHtml(valor)}">${escaparHtml(etiqueta)}</option>`;
                });

                select += `</select>`;
                filtrosBox.innerHTML += select;
            });
        }

        filtrosBox.innerHTML += `
            <button id="limpiar-filtros" type="button" class="btn btn-secondary min-h-[46px] px-4 py-2 text-sm font-bold rounded-xl flex items-center gap-1.5 cursor-pointer">
                <span>✕</span> <span>Limpiar</span>
            </button>
        `;

        if (window.URL_DESACTIVADOS) {
            filtrosBox.innerHTML += `
                <a href="${window.URL_DESACTIVADOS}" class="btn btn-dark">
                    Ver desactivados
                </a>
            `;
        }

        activarEventos();
    }

    function aplicarFiltros() {
        const buscador = document.getElementById("buscar-tabla");
        const selects = document.querySelectorAll(".filtro-select");

        let filtrados = [...datosOriginales];

        // 1. Filtro por Pestañas
        if (filtros.pestanas && filtros.pestanas.campo && valorPestanaActiva !== "") {
            const campo = filtros.pestanas.campo;
            filtrados = filtrados.filter(item =>
                String(item[campo] ?? "").trim().toLowerCase() === valorPestanaActiva.toLowerCase()
            );
        }

        // 2. Filtro por Buscador de texto
        if (buscador && buscador.value.trim() !== "") {
            const texto = buscador.value.toLowerCase();

            filtrados = filtrados.filter(item =>
                filtros.buscar.some(campo =>
                    String(item[campo] ?? "").toLowerCase().includes(texto)
                )
            );
        }

        // 3. Filtro por Selects
        selects.forEach(select => {
            const campo = select.dataset.campo;
            const valor = select.value;

            if (valor !== "") {
                filtrados = filtrados.filter(item => String(item[campo]) === valor);
            }
        });

        render(filtrados);
    }

    function activarEventos() {
        const buscador = document.getElementById("buscar-tabla");
        const selects = document.querySelectorAll(".filtro-select");
        const limpiar = document.getElementById("limpiar-filtros");

        if (buscador) buscador.addEventListener("input", aplicarFiltros);
        selects.forEach(s => s.addEventListener("change", aplicarFiltros));

        if (limpiar) {
            limpiar.addEventListener("click", () => {
                if (buscador) buscador.value = "";
                selects.forEach(s => s.value = "");
                valorPestanaActiva = "";

                if (pestanasBox) {
                    pestanasBox.querySelectorAll(".tab-item").forEach(b => {
                        const active = b.getAttribute("data-valor") === "";
                        b.className = `tab-item inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-sm font-semibold transition-all duration-150 cursor-pointer ${
                            active
                                ? "bg-white text-blue-600 shadow-sm border border-slate-200/80 font-bold"
                                : "text-slate-600 hover:text-slate-900 hover:bg-slate-200/70"
                        }`;
                        b.setAttribute("aria-selected", active ? "true" : "false");
                        const badge = b.querySelector(".tab-badge");
                        if (badge) {
                            badge.className = `tab-badge px-2 py-0.5 text-xs rounded-full font-bold transition-colors ${
                                active ? "bg-blue-100 text-blue-700" : "bg-slate-200 text-slate-600"
                            }`;
                        }
                    });
                }

                render(datosOriginales);
            });
        }
    }
}
