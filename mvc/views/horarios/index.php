<?php
$titulo = "Horarios por Grado";
$api = "src/api/Horario/leer_horarios.php";
$columnas = [
    "grado",
    "dia_semana",
    "hora_inicio",
    "hora_fin",
    "materia",
    "profesor",
    "aula"
];
$filtros = [
    "buscar" => ["materia", "profesor"],
    "placeholder" => "Buscar materia o profesor...",
    "selects" => [
        ["campo" => "grado", "label" => "Grado"],
        ["campo" => "dia_semana", "label" => "Día"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';