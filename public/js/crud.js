document.addEventListener("DOMContentLoaded", () => {
    const btnCrear = document.getElementById("btn-crear");
    const modal = document.getElementById("modal-crear");
    const cerrar = document.getElementById("cerrar-modal");
    const form = document.getElementById("form-crear");
    const mensaje = document.getElementById("mensaje-form");

    if (btnCrear && modal) btnCrear.onclick = () => modal.classList.remove("hidden");
    if (cerrar && modal) cerrar.onclick = () => modal.classList.add("hidden");

    cargarSelects();

    if (form) {
        form.onsubmit = async (e) => {
            e.preventDefault();

            mensaje.innerHTML = "Guardando...";
            mensaje.className = "text-blue-600 font-semibold mb-3";

            try {
                const res = await fetch(form.dataset.api, {
                    method: "POST",
                    body: new FormData(form)
                });

                const data = await res.json();
                const exito = data.status === "success" || String(data.message || "").toLowerCase().includes("cread");

                mensaje.className = exito
                    ? "text-green-600 font-semibold mb-3"
                    : "text-red-600 font-semibold mb-3";

                mensaje.innerHTML = `
                    ${data.message ?? "Operación realizada"}
                    ${exito ? `<br><button onclick="location.reload()" class="mt-3 px-4 py-2 rounded-xl bg-blue-600 text-white">Actualizar tabla</button>` : ""}
                `;

                if (exito) form.reset();

            } catch (error) {
                console.error(error);
                mensaje.className = "text-red-600 font-semibold mb-3";
                mensaje.innerHTML = "Error al procesar la solicitud";
            }
        };
    }
});

async function cargarSelects() {
    const selects = document.querySelectorAll("select[data-api]");

    for (const select of selects) {
        try {
            const api = window.BASE_URL + select.dataset.api;
            const res = await fetch(api);
            const json = await res.json();
            const data = json.data ?? json;

            const valueField = select.dataset.value;
            const labelField = select.dataset.label;

            select.innerHTML = `<option value="">Seleccione una opción</option>`;

            data.forEach(item => {
                select.innerHTML += `
                    <option value="${item[valueField]}">
                        ${item[labelField]}
                    </option>
                `;
            });

        } catch (error) {
            console.error(error);
            select.innerHTML = `<option value="">Error al cargar opciones</option>`;
        }
    }
}