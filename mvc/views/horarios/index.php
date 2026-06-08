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
$formCrear = [
    "api" => "src/api/Horario/crear_horario.php",
    "campos" => [
        [
            "name" => "id_asignacion",
            "label" => "Asignación",
            "type" => "select",
            "api" => "src/api/Asignaciones/leer_asignaciones.php",
            "value" => "id_asignacion",
            "labelField" => "descripcion"
        ],
        ["name" => "grado", "label" => "Grado", "type" => "text"],
        ["name" => "dia_semana", "label" => "Día", "type" => "text"],
        ["name" => "hora_inicio", "label" => "Hora inicio", "type" => "time"],
        ["name" => "hora_fin", "label" => "Hora fin", "type" => "time"],
        ["name" => "aula", "label" => "Aula", "type" => "text"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';