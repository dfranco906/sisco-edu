<?php
// src/api/crear_profesor.php

// 1. Incluir archivos necesarios del backend
require_once '../config/db.php';
require_once '../classes/Profesor.php';

// 2. Inicializar la conexión
$database = new Database();
$db = $database->getConnection();

// 3. Inicializar el objeto Profesor
$profesor = new Profesor($db);

/**
 * LÓGICA DE PROCESAMIENTO
 * Aquí recibimos los datos mediante el método POST
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Capturamos los datos enviados
    $profesor->nombre = $_POST['nombre'] ?? null;
    $profesor->apellido = $_POST['apellido'] ?? null;
    $profesor->cedula_identidad = $_POST['cedula_identidad'] ?? null;
    $profesor->huella_id = $_POST['huella_id'] ?? null;

    // Validación básica de campos obligatorios
    if (!empty($profesor->nombre) && !empty($profesor->apellido) && !empty($profesor->cedula_identidad)) {
        
        if ($profesor->crear()) {
            // Respuesta de éxito (En el backend solemos devolver códigos de estado)
            http_response_code(201);
            echo json_encode(["message" => "Profesor creado con éxito."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Error interno al guardar en la base de datos."]);
        }
    } else {
        // Datos incompletos
        http_response_code(400);
        echo json_encode(["message" => "Faltan datos obligatorios para el registro."]);
    }
} else {
    // Si intentan entrar por GET u otro método no permitido
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido."]);
}
?>