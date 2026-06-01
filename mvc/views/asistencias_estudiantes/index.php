<?php
$titulo = "Asistencias de Estudiantes";
$api = "src/api/AsistenciaEstudiante/leer_asistencias_estudiantes.php";
$columnas = [
    "id_asistencia_estudiante",
    "id_estudiante",
    "huella_id",
    "nombre",
    "apellido",
    "fecha",
    "hora",
    "estado"
];
$filtros = [
    "selects" => [
        ["campo" => "grado", "label" => "Grado"],
        ["campo" => "fecha", "label" => "Fecha"],
        ["campo" => "estado", "label" => "Estado"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';