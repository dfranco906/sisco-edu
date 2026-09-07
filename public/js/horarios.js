(() => {
    "use strict";

    const config = window.HORARIOS_CONFIG || {};
    const diasSemana = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes"];
    const bloquesRegularesNivelMedio = [
        { inicio: "07:00", fin: "07:40" },
        { inicio: "07:40", fin: "08:20" },
        { inicio: "08:20", fin: "09:00" },
        { inicio: "09:00", fin: "09:40" },
        { inicio: "09:40", fin: "10:20" },
        { inicio: "10:20", fin: "10:50", receso: true },
        { inicio: "10:50", fin: "11:30" },
        { inicio: "11:30", fin: "12:10" },
        { inicio: "12:10", fin: "12:50" },
        { inicio: "12:50", fin: "13:30" }
    ];
    const bloquesRegularesTercerCiclo = [
        { inicio: "07:00", fin: "07:40" },
        { inicio: "07:40", fin: "08:20" },
        { inicio: "08:20", fin: "09:00" },
        { inicio: "09:00", fin: "09:40" },
        { inicio: "09:40", fin: "10:10", receso: true },
        { inicio: "10:10", fin: "10:50" },
        { inicio: "10:50", fin: "11:30" },
        { inicio: "11:30", fin: "12:10" },
        { inicio: "12:10", fin: "12:50" },
        { inicio: "12:50", fin: "13:30" }
    ];
    const bloquesExtra = [
        { inicio: "13:30", fin: "14:10", extra: true },
        { inicio: "14:10", fin: "14:50", extra: true },
        { inicio: "14:50", fin: "15:30", extra: true },
        { inicio: "15:30", fin: "16:00", extra: true }
    ];
    let horarios = [];
    let grados = [];
    let asignaciones = [];
    let ultimoGradoSemanal = "";

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

    function horaEnMinutos(valor) {
        const [hora, minuto] = horaCorta(valor).split(":").map(Number);
        return Number.isFinite(hora) && Number.isFinite(minuto) ? hora * 60 + minuto : 0;
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
            .replace(/[^\p{L}\p{N}]+/gu, " ")
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

    function completarTextoHorario(horario) {
        const asignacion = asignacionPorId(horario.id_asignacion);
        const texto = (valor, respaldo) => String(valor ?? "").trim() || respaldo;
        return {
            ...horario,
            materia: texto(horario.materia || asignacion?.materia, "Materia no disponible"),
            profesor: texto(horario.profesor || asignacion?.profesor, "Profesor no disponible"),
            grado: texto(horario.grado || asignacion?.grado, "Grado no disponible")
        };
    }

    function controlesHorario(prefijo) {
        const formulario = $(`#form-${prefijo}-horario`);
        return {
            formulario,
            dia: formulario.querySelector('[name="dia_semana"]'),
            inicio: $(`#${prefijo}-hora-inicio`),
            fin: $(`#${prefijo}-hora-fin`),
            extra: $(`#${prefijo}-horario-extra`),
            bloque: $(`#${prefijo}-bloque-horario`)
        };
    }

    function actualizarOpcionesBloque(prefijo) {
        const controles = controlesHorario(prefijo);
        const asignacion = asignacionPorId($(`#${prefijo}-id-asignacion`).value);
        const bloquesRegulares = bloquesRegularesParaGrado(asignacion?.grado);
        const bloques = [
            ...bloquesRegulares.filter((bloque) => !bloque.receso),
            ...(controles.extra.checked ? bloquesExtra : [])
        ];
        const valorActual = `${horaCorta(controles.inicio.value)}|${horaCorta(controles.fin.value)}`;

        controles.bloque.innerHTML = [
            '<option value="">Elegí una hora cátedra</option>',
            ...bloques.map((bloque) => {
                const valor = `${bloque.inicio}|${bloque.fin}`;
                const sufijo = bloque.extra ? " · Turno tarde" : "";
                return `<option value="${valor}">${bloque.inicio} – ${bloque.fin}${sufijo}</option>`;
            })
        ].join("");
        controles.bloque.value = [...controles.bloque.options].some((opcion) => opcion.value === valorActual)
            ? valorActual
            : "";
    }

    function aplicarBloqueSugerido(prefijo) {
        const controles = controlesHorario(prefijo);
        if (!controles.bloque.value) return;
        const [inicio, fin] = controles.bloque.value.split("|");
        controles.inicio.value = inicio;
        controles.fin.value = fin;
    }

    function actualizarModoExtra(prefijo, completar = false) {
        const controles = controlesHorario(prefijo);
        if (completar && controles.extra.checked && horaEnMinutos(controles.inicio.value) < horaEnMinutos("13:30")) {
            controles.inicio.value = bloquesExtra[0].inicio;
            controles.fin.value = bloquesExtra[0].fin;
        } else if (completar && !controles.extra.checked && horaEnMinutos(controles.inicio.value) >= horaEnMinutos("13:30")) {
            controles.inicio.value = "";
            controles.fin.value = "";
        }
        actualizarOpcionesBloque(prefijo);
    }

    function nivelDelGrado(nombre) {
        return normalizarBusqueda(nombre).match(/[0-9]+/)?.[0] || "";
    }

    function bloquesRegularesParaGrado(nombre) {
        return ["7", "8", "9"].includes(nivelDelGrado(nombre))
            ? bloquesRegularesTercerCiclo
            : bloquesRegularesNivelMedio;
    }

    function asignacionesCorrespondientes(prefijo) {
        const asignacionActual = asignacionPorId($(`#${prefijo}-id-asignacion`).value);
        if (!asignacionActual) return [];

        return asignaciones.filter((asignacionCandidata) =>
            String(asignacionActual.id_grado) !== String(asignacionCandidata.id_grado)
            && String(asignacionActual.id_profesor) === String(asignacionCandidata.id_profesor)
            && ($(`#${prefijo}-materias-distintas`).checked
                || String(asignacionActual.id_materia) === String(asignacionCandidata.id_materia))
            && String(asignacionActual.anio_lectivo) === String(asignacionCandidata.anio_lectivo)
        ).sort((a, b) => String(a.grado || "").localeCompare(
            String(b.grado || ""),
            "es",
            { numeric: true, sensitivity: "base" }
        ));
    }

    function idsClaseConjunta(prefijo) {
        return $(`#${prefijo}-id-asignacion-conjunta`).value
            .split(",")
            .map((id) => id.trim())
            .filter(Boolean);
    }

    function guardarIdsClaseConjunta(prefijo, ids) {
        $(`#${prefijo}-id-asignacion-conjunta`).value = [...new Set(ids.map(String))].join(",");
    }

    function renderizarOpcionesClaseConjunta(prefijo) {
        const contenedor = $(`#${prefijo}-opciones-clase-conjunta`);
        const termino = normalizarBusqueda($(`#${prefijo}-buscar-clase-conjunta`).value);
        const seleccionadas = new Set(idsClaseConjunta(prefijo));
        const candidatos = asignacionesCorrespondientes(prefijo);
        const visibles = candidatos.filter((item) =>
            !termino || normalizarBusqueda(
                [item.grado, item.materia, item.profesor, item.anio_lectivo].filter(Boolean).join(" ")
            ).includes(termino)
        );

        if (!candidatos.length) {
            contenedor.innerHTML = '<p class="app-help">No hay asignaciones compatibles. Para buscar otras materias del mismo profesor y año, activá Incluir materias distintas.</p>';
            return;
        }
        if (!visibles.length) {
            contenedor.innerHTML = '<p class="app-help">No hay cursos que coincidan con la búsqueda.</p>';
            return;
        }

        contenedor.innerHTML = visibles.map((item) => {
            const id = String(item.id_asignacion);
            return `
                <label class="schedule-joint-option">
                    <input type="checkbox" value="${escapar(id)}" data-clase-conjunta-opcion
                           ${seleccionadas.has(id) ? "checked" : ""}>
                    <span>
                        <strong>${escapar(item.grado || "Grado no disponible")}</strong>
                        <small>${escapar(item.materia)} · ${escapar(item.profesor)} · ${escapar(item.anio_lectivo)}</small>
                    </span>
                </label>
            `;
        }).join("");
    }

    function actualizarAyudaClaseConjunta(prefijo) {
        const idsAsignaciones = $(`#${prefijo}-id-asignacion-conjunta`).value.split(",").filter(Boolean);
        const asignacionesSeleccionadas = idsAsignaciones.map(asignacionPorId).filter(Boolean);
        const ayuda = $(`#${prefijo}-clase-conjunta-ayuda`);
        const controles = controlesHorario(prefijo);
        const horariosExistentes = asignacionesSeleccionadas.filter((asignacion) => horarios.find((item) =>
            String(item.id_asignacion) === String(asignacion.id_asignacion)
            && item.dia_semana === controles.dia.value
            && horaCorta(item.hora_inicio) === horaCorta(controles.inicio.value)
            && horaCorta(item.hora_fin) === horaCorta(controles.fin.value)
        )).length;
        const nombres = asignacionesSeleccionadas.map((item) => `${item.grado} (${item.materia})`).join(", ");

        ayuda.textContent = !asignacionesSeleccionadas.length
            ? "Seleccioná los cursos que compartirán esta clase."
            : horariosExistentes === asignacionesSeleccionadas.length
                ? `Las tarjetas existentes de ${nombres} quedarán vinculadas y sincronizadas.`
                : `La misma clase se creará o sincronizará automáticamente en ${nombres}.`;
        actualizarBotonGuardar(prefijo);
    }

    function actualizarClaseConjunta(prefijo, horarioActual = null) {
        const checkbox = $(`#${prefijo}-permite-superposicion`);
        const panel = $(`#${prefijo}-clase-conjunta-panel`);
        const buscador = $(`#${prefijo}-buscar-clase-conjunta`);
        const opciones = $(`#${prefijo}-opciones-clase-conjunta`);
        const ayuda = $(`#${prefijo}-clase-conjunta-ayuda`);
        panel.hidden = !checkbox.checked;

        if (!checkbox.checked) {
            $(`#${prefijo}-materias-distintas`).checked = false;
            guardarIdsClaseConjunta(prefijo, []);
            buscador.value = "";
            opciones.innerHTML = "";
            ayuda.textContent = "";
            actualizarBotonGuardar(prefijo);
            return;
        }

        if (horarioActual) {
            const principal = asignacionPorId(horarioActual.id_asignacion);
            const miembros = horarios.filter((item) =>
                (horarioActual.id_grupo_clase_conjunta && String(item.id_grupo_clase_conjunta) === String(horarioActual.id_grupo_clase_conjunta))
                || String(item.id_horario) === String(horarioActual.id_horario_vinculado)
            );
            $(`#${prefijo}-materias-distintas`).checked = miembros.some((item) => {
                const otra = asignacionPorId(item.id_asignacion);
                return otra && principal && String(otra.id_materia) !== String(principal.id_materia);
            });
        }
        const candidatos = asignacionesCorrespondientes(prefijo);
        if (!candidatos.length) {
            guardarIdsClaseConjunta(prefijo, []);
            renderizarOpcionesClaseConjunta(prefijo);
            ayuda.textContent = "Seleccioná asignaciones de otros grados con el mismo profesor y año lectivo.";
            actualizarBotonGuardar(prefijo);
            return;
        }

        let idsAsignacionesGuardadas = [];
        if (horarioActual?.id_grupo_clase_conjunta) {
            idsAsignacionesGuardadas = horarios
                .filter((item) =>
                    String(item.id_grupo_clase_conjunta) === String(horarioActual.id_grupo_clase_conjunta)
                    && String(item.id_horario) !== String(horarioActual.id_horario)
                )
                .map((item) => String(item.id_asignacion));
        } else if (horarioActual?.id_horario_vinculado) {
            const horarioGuardado = horarios.find((item) => String(item.id_horario) === String(horarioActual.id_horario_vinculado));
            if (horarioGuardado) idsAsignacionesGuardadas = [String(horarioGuardado.id_asignacion)];
        } else {
            const idsCandidatos = new Set(candidatos.map((item) => String(item.id_asignacion)));
            idsAsignacionesGuardadas = idsClaseConjunta(prefijo).filter((id) => idsCandidatos.has(id));
        }

        guardarIdsClaseConjunta(prefijo, idsAsignacionesGuardadas);
        renderizarOpcionesClaseConjunta(prefijo);
        actualizarAyudaClaseConjunta(prefijo);
    }

    function actualizarBotonGuardar(prefijo) {
        const asignacion = asignacionPorId($(`#${prefijo}-id-asignacion`).value);
        const botonGuardar = $(prefijo === "crear" ? "#guardar-horario" : "#actualizar-horario");
        const conjunta = $(`#${prefijo}-permite-superposicion`).checked;
        const vinculo = $(`#${prefijo}-id-asignacion-conjunta`).value;
        const valido = Boolean(asignacion?.id_aula) && (!conjunta || Boolean(vinculo));
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
        if ($(`#${prefijo}-permite-superposicion`).checked) {
            actualizarClaseConjunta(prefijo);
        }
        actualizarOpcionesBloque(prefijo);
        actualizarBotonGuardar(prefijo);
    }

    function activarBuscadorAsignacion(prefijo) {
        const buscador = $(`#${prefijo}-buscar-asignacion`);
        const select = $(`#${prefijo}-id-asignacion`);
        const sugerencias = $(`#${prefijo}-asignacion-sugerencias`);

        select.dataset.searchRequired = select.required ? "1" : "0";
        select.required = false;
        select.tabIndex = -1;
        select.setAttribute("aria-hidden", "true");
        select.classList.add("app-select-source-hidden");
        buscador.required = select.dataset.searchRequired === "1";
        buscador.setAttribute("role", "combobox");
        buscador.setAttribute("aria-haspopup", "listbox");
        buscador.setAttribute("aria-autocomplete", "list");
        buscador.setAttribute("aria-controls", sugerencias.id);
        buscador.setAttribute("aria-expanded", "false");
        let indiceActivo = -1;

        const actualizarValidez = () => {
            const valida = select.dataset.searchRequired !== "1" || Boolean(select.value);
            buscador.setCustomValidity(valida ? "" : "Seleccioná una asignación de la lista.");
            buscador.setAttribute("aria-invalid", valida ? "false" : "true");
        };

        const sincronizarBuscador = () => {
            const asignacion = asignacionPorId(select.value);
            buscador.value = asignacion ? etiquetaAsignacion(asignacion) : "";
            actualizarValidez();
        };

        const ocultar = () => {
            sugerencias.hidden = true;
            buscador.setAttribute("aria-expanded", "false");
            buscador.removeAttribute("aria-activedescendant");
            indiceActivo = -1;
        };

        const activarSugerencia = (indice) => {
            const botones = [...sugerencias.querySelectorAll(".app-select-suggestion")];
            if (!botones.length) return;

            indiceActivo = (indice + botones.length) % botones.length;
            botones.forEach((boton, posicion) => {
                const activa = posicion === indiceActivo;
                boton.classList.toggle("is-active", activa);
                boton.setAttribute("aria-selected", activa ? "true" : "false");
            });

            const botonActivo = botones[indiceActivo];
            buscador.setAttribute("aria-activedescendant", botonActivo.id);
            botonActivo.scrollIntoView({ block: "nearest" });
        };

        const mostrar = () => {
            const consulta = normalizarBusqueda(buscador.value);
            // El panel ya tiene scroll: conservar todas las coincidencias evita
            // ocultar materias de grados con más de doce asignaciones.
            const coincidencias = asignaciones.filter((asignacion) =>
                !consulta || normalizarBusqueda(etiquetaAsignacion(asignacion)).includes(consulta)
            );

            sugerencias.replaceChildren();
            buscador.removeAttribute("aria-activedescendant");
            indiceActivo = -1;
            if (!coincidencias.length) {
                const vacio = document.createElement("p");
                vacio.className = "app-select-suggestions-empty";
                vacio.textContent = "No hay asignaciones que coincidan";
                sugerencias.appendChild(vacio);
            } else {
                coincidencias.forEach((asignacion, indice) => {
                    const boton = document.createElement("button");
                    boton.type = "button";
                    boton.className = "app-select-suggestion";
                    boton.id = `${sugerencias.id}-opcion-${indice}`;
                    boton.setAttribute("role", "option");
                    boton.setAttribute("aria-selected", "false");
                    boton.textContent = etiquetaAsignacion(asignacion);
                    boton.addEventListener("mouseenter", () => activarSugerencia(indice));
                    boton.addEventListener("click", () => {
                        select.value = String(asignacion.id_asignacion);
                        buscador.value = etiquetaAsignacion(asignacion);
                        actualizarValidez();
                        ocultar();
                        select.dispatchEvent(new Event("change", { bubbles: true }));
                        buscador.focus();
                    });
                    sugerencias.appendChild(boton);
                });
            }

            sugerencias.hidden = false;
            buscador.setAttribute("aria-expanded", "true");
        };

        buscador.addEventListener("focus", mostrar);
        buscador.addEventListener("input", () => {
            select.value = "";
            actualizarValidez();
            actualizarDetallesAsignacion(prefijo);
            mostrar();
        });
        buscador.addEventListener("keydown", (evento) => {
            if (evento.key === "Escape") {
                evento.stopPropagation();
                ocultar();
                return;
            }

            if (evento.key === "ArrowDown" || evento.key === "ArrowUp") {
                evento.preventDefault();
                if (sugerencias.hidden) mostrar();
                const cantidad = sugerencias.querySelectorAll(".app-select-suggestion").length;
                if (!cantidad) return;
                const siguiente = indiceActivo < 0
                    ? (evento.key === "ArrowDown" ? 0 : cantidad - 1)
                    : indiceActivo + (evento.key === "ArrowDown" ? 1 : -1);
                activarSugerencia(siguiente);
                return;
            }

            if (evento.key === "Enter" && indiceActivo >= 0) {
                evento.preventDefault();
                sugerencias.querySelectorAll(".app-select-suggestion")[indiceActivo]?.click();
            }
        });
        select.addEventListener("change", () => {
            sincronizarBuscador();
            actualizarDetallesAsignacion(prefijo);
        });
        select.form?.addEventListener("reset", () => {
            setTimeout(() => {
                sincronizarBuscador();
                ocultar();
            }, 0);
        });
        document.addEventListener("click", (evento) => {
            if (evento.target !== buscador && !sugerencias.contains(evento.target)) ocultar();
        });
        sincronizarBuscador();
    }

    function agruparHorariosContiguos(items) {
        return [...items]
            .sort((a, b) =>
                String(a.dia_semana).localeCompare(String(b.dia_semana), "es")
                || String(a.hora_inicio).localeCompare(String(b.hora_inicio))
            )
            .reduce((grupos, horario) => {
                const anterior = grupos.at(-1);
                const mismaClase = anterior
                    && anterior.dia_semana === horario.dia_semana
                    && String(anterior.id_asignacion) === String(horario.id_asignacion)
                    && horaCorta(anterior.hora_fin) === horaCorta(horario.hora_inicio)
                    && horaCorta(horario.hora_inicio) !== "13:30"
                    && Number(anterior.permite_superposicion) === Number(horario.permite_superposicion);

                if (mismaClase) {
                    anterior.hora_fin = horario.hora_fin;
                    anterior._ids.push(horario.id_horario);
                    return grupos;
                }

                grupos.push({ ...horario, _ids: [horario.id_horario] });
                return grupos;
            }, []);
    }

    function cantidadHorasCatedra(horario) {
        const inicio = horaEnMinutos(horario.hora_inicio);
        const fin = horaEnMinutos(horario.hora_fin);
        const bloquesRegulares = bloquesRegularesParaGrado(horario.grado);
        return [...bloquesRegulares, ...bloquesExtra]
            .filter((bloque) =>
                !bloque.receso
                && inicio < horaEnMinutos(bloque.fin)
                && fin > horaEnMinutos(bloque.inicio)
            ).length;
    }

    function tarjetaHorario(horario, compacta = false) {
        const horasCatedra = cantidadHorasCatedra(horario);
        const materia = String(horario.materia || "Materia no disponible").trim();
        const profesor = String(horario.profesor || "Profesor no disponible").trim();
        const idHorarioEditable = horario._ids?.[0] || horario.id_horario;
        const accion = `<button type="button" class="btn btn-edit schedule-entry-edit" data-editar-horario="${escapar(idHorarioEditable)}">Editar</button>`;

        return `
            <article class="schedule-entry${compacta ? " is-compact" : ""}"
                     aria-label="${escapar(`${materia}, ${profesor}`)}">
                <div class="schedule-entry-subject" title="${escapar(materia)}">${escapar(materia)}</div>
                <div class="schedule-entry-meta" title="${escapar(profesor)}">${escapar(profesor)}</div>
                <div class="schedule-entry-footer">
                    ${horasCatedra > 1 ? `<span class="schedule-hours-badge">${horasCatedra} HC</span>` : ""}
                    ${Number(horario.permite_superposicion) === 1 ? '<span class="joint-class-badge">Conjunta</span>' : ""}
                    ${accion}
                </div>
            </article>
        `;
    }

    function posicionEnBloques(horario, bloques) {
        const inicio = horaEnMinutos(horario.hora_inicio);
        const fin = horaEnMinutos(horario.hora_fin);
        const indices = bloques
            .map((bloque, indice) => ({ bloque, indice }))
            .filter(({ bloque }) =>
                inicio < horaEnMinutos(bloque.fin)
                && fin > horaEnMinutos(bloque.inicio)
            )
            .map(({ indice }) => indice);

        return indices.length
            ? { inicio: indices[0], cantidad: indices.at(-1) - indices[0] + 1 }
            : null;
    }

    function renderHorarioSemanal() {
        const idGrado = $("#selector-grado-semanal").value;
        const grado = gradoPorId(idGrado);
        const estado = $("#estado-horario-semanal");
        const desktop = $("#horario-semanal-desktop");
        const mobile = $("#horario-semanal-mobile");
        const controlExtra = $("#mostrar-horas-extra");

        if (!grado) {
            estado.textContent = "Seleccioná un grado para ver su horario.";
            desktop.innerHTML = "";
            mobile.innerHTML = "";
            return;
        }

        const horariosGrado = agruparHorariosContiguos(
            horarios.filter((horario) => String(horario.id_grado) === String(idGrado))
        );
        const tieneHorasExtra = horariosGrado.some((horario) =>
            horaEnMinutos(horario.hora_fin) > horaEnMinutos("13:30")
        );
        if (ultimoGradoSemanal !== String(idGrado)) {
            controlExtra.checked = tieneHorasExtra;
            ultimoGradoSemanal = String(idGrado);
        }

        const mostrarExtra = controlExtra.checked;
        const bloquesRegulares = bloquesRegularesParaGrado(grado.nombre);
        const bloques = [...bloquesRegulares, ...(mostrarExtra ? bloquesExtra : [])];
        const dias = horariosGrado.some((horario) => horario.dia_semana === "Sábado")
            ? [...diasSemana, "Sábado"]
            : diasSemana;
        const extrasOcultos = horariosGrado.filter((horario) =>
            horaEnMinutos(horario.hora_fin) > horaEnMinutos("13:30")
        ).length;
        const avisoExtra = !mostrarExtra && extrasOcultos
            ? `<span class="schedule-hidden-extra">${extrasOcultos} bloque${extrasOcultos === 1 ? "" : "s"} del turno tarde oculto${extrasOcultos === 1 ? "" : "s"}</span>`
            : "";

        estado.innerHTML = `
            <span class="schedule-course-summary">
                <strong>${escapar(grado.nombre)}</strong>
                <span>Aula ${escapar(grado.aula || grado.codigo_aula || "sin asignar")}</span>
                ${avisoExtra}
            </span>
        `;

        const cabeceras = dias.map((dia, indice) =>
            `<div class="schedule-grid-day" style="grid-column: ${indice + 2}; grid-row: 1">${escapar(dia)}</div>`
        ).join("");
        const fondo = bloques.map((bloque, fila) => {
            const claseFila = bloque.receso ? " is-break" : (bloque.extra ? " is-extra" : "");
            const hora = `
                <div class="schedule-grid-time${claseFila}" style="grid-column: 1; grid-row: ${fila + 2}">
                    <strong>${bloque.inicio}</strong>
                    <span>${bloque.fin}</span>
                    ${bloque.receso ? "<small>Receso</small>" : ""}
                </div>
            `;
            const celdas = dias.map((dia, indice) =>
                `<div class="schedule-grid-cell${claseFila}" aria-hidden="true" style="grid-column: ${indice + 2}; grid-row: ${fila + 2}"></div>`
            ).join("");
            return hora + celdas;
        }).join("");
        const clases = horariosGrado.map((horario) => {
            const columna = dias.indexOf(horario.dia_semana);
            const posicion = posicionEnBloques(horario, bloques);
            if (columna < 0 || !posicion) return "";
            return `
                <div class="schedule-grid-item" style="grid-column: ${columna + 2}; grid-row: ${posicion.inicio + 2} / span ${posicion.cantidad}">
                    ${tarjetaHorario(horario)}
                </div>
            `;
        }).join("");

        desktop.innerHTML = `
            <div class="schedule-week-grid" style="--schedule-columns: ${dias.length}; --schedule-rows: ${bloques.length}">
                <div class="schedule-grid-corner">Hora</div>
                ${cabeceras}
                ${fondo}
                ${clases}
            </div>
        `;

        mobile.innerHTML = dias.map((dia) => {
            const items = horariosGrado.filter((horario) =>
                horario.dia_semana === dia
                && (mostrarExtra || horaEnMinutos(horario.hora_inicio) < horaEnMinutos("13:30"))
            );
            return `
                <section class="schedule-mobile-day">
                    <h4>${escapar(dia)}</h4>
                    <div class="schedule-mobile-entries">
                        ${items.length ? items.map((item) => `
                            <div class="schedule-mobile-slot">
                                <div class="schedule-mobile-time">
                                    <strong>${escapar(horaCorta(item.hora_inicio))}</strong>
                                    <span>${escapar(horaCorta(item.hora_fin))}</span>
                                </div>
                                ${tarjetaHorario(item, true)}
                            </div>
                        `).join("") : '<p class="schedule-empty">Sin clases</p>'}
                    </div>
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
        $("#editar-id-asignacion").dispatchEvent(new Event("change", { bubbles: true }));
        $("#editar-dia-semana").value = horario.dia_semana || "Lunes";
        $("#editar-hora-inicio").value = horaCorta(horario.hora_inicio);
        $("#editar-hora-fin").value = horaCorta(horario.hora_fin);
        $("#editar-horario-extra").checked = horaEnMinutos(horario.hora_fin) > horaEnMinutos("13:30");
        actualizarModoExtra("editar");
        $("#editar-permite-superposicion").checked = Number(horario.permite_superposicion) === 1;
        actualizarClaseConjunta("editar", horario);

        const mensaje = $("#mensaje-editar-horario");
        mensaje.textContent = horario.id_grado
            ? ""
            : "Este horario es antiguo y no coincide con una asignación activa. Seleccioná una asignación antes de guardar.";
        mensaje.className = horario.id_grado ? "mb-4" : "text-amber-700 font-semibold mb-4";
        abrirModal("modal-editar-horario");
    }

    async function recargarHorarios() {
        horarios = datosDe(await obtenerJson(config.apiHorarios)).map(completarTextoHorario);
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
                actualizarModoExtra("crear");
                actualizarClaseConjunta("crear");
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
        $("#btn-crear-horario").addEventListener("click", () => {
            const formulario = $("#form-crear-horario");
            formulario.reset();
            $("#mensaje-crear-horario").textContent = "";
            actualizarDetallesAsignacion("crear");
            actualizarModoExtra("crear");
            actualizarClaseConjunta("crear");
            abrirModal("modal-crear-horario");
        });
        activarBuscadorAsignacion("crear");
        activarBuscadorAsignacion("editar");
        $("#selector-grado-semanal").addEventListener("change", renderHorarioSemanal);
        $("#mostrar-horas-extra").addEventListener("change", renderHorarioSemanal);

        ["crear", "editar"].forEach((prefijo) => {
            const controles = controlesHorario(prefijo);
            controles.extra.addEventListener("change", () => actualizarModoExtra(prefijo, true));
            controles.bloque.addEventListener("change", () => aplicarBloqueSugerido(prefijo));
            [controles.inicio, controles.fin].forEach((input) =>
                input.addEventListener("change", () => {
                    controles.extra.checked = horaEnMinutos(controles.fin.value) > horaEnMinutos("13:30");
                    actualizarOpcionesBloque(prefijo);
                    if ($(`#${prefijo}-permite-superposicion`).checked) actualizarAyudaClaseConjunta(prefijo);
                })
            );
            controles.dia.addEventListener("change", () => {
                if ($(`#${prefijo}-permite-superposicion`).checked) actualizarAyudaClaseConjunta(prefijo);
            });
            $(`#${prefijo}-permite-superposicion`).addEventListener("change", () =>
                actualizarClaseConjunta(prefijo)
            );
            $(`#${prefijo}-buscar-clase-conjunta`).addEventListener("input", () =>
                renderizarOpcionesClaseConjunta(prefijo)
            );
            $(`#${prefijo}-materias-distintas`).addEventListener("change", () => actualizarClaseConjunta(prefijo));
            $(`#${prefijo}-opciones-clase-conjunta`).addEventListener("change", (evento) => {
                const opcion = evento.target.closest("[data-clase-conjunta-opcion]");
                if (!opcion) return;
                const seleccionadas = new Set(idsClaseConjunta(prefijo));
                if (opcion.checked) {
                    const nueva = asignacionPorId(opcion.value);
                    for (const id of seleccionadas) {
                        if (String(asignacionPorId(id)?.id_grado) === String(nueva?.id_grado)) seleccionadas.delete(id);
                    }
                    seleccionadas.add(String(opcion.value));
                }
                else seleccionadas.delete(String(opcion.value));
                guardarIdsClaseConjunta(prefijo, [...seleccionadas]);
                renderizarOpcionesClaseConjunta(prefijo);
                actualizarAyudaClaseConjunta(prefijo);
            });
            $(`#${prefijo}-seleccionar-clase-conjunta`).addEventListener("click", () => {
                const porGrado = new Map();
                for (const item of asignacionesCorrespondientes(prefijo)) {
                    const key = String(item.id_grado);
                    if (!porGrado.has(key)) porGrado.set(key, []);
                    porGrado.get(key).push(item);
                }
                const elegidas = new Set(idsClaseConjunta(prefijo));
                for (const opciones of porGrado.values()) {
                    if (opciones.length === 1) elegidas.add(String(opciones[0].id_asignacion));
                }
                guardarIdsClaseConjunta(prefijo, [...elegidas]);
                renderizarOpcionesClaseConjunta(prefijo);
                actualizarAyudaClaseConjunta(prefijo);
                if ([...porGrado.values()].some((opciones) => opciones.length > 1 && !opciones.some((item) => elegidas.has(String(item.id_asignacion))))) {
                    $(`#${prefijo}-clase-conjunta-ayuda`).textContent += ' Hay grados con varias materias: seleccioná explícitamente una en cada grado.';
                }
            });
            $(`#${prefijo}-limpiar-clase-conjunta`).addEventListener("click", () => {
                guardarIdsClaseConjunta(prefijo, []);
                renderizarOpcionesClaseConjunta(prefijo);
                actualizarAyudaClaseConjunta(prefijo);
            });
        });

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

            grados = datosDe(jsonGrados);
            asignaciones = datosDe(jsonAsignaciones);
            horarios = datosDe(jsonHorarios).map(completarTextoHorario);

            cargarOpciones();
            actualizarModoExtra("crear");
            actualizarModoExtra("editar");
            actualizarClaseConjunta("crear");
            actualizarClaseConjunta("editar");
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
