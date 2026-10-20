document.addEventListener("DOMContentLoaded", () => {
    const btnCrear = document.getElementById("btn-crear");
    const modalCrear = document.getElementById("modal-crear");
    const cerrarModal = document.getElementById("cerrar-modal");
    const formCrear = document.getElementById("form-crear");
    const mensajeCrear = document.getElementById("mensaje-form");

    const modalEditar = document.getElementById("modal-editar");
    const cerrarModalEditar = document.getElementById("cerrar-modal-editar");
    const cancelarEditar = document.getElementById("cancelar-editar");
    const formEditar = document.getElementById("form-editar");
    const mensajeEditar = document.getElementById("mensaje-editar");

    let itemEditando = null;

    if (btnCrear && modalCrear) {
        btnCrear.addEventListener("click", () => {
            mensajeCrear.innerHTML = "";
            modalCrear.classList.remove("hidden");
            document.body.classList.add("modal-open");
        });
    }

    if (cerrarModal && modalCrear) {
        cerrarModal.addEventListener("click", () => {
            modalCrear.classList.add("hidden");
            document.body.classList.remove("modal-open");
        });
    }

    document.querySelectorAll(".js-cerrar-modal-crear").forEach((btn) => {
        btn.addEventListener("click", () => {
            modalCrear?.classList.add("hidden");
            document.body.classList.remove("modal-open");
        });
    });

    if (cerrarModalEditar && modalEditar) {
        cerrarModalEditar.addEventListener("click", () => {
            modalEditar.classList.add("hidden");
            document.body.classList.remove("modal-open");
        });
    }

    if (cancelarEditar && modalEditar) {
        cancelarEditar.addEventListener("click", () => {
            modalEditar.classList.add("hidden");
            document.body.classList.remove("modal-open");
        });
    }

    cargarSelects();

    if (formCrear) {
        formCrear.onsubmit = async (e) => {
            e.preventDefault();

            mensajeCrear.innerHTML = "Guardando...";
            mensajeCrear.className = "text-blue-600 font-semibold mb-3";

            try {
                const res = await fetch(formCrear.dataset.api, {
                    method: "POST",
                    body: new FormData(formCrear)
                });

                const data = await res.json();
                const exito = data.status === "success";

                mensajeCrear.className = exito
                    ? "text-green-600 font-semibold mb-3"
                    : "text-red-600 font-semibold mb-3";

                mensajeCrear.innerHTML = `
                    ${data.message ?? "Operación realizada"}
                    ${exito ? `<br><button onclick="location.reload()" class="btn btn-primary mt-3">Actualizar tabla</button>` : ""}
                `;

                if (window.Toast) {
                    if (exito) {
                        Toast.success(data.message ?? "Registro guardado correctamente");
                    } else {
                        Toast.error(data.message ?? "Error al guardar el registro");
                    }
                }

                if (exito) formCrear.reset();

            } catch (error) {
                console.error(error);
                mensajeCrear.className = "text-red-600 font-semibold mb-3";
                mensajeCrear.innerHTML = "Error al procesar la solicitud";
                if (window.Toast) Toast.error("Error al comunicarse con el servidor");
            }
        };
    }

    if (formEditar) {
        formEditar.onsubmit = async (e) => {
            e.preventDefault();

            const formData = new FormData(formEditar);
            formData.append(window.ID_CAMPO, itemEditando[window.ID_CAMPO]);

            mensajeEditar.innerHTML = "Actualizando...";
            mensajeEditar.className = "text-blue-600 font-semibold mb-3";

            try {
                const res = await fetch(window.API_ACTUALIZAR, {
                    method: "POST",
                    body: formData
                });

                const data = await res.json();
                const exito = data.status === "success";

                mensajeEditar.className = exito
                    ? "text-green-600 font-semibold mb-3"
                    : "text-red-600 font-semibold mb-3";

                mensajeEditar.innerHTML = `
                    ${data.message}
                    ${exito ? `<br><button onclick="location.reload()" class="btn btn-primary mt-3">Actualizar tabla</button>` : ""}
                `;

                if (window.Toast) {
                    if (exito) {
                        Toast.success(data.message ?? "Registro actualizado correctamente");
                    } else {
                        Toast.error(data.message ?? "Error al actualizar el registro");
                    }
                }

            } catch (error) {
                console.error(error);
                mensajeEditar.className = "text-red-600 font-semibold mb-3";
                mensajeEditar.innerHTML = "Error al procesar la solicitud";
                if (window.Toast) Toast.error("Error al actualizar el registro");
            }
        };
    }
});

