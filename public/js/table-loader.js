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

function resolverUrlTabla(url) {
    if (!url) return "";

    try {
        const base = `${window.location.origin}${window.BASE_URL || "/"}`;
        return new URL(url, base).toString();
    } catch (error) {
        return url;
    }
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

    let datosOriginales = [];
    let aplicarFiltrosActual = null;

    tbody.innerHTML = `<tr><td colspan="${columnas.length + 1}" class="p-4 text-center">Cargando datos...</td></tr>`;

    try {
        const res = await fetch(api);
        const texto = await res.text();
        let json;

        try {
            json = JSON.parse(texto);
        } catch (error) {
            throw new Error("El servidor no devolvió JSON válido.");
        }

        if (!res.ok || json?.status === "error" || json?.success === false) {
            throw new Error(json?.message || `No se pudieron cargar los datos (HTTP ${res.status}).`);
        }

        datosOriginales = json.data ?? json;

        if (!Array.isArray(datosOriginales)) {
            throw new Error("La respuesta del servidor no contiene una lista de registros.");
        }

        await crearFiltros(datosOriginales, filtros);
        if (aplicarFiltrosActual) aplicarFiltrosActual();
        else render(datosOriginales);

    } catch (error) {
        console.error(error);
        tbody.innerHTML = `<tr><td colspan="${columnas.length + 1}" class="p-4 text-center text-red-600">${escaparHtml(error.message || "Error al cargar datos")}</td></tr>`;
    }

    function render(datos) {
        tbody.innerHTML = "";

        if (!Array.isArray(datos) || datos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${columnas.length + 1}" class="p-4 text-center">No hay datos registrados</td></tr>`;
            return;
        }

        const registrosPorId = new Map();

        datos.forEach(item => {
            const idRegistro = window.ID_CAMPO ? item[window.ID_CAMPO] : null;
            const idValido = idRegistro !== null
                && idRegistro !== undefined
                && String(idRegistro).trim() !== "";
            const idClave = idValido ? String(idRegistro) : "";
            if (idValido) registrosPorId.set(idClave, item);

            let fila = `<tr class="border-b hover:bg-gray-50">`;

            columnas.forEach(col => {
                fila += `<td class="p-3">${contenidoCelda(col, item[col])}</td>`;
            });

            fila += `
    <td class="p-3"><div class="table-actions">
        ${window.API_ACTUALIZAR && idValido ? `
        <button type="button" data-accion-registro="editar" data-registro-id="${escaparHtml(idClave)}"
        class="btn btn-edit">
            Editar
        </button>` : ""}
        ${window.API_DESACTIVAR && idValido ? `
        <button type="button" data-accion-registro="desactivar" data-registro-id="${escaparHtml(idClave)}"
        class="btn btn-warning">
            Desactivar
        </button>` : ""}
        ${window.API_RESTAURAR && idValido ? `
        <button type="button" data-accion-registro="restaurar" data-registro-id="${escaparHtml(idClave)}"
        class="btn btn-restore">
            Restaurar
        </button>` : ""}
        ${window.API_ELIMINAR && idValido ? `
        <button type="button" data-accion-registro="eliminar" data-registro-id="${escaparHtml(idClave)}"
        class="btn btn-danger btn-sm">
            Eliminar
        </button>` : ""}
        ${item.user_id_global && !Object.prototype.hasOwnProperty.call(item, "id_profesor") ? `
        <button type="button" data-accion-registro="huella" data-user-id-global="${escaparHtml(item.user_id_global)}" data-tipo-persona="estudiante"
        class="btn btn-fingerprint btn-huella">
            Huella
        </button>` : ""}

        ${item.user_id_global && Object.prototype.hasOwnProperty.call(item, "id_profesor") ? `
        <button type="button" data-accion-registro="huella" data-user-id-global="${escaparHtml(item.user_id_global)}" data-tipo-persona="profesor"
        class="btn btn-fingerprint btn-huella">
          Huella
        </button>` : ""}
            </div></td>`;

            fila += `</tr>`;
            tbody.innerHTML += fila;
        });

        tbody.querySelectorAll("[data-accion-registro]").forEach((boton) => {
            boton.addEventListener("click", () => {
                const accion = boton.dataset.accionRegistro;
                const id = boton.dataset.registroId;

                if (accion === "editar") window.editarRegistro?.(registrosPorId.get(id));
                if (accion === "desactivar") window.desactivarRegistro?.(id);
                if (accion === "restaurar") window.restaurarRegistro?.(id);
                if (accion === "eliminar") window.eliminarRegistro?.(id);
                if (accion === "huella") {
                    window.descargarYGuardarTemplate?.(
                        boton.dataset.userIdGlobal,
                        boton.dataset.tipoPersona
                    );
                }
            });
        });
    }

    async function crearFiltros(datos, filtros) {
        if (!filtrosBox) return;

        filtrosBox.innerHTML = "";

        if (filtros.principal) {
            const filtro = filtros.principal;
            let datosPrincipal = datos;

            if (filtro.api) {
                try {
                    const respuesta = await fetch(resolverUrlTabla(filtro.api));
                    const json = await respuesta.json();
                    if (respuesta.ok && json?.status !== "error" && json?.success !== false) {
                        const datosApi = json.data ?? json;
                        if (Array.isArray(datosApi)) datosPrincipal = datosApi;
                    }
                } catch (error) {
                    console.error("No se pudieron cargar los grados del filtro principal.", error);
                }
            }

            const opciones = opcionesFiltro(datosPrincipal, filtro);
            let selectPrincipal = `
                <div class="app-filter-primary">
                    <label for="filtro-principal" class="app-filter-primary-label">
                        ${escaparHtml(filtro.label || "Organizar por")}
                    </label>
                    <select id="filtro-principal" data-campo="${escaparHtml(filtro.campo)}"
                            class="filtro-select app-input">
                        <option value="">${escaparHtml(filtro.opcionTodos || "Todos los registros")}</option>
            `;

            opciones.forEach(([valor, etiqueta], indice) => {
                const seleccionado = filtro.porDefecto === "primero" && indice === 0 ? " selected" : "";
                selectPrincipal += `<option value="${escaparHtml(valor)}"${seleccionado}>${escaparHtml(etiqueta)}</option>`;
            });

            selectPrincipal += `
                    </select>
                    <span id="resumen-filtro-principal" class="app-filter-primary-summary" aria-live="polite"></span>
                </div>
            `;
            filtrosBox.innerHTML += selectPrincipal;
        }

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

    function activarEventos() {
        const buscador = document.getElementById("buscar-tabla");
        const selects = document.querySelectorAll(".filtro-select");
        const limpiar = document.getElementById("limpiar-filtros");
        const resumenPrincipal = document.getElementById("resumen-filtro-principal");

        function aplicarFiltros() {
            let filtrados = [...datosOriginales];

            if (buscador && buscador.value.trim() !== "") {
                const texto = buscador.value.toLowerCase();

                filtrados = filtrados.filter(item =>
                    filtros.buscar.some(campo =>
                        String(item[campo] ?? "").toLowerCase().includes(texto)
                    )
                );
            }

            selects.forEach(select => {
                const campo = select.dataset.campo;
                const valor = select.value;

                if (valor !== "") {
                    filtrados = filtrados.filter(item => String(item[campo]) === valor);
                }
            });

            render(filtrados);

            if (resumenPrincipal) {
                const cantidad = filtrados.length;
                resumenPrincipal.textContent = `${cantidad} asignación${cantidad === 1 ? "" : "es"} visible${cantidad === 1 ? "" : "s"}`;
            }
        }

        aplicarFiltrosActual = aplicarFiltros;

        if (buscador) buscador.addEventListener("input", aplicarFiltros);
        selects.forEach(s => s.addEventListener("change", aplicarFiltros));

        limpiar?.addEventListener("click", () => {
            if (buscador) buscador.value = "";
            selects.forEach(s => s.value = "");
            aplicarFiltros();
        });
    }
}
