<?php
// src/api/leer_profesores.php

// 1. Configurar las cabeceras para que acepte peticiones HTTP GET y devuelva JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

// 2. Incluir archivos necesarios del backend
require_once '../config/db.php';
require_once '../classes/Profesor.php';

// 3. Inicializar la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// 4. Inicializar el objeto Profesor
$profesor = new Profesor($db);

/**
 * LÓGICA DE PROCESAMIENTO (READ)
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // Llamamos al método leer() de la clase
    $stmt = $profesor->leer();
    
    // Contamos cuántas filas nos devolvió la base de datos
    $num = $stmt->rowCount();

    // Verificamos si hay al menos un profesor registrado
    if ($num > 0) {
        
        // Creamos un arreglo vacío donde guardaremos los datos
        $profesores_arr = array();
        $profesores_arr["data"] = array();

        // Recorremos los resultados que nos dio la base de datos
        // fetch(PDO::FETCH_ASSOC) convierte cada fila en un arreglo asociativo
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Extraemos las variables de la fila actual
            extract($row);

            $profesor_item = array(
                "id_profesor" => $id_profesor,
                "nombre" => $nombre,
                "apellido" => $apellido,
                "cedula_identidad" => $cedula_identidad,
                "huella_id" => $huella_id,
                "fecha_registro" => $fecha_registro
            );

            // Empujamos este profesor al arreglo principal
            array_push($profesores_arr["data"], $profesor_item);
        }

        // Devolvemos un código 200 (OK) y el JSON con los datos
        http_response_code(200);
        echo json_encode($profesores_arr);

    } else {
        // Si no hay profesores registrados, devolvemos un 404 (Not Found)
        http_response_code(404);
        echo json_encode(
            array("message" => "No se encontraron profesores registrados.")
        );
    }
} else {
    // Si intentan acceder por POST u otro método no permitido
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido. Usa GET para leer datos."]);
}
?>