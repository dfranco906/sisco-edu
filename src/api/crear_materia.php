<?php
// src/api/crear_materia.php
require_once '../config/db.php';
require_once '../classes/Materia.php';

$database = new Database();
$db = $database->getConnection();
$materia = new Materia($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $materia->nombre = $_POST['nombre'] ?? null;
    $materia->descripcion = $_POST['descripcion'] ?? null;
    $materia->carga_horaria_semanal = $_POST['carga'] ?? null;

    if (!empty($materia->nombre) && !empty($materia->carga_horaria_semanal)) {
        if ($materia->crear()) {
            http_response_code(201);
            echo json_encode(["message" => "Materia creada."]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Error al crear materia."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Datos incompletos."]);
    }
}