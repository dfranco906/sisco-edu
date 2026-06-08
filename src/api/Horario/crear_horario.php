<?php
// src/api/crear_horario.php

require_once '../../config/db.php';
require_once '../../classes/Horario.php';

header("Content-Type: application/json; charset=UTF-8");

$database = new Database();
$db = $database->getConnection();
$horario = new Horario($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Asignamos los valores recibidos
    $horario->id_asignacion = $_POST['id_asignacion'] ?? null;
    $horario->grado = $_POST['grado'] ?? null;
    $horario->dia_semana = $_POST['dia_semana'] ?? null;
    $horario->hora_inicio = $_POST['hora_inicio'] ?? null;
    $horario->hora_fin = $_POST['hora_fin'] ?? null;
    $horario->aula = $_POST['aula'] ?? null;    

    // Validación: No pueden faltar estos datos
    if (!empty($horario->id_asignacion) && !empty($horario->dia_semana) && !empty($horario->hora_inicio)) {
        
        if ($horario->crear()) {
            http_response_code(201);
            echo json_encode(["message" => "Horario asignado correctamente."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Error al registrar el horario."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Faltan datos requeridos (Asignación, Día o Hora)."]);
    }
} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido."]);
}
?>