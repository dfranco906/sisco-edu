<?php
$titulo = "Asistencias";
$api = "src/api/Asistencia/leer_asistencias.php";
$columnas = ["id_asistencia", "id_estudiante", "id_horario", "fecha", "hora", "estado"];
$filtros = [
    "selects" => [
        ["campo" => "grado", "label" => "Grado"],
        ["campo" => "fecha", "label" => "Fecha"],
        ["campo" => "estado", "label" => "Estado"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';