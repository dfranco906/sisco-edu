// Estado global para el registro actualmente en edición en cualquier vista CRUD
let itemEditando = null;
window.itemEditando = null;

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

            const registro = itemEditando || window.itemEditando;
            const formData = new FormData(formEditar);
            
            if (window.ID_CAMPO && registro && registro[window.ID_CAMPO] !== undefined) {
                formData.set(window.ID_CAMPO, registro[window.ID_CAMPO]);
            }

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
    window.itemEditando = item;

    const modal = document.getElementById("modal-editar");
    const campos = document.getElementById("campos-editar");
    const mensaje = document.getElementById("mensaje-editar");

    if (!modal || !campos) return;

    if (mensaje) mensaje.innerHTML = "";
    campos.innerHTML = "";

    if (window.ID_CAMPO && item && item[window.ID_CAMPO] !== undefined) {
        campos.innerHTML += `<input type="hidden" name="${window.ID_CAMPO}" value="${item[window.ID_CAMPO]}">`;
    }

    const camposEditables = window.CAMPOS_EDITAR || [];
    const formCampos = window.FORM_CAMPOS || [];

    camposEditables.forEach(campoItem => {
        const isObj = typeof campoItem === 'object' && campoItem !== null;
        const campo = isObj ? campoItem.name : campoItem;
        const def = isObj ? campoItem : formCampos.find(f => f.name === campo);
        const valorActual = item[campo] ?? "";
        const label = def?.label || (typeof campo === 'string' ? campo.replace(/_/g, ' ') : '');

        if (def && def.type === 'select') {
            const required = (def.required !== false) ? 'required' : '';
            if (def.options) {
                const optsHtml = def.options.map(op => {
                    const sel = String(op.value) === String(valorActual) ? 'selected' : '';
                    return `<option value="${op.value}" ${sel}>${op.label}</option>`;
                }).join('');
                campos.innerHTML += `
                    <div>
                        <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">${label}</label>
                        <select name="${campo}" class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none transition mb-4" ${required}>
                            <option value="">Seleccione una opción</option>
                            ${optsHtml}
                        </select>
                    </div>
                `;
            } else if (def.api) {
                campos.innerHTML += `
                    <div>
                        <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">${label}</label>
                        <select name="${campo}"
                                data-api="${def.api}"
                                data-value="${def.value}"
                                data-label="${def.labelField}"
                                data-selected-val="${valorActual}"
                                class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none transition mb-4 js-select-editar"
                                ${required}>
                            <option value="">Cargando opciones...</option>
                        </select>
                    </div>
                `;
            }
        } else {
            const tipo = def?.type || 'text';
            const required = (def?.required !== false) ? 'required' : '';
            campos.innerHTML += `
                <div>
                    <label class="block text-sm sm:text-base font-extrabold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">${label}</label>
                    <input type="${tipo}"
                           name="${campo}"
                           value="${valorActual}"
                           class="w-full min-h-[48px] px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white text-base font-semibold focus:ring-2 focus:ring-blue-500 focus:outline-none transition mb-4"
                           ${required}>
                </div>
            `;
        }
    });

    const selectsEditar = campos.querySelectorAll('.js-select-editar');
    selectsEditar.forEach(select => {
        cargarSelectEditar(select);
    });

    modal.classList.remove("hidden");
    document.body.classList.add("modal-open");
};

async function cargarSelectEditar(select) {
    const api = select.dataset.api;
    const valueField = select.dataset.value;
    const labelField = select.dataset.label;
    const selectedVal = select.dataset.selectedVal;

    try {
        const url = api.startsWith('http') ? api : `${window.BASE_URL}/${api}`;
        const res = await fetch(url);
        const data = await res.json();
        const lista = Array.isArray(data) ? data : (data.data || []);

        let opciones = '<option value="">Seleccione una opción</option>';
        lista.forEach(opt => {
            const val = opt[valueField];
            const lbl = opt[labelField] || val;
            const isSelected = String(val) === String(selectedVal) ? 'selected' : '';
            opciones += `<option value="${val}" ${isSelected}>${lbl}</option>`;
        });
        select.innerHTML = opciones;
    } catch (err) {
        console.error("Error cargando opciones de edición:", err);
        select.innerHTML = '<option value="">Error al cargar opciones</option>';
    }
}

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
