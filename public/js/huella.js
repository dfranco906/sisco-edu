async function solicitarCaptura(idEstudiante) {
    if (!confirm("¿Solicitar captura de huella para este estudiante?")) return;

    const formData = new FormData();
    formData.append("id_estudiante", idEstudiante);

    const res = await fetch(window.BASE_URL + "src/api/Huella/solicitar_captura.php", {
        method: "POST",
        body: formData
    });

    const data = await res.json();
    alert(data.message);
}