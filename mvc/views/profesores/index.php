<?php
$titulo = "Profesores";
$api = "src/api/Profesor/leer_profesores.php";
$columnas = ["id_profesor", "nombre", "apellido", "cedula_identidad", "huella_id"];

$filtros = [
    "buscar" => ["nombre", "apellido", "cedula_identidad"],
    "placeholder" => "Buscar profesor...",
    "selects" => [
        ["campo" => "activo", "label" => "Estado"]
    ]
];
$formCrear = [
    "api" => "src/api/Profesor/crear_profesor.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre", "type" => "text"],
        ["name" => "apellido", "label" => "Apellido", "type" => "text"],
        ["name" => "cedula_identidad", "label" => "Cédula", "type" => "text"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';