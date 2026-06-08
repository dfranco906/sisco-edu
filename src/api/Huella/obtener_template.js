async function descargarYGuardarTemplate() {
    const ipESP32 = "192.168.100.37"; // IP del equipo de registro
    
    try {
        let respuesta = await fetch(`http://${ipESP32}/obtener_template`);
        let data = await respuesta.json();
        
        if (data.status === "success") {
            // AQUÍ TENES TU VARIABLE LISTA PARA ENVIAR AL BACKEND PHP
            let huellaHexadecimal = data.template; 
            console.log("Template capturado con éxito. Longitud del String:", huellaHexadecimal.length);
            
            // Ejemplo de envío inmediato a tu base de datos central a través de tu Backend:
            // enviarAlServidorCentral(idAlumno, huellaHexadecimal);
        } else {
            alert("Error: " + data.message);
        }
    } catch (error) {
        console.error("Error de comunicación de red:", error);
    }
}

// Ejmplo de respuesta de la API del ESP32:
//{
//  "status": "success",
//  "bytes": 1536,
//  "template": "010000e304f21a...[aquí van los 3072 caracteres hex]...003f"
//}