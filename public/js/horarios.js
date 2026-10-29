(function () {
    "use strict";

    const config = window.HORARIOS_CONFIG || {};
    const diasSemana = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes"];

    let grados = [];
    let asignaciones = [];
    let horariosGrado = [];
    let recesosGrado = [];
    let gradoSeleccionado = null;
    let vistaActual = "grilla";
    let intervaloEnVivo = null;

    const $ = (selector) => document.querySelector(selector);
    const $$ = (selector) => Array.from(document.querySelectorAll(selector));

    function escapar(cadena) {
        if (cadena === null || cadena === undefined) return "";
        return String(cadena)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function horaCorta(valor) {
        if (!valor) return "";
        return String(valor).slice(0, 5);
    }

    function datosDe(respuesta) {
        if (Array.isArray(respuesta)) return respuesta;
        if (respuesta && Array.isArray(respuesta.data)) return respuesta.data;
        return [];
    }

    async function obtenerJson(url, opciones = {}) {
        const respuesta = await fetch(url, {
            headers: { Accept: "application/json" },
            ...opciones
        });

        const texto = await respuesta.text();
        try {
            return JSON.parse(texto);
        } catch (error) {
            console.error("Error parseando JSON de:", url, texto);
            throw new Error(`Respuesta inválida del servidor (HTTP ${respuesta.status})`);
        }
    }

    function abrirModal(modalId) {
        const modal = $(`#${modalId}`);
        if (!modal) return;
        modal.classList.remove("hidden");
        document.body.classList.add("modal-open");
    }

    function cerrarModal(modalId) {
        const modal = $(`#${modalId}`);
        if (!modal) return;
        modal.classList.add("hidden");
        if (!$$(".app-modal:not(.hidden)").length) {
            document.body.classList.remove("modal-open");
        }
    }

    function mostrarMensaje(contenedor, mensaje, esExito = false) {
        if (!contenedor) return;
        contenedor.textContent = mensaje;
        contenedor.className = esExito
            ? "text-emerald-600 dark:text-emerald-400 font-semibold mb-4 text-sm"
            : "text-red-600 dark:text-red-400 font-semibold mb-4 text-sm";
    }

    function gradoPorId(idGrado) {
        return grados.find((g) => String(g.id_grado) === String(idGrado)) || null;
    }

    function sincronizarAula(select, inputAula, botonGuardar) {
        if (!select || !inputAula) return;
        const grado = gradoPorId(select.value);
        const tieneAula = Boolean(grado && grado.id_aula);

        inputAula.value = grado
            ? (grado.aula || (grado.id_aula ? `Aula #${grado.id_aula}` : "Sin aula asignada"))
            : "";
        inputAula.placeholder = grado
            ? (tieneAula ? "Aula asignada automáticamente" : "Grado sin aula asignada")
            : "Seleccione un grado";

        if (botonGuardar) {
            botonGuardar.disabled = !tieneAula;
            botonGuardar.classList.toggle("opacity-50", !tieneAula);
            botonGuardar.classList.toggle("cursor-not-allowed", !tieneAula);
        }
    }

    function actualizarAsignacionesPorGrado(selectGrado, selectAsignacion, valorSeleccionado = null) {
        if (!selectGrado || !selectAsignacion) return;
        const idGrado = selectGrado.value;

        if (!idGrado) {
            selectAsignacion.innerHTML = '<option value="">Seleccione primero un grado</option>';
            selectAsignacion.disabled = true;
            return;
        }

        const filtradas = asignaciones.filter((a) => String(a.id_grado) === String(idGrado));

        if (filtradas.length === 0) {
            selectAsignacion.innerHTML = '<option value="">No hay materias asignadas a este grado</option>';
            selectAsignacion.disabled = false;
            return;
        }

        selectAsignacion.disabled = false;
        let html = '<option value="">Seleccione materia y docente</option>';
        filtradas.forEach((asig) => {
            const carga = asig.carga_horaria ? ` (${asig.carga_horaria} hs)` : '';
            const label = `${asig.materia || "Materia"} — Prof. ${asig.profesor || "Docente"}${carga}`;
            const isSel = valorSeleccionado && String(asig.id_asignacion) === String(valorSeleccionado) ? 'selected' : '';
            html += `<option value="${escapar(asig.id_asignacion)}" ${isSel}>${escapar(label)}</option>`;
        });

        selectAsignacion.innerHTML = html;
    }

    function cargarOpcionesFormularios() {
        const opcionesGrado = [
            '<option value="">Seleccione grado</option>',
            ...grados.map((g) => {
                const desc = g.descripcion || `${g.nombre} - ${g.aula || "Sin aula"}`;
                return `<option value="${escapar(g.id_grado)}">${escapar(desc)}</option>`;
            })
        ].join("");

        const crearGrado = $("#crear-id-grado");
        const editarGrado = $("#editar-id-grado");

        if (crearGrado) crearGrado.innerHTML = opcionesGrado;
        if (editarGrado) editarGrado.innerHTML = opcionesGrado;
    }

    // ==========================================
    // Filtros en cascada: Turno -> Nivel -> Bachillerato -> Grado
    // ==========================================
    function inicializarFiltrosCascada() {
        const fTurno = $("#filtro-turno");
        const fNivel = $("#filtro-nivel");
        const fBachi = $("#filtro-bachillerato");
        const fGrado = $("#filtro-grado");

        if (!fGrado) return;

        // 1. Turnos únicos
        const turnosSet = new Map();
        grados.forEach((g) => {
            if (g.id_turno && g.turno && g.turno !== "Sin turno") {
                turnosSet.set(String(g.id_turno), g.turno);
            }
        });
        if (fTurno) {
            fTurno.innerHTML = '<option value="">Todos los turnos</option>' +
                Array.from(turnosSet.entries())
                    .map(([id, nombre]) => `<option value="${escapar(id)}">${escapar(nombre)}</option>`)
                    .join("");
        }

        actualizarNivelesCascada();
    }

    function actualizarNivelesCascada() {
        const fTurno = $("#filtro-turno");
        const fNivel = $("#filtro-nivel");
        const turnoVal = fTurno?.value || "";

        const nivelesSet = new Map();
        grados.forEach((g) => {
            if (turnoVal && String(g.id_turno) !== turnoVal) return;
            if (g.id_nivel && g.nivel && g.nivel !== "Sin nivel") {
                nivelesSet.set(String(g.id_nivel), g.nivel);
            }
        });

        if (fNivel) {
            const nivelActual = fNivel.value;
            fNivel.innerHTML = '<option value="">Todos los ciclos</option>' +
                Array.from(nivelesSet.entries())
                    .map(([id, nombre]) => `<option value="${escapar(id)}">${escapar(nombre)}</option>`)
                    .join("");
            if (nivelesSet.has(nivelActual)) {
                fNivel.value = nivelActual;
            }
        }

        actualizarBachilleratosCascada();
    }

    function actualizarBachilleratosCascada() {
        const fTurno = $("#filtro-turno");
        const fNivel = $("#filtro-nivel");
        const fBachi = $("#filtro-bachillerato");

        const turnoVal = fTurno?.value || "";
        const nivelVal = fNivel?.value || "";

        const bachilleratosSet = new Map();
        grados.forEach((g) => {
            if (turnoVal && String(g.id_turno) !== turnoVal) return;
            if (nivelVal && String(g.id_nivel) !== nivelVal) return;
            if (g.id_bachillerato && g.bachillerato && g.bachillerato !== "N/A") {
                bachilleratosSet.set(String(g.id_bachillerato), g.bachillerato);
            }
        });

        if (fBachi) {
            if (bachilleratosSet.size === 0) {
                fBachi.innerHTML = '<option value="">No aplica</option>';
                fBachi.disabled = true;
                fBachi.classList.add("opacity-60");
            } else {
                fBachi.disabled = false;
                fBachi.classList.remove("opacity-60");
                const bachiActual = fBachi.value;
                fBachi.innerHTML = '<option value="">Todos los bachilleratos</option>' +
                    Array.from(bachilleratosSet.entries())
                        .map(([id, nombre]) => `<option value="${escapar(id)}">${escapar(nombre)}</option>`)
                        .join("");
                if (bachilleratosSet.has(bachiActual)) {
                    fBachi.value = bachiActual;
                }
            }
        }

        actualizarGradosCascada();
    }

    function actualizarGradosCascada() {
        const fTurno = $("#filtro-turno");
        const fNivel = $("#filtro-nivel");
        const fBachi = $("#filtro-bachillerato");
        const fGrado = $("#filtro-grado");

        if (!fGrado) return;

        const turnoVal = fTurno?.value || "";
        const nivelVal = fNivel?.value || "";
        const bachiVal = fBachi?.disabled ? "" : (fBachi?.value || "");

        const gradosFiltrados = grados.filter((g) => {
            if (turnoVal && String(g.id_turno) !== turnoVal) return false;
            if (nivelVal && String(g.id_nivel) !== nivelVal) return false;
            if (bachiVal && String(g.id_bachillerato) !== bachiVal) return false;
            return true;
        });

        const gradoAnterior = fGrado.value || sessionStorage.getItem("horarios_grado_id") || "";

        if (gradosFiltrados.length === 0) {
            fGrado.innerHTML = '<option value="">No hay grados disponibles</option>';
            limpiarVistaHorarios();
            return;
        }

        fGrado.innerHTML = '<option value="">Seleccione un grado...</option>' +
            gradosFiltrados
                .map((g) => `<option value="${escapar(g.id_grado)}">${escapar(g.nombre)} (${escapar(g.aula || "Sin aula")})</option>`)
                .join("");

        // Si el grado guardado o anterior sigue disponible, seleccionarlo automáticamente
        const existeGrado = gradosFiltrados.some((g) => String(g.id_grado) === String(gradoAnterior));
        if (existeGrado) {
            fGrado.value = String(gradoAnterior);
            cargarHorariosGrado(gradoAnterior);
        } else if (gradosFiltrados.length === 1) {
            // Si solo queda un grado filtrado, auto-seleccionarlo
            fGrado.value = String(gradosFiltrados[0].id_grado);
            cargarHorariosGrado(gradosFiltrados[0].id_grado);
        } else {
            limpiarVistaHorarios();
        }
    }

    function limpiarVistaHorarios() {
        gradoSeleccionado = null;
        horariosGrado = [];
        recesosGrado = [];
        $("#info-grado-seleccionado")?.classList.add("hidden");
        $("#grilla-tabla-wrap")?.classList.add("hidden");
        $("#lista-tabla-wrap")?.classList.add("hidden");
        $("#grilla-vacia")?.classList.remove("hidden");
        actualizarEstadoEnVivo();
    }

    // ==========================================
    // Carga de Horarios por Grado (Bajo Demanda)
    // ==========================================
    async function cargarHorariosGrado(idGrado) {
        if (!idGrado) {
            limpiarVistaHorarios();
            return;
        }

        gradoSeleccionado = gradoPorId(idGrado);
        sessionStorage.setItem("horarios_grado_id", String(idGrado));

        const loading = $("#grilla-loading");
        const vacio = $("#grilla-vacia");
        const info = $("#info-grado-seleccionado");
        const grillaWrap = $("#grilla-tabla-wrap");
        const listaWrap = $("#lista-tabla-wrap");

        if (loading) loading.classList.remove("hidden");
        if (vacio) vacio.classList.add("hidden");
        if (grillaWrap) grillaWrap.classList.add("hidden");
        if (listaWrap) listaWrap.classList.add("hidden");

        try {
            const urlHorarios = `${config.apiHorarios}?id_grado=${encodeURIComponent(idGrado)}`;
            const urlRecesos = config.apiRecesos ? `${config.apiRecesos}?modo=curso&id_grado=${encodeURIComponent(idGrado)}` : null;

            const [respHorarios, respRecesos] = await Promise.all([
                obtenerJson(urlHorarios),
                urlRecesos ? obtenerJson(urlRecesos).catch(() => ({ data: [] })) : Promise.resolve({ data: [] })
            ]);

            horariosGrado = datosDe(respHorarios);
            recesosGrado = datosDe(respRecesos);

            actualizarTiraMetadatos();
            renderGrillaMatriz();
            renderListaGrado();
            actualizarEstadoEnVivo();

            if (info) info.classList.remove("hidden");
            if (vistaActual === "grilla") {
                if (grillaWrap) grillaWrap.classList.remove("hidden");
            } else {
                if (listaWrap) listaWrap.classList.remove("hidden");
            }
        } catch (error) {
            console.error("Error al cargar horario del grado:", error);
            if (window.Toast) {
                Toast.error("Error al obtener los horarios del grado.");
            }
        } finally {
            if (loading) loading.classList.add("hidden");
        }
    }

    function actualizarTiraMetadatos() {
        if (!gradoSeleccionado) return;

        const elNombre = $("#meta-grado-nombre");
        const elAula = $("#meta-grado-aula");
        const elUbicacion = $("#meta-grado-ubicacion");
        const elEstudiantes = $("#meta-grado-estudiantes");

        if (elNombre) elNombre.textContent = gradoSeleccionado.nombre || "Grado";
        if (elAula) elAula.textContent = gradoSeleccionado.aula || "Sin aula asignada";

        const ubicacion = horariosGrado[0]?.ubicacion || "Planta Central";
        if (elUbicacion) elUbicacion.textContent = ubicacion;

        const totalEstudiantes = Number(horariosGrado[0]?.total_estudiantes || 0);
        if (elEstudiantes) {
            elEstudiantes.textContent = totalEstudiantes > 0
                ? `${totalEstudiantes} estudiantes activos`
                : "Sin estudiantes";
        }
    }

    // ==========================================
    // Generación de la Matriz Semanal (Grilla Horaria)
    // ==========================================
    function calcularSlotsAtomicos(horarios, recesos = []) {
        const puntosSet = new Set();
        horarios.forEach((h) => {
            if (h.hora_inicio) puntosSet.add(horaCorta(h.hora_inicio));
            if (h.hora_fin) puntosSet.add(horaCorta(h.hora_fin));
        });
        recesos.forEach((r) => {
            if (r.hora_inicio) puntosSet.add(horaCorta(r.hora_inicio));
            if (r.hora_fin) puntosSet.add(horaCorta(r.hora_fin));
        });

        const puntosOrdenados = Array.from(puntosSet).sort((a, b) => a.localeCompare(b));
        const slots = [];

        for (let i = 0; i < puntosOrdenados.length - 1; i++) {
            slots.push({
                inicio: puntosOrdenados[i],
                fin: puntosOrdenados[i + 1]
            });
        }

        let horaCatedraContador = 1;
        slots.forEach((slot) => {
            const tieneMateriaReceso = horarios.some(
                (h) => (h.materia || "").toUpperCase() === "RECESO" &&
                    horaCorta(h.hora_inicio) <= slot.inicio &&
                    horaCorta(h.hora_fin) >= slot.fin
            );

            const tieneAlgunaClaseReal = horarios.some(
                (h) => (h.materia || "").toUpperCase() !== "RECESO" &&
                    horaCorta(h.hora_inicio) <= slot.inicio &&
                    horaCorta(h.hora_fin) >= slot.fin
            );

            // Se considera receso en la columna lateral si tiene materia explícita "RECESO" o si no hay ninguna clase real en toda la semana
            const esReceso = tieneMateriaReceso || !tieneAlgunaClaseReal;

            if (esReceso) {
                slot.badge = "RECESO";
                slot.esReceso = true;
            } else {
                slot.badge = `${horaCatedraContador}ª HC`;
                slot.esReceso = false;
                horaCatedraContador++;
            }
        });

        return slots;
    }

    function esClaseEnVivo(horario) {
        if (!horario || !horario.dia_semana || !horario.hora_inicio || !horario.hora_fin) return false;
        const diasNombres = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
        const hoy = diasNombres[new Date().getDay()];
        if (horario.dia_semana !== hoy) return false;

        const ahoraStr = new Date().toTimeString().slice(0, 5);
        const inicioStr = horaCorta(horario.hora_inicio);
        const finStr = horaCorta(horario.hora_fin);

        return ahoraStr >= inicioStr && ahoraStr < finStr;
    }

    function renderGrillaMatriz() {
        const theadRow = $("#matriz-thead-row");
        const tbody = $("#matriz-tbody");
        if (!theadRow || !tbody) return;

        if (horariosGrado.length === 0) {
            theadRow.innerHTML = '<th class="p-3">Horario</th><th class="p-3">Semana</th>';
            tbody.innerHTML = `
                <tr>
                    <td colspan="2" class="p-10 text-center text-muted">
                        <p class="font-bold text-base">No hay clases programadas para este grado</p>
                        <p class="text-xs mt-1">Podés crear horarios para este curso usando el botón "+ Crear horario".</p>
                    </td>
                </tr>
            `;
            return;
        }

        const diasActivos = [...diasSemana];
        const tieneSabado = horariosGrado.some((h) => h.dia_semana === "Sábado");
        if (tieneSabado) diasActivos.push("Sábado");

        const diasNombres = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
        const diaActual = diasNombres[new Date().getDay()];

        // Construir Cabecera de la Tabla
        let headerHtml = '<th class="slot-time-col">Hora / Período</th>';
        diasActivos.forEach((dia) => {
            const esHoy = (dia === diaActual);
            headerHtml += `
                <th class="${esHoy ? 'dia-actual' : ''}">
                    <div class="flex items-center justify-center gap-1.5">
                        <span>${escapar(dia)}</span>
                        ${esHoy ? '<span class="text-[0.65rem] px-2 py-0.5 rounded-full bg-primary text-white font-black tracking-wide">HOY</span>' : ''}
                    </div>
                </th>
            `;
        });
        theadRow.innerHTML = headerHtml;

        // Calcular Slots Atómicos
        const slots = calcularSlotsAtomicos(horariosGrado, recesosGrado);
        if (slots.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="p-8 text-center text-muted">Sin intervalos registrados.</td></tr>';
            return;
        }

        // Agrupar horarios por día
        const horariosPorDia = {};
        diasActivos.forEach((dia) => {
            horariosPorDia[dia] = horariosGrado
                .filter((h) => h.dia_semana === dia)
                .sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio));
        });

        // Matriz de ocupación para celdas saltadas por rowspan
        const celdasOcupadas = {};
        diasActivos.forEach((dia) => {
            celdasOcupadas[dia] = new Set();
        });

        // Construir Filas de Slots
        let bodyHtml = "";
        slots.forEach((slot, sIdx) => {
            bodyHtml += "<tr>";

            // 1ra Columna: Información de Hora Cátedra y Horario
            bodyHtml += `
                <td class="slot-time-col">
                    <span class="matrix-slot-badge ${slot.esReceso ? 'is-receso' : ''}">${escapar(slot.badge)}</span>
                    <span class="matrix-slot-hours">${escapar(slot.inicio)} - ${escapar(slot.fin)}</span>
                </td>
            `;

            // Columnas de Días
            diasActivos.forEach((dia) => {
                if (celdasOcupadas[dia].has(sIdx)) {
                    // Cubierto por rowspan anterior, no emitir <td>
                    return;
                }

                // Buscar clase que inicie exactamente en este slot
                const clase = horariosPorDia[dia].find((h) => horaCorta(h.hora_inicio) === slot.inicio);

                if (clase) {
                    const finClase = horaCorta(clase.hora_fin);
                    const endSlotIdx = slots.findIndex((sl) => sl.fin === finClase);
                    let span = 1;

                    if (endSlotIdx >= sIdx) {
                        span = (endSlotIdx - sIdx) + 1;
                    } else {
                        span = slots.filter((sl, idx) => idx >= sIdx && sl.inicio < finClase).length || 1;
                    }

                    // Marcar slots subsiguientes como ocupados por el rowspan
                    for (let k = 1; k < span; k++) {
                        celdasOcupadas[dia].add(sIdx + k);
                    }

                    const isLive = esClaseEnVivo(clase);
                    const esReceso = (clase.materia || "").toUpperCase() === "RECESO";

                    bodyHtml += `
                        <td rowspan="${span}">
                            <article class="matrix-subject-card ${isLive ? 'is-live-class' : ''} ${esReceso ? 'is-receso-card' : ''}">
                                ${isLive ? '<span class="matrix-live-pill"><span class="live-dot"></span> EN CURSO</span>' : ''}
                                <div class="matrix-subject-name ${esReceso ? 'text-amber-800 dark:text-amber-300 font-extrabold' : ''}">
                                    ${esReceso ? '☕ RECESO' : escapar(clase.materia || "Sin materia")}
                                </div>
                                <button type="button" class="btn-ver-horario" data-ver-horario="${escapar(clase.id_horario)}">
                                    Ver
                                </button>
                            </article>
                        </td>
                    `;
                } else {
                    // Verificar si existe un receso institucional determinista para este día
                    const recesoInicio = recesosGrado.find((r) =>
                        (r.dia_semana === dia || r.dia_semana === "Todos") &&
                        horaCorta(r.hora_inicio) === slot.inicio
                    );

                    if (recesoInicio) {
                        const finRec = horaCorta(recesoInicio.hora_fin);
                        const endRecSlotIdx = slots.findIndex((sl) => sl.fin === finRec);
                        let spanRec = 1;

                        if (endRecSlotIdx >= sIdx) {
                            spanRec = (endRecSlotIdx - sIdx) + 1;
                        } else {
                            spanRec = slots.filter((sl, idx) => idx >= sIdx && sl.inicio < finRec).length || 1;
                        }

                        for (let k = 1; k < spanRec; k++) {
                            celdasOcupadas[dia].add(sIdx + k);
                        }

                        bodyHtml += `
                            <td rowspan="${spanRec}" class="matrix-empty-cell">
                                <div class="matrix-subject-card is-receso-card">
                                    <div class="matrix-subject-name text-amber-800 dark:text-amber-300 font-extrabold text-xs">
                                        ☕ ${escapar(recesoInicio.nombre || "RECESO")}
                                    </div>
                                    <div class="text-[0.65rem] text-amber-700/80 dark:text-amber-400/80 mt-0.5 font-semibold">
                                        ${escapar(horaCorta(recesoInicio.hora_inicio))} - ${escapar(horaCorta(recesoInicio.hora_fin))}
                                    </div>
                                </div>
                            </td>
                        `;
                    } else if (slot.esReceso) {
                        bodyHtml += `
                            <td class="matrix-empty-cell">
                                <div class="matrix-subject-card is-receso-card">
                                    <div class="matrix-subject-name text-amber-800 dark:text-amber-300 font-extrabold text-xs">
                                        ☕ RECESO
                                    </div>
                                </div>
                            </td>
                        `;
                    } else {
                        bodyHtml += '<td class="matrix-empty-cell"></td>';
                    }
                }
            });

            bodyHtml += "</tr>";
        });

        tbody.innerHTML = bodyHtml;
    }

    // ==========================================
    // Vista Lista Detallada (Pestaña Alternativa)
    // ==========================================
    function renderListaGrado() {
        const tbody = $("#tabla-lista-grado-body");
        if (!tbody) return;

        if (horariosGrado.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-slate-400 dark:text-slate-500 italic text-base">No hay horarios registrados para este curso.</td></tr>';
            return;
        }

        const ordenDias = [...diasSemana, "Sábado"];
        const ordenados = [...horariosGrado].sort((a, b) => {
            const cmpDia = ordenDias.indexOf(a.dia_semana) - ordenDias.indexOf(b.dia_semana);
            if (cmpDia !== 0) return cmpDia;
            return a.hora_inicio.localeCompare(b.hora_inicio);
        });

        tbody.innerHTML = ordenados
            .map((h) => {
                const totalEst = Number(h.total_estudiantes || 0);
                const estText = totalEst > 0
                    ? `<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">${totalEst} alumnos</span>`
                    : '<span class="text-slate-400 dark:text-slate-500 font-medium text-sm">Sin estudiantes</span>';
                
                const botonesAccion = config.esDocente
                    ? ""
                    : `
                        <td class="p-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" class="min-h-[40px] px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 rounded-xl text-sm font-bold border border-indigo-200 dark:border-indigo-800 transition-colors shadow-2xs cursor-pointer flex items-center gap-1.5" data-editar-horario="${escapar(h.id_horario)}">
                                    <span>✏️</span> Editar
                                </button>
                                <button type="button" class="min-h-[40px] px-3.5 py-1.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 rounded-xl text-sm font-bold border border-rose-200 dark:border-rose-800 transition-colors shadow-2xs cursor-pointer flex items-center gap-1.5" data-desactivar-horario="${escapar(h.id_horario)}">
                                    <span>🗑️</span> Desactivar
                                </button>
                            </div>
                        </td>
                    `;

                return `
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                        <td class="p-4 font-black text-slate-900 dark:text-white text-base">${escapar(h.dia_semana)}</td>
                        <td class="p-4 font-mono font-bold text-slate-700 dark:text-slate-300 text-sm sm:text-base">${escapar(horaCorta(h.hora_inicio))} - ${escapar(horaCorta(h.hora_fin))}</td>
                        <td class="p-4 font-black text-indigo-600 dark:text-indigo-400 text-base">${escapar(h.materia || "Sin materia")}</td>
                        <td class="p-4 font-bold text-slate-800 dark:text-slate-200 text-sm sm:text-base">${escapar(h.profesor || "Sin profesor")}</td>
                        <td class="p-4 text-sm sm:text-base">
                            <span class="font-bold text-slate-900 dark:text-white block">${escapar(h.aula || "Sin aula")}</span>
                            <span class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium block mt-0.5">${escapar(h.ubicacion || "")}</span>
                        </td>
                        <td class="p-4">${estText}</td>
                        ${botonesAccion}
                    </tr>
                `;
            })
            .join("");
    }

    // ==========================================
    // Estado de Clase En Vivo (Header Real-time)
    // ==========================================
    function actualizarEstadoEnVivo() {
        const contenedor = $("#indicador-en-vivo");
        const texto = $("#texto-en-vivo");
        if (!contenedor || !texto) return;

        const ahora = new Date();
        const diasNombres = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
        const hoyNombre = diasNombres[ahora.getDay()];
        const horaActual = ahora.toTimeString().slice(0, 5);

        if (!gradoSeleccionado) {
            contenedor.className = "flex items-center gap-2.5 min-h-[44px] px-4 py-2 rounded-xl text-sm font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-2 border-slate-200 dark:border-slate-700";
            texto.textContent = "Seleccioná un grado para ver su estado";
            return;
        }

        const claseActual = horariosGrado.find((h) => esClaseEnVivo(h));

        if (claseActual) {
            contenedor.className = "flex items-center gap-2.5 min-h-[44px] px-4 py-2 rounded-xl text-sm font-extrabold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border-2 border-emerald-300 dark:border-emerald-700 shadow-sm animate-pulse";
            texto.innerHTML = `<span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 animate-ping mr-1"></span>En curso: ${escapar(claseActual.materia)} • ${escapar(claseActual.profesor || "Docente")} (hasta ${escapar(horaCorta(claseActual.hora_fin))})`;
        } else {
            contenedor.className = "flex items-center gap-2.5 min-h-[44px] px-4 py-2 rounded-xl text-sm font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-2 border-slate-200 dark:border-slate-700";
            texto.textContent = `Sin clase en este momento (${hoyNombre} ${horaActual})`;
        }
    }

    // ==========================================
    // Modal de Detalle / Metadatos de Materia
    // ==========================================
    function abrirDetalleMateria(idHorario) {
        const horario = horariosGrado.find((h) => String(h.id_horario) === String(idHorario));
        if (!horario) return;

        const badgeDia = $("#detalle-badge-dia");
        const materiaNombre = $("#detalle-materia-nombre");
        const horarioTiempo = $("#detalle-horario-tiempo");
        const profesorNombre = $("#detalle-profesor-nombre");
        const profesorCi = $("#detalle-profesor-ci");
        const aulaNombre = $("#detalle-aula-nombre");
        const aulaCodigo = $("#detalle-aula-codigo");
        const aulaUbicacion = $("#detalle-aula-ubicacion");
        const totalEstudiantes = $("#detalle-total-estudiantes");
        const badgeEstudiantes = $("#detalle-badge-estudiantes");
        const gradoNombre = $("#detalle-grado-nombre");
        const turnoNombre = $("#detalle-turno-nombre");

        if (badgeDia) badgeDia.textContent = horario.dia_semana || "Día";
        if (materiaNombre) materiaNombre.textContent = horario.materia || "Sin materia";
        if (horarioTiempo) {
            horarioTiempo.textContent = `${horaCorta(horario.hora_inicio)} a ${horaCorta(horario.hora_fin)}`;
        }

        if (profesorNombre) profesorNombre.textContent = horario.profesor || "Docente no asignado";
        if (profesorCi) {
            profesorCi.textContent = horario.profesor_ci ? `C.I.: ${horario.profesor_ci}` : "Sin documento registrado";
        }

        if (aulaNombre) aulaNombre.textContent = horario.aula || "Sin aula";
        if (aulaCodigo) {
            aulaCodigo.textContent = horario.codigo_aula ? `Código: ${horario.codigo_aula}` : "";
        }
        if (aulaUbicacion) aulaUbicacion.textContent = horario.ubicacion || "Sin ubicación registrada";

        const cantEst = Number(horario.total_estudiantes || 0);
        if (totalEstudiantes) {
            totalEstudiantes.textContent = cantEst > 0 ? `${cantEst} estudiantes asignados` : "Sin estudiantes";
        }
        if (badgeEstudiantes) {
            badgeEstudiantes.textContent = cantEst > 0 ? "Activos" : "Vacío";
            badgeEstudiantes.className = cantEst > 0
                ? "text-xs sm:text-sm font-bold px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700"
                : "text-xs sm:text-sm font-bold px-3 py-1 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-600";
        }

        if (gradoNombre) gradoNombre.textContent = horario.grado || gradoSeleccionado?.nombre || "-";
        if (turnoNombre) turnoNombre.textContent = gradoSeleccionado?.turno || "Turno";

        // Configurar botones de acción del modal
        const btnEditar = $("#btn-detalle-editar");
        const btnDesactivar = $("#btn-detalle-desactivar");

        if (btnEditar) {
            btnEditar.onclick = () => {
                cerrarModal("modal-detalle-horario");
                abrirEdicion(horario.id_horario);
            };
        }

        if (btnDesactivar) {
            btnDesactivar.onclick = () => {
                cerrarModal("modal-detalle-horario");
                desactivarHorario(horario.id_horario);
            };
        }

        abrirModal("modal-detalle-horario");
    }

    // ==========================================
    // Acciones de Creación, Edición y Desactivación
    // ==========================================
    function abrirCreacion() {
        const formulario = $("#form-crear-horario");
        if (formulario) formulario.reset();

        const selectGrado = $("#crear-id-grado");
        if (selectGrado) {
            if (gradoSeleccionado) {
                selectGrado.value = String(gradoSeleccionado.id_grado);
            }
            sincronizarAula(selectGrado, $("#crear-id-aula"), $("#guardar-horario"));
            actualizarAsignacionesPorGrado(selectGrado, $("#crear-id-asignacion"));
        }

        mostrarMensaje($("#mensaje-crear-horario"), "");
        abrirModal("modal-crear-horario");
    }

    function abrirEdicion(idHorario) {
        const horario = horariosGrado.find((h) => String(h.id_horario) === String(idHorario));
        if (!horario) {
            if (window.Toast) Toast.error("No se encontró la información del horario.");
            return;
        }

        const editIdHorario = $("#editar-id-horario");
        const editIdAsignacion = $("#editar-id-asignacion");
        const editIdGrado = $("#editar-id-grado");
        const editDiaSemana = $("#editar-dia-semana");
        const editHoraInicio = $("#editar-hora-inicio");
        const editHoraFin = $("#editar-hora-fin");

        if (editIdHorario) editIdHorario.value = horario.id_horario;
        if (editIdGrado) {
            editIdGrado.value = horario.id_grado || "";
            sincronizarAula(editIdGrado, $("#editar-id-aula"), $("#actualizar-horario"));
            actualizarAsignacionesPorGrado(editIdGrado, editIdAsignacion, horario.id_asignacion);
        }
        if (editDiaSemana) editDiaSemana.value = horario.dia_semana || "Lunes";
        if (editHoraInicio) editHoraInicio.value = horaCorta(horario.hora_inicio);
        if (editHoraFin) editHoraFin.value = horaCorta(horario.hora_fin);

        const mensaje = $("#mensaje-editar-horario");
        if (mensaje) mostrarMensaje(mensaje, "");

        abrirModal("modal-editar-horario");
    }

    async function enviarFormulario(formulario, mensajeBox, modalId) {
        const boton = formulario.querySelector('button[type="submit"]');
        if (boton) boton.disabled = true;
        if (mensajeBox) {
            mensajeBox.textContent = "Guardando...";
            mensajeBox.className = "text-blue-600 font-semibold mb-4 text-sm";
        }

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
            if (window.Toast) Toast.success(json.message);

            // Recargar horario del grado seleccionado
            const idGradoAfectado = formulario.querySelector('[name="id_grado"]')?.value || gradoSeleccionado?.id_grado;
            await cargarHorariosGrado(idGradoAfectado);

            setTimeout(() => cerrarModal(modalId), 400);
        } catch (error) {
            console.error(error);
            mostrarMensaje(mensajeBox, error.message || "Error al procesar la solicitud.");
        } finally {
            if (boton) boton.disabled = false;
        }
    }

    async function desactivarHorario(idHorario) {
        const confirmado = window.SISCO && window.SISCO.confirm
            ? await SISCO.confirm({
                title: "Desactivar Horario",
                message: "¿Seguro que desea desactivar este horario de clase?",
                confirmText: "Desactivar",
                type: "warning"
            })
            : confirm("¿Seguro que desea desactivar este horario?");

        if (!confirmado) return;

        const datos = new FormData();
        datos.append("id_horario", idHorario);

        try {
            const json = await obtenerJson(config.apiDesactivar, { method: "POST", body: datos });
            if (json.status !== "success") {
                if (window.Toast) {
                    Toast.error(json.message || "No se pudo desactivar el horario.");
                } else {
                    alert(json.message || "No se pudo desactivar el horario.");
                }
                return;
            }

            if (window.Toast) Toast.success("Horario desactivado correctamente");
            if (gradoSeleccionado) {
                await cargarHorariosGrado(gradoSeleccionado.id_grado);
            }
        } catch (error) {
            console.error(error);
            if (window.Toast) Toast.error("Error al desactivar el horario.");
        }
    }

    // ==========================================
    // Activación de Eventos
    // ==========================================
    function activarEventos() {
        // Botón Crear Horario
        const btnCrear = $("#btn-crear-horario");
        if (btnCrear) {
            btnCrear.addEventListener("click", abrirCreacion);
        }

        // Filtros en cascada
        const fTurno = $("#filtro-turno");
        const fNivel = $("#filtro-nivel");
        const fBachi = $("#filtro-bachillerato");
        const fGrado = $("#filtro-grado");

        if (fTurno) fTurno.addEventListener("change", actualizarNivelesCascada);
        if (fNivel) fNivel.addEventListener("change", actualizarBachilleratosCascada);
        if (fBachi) fBachi.addEventListener("change", actualizarGradosCascada);
        if (fGrado) {
            fGrado.addEventListener("change", (e) => {
                cargarHorariosGrado(e.target.value);
            });
        }

        // Sincronización de aula y asignaciones en formularios
        const crearGrado = $("#crear-id-grado");
        if (crearGrado) {
            crearGrado.addEventListener("change", () => {
                sincronizarAula(crearGrado, $("#crear-id-aula"), $("#guardar-horario"));
                actualizarAsignacionesPorGrado(crearGrado, $("#crear-id-asignacion"));
            });
        }

        const editarGrado = $("#editar-id-grado");
        if (editarGrado) {
            editarGrado.addEventListener("change", () => {
                sincronizarAula(editarGrado, $("#editar-id-aula"), $("#actualizar-horario"));
                actualizarAsignacionesPorGrado(editarGrado, $("#editar-id-asignacion"));
            });
        }

        // Alternador de vistas Grilla / Lista
        const btnGrilla = $("#btn-vista-grilla");
        const btnLista = $("#btn-vista-lista");
        const grillaWrap = $("#grilla-tabla-wrap");
        const listaWrap = $("#lista-tabla-wrap");

        if (btnGrilla && btnLista) {
            btnGrilla.addEventListener("click", () => {
                vistaActual = "grilla";
                btnGrilla.className = "min-h-[38px] px-4 py-1.5 rounded-lg text-sm font-extrabold bg-indigo-600 text-white shadow-xs cursor-pointer transition-all";
                btnLista.className = "min-h-[38px] px-4 py-1.5 rounded-lg text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer transition-all";
                if (horariosGrado.length > 0) {
                    if (grillaWrap) grillaWrap.classList.remove("hidden");
                    if (listaWrap) listaWrap.classList.add("hidden");
                }
            });

            btnLista.addEventListener("click", () => {
                vistaActual = "lista";
                btnLista.className = "min-h-[38px] px-4 py-1.5 rounded-lg text-sm font-extrabold bg-indigo-600 text-white shadow-xs cursor-pointer transition-all";
                btnGrilla.className = "min-h-[38px] px-4 py-1.5 rounded-lg text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer transition-all";
                if (horariosGrado.length > 0) {
                    if (listaWrap) listaWrap.classList.remove("hidden");
                    if (grillaWrap) grillaWrap.classList.add("hidden");
                }
            });
        }

        // Envío de formularios
        const formCrear = $("#form-crear-horario");
        if (formCrear) {
            formCrear.addEventListener("submit", (e) => {
                e.preventDefault();
                enviarFormulario(e.currentTarget, $("#mensaje-crear-horario"), "modal-crear-horario");
            });
        }

        const formEditar = $("#form-editar-horario");
        if (formEditar) {
            formEditar.addEventListener("submit", (e) => {
                e.preventDefault();
                enviarFormulario(e.currentTarget, $("#mensaje-editar-horario"), "modal-editar-horario");
            });
        }

        // Delegación de eventos para clicks globales (Ver, Editar, Desactivar, Modales)
        document.addEventListener("click", (e) => {
            const btnCerrar = e.target.closest("[data-close-modal]");
            const btnVer = e.target.closest("[data-ver-horario]");
            const btnEditar = e.target.closest("[data-editar-horario]");
            const btnDesactivar = e.target.closest("[data-desactivar-horario]");

            if (btnCerrar) {
                cerrarModal(btnCerrar.dataset.closeModal);
                return;
            }

            if (btnVer) {
                abrirDetalleMateria(btnVer.dataset.verHorario);
                return;
            }

            if (btnEditar) {
                abrirEdicion(btnEditar.dataset.editarHorario);
                return;
            }

            if (btnDesactivar) {
                desactivarHorario(btnDesactivar.dataset.desactivarHorario);
                return;
            }

            if (e.target.classList.contains("app-modal")) {
                cerrarModal(e.target.id);
            }
        });

        // Tecla escape para cerrar modales
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                $$(".app-modal:not(.hidden)").forEach((modal) => cerrarModal(modal.id));
            }
        });

        // Timer para actualizar indicador en vivo cada 30 segundos
        if (intervaloEnVivo) clearInterval(intervaloEnVivo);
        intervaloEnVivo = setInterval(() => {
            actualizarEstadoEnVivo();
            if (gradoSeleccionado && vistaActual === "grilla") {
                renderGrillaMatriz();
            }
        }, 30000);
    }

    // ==========================================
    // Inicialización del Módulo
    // ==========================================
    async function iniciar() {
        activarEventos();

        try {
            // Cargar únicamente grados y asignaciones (Liviano, sin traer 500+ horarios de una vez)
            const [jsonGrados, jsonAsignaciones] = await Promise.all([
                obtenerJson(config.apiGrados),
                obtenerJson(config.apiAsignaciones)
            ]);

            grados = datosDe(jsonGrados);
            asignaciones = datosDe(jsonAsignaciones);

            cargarOpcionesFormularios();
            inicializarFiltrosCascada();
        } catch (error) {
            console.error("Error al inicializar módulo de horarios:", error);
            if (window.Toast) {
                Toast.error("Error al cargar la información inicial de grados.");
            }
        }
    }

    document.addEventListener("DOMContentLoaded", iniciar);
})();
