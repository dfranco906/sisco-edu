async function descargarYGuardarTemplate(idEstudiante) {
    const ipESP32 = "192.168.100.37";

    try {
        const registro = await fetch(`http://${ipESP32}/registrar`);
        const dataRegistro = await registro.json();

        if (dataRegistro.status !== "success") {
            alert("Error al registrar huella: " + dataRegistro.message);
            return;
        }

        const respuesta = await fetch(`http://${ipESP32}/obtener_template`);
        const data = await respuesta.json();

        if (data.status !== "success") {
            alert("Error al obtener template: " + data.message);
            return;
        }

        const formData = new FormData();
        formData.append("id_estudiante", idEstudiante);
        formData.append("template", data.template);
        formData.append("bytes", data.bytes);

        const resBackend = await fetch(window.BASE_URL + "src/api/Huella/guardar_template.php", {
            method: "POST",
            body: formData
        });

        const resultado = await resBackend.json();
        alert(resultado.message);

        if (resultado.status === "success") {
            location.reload();
        }

    } catch (error) {
        console.error(error);
        alert(resultado.message + (resultado.debug ? "\n\nDEBUG: " + resultado.debug : ""));
    }
}