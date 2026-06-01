async function cargarTabla(api, columnas) {
    const tbody = document.getElementById("tabla-body");
    const filtroBotones = document.getElementById("filtro-grado-botones");

    tbody.innerHTML = `<tr><td colspan="${columnas.length}" class="p-4 text-center">Cargando datos...</td></tr>`;

    try {
        const res = await fetch(api);
        const json = await res.json();
        const data = json.data ?? json;

        function render(datos) {
            tbody.innerHTML = "";

            if (!Array.isArray(datos) || datos.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${columnas.length}" class="p-4 text-center">No hay datos registrados</td></tr>`;
                return;
            }

            datos.forEach(item => {
                let fila = `<tr class="border-b hover:bg-gray-50">`;

                columnas.forEach(col => {
                    fila += `<td class="p-3">${item[col] ?? ""}</td>`;
                });

                fila += `</tr>`;
                tbody.innerHTML += fila;
            });
        }

        if (filtroBotones && Array.isArray(data)) {
            const grados = [...new Set(data.map(i => i.grado).filter(Boolean))];

            filtroBotones.innerHTML = "";

            const btnTodos = document.createElement("button");
            btnTodos.textContent = "Todos";
            btnTodos.className = "px-5 py-2 rounded-xl bg-blue-600 text-white font-semibold";
            btnTodos.onclick = () => render(data);
            filtroBotones.appendChild(btnTodos);

            grados.forEach(grado => {
                const btn = document.createElement("button");
                btn.textContent = grado;
                btn.className = "px-5 py-2 rounded-xl bg-gray-100 hover:bg-blue-100 font-semibold";
                btn.onclick = () => render(data.filter(i => i.grado === grado));
                filtroBotones.appendChild(btn);
            });
        }

        render(data);

    } catch (error) {
        console.error(error);
        tbody.innerHTML = `<tr><td colspan="${columnas.length}" class="p-4 text-center text-red-600">Error al cargar datos</td></tr>`;
    }
}