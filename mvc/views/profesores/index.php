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

require_once __DIR__ . '/../partials/table_page.php';