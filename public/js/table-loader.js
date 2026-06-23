async function cargarTabla(api, columnas, filtros = {}) {
    const tbody = document.getElementById("tabla-body");
    const filtrosBox = document.getElementById("filtros-tabla");

    let datosOriginales = [];

    tbody.innerHTML = `<tr><td colspan="${columnas.length}" class="p-4 text-center">Cargando datos...</td></tr>`;

    try {
        const res = await fetch(api);
        const json = await res.json();
        datosOriginales = json.data ?? json;

        crearFiltros(datosOriginales, filtros);
        render(datosOriginales);

    } catch (error) {
        console.error(error);
        tbody.innerHTML = `<tr><td colspan="${columnas.length}" class="p-4 text-center text-red-600">Error al cargar datos</td></tr>`;
    }

    function render(datos) {
        tbody.innerHTML = "";

        if (!Array.isArray(datos) || datos.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${columnas.length}" class="p-4 text-center">No hay datos registrados</td></tr>`;
            return;
        }

        datos.forEach(item => {
            const idRegistro = window.ID_CAMPO ? item[window.ID_CAMPO] : null;
            let fila = `<tr class="border-b hover:bg-gray-50">`;

            columnas.forEach(col => {
                fila += `<td class="p-3">${item[col] ?? ""}</td>`;
            });

            fila += `
    <td class="p-3 flex gap-2">
        ${window.API_ACTUALIZAR && idRegistro ? `
        <button onclick='editarRegistro(${JSON.stringify(item)})'
        class="px-3 py-1 rounded-lg bg-blue-100 text-blue-700 text-xs font-semibold">
            Editar
        </button>` : ""}
        ${window.API_DESACTIVAR && idRegistro ? `
        <button onclick='desactivarRegistro(${JSON.stringify(idRegistro)})'
        class="px-3 py-1 rounded-lg bg-amber-100 text-amber-700 text-xs font-semibold">
            Desactivar
        </button>` : ""}
        ${window.API_RESTAURAR && idRegistro ? `
        <button onclick='restaurarRegistro(${JSON.stringify(idRegistro)})'
        class="px-3 py-1 rounded-lg bg-green-100 text-green-700 text-xs font-semibold">
            Restaurar
        </button>` : ""}
        ${window.API_ELIMINAR && idRegistro ? `
        <button onclick='eliminarRegistro(${JSON.stringify(idRegistro)})'
        class="px-3 py-1 rounded-lg bg-red-600 text-white text-xs font-semibold">
            Eliminar
        </button>` : ""}
        ${item.id_estudiante ? `
        <button onclick="descargarYGuardarTemplate(${item.id_estudiante}, 'estudiante')"
        class="btn-huella px-3 py-1 rounded-lg bg-green-100 text-green-700 text-xs font-semibold">
            Huella
        </button>` : ""}

        ${item.id_profesor ? `
        <button onclick="descargarYGuardarTemplate(${item.id_profesor}, 'profesor')"
        class="btn-huella px-3 py-1 rounded-lg bg-green-100 text-green-700 text-xs font-semibold">
          Huella
        </button>` : ""}
            </td>`;

            fila += `</tr>`;
            tbody.innerHTML += fila;
        });
    }

    function crearFiltros(datos, filtros) {
        if (!filtrosBox) return;

        filtrosBox.innerHTML = "";

        if (filtros.buscar) {
            filtrosBox.innerHTML += `
                <input id="buscar-tabla" 
                       type="text" 
                       placeholder="${filtros.placeholder ?? 'Buscar...'}"
                       class="border rounded-xl px-4 py-2 w-full md:w-72">
            `;
        }

        if (filtros.selects) {
            filtros.selects.forEach(f => {
                const valores = [...new Set(datos.map(i => i[f.campo]).filter(v => v !== null && v !== ""))];

                let select = `
                    <select data-campo="${f.campo}" class="filtro-select border rounded-xl px-4 py-2">
                        <option value="">${f.label}</option>
                `;

                valores.forEach(v => {
                    select += `<option value="${v}">${v}</option>`;
                });

                select += `</select>`;
                filtrosBox.innerHTML += select;
            });
        }

        filtrosBox.innerHTML += `
            <button id="limpiar-filtros" class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200">
                Limpiar filtros
            </button>
        `;

        if (window.URL_DESACTIVADOS) {
            filtrosBox.innerHTML += `
                <a href="${window.URL_DESACTIVADOS}" class="px-4 py-2 rounded-xl bg-slate-700 text-white hover:bg-slate-800">
                    Desactivados
                </a>
            `;
        }

        activarEventos();
    }

    function activarEventos() {
        const buscador = document.getElementById("buscar-tabla");
        const selects = document.querySelectorAll(".filtro-select");
        const limpiar = document.getElementById("limpiar-filtros");

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
        }

        if (buscador) buscador.addEventListener("input", aplicarFiltros);
        selects.forEach(s => s.addEventListener("change", aplicarFiltros));

        limpiar.addEventListener("click", () => {
            if (buscador) buscador.value = "";
            selects.forEach(s => s.value = "");
            render(datosOriginales);
        });
    }
}
