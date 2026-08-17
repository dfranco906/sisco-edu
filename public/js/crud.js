let itemEditando = null;

function operacionExitosa(data) {
    return data?.success === true || data?.status === "success";
}

function resolverUrlCrud(url) {
    if (!url) return "";

    try {
        const base = `${window.location.origin}${window.BASE_URL || "/"}`;
        return new URL(url, base).toString();
    } catch (error) {
        return url;
    }
}

async function solicitarJsonCrud(url, opciones = {}) {
    const respuesta = await fetch(resolverUrlCrud(url), opciones);
    const texto = await respuesta.text();
    let data;

    try {
        data = JSON.parse(texto);
    } catch (error) {
        throw new Error(
            respuesta.ok
                ? "El servidor devolvió una respuesta no válida."
                : `Error HTTP ${respuesta.status}: el servidor no devolvió JSON.`
        );
    }

    data.httpOk = respuesta.ok;
    data.httpStatus = respuesta.status;
    return data;
}

function mostrarMensajeCrud(elemento, mensaje, tipo = "error") {
    if (!elemento) return;

    const clases = {
        info: "text-blue-600 font-semibold mb-3",
        success: "text-green-600 font-semibold mb-3",
        error: "text-red-600 font-semibold mb-3"
    };

    elemento.textContent = mensaje;
    elemento.className = clases[tipo] || clases.error;
}

function aplicarAtributosCampo(control, campo) {
    control.name = campo.name;
    control.className = "app-input w-full mb-4";
    control.required = campo.required !== false;

    ["min", "max", "step", "maxlength", "placeholder"].forEach((atributo) => {
        if (campo[atributo] !== undefined && campo[atributo] !== null) {
            control.setAttribute(atributo, campo[atributo]);
        }
    });
}

function etiquetaOpcion(item, select) {
    const campos = (select.dataset.labelFields || "")
        .split(",")
        .map((campo) => campo.trim())
        .filter(Boolean);

    if (campos.length) {
        return campos
            .map((campo) => item[campo])
            .filter((valor) => valor !== null && valor !== undefined && String(valor).trim() !== "")
            .join(" ");
    }

    return item[select.dataset.label] ?? item[select.dataset.value] ?? "";
}

async function cargarOpcionesSelect(select, valorActual = "", etiquetaActual = "") {
    if (!select?.dataset.api) return;

    select.disabled = true;
    select.replaceChildren(new Option("Cargando opciones...", ""));

    try {
        const json = await solicitarJsonCrud(select.dataset.api);
        if (!json.httpOk || json.status === "error" || json.success === false) {
            throw new Error(json.message || "No se pudieron cargar las opciones.");
        }

        const data = json.data ?? json;
        if (!Array.isArray(data)) throw new Error("La API de opciones no devolvió una lista.");

        select.replaceChildren(new Option("Seleccione una opción", ""));
        data.forEach((item) => {
            const value = item[select.dataset.value];
            if (value === null || value === undefined) return;
            select.add(new Option(String(etiquetaOpcion(item, select)), String(value)));
        });

        const valorNormalizado = valorActual === null || valorActual === undefined ? "" : String(valorActual);
        if (valorNormalizado !== "" && ![...select.options].some((opcion) => opcion.value === valorNormalizado)) {
            const etiqueta = String(etiquetaActual || valorNormalizado).trim();
            select.add(new Option(`${etiqueta} (actual, no disponible)`, valorNormalizado));
        }
        select.value = valorNormalizado;
        select.disabled = false;
    } catch (error) {
        console.error(error);
        select.replaceChildren(new Option(error.message || "Error al cargar opciones", ""));
        select.disabled = true;
    }
}

async function cargarSelects(contenedor = document) {
    const selects = [...contenedor.querySelectorAll("select[data-api]")];
    await Promise.all(selects.map((select) => cargarOpcionesSelect(select, select.value)));
}

function normalizarCampoEditar(campo) {
    if (typeof campo === "string") {
        return {
            name: campo,
            label: campo.replaceAll("_", " "),
            type: "text"
        };
    }

    return {
        type: "text",
        required: true,
        ...campo
    };
}