async function cargarSelects() {
    const selects = document.querySelectorAll("select[data-api]");

    for (const select of selects) {
        try {
            const res = await fetch(window.BASE_URL + select.dataset.api);
            const json = await res.json();
            const data = json.data ?? json;

            select.innerHTML = `<option value="">Seleccione una opción</option>`;

            data.forEach(item => {
                select.innerHTML += `
                    <option value="${item[select.dataset.value]}">
                        ${item[select.dataset.label]}
                    </option>
                `;
            });

        } catch (error) {
            console.error(error);
            select.innerHTML = `<option value="">Error al cargar opciones</option>`;
        }
    }
}

window.editarRegistro = function(item) {
    itemEditando = item;

    const modal = document.getElementById("modal-editar");
    const campos = document.getElementById("campos-editar");
    const mensaje = document.getElementById("mensaje-editar");

    mensaje.innerHTML = "";
    campos.innerHTML = "";

    const camposEditables = window.CAMPOS_EDITAR || [];

    camposEditables.forEach(campo => {
        campos.innerHTML += `
            <label class="block mb-2 font-semibold text-slate-800 dark:text-slate-200 capitalize">${campo.replace('_', ' ')}</label>
            <input 
                name="${campo}"
                value="${item[campo] ?? ""}"
                class="app-input w-full mb-4"
                required
            >
        `;
    });

    modal.classList.remove("hidden");
    document.body.classList.add("modal-open");
};

async function ejecutarAccionRegistro(api, id, mensajeConfirmacion, tipoAccion = 'warning') {
    if (!api || !window.ID_CAMPO) {
        if (window.Toast) {
            Toast.warning("Acción no configurada para este módulo");
        } else {
            alert("Acción no configurada para este módulo");
        }
        return;
    }

    const confirmado = window.SISCO && window.SISCO.confirm
        ? await SISCO.confirm({
            title: tipoAccion === 'danger' ? "¿Eliminar permanentemente?" : "¿Confirmar operación?",
            message: mensajeConfirmacion,
            confirmText: tipoAccion === 'danger' ? "Eliminar" : "Confirmar",
            type: tipoAccion
        })
        : confirm(mensajeConfirmacion);

    if (!confirmado) return;

    const formData = new FormData();
    formData.append(window.ID_CAMPO, id);

    try {
        const res = await fetch(api, {
            method: "POST",
            body: formData
        });

        const texto = await res.text();
        let data;

        try {
            data = JSON.parse(texto);
        } catch (error) {
            data = {
                status: res.ok ? "success" : "error",
                message: texto || "Operación finalizada"
            };
        }

        const esExito = data.status === "success";

        if (window.Toast) {
            if (esExito) {
                Toast.success(data.message ?? "Operación finalizada con éxito");
            } else {
                Toast.error(data.message ?? "No se pudo completar la operación");
            }
        }

        if (esExito) {
            setTimeout(() => {
                location.reload();
            }, 650);
        }
    } catch (error) {
        console.error(error);
        if (window.Toast) {
            Toast.error("Error al procesar la solicitud con el servidor");
        }
    }
}

window.desactivarRegistro = function(id) {
    ejecutarAccionRegistro(
        window.API_DESACTIVAR,
        id,
        "¿Seguro que desea desactivar este registro?",
        "warning"
    );
};

window.restaurarRegistro = function(id) {
    ejecutarAccionRegistro(
        window.API_RESTAURAR,
        id,
        "¿Seguro que desea restaurar este registro al estado activo?",
        "primary"
    );
};

window.eliminarRegistro = function(id) {
    ejecutarAccionRegistro(
        window.API_ELIMINAR,
        id,
        "¿Seguro que desea eliminar este registro permanentemente? Esta acción no se puede deshacer.",
        "danger"
    );
};
