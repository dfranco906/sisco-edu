/**
 * SISCO-EDU: Gestión de Captura y Enrolamiento Biométrico
 * Comunica el navegador con el ESP32 Registrador vía Web Serial API (Cable USB directo)
 * o con fallback automático a red Wi-Fi (proxy backend / HTTP directo)
 * para registrar templates normalizados de 1536 bytes (3072 caracteres HEX).
 * 
 * Separación de momentos:
 * - Momento 1: descargarYGuardarTemplate() registra en la base de datos sin recargar la página.
 * - Momento 2: enviarHuellaAulas() encola la huella seleccionada hacia el Gateway LoRa para sincronización en las aulas.
 */

function urlApi(relativa) {
    const base = (window.BASE_URL || "").replace(/\/+$/, "");
    const limpia = (relativa || "").replace(/^\/+/, "");
    return base ? `${base}/${limpia}` : `/${limpia}`;
}

// ============================================================================
// ADAPTADOR WEB SERIAL API (CONEXIÓN DIRECTA POR CABLE USB)
// ============================================================================
const SiscoWebSerial = {
    port: null,
    reader: null,
    writer: null,
    isConnected: false,
    lineBuffer: "",
    lineWaiters: [],

    isSupported() {
        return Boolean(typeof navigator !== "undefined" && "serial" in navigator);
    },

    async conectar() {
        if (!this.isSupported()) {
            throw new Error("Web Serial API no es compatible con este navegador. Utilice Google Chrome o Microsoft Edge.");
        }
        if (this.isConnected && this.port) {
            return true;
        }

        try {
            this.port = await navigator.serial.requestPort();
            await this.port.open({ baudRate: 115200 });
            this.isConnected = true;
            this.lineBuffer = "";
            this._iniciarLectura();

            // Saludo de confirmación con el registrador
            try {
                await this.escribir("CMD:PING");
                const resp = await this.esperarLinea(l => l.startsWith("PONG:"), 3000);
                console.log("[WebSerial] Handshake con registrador exitoso:", resp);
            } catch (e) {
                console.warn("[WebSerial] Handshake sin confirmación inmediata:", e.message);
            }

            this._notificarEstado(true);
            return true;
        } catch (err) {
            this.isConnected = false;
            this._notificarEstado(false);
            throw err;
        }
    },

    async desconectar() {
        try {
            if (this.reader) {
                await this.reader.cancel();
                this.reader = null;
            }
            if (this.port) {
                await this.port.close();
                this.port = null;
            }
        } catch (e) {
            console.warn("[WebSerial] Error al cerrar puerto serial:", e);
        } finally {
            this.isConnected = false;
            this._notificarEstado(false);
        }
    },

    _notificarEstado(conectado) {
        window.dispatchEvent(new CustomEvent("siscoSerialStatus", { detail: { conectado } }));
        actualizarBadgesCanalSerie();
    },

    async _iniciarLectura() {
        const textDecoder = new TextDecoderStream();
        this.port.readable.pipeTo(textDecoder.writable).catch(() => {});
        const reader = textDecoder.readable.getReader();
        this.reader = reader;

        try {
            while (this.isConnected) {
                const { value, done } = await reader.read();
                if (done) break;
                if (value) {
                    this.lineBuffer += value;
                    let idx;
                    while ((idx = this.lineBuffer.indexOf("\n")) >= 0) {
                        const line = this.lineBuffer.slice(0, idx).trim();
                        this.lineBuffer = this.lineBuffer.slice(idx + 1);
                        if (line.length > 0) {
                            this._procesarLinea(line);
                        }
                    }
                }
            }
        } catch (err) {
            console.warn("[WebSerial] Lectura finalizada o puerto desconectado:", err.message);
        } finally {
            try { reader.releaseLock(); } catch (_) {}
            this.isConnected = false;
            this._notificarEstado(false);
        }
    },

    _procesarLinea(line) {
        // Atender a promesas que esperan respuestas de comandos
        for (let i = 0; i < this.lineWaiters.length; i++) {
            const waiter = this.lineWaiters[i];
            if (waiter.filtro(line)) {
                this.lineWaiters.splice(i, 1);
                waiter.resolve(line);
                return;
            }
        }
        window.dispatchEvent(new CustomEvent("siscoSerialLine", { detail: { line } }));
    },

    async escribir(texto) {
        if (!this.port || !this.port.writable) {
            throw new Error("El puerto USB serial no está abierto para escritura.");
        }
        const encoder = new TextEncoder();
        const writer = this.port.writable.getWriter();
        try {
            await writer.write(encoder.encode(texto + "\n"));
        } finally {
            writer.releaseLock();
        }
    },

    esperarLinea(filtro, timeoutMs = 45000) {
        return new Promise((resolve, reject) => {
            const timer = setTimeout(() => {
                const idx = this.lineWaiters.findIndex(w => w.resolve === resolve);
                if (idx !== -1) this.lineWaiters.splice(idx, 1);
                reject(new Error("Tiempo agotado esperando respuesta del nodo registrador USB."));
            }, timeoutMs);

            this.lineWaiters.push({
                filtro,
                resolve: (line) => {
                    clearTimeout(timer);
                    resolve(line);
                }
            });
        });
    },

    async enviarComando(cmd, timeoutMs = 15000) {
        await this.escribir(cmd);
        return await this.esperarLinea(
            l => l.startsWith("PONG:") || l.startsWith("STATUS:") || l.startsWith("TEMPLATE:") || l.startsWith("ERROR:") || l.startsWith("OK:") || l.startsWith("{"),
            timeoutMs
        );
    },

    async capturarYExportar(onProgreso = null) {
        if (onProgreso) onProgreso("Sensor USB activo. Coloque su dedo 2 veces en el lector óptico...");
        await this.escribir("CMD:CAPTURAR");

        const linea = await this.esperarLinea(
            l => l.startsWith("TEMPLATE:") || l.startsWith("ERROR:"),
            90000
        );

        if (linea.startsWith("ERROR:")) {
            const err = linea.substring(6).trim();
            throw new Error(err || "Error durante la captura óptica en el lector.");
        }

        if (linea.startsWith("TEMPLATE:")) {
            const jsonStr = linea.substring(9).trim();
            try {
                return JSON.parse(jsonStr);
            } catch (e) {
                throw new Error("El nodo USB devolvió un template con formato JSON no válido.");
            }
        }

        throw new Error("Respuesta no reconocida del sensor registrador.");
    }
};

