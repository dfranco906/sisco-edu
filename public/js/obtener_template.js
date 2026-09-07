async function descargarYGuardarTemplate(userIdGlobal, tipoUsuario = "estudiante") {
    const ipESP32 = "192.168.100.125";
    const baseESP32 = `http://${ipESP32}`;

    let aulasSeleccionadas = [];
    if (tipoUsuario === "profesor") {
        try {
            const seleccion = await seleccionarAulasProfesor(userIdGlobal, "captura");
            if (!seleccion) return;
            aulasSeleccionadas = seleccion.aulas;
        } catch (error) {
            mostrarEstadoHuella(error.message, false, true);
            return;
        }
    }

    mostrarEstadoHuella("Conectando con ESP32...", true);

    try {
        bloquearBotonesHuella(true);

        mostrarEstadoHuella("Registrando huella en el sensor...");
        const { respuesta: registro, data: dataRegistro } = await solicitarJson(`${baseESP32}/registrar`);

        if (!registro.ok || dataRegistro.status !== "success") {
            mostrarEstadoHuella("Error al registrar: " + (dataRegistro.message || `HTTP ${registro.status}`), false, true);
            bloquearBotonesHuella(false);
            return;
        }

        mostrarEstadoHuella("Complete las dos lecturas en el sensor...", true);
        const estadoCaptura = await esperarResultadoCaptura(baseESP32);
        if (!estadoCaptura.template_disponible) {
            const detalle = estadoCaptura.ultimo_error || "No se genero un template biometrico";
            mostrarEstadoHuella("Error al capturar la huella: " + detalle, false, true);
            bloquearBotonesHuella(false);
            return;
        }

        mostrarEstadoHuella("Obteniendo template biometrico...", true);
        const { respuesta, data } = await solicitarJson(`${baseESP32}/obtener_template`);

        if (!respuesta.ok || data.status !== "success") {
            mostrarEstadoHuella("Error al obtener template: " + (data.message || `HTTP ${respuesta.status}`), false, true);
            bloquearBotonesHuella(false);
            return;
        }

        const templateValido = data.bytes === 1536
            && typeof data.template === "string"
            && /^[0-9a-fA-F]{3072}$/.test(data.template)
            && typeof data.crc32 === "string"
            && /^[0-9a-fA-F]{8}$/.test(data.crc32);
        if (!templateValido) {
            mostrarEstadoHuella("El ESP32 devolvio un template o CRC32 incompatible.", false, true);
            bloquearBotonesHuella(false);
            return;
        }

        mostrarEstadoHuella("Guardando huella en la base de datos...");

        const formData = new FormData();
        formData.append("user_id_global", userIdGlobal);
        formData.append("tipo_usuario", tipoUsuario);
        formData.append("template", data.template);
        formData.append("bytes", data.bytes);
        formData.append("crc32", data.crc32.toLowerCase());
        aulasSeleccionadas.forEach(aula => formData.append("id_aulas[]", aula.id_aula));

        const { respuesta: resBackend, data: resultado } = await solicitarJson(window.BASE_URL + "src/api/Huella/guardar_template.php", {
            method: "POST",
            body: formData
        });

        if (resBackend.ok && resultado.status === "success") {
            let mensaje = "Huella guardada correctamente. Sincronización Gateway pendiente.";
            try {
                const limpieza = await limpiarSlotTemporalRemoto(baseESP32);
                if (!limpieza.ok) {
                    mensaje += `\nAdvertencia: ${limpieza.mensaje}`;
                }
            } catch (errorLimpieza) {
                console.warn("La huella se guardo, pero no se pudo limpiar el slot temporal", errorLimpieza);
                mensaje += "\nAdvertencia: no se pudo limpiar la ranura temporal del sensor.";
            }
            mostrarEstadoHuella(mensaje, false);
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarEstadoHuella((resultado.message || `Error HTTP ${resBackend.status}`) + (resultado.debug ? "\n" + resultado.debug : ""), false, true);
            bloquearBotonesHuella(false);
        }

    } catch (error) {
        console.error(error);
        const detalle = error.name === "AbortError"
            ? "La captura excedio el tiempo de espera"
            : error.message;
        mostrarEstadoHuella("No se pudo completar el registro: " + detalle, false, true);
        bloquearBotonesHuella(false);
    }
}

