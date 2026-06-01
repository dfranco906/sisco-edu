<?php
$titulo = "Asignaciones";
$api = "src/api/Asignaciones/leer_asignaciones.php";
$columnas = [
    "id_asignacion",
    "profesor",
    "materia",
    "año_lectivo"
];
$filtros = [
    "selects" => [
        ["campo" => "profesor", "label" => "Profesor"],
        ["campo" => "materia", "label" => "Materia"],
        ["campo" => "año_lectivo", "label" => "Año lectivo"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';