window.SiscoWebSerial = SiscoWebSerial;

// ============================================================================
// CONEXIÓN WI-FI (FALLBACK AUTOMÁTICO)
// ============================================================================
async function llamadaRegistrador(accion, ipESP32, timeoutMs = 25000) {
    // 1. Intentar primero a través del proxy del backend (mismo origen, evita bloqueos CORS/PNA del navegador)
    const urlProxy = urlApi(`src/api/Huella/proxy_registrador.php?accion=${encodeURIComponent(accion)}`);
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

/**
 * Momento 1: Captura la huella en el lector óptico del nodo registrador
 * (directamente por cable USB vía Web Serial o por Wi-Fi si no está conectado el cable)
 * y la almacena de forma persistente en la base de datos (huellas_templates).
 */
async function descargarYGuardarTemplate(userIdGlobal, tipoUsuario = "estudiante") {
    let data;
    const esUsb = Boolean(SiscoWebSerial.isConnected);

    if (esUsb) {
        mostrarEstadoHuella("Conectado directamente por cable USB (Web Serial). Iniciando captura...", true);
    } else {
        const ipESP32 = (window.CONFIG_RED && window.CONFIG_RED.ipRegistrador) ? window.CONFIG_RED.ipRegistrador : "192.168.100.39";
        mostrarEstadoHuella(`Conectando con ESP32 Registrador vía Wi-Fi (${ipESP32})...`, true);
    }

    try {
        bloquearBotonesHuella(true);

        if (esUsb) {
            data = await SiscoWebSerial.capturarYExportar((msg) => mostrarEstadoHuella(msg, true));
        } else {
            const ipESP32 = (window.CONFIG_RED && window.CONFIG_RED.ipRegistrador) ? window.CONFIG_RED.ipRegistrador : "192.168.100.39";

            mostrarEstadoHuella("Iniciando captura en el sensor registrador...", true);
            const { respuesta: registro, data: dataRegistro } = await llamadaRegistrador("registrar", ipESP32, 12000);

            if (!registro.ok || dataRegistro.status !== "success") {
                const err = "Error al iniciar captura: " + (dataRegistro.message || `HTTP ${registro.status}`);
                mostrarEstadoHuella(err, false, true);
                bloquearBotonesHuella(false);
                return { ok: false, message: err };
            }

            mostrarEstadoHuella("Sensor activo. Coloque su dedo 2 veces en el lector...", true);
            const estadoCaptura = await esperarResultadoCaptura(ipESP32);
            if (!estadoCaptura.template_disponible) {
                const detalle = estadoCaptura.ultimo_error || "No se generó un template biométrico válido";
                const err = "Error al capturar la huella: " + detalle;
                mostrarEstadoHuella(err, false, true);
                bloquearBotonesHuella(false);
                return { ok: false, message: err };
            }

            mostrarEstadoHuella("Descargando template biométrico del lector...", true);
            const { respuesta, data: dataHttp } = await llamadaRegistrador("obtener_template", ipESP32, 20000);

            if (!respuesta.ok || dataHttp.status !== "success") {
                const err = "Error al obtener template: " + (dataHttp.message || `HTTP ${respuesta.status}`);
                mostrarEstadoHuella(err, false, true);
                bloquearBotonesHuella(false);
                return { ok: false, message: err };
            }
            data = dataHttp;
        }

        // Validación estricta del contrato de integridad biométrica (1536 bytes / 3072 HEX / CRC32)
        const templateValido = data.bytes === 1536
            && typeof data.template === "string"
            && /^[0-9a-fA-F]{3072}$/.test(data.template)
            && typeof data.crc32 === "string"
            && /^[0-9a-fA-F]{8}$/.test(data.crc32);

        if (!templateValido) {
            const err = "El ESP32 devolvió un template o CRC32 incompatible con el contrato (1536 bytes / 3072 HEX).";
            mostrarEstadoHuella(err, false, true);
            bloquearBotonesHuella(false);
            return { ok: false, message: err };
        }

        mostrarEstadoHuella("Guardando huella en la base de datos...", true);

        const formData = new FormData();
        formData.append("user_id_global", userIdGlobal);
        formData.append("tipo_usuario", tipoUsuario);
        formData.append("template", data.template);
        formData.append("bytes", data.bytes);
        formData.append("crc32", data.crc32.toLowerCase());

        const { respuesta: resBackend, data: resultado } = await solicitarJson(urlApi("src/api/Huella/guardar_template.php"), {
            method: "POST",
            body: formData
        });

        if (resBackend.ok && resultado.status === "success") {
            try {
                if (esUsb) {
                    await SiscoWebSerial.enviarComando("CMD:LIMPIAR", 4000);
                } else {
                    const ipESP32 = (window.CONFIG_RED && window.CONFIG_RED.ipRegistrador) ? window.CONFIG_RED.ipRegistrador : "192.168.100.39";
                    await limpiarSlotTemporalRemoto(ipESP32);
                }
            } catch (errorLimpieza) {
                console.warn("[Biometría] Advertencia al limpiar slot temporal:", errorLimpieza);
            }

            const canalTexto = esUsb ? "cable USB directo" : "Wi-Fi";
            const mensajeExito = `✓ ¡Huella biométrica guardada exitosamente en la base de datos vía ${canalTexto}!\nPuede sincronizarla con las aulas ahora o en otro momento.`;
            mostrarEstadoHuella(mensajeExito, false, false, true);
            bloquearBotonesHuella(false);

            // Disparar evento desacoplado para actualizar tablas/modales reactivamente sin recargar
            window.dispatchEvent(new CustomEvent("huellaRegistrada", {
                detail: {
                    tipoUsuario,
                    userIdGlobal,
                    idPersona: resultado.data ? resultado.data.id_persona : null,
                    idHuella: resultado.data ? resultado.data.id_huella : null,
                    canal: esUsb ? 'usb' : 'wifi',
                    resultado: resultado.data
                }
            }));

            return { ok: true, data: resultado.data };
        } else {
            const msgError = (resultado.message || `Error HTTP ${resBackend.status}`) + (resultado.debug ? "\n" + resultado.debug : "");
            mostrarEstadoHuella(msgError, false, true);
            bloquearBotonesHuella(false);
            return { ok: false, message: msgError };
        }

    } catch (error) {
        console.error("[Biometría] Excepción en captura:", error);
        const detalle = error.name === "AbortError"
            ? "La captura excedió el tiempo límite (90s)"
            : error.message;
        mostrarEstadoHuella("No se pudo completar el registro: " + detalle, false, true);
        bloquearBotonesHuella(false);
        return { ok: false, message: detalle };
    }
}

/**
 * Momento 2: Encola la huella seleccionada hacia el Gateway LoRa para su transmisión
 * a la o las aulas correspondientes (docente -> aulas con clase / estudiante -> su aula).
 */
async function enviarHuellaAulas({ idPersona, userIdGlobal, tipoUsuario = "estudiante", idAula = null, idHuella = null } = {}) {
    bloquearBotonesHuella(true);

    try {
        const formData = new FormData();
        if (idPersona) formData.append("id_persona", idPersona);
        if (userIdGlobal) formData.append("user_id_global", userIdGlobal);
        formData.append("tipo_usuario", tipoUsuario);
        if (idAula) formData.append("id_aula", idAula);
        if (idHuella) formData.append("id_huella", idHuella);

        const url = urlApi("src/api/Huella/enviar_huella_aulas.php");
        const { respuesta, data } = await solicitarJson(url, {
            method: "POST",
            body: formData
        }, 25000);

        bloquearBotonesHuella(false);

        if (respuesta.ok && data.status === "success") {
            window.dispatchEvent(new CustomEvent("huellaEnviadaAulas", {
                detail: { idPersona, userIdGlobal, tipoUsuario, idAula, idHuella, resultado: data }
            }));
            return { ok: true, data };
        } else {
            const err = (data && data.message) ? data.message : `Error de comunicación (HTTP ${respuesta.status})`;
            return { ok: false, message: err, data };
        }
    } catch (error) {
        console.error("[Biometría] Error al enviar a aulas:", error);
        bloquearBotonesHuella(false);
        return { ok: false, message: error.message };
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

/**
 * Sondeo resiliente de estado vía Wi-Fi:
 * No aborta ante un fallo transitorio o timeout de un único sondeo mientras
 * el usuario está colocando el dedo en el sensor.
 */
async function esperarResultadoCaptura(ipESP32, timeoutMs = 90000) {
    const limite = Date.now() + timeoutMs;
    let fallosConsecutivos = 0;

    while (Date.now() < limite) {
        try {
            const { respuesta, data } = await llamadaRegistrador("estado", ipESP32, 10000);
            if (respuesta.ok && data && data.status === "success") {
                fallosConsecutivos = 0;
                if (!data.capturando) {
                    return data;
                }
            } else {
                fallosConsecutivos++;
                console.warn(`[Biometría] Sondeo /estado devolvió status ${respuesta?.status} (fallo consecutivo ${fallosConsecutivos}):`, data?.message || "");
            }
        } catch (errPoll) {
            fallosConsecutivos++;
            console.warn(`[Biometría] Error transitorio en sondeo de estado (${fallosConsecutivos}):`, errPoll.message);
        }

        if (fallosConsecutivos >= 15) {
            throw new Error("Se interrumpió la comunicación continua con el lector óptico.");
        }

        await new Promise(resolve => setTimeout(resolve, 800));
    }

    throw new DOMException("Tiempo de captura agotado (90s).", "AbortError");
}

async function limpiarSlotTemporalRemoto(ipESP32) {
    const { respuesta, data } = await llamadaRegistrador("limpiar", ipESP32, 10000);
    return {
        ok: respuesta.ok && data.status === "success",
        mensaje: data.message || `No se pudo limpiar el sensor (HTTP ${respuesta.status})`
    };
}

function cerrarModalEstadoHuella() {
    const modal = document.getElementById("modal-huella");
    if (modal) modal.remove();
}

function mostrarEstadoHuella(mensaje, cargando = false, error = false, exito = false) {
    let modal = document.getElementById("modal-huella");

    if (!modal) {
        modal = document.createElement("div");
        modal.id = "modal-huella";
        modal.className = "fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4";
        modal.innerHTML = `
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 w-full max-w-md shadow-2xl text-center border border-slate-200 dark:border-slate-800 relative">
                <button type="button" onclick="cerrarModalEstadoHuella()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-700 dark:hover:text-white text-2xl font-bold p-1">
                    ×
                </button>
                <div id="huella-icono" class="w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-3xl font-bold mb-4"></div>
                <h3 id="huella-titulo" class="text-xl font-bold mb-2 text-slate-900 dark:text-slate-100">Captura de Huella</h3>
                <div id="huella-loader" class="mb-3 text-purple-600 dark:text-purple-400 font-semibold animate-pulse text-sm"></div>
                <p id="huella-mensaje" class="text-slate-700 dark:text-slate-300 whitespace-pre-line text-sm leading-relaxed"></p>
                <div id="huella-acciones" class="mt-5 hidden">
                    <button type="button" onclick="cerrarModalEstadoHuella()" class="btn btn-primary w-full py-2.5 px-4 text-sm font-bold">
                        Aceptar
                    </button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    const icono = document.getElementById("huella-icono");
    const titulo = document.getElementById("huella-titulo");
    const loader = document.getElementById("huella-loader");
    const msg = document.getElementById("huella-mensaje");
    const acciones = document.getElementById("huella-acciones");

    if (loader) loader.innerText = cargando ? "⚡ Comunicación en curso..." : "";

    if (error) {
        if (icono) {
            icono.className = "w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-3xl font-bold mb-4 bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800";
            icono.innerHTML = "✕";
        }
        if (titulo) titulo.innerText = "Error en Captura";
        if (msg) msg.className = "text-rose-600 dark:text-rose-400 font-medium whitespace-pre-line text-sm leading-relaxed";
        if (acciones) acciones.classList.remove("hidden");
    } else if (exito) {
        if (icono) {
            icono.className = "w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-3xl font-bold mb-4 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800";
            icono.innerHTML = "✓";
        }
        if (titulo) titulo.innerText = "¡Registro Exitoso!";
        if (msg) msg.className = "text-emerald-700 dark:text-emerald-300 font-medium whitespace-pre-line text-sm leading-relaxed";
        if (acciones) acciones.classList.remove("hidden");

        setTimeout(() => {
            cerrarModalEstadoHuella();
        }, 3000);
    } else {
        if (icono) {
            icono.className = "w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-3xl font-bold mb-4 bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-800";
            icono.innerHTML = "👆";
        }
        if (titulo) titulo.innerText = "Procesando Biometría";
        if (msg) msg.className = "text-slate-700 dark:text-slate-300 whitespace-pre-line text-sm leading-relaxed";
        if (acciones) acciones.classList.add("hidden");
    }

    if (msg) msg.innerText = mensaje;
}

function bloquearBotonesHuella(estado) {
    document.querySelectorAll(".btn-huella, #mbp-btn-iniciar-captura, #mbe-btn-iniciar-captura, #mbp-btn-enviar-aulas, #mbe-btn-enviar-aulas").forEach(btn => {
        btn.disabled = estado;
        btn.classList.toggle("opacity-50", estado);
        btn.classList.toggle("cursor-not-allowed", estado);
    });
}

// ============================================================================
// GESTIÓN DE INTERFAZ Y CONEXIÓN USB SERIAL
// ============================================================================
async function conectarUsbRegistrador(tipo = 'estudiante') {
    if (!SiscoWebSerial.isSupported()) {
        alert("Su navegador no soporta Web Serial API. Para conexión directa USB utilice Google Chrome o Microsoft Edge.");
        return;
    }
    const btnId = (tipo === 'profesor') ? 'mbp-btn-conectar-usb' : 'mbe-btn-conectar-usb';
    const btn = document.getElementById(btnId);
    if (btn) btn.disabled = true;

    try {
        if (SiscoWebSerial.isConnected) {
            await SiscoWebSerial.desconectar();
        } else {
            await SiscoWebSerial.conectar();
        }
    } catch (e) {
        if (e.name !== 'NotFoundError') {
            alert("No se pudo establecer la conexión por cable USB: " + e.message);
        }
    } finally {
        if (btn) btn.disabled = false;
        actualizarBadgesCanalSerie();
    }
}

function actualizarBadgesCanalSerie() {
    const conectado = Boolean(window.SiscoWebSerial && window.SiscoWebSerial.isConnected);
    
    // Modal estudiante
    const badgeEst = document.getElementById('mbe-badge-canal');
    const btnEstTxt = document.getElementById('mbe-btn-conectar-usb-txt');
    if (badgeEst) {
        if (conectado) {
            badgeEst.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 shadow-sm';
            badgeEst.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span><span>✓ Cable USB</span>';
        } else {
            badgeEst.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700';
            badgeEst.innerHTML = '<span>📶 Modo Wi-Fi</span>';
        }
    }
    if (btnEstTxt) {
        btnEstTxt.textContent = conectado ? 'Desconectar USB' : 'Conectar USB';
    }

    // Modal profesor
    const badgeProf = document.getElementById('mbp-badge-canal');
    const btnProfTxt = document.getElementById('mbp-btn-conectar-usb-txt');
    if (badgeProf) {
        if (conectado) {
            badgeProf.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 shadow-sm';
            badgeProf.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span><span>✓ Cable USB</span>';
        } else {
            badgeProf.className = 'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-medium bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700';
            badgeProf.innerHTML = '<span>📶 Modo Wi-Fi</span>';
        }
    }
    if (btnProfTxt) {
        btnProfTxt.textContent = conectado ? 'Desconectar USB' : 'Conectar USB';
    }
}

// Escuchar cambios de estado para reactividad en la UI
window.addEventListener('siscoSerialStatus', () => actualizarBadgesCanalSerie());

// Exponer funciones globales
window.urlApi = urlApi;
window.llamadaRegistrador = llamadaRegistrador;
window.descargarYGuardarTemplate = descargarYGuardarTemplate;
window.enviarHuellaAulas = enviarHuellaAulas;
window.conectarUsbRegistrador = conectarUsbRegistrador;
window.actualizarBadgesCanalSerie = actualizarBadgesCanalSerie;
window.mostrarEstadoHuella = mostrarEstadoHuella;
window.cerrarModalEstadoHuella = cerrarModalEstadoHuella;
window.bloquearBotonesHuella = bloquearBotonesHuella;