async function sincronizarHuellaProfesor(userIdGlobal) {
    try {
        const seleccion = await seleccionarAulasProfesor(userIdGlobal, "sincronizacion");
        if (!seleccion) return;
        bloquearBotonesHuella(true);
        mostrarEstadoHuella("Preparando envío de la huella al Gateway...", true);
        const formData = new FormData();
        formData.append("user_id_global", userIdGlobal);
        seleccion.aulas.forEach(aula => formData.append("id_aulas[]", aula.id_aula));
        const { respuesta, data } = await solicitarJson(
            window.BASE_URL + "src/api/Huella/sincronizar_profesor.php",
            { method: "POST", body: formData },
            15000
        );
        if (!respuesta.ok || data.status !== "success") {
            throw new Error(data.message || `No se pudo preparar el envío (HTTP ${respuesta.status})`);
        }
        const aviso = data.data?.gateway_avisado
            ? " El Gateway recibió el aviso."
            : " Quedó pendiente; verifique que el Gateway esté encendido y tenga configurado el ID del aula.";
        mostrarEstadoHuella(`${data.message}${aviso}`, false);
        setTimeout(() => location.reload(), 2200);
    } catch (error) {
        mostrarEstadoHuella(error.message, false, true);
        bloquearBotonesHuella(false);
    }
}

async function seleccionarAulasProfesor(userIdGlobal, modo) {
    const { respuesta, data } = await solicitarJson(
        window.BASE_URL + "src/api/Huella/aulas_profesor.php?user_id_global=" + encodeURIComponent(userIdGlobal),
        {},
        15000
    );
    if (!respuesta.ok || data.status !== "success") {
        throw new Error(data.message || "No se pudieron consultar las aulas del profesor.");
    }
    const info = data.data;
    if (modo === "sincronizacion" && !info.huella) {
        throw new Error("El profesor todavía no tiene una huella registrada.");
    }
    if (!Array.isArray(info.aulas) || info.aulas.length === 0) {
        throw new Error("El profesor no tiene aulas activas en sus horarios.");
    }

    document.getElementById("modal-aulas-huella")?.remove();
    const estadosOcupados = new Set(["PENDIENTE", "ENVIADO", "CONFIRMADO"]);
    const etiquetas = { PENDIENTE: "Pendiente", ENVIADO: "Enviada al aula", CONFIRMADO: "Instalada", ERROR: "Error anterior" };
    const modal = document.createElement("div");
    modal.id = "modal-aulas-huella";
    modal.className = "app-modal";
    modal.innerHTML = `
        <div class="app-modal-dialog max-w-lg">
            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-xl font-bold">${modo === "captura" ? "Registrar y distribuir huella" : "Enviar huella a aulas"}</h3>
                    <p class="text-sm mt-1" style="color:var(--color-muted)">${escaparHuella(info.profesor.nombre)} ${escaparHuella(info.profesor.apellido)}</p>
                </div>
                <button type="button" class="app-modal-close" data-cancelar-aulas aria-label="Cerrar">×</button>
            </div>
            <p class="text-sm mb-3">Seleccione una o varias aulas incluidas en el horario del profesor.</p>
            <div class="app-checkbox-options" data-lista-aulas>
                ${info.aulas.map(aula => {
                    const ocupada = modo === "sincronizacion" && estadosOcupados.has(aula.estado_sync);
                    const estado = etiquetas[aula.estado_sync] || "Sin enviar";
                    return `<label class="app-checkbox-option${ocupada ? " opacity-60" : ""}">
                        <input type="checkbox" value="${Number(aula.id_aula)}" ${ocupada ? "disabled" : ""}>
                        <span><strong>${escaparHuella(aula.nombre)}</strong><br><small>${escaparHuella(aula.grados || aula.codigo)} · ${escaparHuella(estado)}${Number(aula.nodo_registrado) ? "" : " · Nodo/Gateway no configurado"}</small></span>
                    </label>`;
                }).join("")}
            </div>
            <p data-error-aulas class="app-message-error mt-3" hidden>Seleccione al menos un aula disponible.</p>
            <div class="app-modal-actions mt-4">
                <button type="button" class="btn btn-muted" data-cancelar-aulas>Cancelar</button>
                <button type="button" class="btn btn-success" data-confirmar-aulas>${modo === "captura" ? "Continuar con captura" : "Enviar huella"}</button>
            </div>
        </div>`;
    document.body.appendChild(modal);

    return new Promise(resolve => {
        const cerrar = () => { modal.remove(); resolve(null); };
        modal.querySelectorAll("[data-cancelar-aulas]").forEach(boton => boton.addEventListener("click", cerrar));
        modal.addEventListener("click", evento => { if (evento.target === modal) cerrar(); });
        modal.querySelector("[data-confirmar-aulas]").addEventListener("click", () => {
            const ids = [...modal.querySelectorAll('input[type="checkbox"]:checked')].map(input => Number(input.value));
            if (!ids.length) {
                modal.querySelector("[data-error-aulas]").hidden = false;
                return;
            }
            const aulas = info.aulas.filter(aula => ids.includes(Number(aula.id_aula)));
            modal.remove();
            resolve({ aulas, profesor: info.profesor, huella: info.huella });
        });
    });
}

