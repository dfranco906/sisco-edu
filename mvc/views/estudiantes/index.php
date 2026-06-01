<?php
$titulo = "Estudiantes";
$api = "src/api/Estudiante/leer_estudiantes.php";
$columnas = ["id_estudiante", "nombre", "apellido", "cedula_identidad", "huella_id"];
$filtros = [
    "buscar" => ["nombre", "apellido", "cedula_identidad"],
    "placeholder" => "Buscar estudiante...",
    "selects" => [
        ["campo" => "grado", "label" => "Grado"],
        ["campo" => "activo", "label" => "Estado"]
    ]
];
$formCrear = [
    "api" => "src/api/Estudiante/crear_estudiante.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre", "type" => "text"],
        ["name" => "apellido", "label" => "Apellido", "type" => "text"],
        ["name" => "cedula_identidad", "label" => "Cédula", "type" => "text"],
        ["name" => "grado", "label" => "Grado", "type" => "text"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';