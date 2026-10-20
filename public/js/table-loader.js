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

function contenidoCelda(columna, valor) {
    const texto = String(valor ?? "");

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
        return `<span class="status-badge ${activo ? "status-success" : "status-neutral"}">${activo ? "Activo" : "Inactivo"}</span>`;
    }

    return escaparHtml(texto);
}

async function cargarTabla(api, columnas, filtros = {}) {
    const tbody = document.getElementById("tabla-body");
    const filtrosBox = document.getElementById("filtros-tabla");
    const pestanasBox = document.getElementById("pestanas-tabla");

    let datosOriginales = [];
    let valorPestanaActiva = "";

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
            let fila = `<tr class="border-b hover:bg-gray-50">`;

            columnas.forEach(col => {
                fila += `<td class="p-3">${contenidoCelda(col, item[col])}</td>`;
            });

            fila += `
    <td class="p-3"><div class="table-actions">
        ${window.API_ACTUALIZAR && idRegistro ? `
        <button onclick='editarRegistro(${JSON.stringify(item)})'
        class="btn btn-edit">
            Editar
        </button>` : ""}
        ${window.API_DESACTIVAR && idRegistro ? `
        <button onclick='desactivarRegistro(${JSON.stringify(idRegistro)})'
        class="btn btn-warning">
            Desactivar
        </button>` : ""}
        ${window.API_RESTAURAR && idRegistro ? `
        <button onclick='restaurarRegistro(${JSON.stringify(idRegistro)})'
        class="btn btn-restore">
            Restaurar
        </button>` : ""}
        ${window.API_ELIMINAR && idRegistro ? `
        <button onclick='eliminarRegistro(${JSON.stringify(idRegistro)})'
        class="btn btn-danger btn-sm">
            Eliminar
        </button>` : ""}
        ${item.user_id_global && !item.id_profesor ? `
        <button onclick="descargarYGuardarTemplate('${item.user_id_global}', 'estudiante')"
        class="btn btn-fingerprint btn-huella">
            Huella
        </button>` : ""}

        ${item.user_id_global && item.id_profesor ? `
        <button onclick="descargarYGuardarTemplate('${item.user_id_global}', 'profesor')"
        class="btn btn-fingerprint btn-huella">
          Huella
        </button>` : ""}
            </div></td>`;

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
        contenedor.className = "flex flex-wrap items-center gap-2 p-1.5 bg-slate-100 rounded-xl border border-slate-200 shadow-2xs";
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

            btn.className = `tab-item inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-sm font-semibold transition-all duration-150 cursor-pointer ${
                esActivo
                    ? "bg-white text-blue-600 shadow-sm border border-slate-200/80 font-bold"
                    : "text-slate-600 hover:text-slate-900 hover:bg-slate-200/70"
            }`;
            btn.setAttribute("aria-selected", esActivo ? "true" : "false");

            btn.innerHTML = `
                <span>${escaparHtml(op.label)}</span>
                <span class="tab-badge px-2 py-0.5 text-xs rounded-full font-bold transition-colors ${
                    esActivo
                        ? "bg-blue-100 text-blue-700"
                        : "bg-slate-200 text-slate-600"
                }">${count}</span>
            `;

            btn.addEventListener("click", () => {
                valorPestanaActiva = op.valor;
                contenedor.querySelectorAll(".tab-item").forEach(b => {
                    const active = b.getAttribute("data-valor").toLowerCase() === valorPestanaActiva.toLowerCase();
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
                <input id="buscar-tabla" 
                       type="text" 
                       placeholder="${filtros.placeholder ?? 'Buscar...'}"
                       class="app-input w-full">
            `;
        }

        if (filtros.selects) {
            filtros.selects.forEach(f => {
                const opciones = opcionesFiltro(datos, f);
                if (opciones.length === 0) return;

                let select = `
                    <select data-campo="${f.campo}" class="filtro-select app-input">
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
            <button id="limpiar-filtros" class="btn btn-muted">
                Limpiar filtros
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
