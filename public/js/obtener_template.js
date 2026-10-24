/**
 * SISCO-EDU: Gestión de Captura y Enrolamiento Biométrico
 * Comunica el navegador con el ESP32 Registrador vía proxy del backend
 * o con fallback directo HTTP para registrar templates de 1536 bytes.
 */

async function llamadaRegistrador(accion, ipESP32, timeoutMs = 15000) {
    // 1. Intentar primero a través del proxy del backend (mismo origen, evita bloqueos CORS/PNA del navegador)
    const urlProxy = (window.BASE_URL || "") + `src/api/Huella/proxy_registrador.php?accion=${encodeURIComponent(accion)}`;
    try {
        const resultado = await solicitarJson(urlProxy, {}, timeoutMs);
        if (resultado.respuesta.ok) {
            return resultado;
        }
        console.warn(`[Biometría] Proxy backend respondió status ${resultado.respuesta.status}`);
    } catch (e) {
        console.warn(`[Biometría] Falló llamada proxy para '${accion}' (${e.message}), intentando directo a ESP32...`);
    }

    // 2. Fallback a conexión HTTP directa al ESP32
    const urlDirecta = `http://${ipESP32}/${accion}`;
    return await solicitarJson(urlDirecta, {}, timeoutMs);
}

async function descargarYGuardarTemplate(userIdGlobal, tipoUsuario = "estudiante") {
    const ipESP32 = (window.CONFIG_RED && window.CONFIG_RED.ipRegistrador) ? window.CONFIG_RED.ipRegistrador : "192.168.100.39";

    mostrarEstadoHuella(`Conectando con ESP32 Registrador (${ipESP32})...`, true);

    try {
        bloquearBotonesHuella(true);

        mostrarEstadoHuella("Iniciando captura en el sensor registrador...", true);
        const { respuesta: registro, data: dataRegistro } = await llamadaRegistrador("registrar", ipESP32, 10000);

        if (!registro.ok || dataRegistro.status !== "success") {
            mostrarEstadoHuella("Error al iniciar captura: " + (dataRegistro.message || `HTTP ${registro.status}`), false, true);
            bloquearBotonesHuella(false);
            return;
        }

        mostrarEstadoHuella("Sensor activo. Coloque su dedo 2 veces en el lector...", true);
        const estadoCaptura = await esperarResultadoCaptura(ipESP32);
        if (!estadoCaptura.template_disponible) {
            const detalle = estadoCaptura.ultimo_error || "No se generó un template biométrico válido";
            mostrarEstadoHuella("Error al capturar la huella: " + detalle, false, true);
            bloquearBotonesHuella(false);
            return;
        }

        mostrarEstadoHuella("Descargando template biométrico...", true);
        const { respuesta, data } = await llamadaRegistrador("obtener_template", ipESP32, 15000);

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
            mostrarEstadoHuella("El ESP32 devolvió un template o CRC32 incompatible con el contrato (1536 bytes / 3072 HEX).", false, true);
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

        const { respuesta: resBackend, data: resultado } = await solicitarJson(window.BASE_URL + "src/api/Huella/guardar_template.php", {
            method: "POST",
            body: formData
        });

        if (resBackend.ok && resultado.status === "success") {
            let mensaje = "¡Huella registrada exitosamente!";
            if (resultado.data && resultado.data.gateway_avisado) {
                mensaje += "\nSincronización LoRa iniciada hacia el aula.";
            } else {
                mensaje += "\nEncolada para sincronización LoRa.";
            }
            try {
                const limpieza = await limpiarSlotTemporalRemoto(ipESP32);
                if (!limpieza.ok) {
                    mensaje += `\nAdvertencia sensor: ${limpieza.mensaje}`;
                }
            } catch (errorLimpieza) {
                console.warn("La huella se guardó, pero no se pudo limpiar el slot temporal", errorLimpieza);
            }
            mostrarEstadoHuella(mensaje, false);
            setTimeout(() => location.reload(), 1800);
        } else {
            mostrarEstadoHuella((resultado.message || `Error HTTP ${resBackend.status}`) + (resultado.debug ? "\n" + resultado.debug : ""), false, true);
            bloquearBotonesHuella(false);
        }

    } catch (error) {
        console.error(error);
        const detalle = error.name === "AbortError"
            ? "La captura excedió el tiempo límite (90s)"
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
            throw new Error(`Respuesta JSON inválida de ${url}`);
        }
        return { respuesta, data };
    } finally {
        clearTimeout(temporizador);
    }
}

async function esperarResultadoCaptura(ipESP32, timeoutMs = 90000) {
    const limite = Date.now() + timeoutMs;

    while (Date.now() < limite) {
        const restante = limite - Date.now();
        const { respuesta, data } = await llamadaRegistrador(
            "estado",
            ipESP32,
            Math.min(5000, Math.max(1000, restante))
        );

        if (!respuesta.ok || data.status !== "success") {
            throw new Error(data.message || `No se pudo consultar el estado (HTTP ${respuesta.status})`);
        }
        if (!data.capturando) return data;

        await new Promise(resolve => setTimeout(resolve, 600));
    }

    throw new DOMException("Tiempo de captura agotado", "AbortError");
}

async function limpiarSlotTemporalRemoto(ipESP32) {
    const { respuesta, data } = await llamadaRegistrador("limpiar", ipESP32, 8000);
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
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 w-full max-w-md shadow-2xl text-center border border-slate-200 dark:border-slate-700">
                <h3 class="text-xl font-bold mb-4 text-slate-900 dark:text-slate-100">Registro de Huella</h3>
                <div id="huella-loader" class="mb-4 text-purple-600 dark:text-purple-400 font-semibold animate-pulse"></div>
                <p id="huella-mensaje" class="text-slate-700 dark:text-slate-300 whitespace-pre-line text-sm"></p>
            </div>
        `;
        document.body.appendChild(modal);
    }

    const loader = document.getElementById("huella-loader");
    const msg = document.getElementById("huella-mensaje");
    if (loader) loader.innerText = cargando ? "⚡ En proceso..." : "";
    if (msg) {
        msg.innerText = mensaje;
        msg.className = error
            ? "text-red-600 dark:text-red-400 font-semibold whitespace-pre-line text-sm"
            : "text-slate-700 dark:text-slate-300 whitespace-pre-line text-sm";
    }
}

function bloquearBotonesHuella(estado) {
    document.querySelectorAll(".btn-huella, #mbp-btn-iniciar-captura").forEach(btn => {
        btn.disabled = estado;
        btn.classList.toggle("opacity-50", estado);
        btn.classList.toggle("cursor-not-allowed", estado);
    });
}