async function construirCamposEditar(camposBox, item) {
    camposBox.replaceChildren();
    const cargas = [];

    (window.CAMPOS_EDITAR || []).map(normalizarCampoEditar).forEach((campo) => {
        const label = document.createElement("label");
        label.className = "block mb-2 font-semibold";
        label.textContent = campo.label || campo.name.replaceAll("_", " ");
        camposBox.appendChild(label);

        let control;

        if (campo.type === "select") {
            control = document.createElement("select");
            aplicarAtributosCampo(control, campo);

            if (Array.isArray(campo.options)) {
                control.add(new Option("Seleccione una opción", ""));
                campo.options.forEach((opcion) => {
                    control.add(new Option(String(opcion.label), String(opcion.value)));
                });
                control.value = item[campo.name] ?? "";
            } else {
                control.dataset.api = campo.api;
                control.dataset.value = campo.value;
                control.dataset.label = campo.labelField || campo.value;
                if (Array.isArray(campo.labelFields)) {
                    control.dataset.labelFields = campo.labelFields.join(",");
                }
                const camposEtiquetaActual = campo.currentLabelFields
                    || (campo.currentLabelField ? [campo.currentLabelField] : []);
                const etiquetaActual = camposEtiquetaActual
                    .map((nombreCampo) => item[nombreCampo])
                    .filter((valor) => valor !== null && valor !== undefined && String(valor).trim() !== "")
                    .join(" ");
                cargas.push(cargarOpcionesSelect(control, item[campo.name] ?? "", etiquetaActual));
            }
        } else {
            control = document.createElement("input");
            control.type = campo.type || "text";
            aplicarAtributosCampo(control, campo);
            control.value = item[campo.name] ?? "";
        }

        camposBox.appendChild(control);
    });

    await Promise.all(cargas);
}

document.addEventListener("DOMContentLoaded", () => {
    const btnCrear = document.getElementById("btn-crear");
    const modalCrear = document.getElementById("modal-crear");
    const cerrarCrear = document.getElementById("cerrar-modal");
    const formCrear = document.getElementById("form-crear");
    const mensajeCrear = document.getElementById("mensaje-form");

    const modalEditar = document.getElementById("modal-editar");
    const cancelarEditar = document.getElementById("cancelar-editar");
    const cerrarEditar = document.getElementById("cerrar-modal-editar");
    const formEditar = document.getElementById("form-editar");
    const mensajeEditar = document.getElementById("mensaje-editar");

    const abrirModal = (modal) => {
        modal?.classList.remove("hidden");
        document.body.classList.add("modal-open");
    };

    const cerrarModal = (modal) => {
        modal?.classList.add("hidden");
        if (!document.querySelector(".app-modal:not(.hidden)")) document.body.classList.remove("modal-open");
    };

    if (btnCrear && modalCrear) btnCrear.onclick = () => abrirModal(modalCrear);
    if (cerrarCrear && modalCrear) cerrarCrear.onclick = () => cerrarModal(modalCrear);
    document.querySelectorAll(".js-cerrar-modal-crear").forEach((boton) => {
        boton.addEventListener("click", () => cerrarModal(modalCrear));
    });

    if (cancelarEditar && modalEditar) cancelarEditar.onclick = () => cerrarModal(modalEditar);
    if (cerrarEditar && modalEditar) cerrarEditar.onclick = () => cerrarModal(modalEditar);

    [modalCrear, modalEditar].forEach((modal) => {
        modal?.addEventListener("click", (evento) => {
            if (evento.target === modal) cerrarModal(modal);
        });
    });

    document.addEventListener("keydown", (evento) => {
        if (evento.key === "Escape") {
            cerrarModal(modalCrear);
            cerrarModal(modalEditar);
        }
    });

    cargarSelects();

    if (formCrear) {
        formCrear.onsubmit = async (evento) => {
            evento.preventDefault();
            const boton = formCrear.querySelector('button[type="submit"]');
            boton.disabled = true;
            mostrarMensajeCrud(mensajeCrear, "Guardando...", "info");

            try {
                const data = await solicitarJsonCrud(formCrear.dataset.api, {
                    method: "POST",
                    body: new FormData(formCrear)
                });

                if (!data.httpOk || !operacionExitosa(data)) {
                    mostrarMensajeCrud(mensajeCrear, data.message || "No se pudo crear el registro.");
                    return;
                }

                mostrarMensajeCrud(mensajeCrear, data.message || "Registro creado correctamente.", "success");
                formCrear.reset();
                setTimeout(() => window.location.reload(), 650);
            } catch (error) {
                console.error(error);
                mostrarMensajeCrud(mensajeCrear, error.message || "Error al procesar la solicitud.");
            } finally {
                boton.disabled = false;
            }
        };
    }

    if (formEditar) {
        formEditar.onsubmit = async (evento) => {
            evento.preventDefault();
            if (!itemEditando || !window.ID_CAMPO) return;

            const boton = formEditar.querySelector('button[type="submit"]');
            const formData = new FormData(formEditar);
            formData.append(window.ID_CAMPO, itemEditando[window.ID_CAMPO]);
            boton.disabled = true;
            mostrarMensajeCrud(mensajeEditar, "Actualizando...", "info");

            try {
                const data = await solicitarJsonCrud(window.API_ACTUALIZAR, {
                    method: "POST",
                    body: formData
                });

                if (!data.httpOk || !operacionExitosa(data)) {
                    mostrarMensajeCrud(mensajeEditar, data.message || "No se pudo actualizar el registro.");
                    return;
                }

                mostrarMensajeCrud(mensajeEditar, data.message || "Registro actualizado correctamente.", "success");
                setTimeout(() => window.location.reload(), 650);
            } catch (error) {
                console.error(error);
                mostrarMensajeCrud(mensajeEditar, error.message || "Error al actualizar.");
            } finally {
                boton.disabled = false;
            }
        };
    }
});

