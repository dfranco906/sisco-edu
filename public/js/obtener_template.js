async function descargarYGuardarTemplate(userIdGlobal, tipoUsuario = "estudiante") {
    const ipESP32 = "192.168.100.125";
    const baseESP32 = `http://${ipESP32}`;

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
            && /^[0-9a-fA-F]{3072}$/.test(data.template);
        if (!templateValido) {
            mostrarEstadoHuella("El ESP32 devolvio un template incompatible; se esperaban 1536 bytes HEX.", false, true);
            bloquearBotonesHuella(false);
            return;
        }

        mostrarEstadoHuella("Guardando huella en la base de datos...");

        const formData = new FormData();
        formData.append("user_id_global", userIdGlobal);
        formData.append("tipo_usuario", tipoUsuario);
        formData.append("template", data.template);
        formData.append("bytes", data.bytes);

        const { respuesta: resBackend, data: resultado } = await solicitarJson(window.BASE_URL + "src/api/Huella/guardar_template.php", {
            method: "POST",
            body: formData
        });

        if (resBackend.ok && resultado.status === "success") {
            mostrarEstadoHuella("Huella guardada correctamente. Sincronización Gateway pendiente.", false);
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
