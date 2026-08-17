<?php
// src/api/crear_profesor.php

// 1. Incluir archivos necesarios del backend
require_once '../../config/db.php';
require_once '../../classes/Profesor.php';

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
    
    $profesor->nombre = $_POST['nombre'] ?? null;
    $profesor->apellido = $_POST['apellido'] ?? null;
    $profesor->cedula_identidad = $_POST['cedula_identidad'] ?? null;
    $profesor->user_id_global = $_POST['user_id_global'] ?? ('PROF_' . uniqid() . '_' . random_int(100, 999));

    if (!$profesor->nombre || !$profesor->apellido || !$profesor->cedula_identidad) {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        exit;
    }

    if ($profesor->crear()) {
        http_response_code(201);
        echo json_encode(["status" => "success", "message" => "Profesor creado con éxito."]);
    } else {
        http_response_code(503);
        echo json_encode(["status" => "error", "message" => "Error interno al guardar en la base de datos."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
}
?>