window.editarRegistro = async function editarRegistro(item) {
    if (!item) return;
    itemEditando = item;

    const modal = document.getElementById("modal-editar");
    const campos = document.getElementById("campos-editar");
    const mensaje = document.getElementById("mensaje-editar");

    mostrarMensajeCrud(mensaje, "Cargando formulario...", "info");
    modal?.classList.remove("hidden");
    document.body.classList.add("modal-open");

    try {
        await construirCamposEditar(campos, item);
        mensaje.textContent = "";
        mensaje.className = "mb-3";
        campos.querySelector("select, input")?.focus();
    } catch (error) {
        console.error(error);
        mostrarMensajeCrud(mensaje, error.message || "No se pudo preparar el formulario.");
    }
};

async function ejecutarAccionRegistro(api, id, mensajeConfirmacion) {
    if (!api || !window.ID_CAMPO) {
        alert("Acción no configurada para este módulo.");
        return;
    }

    if (!confirm(mensajeConfirmacion)) return;

    const formData = new FormData();
    formData.append(window.ID_CAMPO, id);

    try {
        const data = await solicitarJsonCrud(api, { method: "POST", body: formData });
        alert(data.message || "Operación finalizada.");

        if (data.httpOk && operacionExitosa(data)) window.location.reload();
    } catch (error) {
        console.error(error);
        alert(error.message || "Error al procesar la solicitud.");
    }
}

window.desactivarRegistro = (id) => ejecutarAccionRegistro(
    window.API_DESACTIVAR,
    id,
    "¿Seguro que desea desactivar este registro?"
);

window.restaurarRegistro = (id) => ejecutarAccionRegistro(
    window.API_RESTAURAR,
    id,
    "¿Seguro que desea restaurar este registro?"
);

window.eliminarRegistro = (id) => ejecutarAccionRegistro(
    window.API_ELIMINAR,
    id,
    "¿Seguro que desea eliminar este registro? Esta acción no se puede deshacer."
);
