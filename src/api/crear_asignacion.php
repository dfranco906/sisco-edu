<?php
// src/api/crear_asignacion.php

require_once '../config/db.php';
require_once '../classes/Asignacion.php';

header("Content-Type: application/json; charset=UTF-8");

$database = new Database();
$db = $database->getConnection();
$asignacion = new Asignacion($db);

// Verificamos que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Obtenemos los IDs que vienen del frontend (o de donde se envíen)
    $asignacion->id_profesor = $_POST['id_profesor'] ?? null;
    $asignacion->id_materia = $_POST['id_materia'] ?? null;
    $asignacion->año_lectivo = $_POST['anio'] ?? date("Y"); // Si no envían, usa el año actual

    // Validación: Los IDs de profesor y materia son obligatorios
    if (!empty($asignacion->id_profesor) && !empty($asignacion->id_materia)) {
        
        if ($asignacion->crear()) {
            http_response_code(201);
            echo json_encode([
                "status" => "success",
                "message" => "Asignación docente creada correctamente."
            ]);
        } else {
            http_response_code(503);
            echo json_encode([
                "status" => "error",
                "message" => "No se pudo realizar la asignación en la base de datos."
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Datos incompletos. Se requiere id_profesor e id_materia."
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido."]);
}
?>