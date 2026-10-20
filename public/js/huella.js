async function solicitarCaptura(idEstudiante) {
    const confirmado = window.SISCO && window.SISCO.confirm
        ? await SISCO.confirm({
            title: "Captura Biométrica",
            message: "¿Solicitar captura de huella para este estudiante en el registrador?",
            confirmText: "Solicitar captura",
            type: "primary"
        })
        : confirm("¿Solicitar captura de huella para este estudiante?");

    if (!confirmado) return;

    const formData = new FormData();
    formData.append("id_estudiante", idEstudiante);

    try {
        const res = await fetch(window.BASE_URL + "src/api/Huella/solicitar_captura.php", {
            method: "POST",
            body: formData
        });

        const data = await res.json();
        const esExito = data.status === "success";

        if (window.Toast) {
            if (esExito) {
                Toast.success(data.message, "Biometría");
            } else {
                Toast.error(data.message, "Biometría");
            }
        } else {
            alert(data.message);
        }
    } catch (error) {
        console.error(error);
        if (window.Toast) {
            Toast.error("Error al conectar con el servicio biométrico");
        } else {
            alert("Error al conectar con el servicio biométrico");
        }
    }
}