function escaparHuella(valor) {
    return String(valor ?? "").replace(/[&<>'"]/g, caracter => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#39;", '"': "&quot;"
    })[caracter]);
}

window.sincronizarHuellaProfesor = sincronizarHuellaProfesor;

async function solicitarJson(url, opciones = {}, timeoutMs = 90000) {
    const controlador = new AbortController();
    const temporizador = setTimeout(() => controlador.abort(), timeoutMs);

    try {
        const respuesta = await fetch(url, {
            ...opciones,
            cache: "no-store",
            signal: controlador.signal
        });
        let data;
        try {

            data = await respuesta.json();
        } catch (_) {
            throw new Error(`Respuesta JSON invalida de ${url}`);
        }
        return { respuesta, data };
    } finally {
        clearTimeout(temporizador);
    }
}

async function esperarResultadoCaptura(baseESP32, timeoutMs = 90000) {
    const limite = Date.now() + timeoutMs;

    while (Date.now() < limite) {
        const restante = limite - Date.now();
        const { respuesta, data } = await solicitarJson(
            `${baseESP32}/estado`,
            {},
            Math.max(1000, restante)
        );

        if (!respuesta.ok || data.status !== "success") {
            throw new Error(data.message || `No se pudo consultar el estado (HTTP ${respuesta.status})`);
        }
        if (!data.capturando) return data;

        await new Promise(resolve => setTimeout(resolve, 500));
    }

    throw new DOMException("Tiempo de captura agotado", "AbortError");
}

async function limpiarSlotTemporalRemoto(baseESP32) {
    const { respuesta, data } = await solicitarJson(`${baseESP32}/limpiar`, {}, 10000);
    return {
        ok: respuesta.ok && data.status === "success",
        mensaje: data.message || `No se pudo limpiar el sensor (HTTP ${respuesta.status})`
    };
}

function mostrarEstadoHuella(mensaje, cargando = false, error = false) {
    let modal = document.getElementById("modal-huella");

    if (!modal) {
        modal = document.createElement("div");
        modal.id = "modal-huella";
        modal.className = "fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50";
        modal.innerHTML = `
            <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl text-center">
                <h3 class="text-xl font-bold mb-4">Registro de huella</h3>
                <div id="huella-loader" class="mb-4 text-blue-600 font-semibold"></div>
                <p id="huella-mensaje" class="text-gray-700 whitespace-pre-line"></p>
            </div>
        `;
        document.body.appendChild(modal);
    }

    document.getElementById("huella-loader").innerText = cargando ? "Procesando..." : "";
    document.getElementById("huella-mensaje").innerText = mensaje;
    document.getElementById("huella-mensaje").className = error
        ? "text-red-600 whitespace-pre-line"
        : "text-gray-700 whitespace-pre-line";
}

function bloquearBotonesHuella(estado) {
    document.querySelectorAll(".btn-huella").forEach(btn => {
        btn.disabled = estado;
        btn.classList.toggle("opacity-50", estado);
        btn.classList.toggle("cursor-not-allowed", estado);
    });
}
