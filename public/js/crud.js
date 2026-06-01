document.addEventListener("DOMContentLoaded", () => {
    const btnCrear = document.getElementById("btn-crear");
    const modal = document.getElementById("modal-crear");
    const cerrar = document.getElementById("cerrar-modal");
    const form = document.getElementById("form-crear");

    if (btnCrear && modal) {
        btnCrear.addEventListener("click", () => {
            modal.classList.remove("hidden");
        });
    }

    if (cerrar && modal) {
        cerrar.addEventListener("click", () => {
            modal.classList.add("hidden");
        });
    }

    if (form) {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();

            const api = form.dataset.api;
            const datos = new FormData(form);

            const res = await fetch(api, {
                method: "POST",
                body: datos
            });

            const data = await res.json();

            alert(data.message);

            if (data.status === "success") {
                location.reload();
            }
        });
    }
});