let itemEditando = null;

document.addEventListener("DOMContentLoaded", () => {
    const btnCrear = document.getElementById("btn-crear");
    const modalCrear = document.getElementById("modal-crear");
    const cerrarCrear = document.getElementById("cerrar-modal");
    const formCrear = document.getElementById("form-crear");
    const mensajeCrear = document.getElementById("mensaje-form");

    const modalEditar = document.getElementById("modal-editar");
    const cancelarEditar = document.getElementById("cancelar-editar");
    const formEditar = document.getElementById("form-editar");
    const mensajeEditar = document.getElementById("mensaje-editar");

    if (btnCrear && modalCrear) btnCrear.onclick = () => modalCrear.classList.remove("hidden");
    if (cerrarCrear && modalCrear) cerrarCrear.onclick = () => modalCrear.classList.add("hidden");

    if (cancelarEditar && modalEditar) {
        cancelarEditar.onclick = () => modalEditar.classList.add("hidden");
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
                    ${exito ? `<br><button onclick="location.reload()" class="mt-3 px-4 py-2 rounded-xl bg-blue-600 text-white">Actualizar tabla</button>` : ""}
                `;

                if (exito) formCrear.reset();

            } catch (error) {
                console.error(error);
                mensajeCrear.className = "text-red-600 font-semibold mb-3";
                mensajeCrear.innerHTML = "Error al procesar la solicitud";
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

                mensajeEditar.className = data.status === "success"
                    ? "text-green-600 font-semibold mb-3"
                    : "text-red-600 font-semibold mb-3";

                mensajeEditar.innerHTML = `
                    ${data.message}
                    ${data.status === "success" ? `<br><button onclick="location.reload()" class="mt-3 px-4 py-2 rounded-xl bg-blue-600 text-white">Actualizar tabla</button>` : ""}
                `;

            } catch (error) {
                console.error(error);
                mensajeEditar.className = "text-red-600 font-semibold mb-3";
                mensajeEditar.innerHTML = "Error al actualizar";
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
            <label class="block mb-2 font-semibold">${campo}</label>
            <input 
                name="${campo}"
                value="${item[campo] ?? ""}"
                class="border rounded-xl px-4 py-2 w-full mb-4"
                required
            >
        `;
    });

    modal.classList.remove("hidden");
};