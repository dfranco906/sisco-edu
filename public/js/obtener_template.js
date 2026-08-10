async function descargarYGuardarTemplate(userIdGlobal, tipoUsuario = "estudiante") {
    const ipESP32 = "192.168.100.125";

    mostrarEstadoHuella("Conectando con ESP32...", true);

    try {
        bloquearBotonesHuella(true);

        mostrarEstadoHuella("Registrando huella en el sensor...");
        const registro = await fetch(`http://${ipESP32}/registrar`);
        const dataRegistro = await registro.json();

        if (dataRegistro.status !== "success") {
            mostrarEstadoHuella("Error al registrar: " + dataRegistro.message, false, true);
            bloquearBotonesHuella(false);
            return;
        }

        mostrarEstadoHuella("Obteniendo template biométrico...");
        const respuesta = await fetch(`http://${ipESP32}/obtener_template`);
        const data = await respuesta.json();

        if (data.status !== "success") {
            mostrarEstadoHuella("Error al obtener template: " + data.message, false, true);
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

        const resBackend = await fetch(window.BASE_URL + "src/api/Huella/guardar_template.php", {
            method: "POST",
            body: formData
        });

        const resultado = await resBackend.json();

        if (resultado.status === "success") {
            mostrarEstadoHuella("Huella guardada correctamente. Sincronización Gateway pendiente.", false);
            setTimeout(() => location.reload(), 1500);
        } else {
            mostrarEstadoHuella(resultado.message + (resultado.debug ? "\n" + resultado.debug : ""), false, true);
            bloquearBotonesHuella(false);
        }

    } catch (error) {
        console.error(error);
        mostrarEstadoHuella("No se pudo comunicar con el ESP32 o backend.", false, true);
        bloquearBotonesHuella(false);
    }
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
