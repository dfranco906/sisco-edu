async function cargarTabla(api, columnas) {
    const tbody = document.getElementById("tabla-body");
    tbody.innerHTML = `<tr><td colspan="${columnas.length}" class="p-4 text-center">Cargando...</td></tr>`;

    try {
        const res = await fetch(api);
        const json = await res.json();

        const data = json.data ?? json;

        tbody.innerHTML = "";

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${columnas.length}" class="p-4 text-center">No hay datos registrados</td></tr>`;
            return;
        }

        data.forEach(item => {
            let fila = "<tr class='border-b hover:bg-gray-50'>";

            columnas.forEach(col => {
                fila += `<td class="p-3">${item[col] ?? ""}</td>`;
            });

            fila += "</tr>";
            tbody.innerHTML += fila;
        });

    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="${columnas.length}" class="p-4 text-center text-red-600">Error al cargar datos</td></tr>`;
    }
}