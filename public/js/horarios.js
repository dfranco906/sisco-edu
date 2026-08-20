(() => {
    "use strict";

    const config = window.HORARIOS_CONFIG || {};
    const diasSemana = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes"];
    let horarios = [];
    let grados = [];
    let asignaciones = [];

    const $ = (selector) => document.querySelector(selector);

    function escapar(valor) {
        return String(valor ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function horaCorta(valor) {
        return String(valor ?? "").slice(0, 5);
    }

    async function obtenerJson(url, opciones = {}) {
        const respuesta = await fetch(url, opciones);
        const texto = await respuesta.text();
        let json;

        try {
            json = JSON.parse(texto);
        } catch (error) {
            throw new Error("El servidor devolvió una respuesta no válida.");
        }

        if (!respuesta.ok && !json.message) {
            throw new Error("No se pudo completar la solicitud.");
        }

        return json;
    }

    function datosDe(json) {
        const datos = json?.data ?? json;
        return Array.isArray(datos) ? datos : [];
    }

    function opcionGrado(grado, deshabilitarSinAula = false) {
        const aulaTexto = grado.aula || grado.codigo_aula || "Sin aula";
        const disabled = deshabilitarSinAula && !grado.id_aula ? " disabled" : "";
        return `<option value="${escapar(grado.id_grado)}"${disabled}>${escapar(grado.nombre)} - ${escapar(aulaTexto)}</option>`;
    }

    function normalizarBusqueda(valor) {
        return String(valor ?? "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLocaleLowerCase("es")
            .trim();
    }

    function etiquetaAsignacion(asignacion) {
        return [
            asignacion.grado || "Sin grado",
            asignacion.materia || "Sin materia",
            asignacion.profesor || "Sin profesor",
            asignacion.anio_lectivo || "Sin año"
        ].join(" · ");
    }

    function compararAsignaciones(a, b) {
        const textoA = [a.grado, a.materia, a.profesor].join(" ");
        const textoB = [b.grado, b.materia, b.profesor].join(" ");
        const comparacion = textoA.localeCompare(textoB, "es", { sensitivity: "base", numeric: true });
        return comparacion || Number(b.anio_lectivo || 0) - Number(a.anio_lectivo || 0);
    }

    function cargarOpciones() {
        const gradosConAula = grados.filter((grado) => String(grado.id_aula ?? "").trim() !== "");
        const opcionesSemanal = gradosConAula.map((grado) => opcionGrado(grado)).join("");
        asignaciones.sort(compararAsignaciones);
        const opcionesAsignacion = asignaciones
            .map((asignacion) => `<option value="${escapar(asignacion.id_asignacion)}">${escapar(etiquetaAsignacion(asignacion))}</option>`)
            .join("");

        ["crear", "editar"].forEach((prefijo) => {
            const select = $(`#${prefijo}-id-asignacion`);
            const buscador = $(`#${prefijo}-buscar-asignacion`);
            select.innerHTML = asignaciones.length
                ? `<option value="">Seleccione una asignación</option>${opcionesAsignacion}`
                : '<option value="">No hay asignaciones activas disponibles</option>';
            select.disabled = asignaciones.length === 0;
            buscador.disabled = asignaciones.length === 0;
        });

        const selectorSemanal = $("#selector-grado-semanal");
        const filtroGrado = $("#filtro-horario-grado");
        const filtroDia = $("#filtro-horario-dia");
        selectorSemanal.innerHTML = `<option value="">Seleccione un grado</option>${opcionesSemanal}`;

        const opcionesLista = new Map();
        horarios.forEach((horario) => {
            if (horario.id_grado) {
                const grado = gradoPorId(horario.id_grado);
                const etiqueta = grado
                    ? `${grado.nombre} - ${grado.aula || grado.codigo_aula || "Sin aula"}`
                    : `${horario.grado} - ${horario.aula || "Sin aula"}`;
                opcionesLista.set(`id:${horario.id_grado}`, etiqueta);
            } else if (horario.grado) {
                opcionesLista.set(`legacy:${horario.grado}`, `${horario.grado} - ${horario.aula || "Aula antigua"}`);
            }
        });
        filtroGrado.innerHTML = `<option value="">Todos los grados</option>${[...opcionesLista.entries()]
            .sort((a, b) => a[1].localeCompare(b[1], "es", { numeric: true }))
            .map(([valor, etiqueta]) => `<option value="${escapar(valor)}">${escapar(etiqueta)}</option>`)
            .join("")}`;

        const ordenDias = [...diasSemana, "Sábado"];
        const diasDisponibles = [...new Set(horarios.map((horario) => horario.dia_semana).filter(Boolean))]
            .sort((a, b) => ordenDias.indexOf(a) - ordenDias.indexOf(b));
        filtroDia.innerHTML = `<option value="">Todos los días</option>${diasDisponibles
            .map((dia) => `<option value="${escapar(dia)}">${escapar(dia)}</option>`)
            .join("")}`;

        const primerGradoConHorario = horarios.find((horario) =>
            horario.id_grado && gradosConAula.some((grado) => String(grado.id_grado) === String(horario.id_grado))
        )?.id_grado;
        selectorSemanal.value = String(primerGradoConHorario || gradosConAula[0]?.id_grado || "");
    }

    function gradoPorId(idGrado) {
        return grados.find((grado) => String(grado.id_grado) === String(idGrado));
    }

    function asignacionPorId(idAsignacion) {
        return asignaciones.find((asignacion) =>
            String(asignacion.id_asignacion) === String(idAsignacion)
        );
    }

    function actualizarBotonGuardar(prefijo) {
        const asignacion = asignacionPorId($(`#${prefijo}-id-asignacion`).value);
        const botonGuardar = $(prefijo === "crear" ? "#guardar-horario" : "#actualizar-horario");
        const valido = Boolean(asignacion?.id_aula);
        botonGuardar.disabled = !valido;
        botonGuardar.classList.toggle("opacity-50", !valido);
        botonGuardar.classList.toggle("cursor-not-allowed", !valido);
    }

    function actualizarDetallesAsignacion(prefijo) {
        const asignacion = asignacionPorId($(`#${prefijo}-id-asignacion`).value);
        const valores = {
            profesor: asignacion?.profesor || "",
            materia: asignacion?.materia || "",
            grado: asignacion?.grado || ""
        };

        Object.entries(valores).forEach(([campo, valor]) => {
            const input = $(`#${prefijo}-${campo}`);
            input.value = valor;
            input.placeholder = asignacion ? "No disponible" : "Seleccione una asignación";
        });

        const inputAula = $(`#${prefijo}-id-aula`);
        inputAula.value = asignacion
            ? [asignacion.aula, asignacion.codigo_aula].filter(Boolean).join(" - ")
            : "";
        inputAula.placeholder = asignacion ? "Aula no disponible" : "Seleccione una asignación";
        actualizarBotonGuardar(prefijo);
    }

    function activarBuscadorAsignacion(prefijo) {
        const buscador = $(`#${prefijo}-buscar-asignacion`);
        const select = $(`#${prefijo}-id-asignacion`);
        const sugerencias = $(`#${prefijo}-asignacion-sugerencias`);

        buscador.setAttribute("aria-autocomplete", "list");
        buscador.setAttribute("aria-controls", sugerencias.id);
        buscador.setAttribute("aria-expanded", "false");

        const ocultar = () => {
            sugerencias.hidden = true;
            buscador.setAttribute("aria-expanded", "false");
        };

        const mostrar = () => {
            const consulta = normalizarBusqueda(buscador.value);
            const coincidencias = asignaciones
                .filter((asignacion) =>
                    !consulta || normalizarBusqueda(etiquetaAsignacion(asignacion)).includes(consulta)
                )
                .slice(0, 12);

            sugerencias.replaceChildren();
            if (!coincidencias.length) {
                const vacio = document.createElement("p");
                vacio.className = "app-select-suggestions-empty";
                vacio.textContent = "No hay asignaciones que coincidan";
                sugerencias.appendChild(vacio);
            } else {
                coincidencias.forEach((asignacion) => {
                    const boton = document.createElement("button");
                    boton.type = "button";
                    boton.className = "app-select-suggestion";
                    boton.setAttribute("role", "option");
                    boton.textContent = etiquetaAsignacion(asignacion);
                    boton.addEventListener("click", () => {
                        select.value = String(asignacion.id_asignacion);
                        buscador.value = "";
                        ocultar();
                        select.dispatchEvent(new Event("change", { bubbles: true }));
                    });
                    sugerencias.appendChild(boton);
                });
            }

            sugerencias.hidden = false;
            buscador.setAttribute("aria-expanded", "true");
        };

        buscador.addEventListener("focus", mostrar);
        buscador.addEventListener("input", mostrar);
        buscador.addEventListener("keydown", (evento) => {
            if (evento.key === "Escape") ocultar();
        });
        select.addEventListener("change", () => actualizarDetallesAsignacion(prefijo));
        document.addEventListener("click", (evento) => {
            if (evento.target !== buscador && !sugerencias.contains(evento.target)) ocultar();
        });
    }

    function tarjetaHorario(horario, compacta = false) {
        return `
            <article class="schedule-entry${compacta ? " is-compact" : ""}">
                <div class="schedule-entry-time">${escapar(horaCorta(horario.hora_inicio))} - ${escapar(horaCorta(horario.hora_fin))}</div>
                <div class="schedule-entry-subject">${escapar(horario.materia || "Sin materia")}</div>
                <div class="schedule-entry-meta">${escapar(horario.profesor || "Sin profesor")}</div>
                <div class="schedule-entry-room">${escapar(horario.aula || "Sin aula")}</div>
                ${Number(horario.permite_superposicion) === 1 ? '<span class="joint-class-badge">Clase conjunta</span>' : ""}
                <button type="button" class="btn btn-edit schedule-entry-edit" data-editar-horario="${escapar(horario.id_horario)}">Editar</button>
            </article>
        `;
    }

    function renderHorarioSemanal() {
        const idGrado = $("#selector-grado-semanal").value;
        const grado = gradoPorId(idGrado);
        const estado = $("#estado-horario-semanal");
        const desktop = $("#horario-semanal-desktop");
        const mobile = $("#horario-semanal-mobile");

        if (!grado) {
            estado.textContent = "Seleccioná un grado para ver su horario.";
            desktop.innerHTML = "";
            mobile.innerHTML = "";
            return;
        }

        const horariosGrado = horarios
            .filter((horario) => String(horario.id_grado) === String(idGrado))
            .sort((a, b) => String(a.hora_inicio).localeCompare(String(b.hora_inicio)));

        estado.innerHTML = `<strong>${escapar(grado.nombre)}</strong> · Aula <strong>${escapar(grado.aula || grado.codigo_aula || "sin asignar")}</strong>`;

        desktop.innerHTML = diasSemana.map((dia) => {
            const items = horariosGrado.filter((horario) => horario.dia_semana === dia);
            return `
                <section class="schedule-day-column">
                    <h4>${escapar(dia)}</h4>
                    <div class="schedule-day-entries">
                        ${items.length ? items.map((item) => tarjetaHorario(item)).join("") : '<p class="schedule-empty">Sin clases</p>'}
                    </div>
                </section>
            `;
        }).join("");

        mobile.innerHTML = diasSemana.map((dia) => {
            const items = horariosGrado.filter((horario) => horario.dia_semana === dia);
            return `
                <section class="schedule-mobile-day">
                    <h4>${escapar(dia)}</h4>
                    ${items.length ? items.map((item) => tarjetaHorario(item, true)).join("") : '<p class="schedule-empty">Sin clases</p>'}
                </section>
            `;
        }).join("");
    }

    function horariosFiltrados() {
        const texto = $("#filtro-horario-buscar").value.trim().toLowerCase();
        const filtroGrado = $("#filtro-horario-grado").value;
        const dia = $("#filtro-horario-dia").value;

        return horarios.filter((horario) => {
            const coincideTexto = !texto || [horario.materia, horario.profesor, horario.aula, horario.codigo_aula, horario.grado]
                .some((valor) => String(valor ?? "").toLowerCase().includes(texto));
            const coincideGrado = !filtroGrado
                || (filtroGrado.startsWith("id:") && String(horario.id_grado) === filtroGrado.slice(3))
                || (filtroGrado.startsWith("legacy:") && horario.grado === filtroGrado.slice(7));
            const coincideDia = !dia || horario.dia_semana === dia;
            return coincideTexto && coincideGrado && coincideDia;
        });
    }

    function renderTabla() {
        const tbody = $("#tabla-horarios-body");
        const datos = horariosFiltrados();

        if (!datos.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No hay horarios para los filtros seleccionados.</td></tr>';
            return;
        }

        tbody.innerHTML = datos.map((horario) => `
            <tr>
                <td data-label="Grado">${escapar(horario.grado)}</td>
                <td data-label="Día">${escapar(horario.dia_semana)}</td>
                <td data-label="Hora inicio">${escapar(horaCorta(horario.hora_inicio))}</td>
                <td data-label="Hora fin">${escapar(horaCorta(horario.hora_fin))}</td>
                <td data-label="Materia">${escapar(horario.materia)}</td>
                <td data-label="Profesor">${escapar(horario.profesor)}</td>
                <td data-label="Aula">
                    <span class="room-badge">${escapar(horario.aula || "—")}</span>
                    ${Number(horario.permite_superposicion) === 1 ? '<span class="joint-class-badge">Clase conjunta</span>' : ""}
                </td>
                <td data-label="Acciones">
                    <div class="table-actions">
                        <button type="button" data-editar-horario="${escapar(horario.id_horario)}"
                                class="btn btn-edit">Editar</button>
                        <button type="button" data-desactivar-horario="${escapar(horario.id_horario)}"
                                class="btn btn-warning">Desactivar</button>
                    </div>
                </td>
            </tr>
        `).join("");
    }

    function abrirModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove("hidden");
        document.body.classList.add("modal-open");
        modal.querySelector("select, input, button")?.focus();
    }

    function cerrarModal(id) {
        document.getElementById(id)?.classList.add("hidden");
        if (!document.querySelector(".app-modal:not(.hidden)")) {
            document.body.classList.remove("modal-open");
        }
    }

    function mostrarMensaje(elemento, mensaje, exito = false) {
        elemento.textContent = mensaje;
        elemento.className = `${exito ? "text-green-700" : "text-red-600"} font-semibold mb-4`;
    }

    function abrirEdicion(idHorario) {
        const horario = horarios.find((item) => String(item.id_horario) === String(idHorario));
        if (!horario) return;

        $("#editar-id-horario").value = horario.id_horario;
        $("#editar-id-asignacion").value = horario.id_asignacion || "";
        $("#editar-buscar-asignacion").value = "";
        actualizarDetallesAsignacion("editar");
        $("#editar-dia-semana").value = horario.dia_semana || "Lunes";
        $("#editar-hora-inicio").value = horaCorta(horario.hora_inicio);
        $("#editar-hora-fin").value = horaCorta(horario.hora_fin);
        $("#editar-permite-superposicion").checked = Number(horario.permite_superposicion) === 1;

        const mensaje = $("#mensaje-editar-horario");
        mensaje.textContent = horario.id_grado
            ? ""
            : "Este horario es antiguo y no coincide con una asignación activa. Seleccioná una asignación antes de guardar.";
        mensaje.className = horario.id_grado ? "mb-4" : "text-amber-700 font-semibold mb-4";
        abrirModal("modal-editar-horario");
    }

    async function recargarHorarios() {
        horarios = datosDe(await obtenerJson(config.apiHorarios));
        renderHorarioSemanal();
        renderTabla();
    }

    async function enviarFormulario(formulario, mensajeBox, modalId) {
        const boton = formulario.querySelector('button[type="submit"]');
        boton.disabled = true;
        mensajeBox.textContent = "Guardando...";
        mensajeBox.className = "text-blue-600 font-semibold mb-4";

        try {
            const json = await obtenerJson(formulario.dataset.api, {
                method: "POST",
                body: new FormData(formulario)
            });

            if (json.status !== "success") {
                mostrarMensaje(mensajeBox, json.message || "No se pudo guardar el horario.");
                return;
            }

            mostrarMensaje(mensajeBox, json.message, true);
            await recargarHorarios();
            setTimeout(() => cerrarModal(modalId), 500);

            if (modalId === "modal-crear-horario") {
                formulario.reset();
                $("#crear-buscar-asignacion").value = "";
            }
            actualizarDetallesAsignacion(modalId === "modal-crear-horario" ? "crear" : "editar");
        } catch (error) {
            console.error(error);
            mostrarMensaje(mensajeBox, error.message || "Error al procesar la solicitud.");
        } finally {
            actualizarDetallesAsignacion(modalId === "modal-crear-horario" ? "crear" : "editar");
        }
    }

    async function desactivarHorario(idHorario) {
        if (!confirm("¿Seguro que desea desactivar este horario?")) return;

        const datos = new FormData();
        datos.append("id_horario", idHorario);

        try {
            const json = await obtenerJson(config.apiDesactivar, { method: "POST", body: datos });
            if (json.status !== "success") {
                alert(json.message || "No se pudo desactivar el horario.");
                return;
            }
            await recargarHorarios();
        } catch (error) {
            console.error(error);
            alert("Error al desactivar el horario.");
        }
    }

    function activarEventos() {
        $("#btn-crear-horario").addEventListener("click", () => abrirModal("modal-crear-horario"));
        activarBuscadorAsignacion("crear");
        activarBuscadorAsignacion("editar");
        $("#selector-grado-semanal").addEventListener("change", renderHorarioSemanal);

        [$("#filtro-horario-buscar"), $("#filtro-horario-grado"), $("#filtro-horario-dia")]
            .forEach((control) => control.addEventListener(control.tagName === "INPUT" ? "input" : "change", renderTabla));

        $("#limpiar-filtros-horario").addEventListener("click", () => {
            $("#filtro-horario-buscar").value = "";
            $("#filtro-horario-grado").value = "";
            $("#filtro-horario-dia").value = "";
            renderTabla();
        });

        $("#form-crear-horario").addEventListener("submit", (evento) => {
            evento.preventDefault();
            enviarFormulario(evento.currentTarget, $("#mensaje-crear-horario"), "modal-crear-horario");
        });

        $("#form-editar-horario").addEventListener("submit", (evento) => {
            evento.preventDefault();
            enviarFormulario(evento.currentTarget, $("#mensaje-editar-horario"), "modal-editar-horario");
        });

        document.addEventListener("click", (evento) => {
            const cerrar = evento.target.closest("[data-close-modal]");
            const editar = evento.target.closest("[data-editar-horario]");
            const desactivar = evento.target.closest("[data-desactivar-horario]");

            if (cerrar) cerrarModal(cerrar.dataset.closeModal);
            if (editar) abrirEdicion(editar.dataset.editarHorario);
            if (desactivar) desactivarHorario(desactivar.dataset.desactivarHorario);

            if (evento.target.classList.contains("app-modal")) {
                cerrarModal(evento.target.id);
            }
        });

        document.addEventListener("keydown", (evento) => {
            if (evento.key === "Escape") {
                document.querySelectorAll(".app-modal:not(.hidden)").forEach((modal) => cerrarModal(modal.id));
            }
        });
    }

    async function iniciar() {
        activarEventos();

        try {
            const [jsonHorarios, jsonGrados, jsonAsignaciones] = await Promise.all([
                obtenerJson(config.apiHorarios),
                obtenerJson(config.apiGrados),
                obtenerJson(config.apiAsignaciones)
            ]);

            horarios = datosDe(jsonHorarios);
            grados = datosDe(jsonGrados);
            asignaciones = datosDe(jsonAsignaciones);

            cargarOpciones();
            actualizarDetallesAsignacion("crear");
            actualizarDetallesAsignacion("editar");
            renderHorarioSemanal();
            renderTabla();
        } catch (error) {
            console.error(error);
            $("#estado-horario-semanal").textContent = "No se pudieron cargar los datos de horarios.";
            $("#tabla-horarios-body").innerHTML = '<tr><td colspan="8" class="text-center text-red-600">Error al cargar horarios.</td></tr>';
        }
    }

    document.addEventListener("DOMContentLoaded", iniciar);
})();
