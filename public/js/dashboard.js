(() => {
    "use strict";

    document.addEventListener("DOMContentLoaded", async () => {
        const contenedor = document.getElementById("dashboard-metricas");
        const estado = document.getElementById("dashboard-metricas-estado");
        const config = window.DASHBOARD_CONFIG || {};

        if (!contenedor || !config.apiResumen) return;

        try {
            const respuesta = await fetch(config.apiResumen);
            const texto = await respuesta.text();
            let json;

            try {
                json = JSON.parse(texto);
            } catch (error) {
                throw new Error("El servidor no devolvió JSON válido.");
            }

            if (!respuesta.ok || json.status === "error" || json.success === false) {
                throw new Error(json.message || "No se pudieron cargar las métricas.");
            }

            const metricas = [
                ["Profesores activos", "profesores"],
                ["Estudiantes activos", "estudiantes"],
                ["Materias activas", "materias"],
                ["Aulas activas", "aulas"],
                ["Grados activos", "grados"],
                ["Asignaciones activas", "asignaciones"],
                ["Horarios activos", "horarios"],
                ["Marcas recibidas hoy", "marcas_hoy"]
            ];

            contenedor.innerHTML = metricas.map(([etiqueta, campo]) => `
                <article class="app-card p-5">
                    <p class="text-sm" style="color: var(--color-muted);">${etiqueta}</p>
                    <p class="text-3xl font-bold mt-2">${Number(json.data?.[campo] || 0)}</p>
                </article>
            `).join("");
            estado.textContent = "";
        } catch (error) {
            console.error(error);
            estado.textContent = error.message || "No se pudieron cargar las métricas.";
            estado.className = "text-red-600 font-semibold mb-4";
        }
    });
})();
