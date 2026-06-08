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
$formCrear = [
    "api" => "src/api/AsistenciaEstudiante/crear_asistencia_estudiante.php",
    "campos" => [
        ["name" => "id_estudiante", "label" => "ID Estudiante", "type" => "number"],
        ["name" => "huella_id", "label" => "Huella ID", "type" => "number"],
        ["name" => "fecha", "label" => "Fecha", "type" => "date"],
        ["name" => "hora", "label" => "Hora", "type" => "time"],
        ["name" => "estado", "label" => "Estado", "type" => "text"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';