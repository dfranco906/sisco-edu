<?php
$titulo = "Asistencias de Profesores";
$api = "src/api/AsistenciaProfesor/leer_asistencias_profesores.php";
$columnas = [
    "id_asistencia_profesor",
    "id_profesor",
    "huella_id",
    "nombre",
    "apellido",
    "fecha",
    "hora",
    "estado"
];
$filtros = [
    "selects" => [
        ["campo" => "fecha", "label" => "Fecha"],
        ["campo" => "profesor", "label" => "Profesor"],
        ["campo" => "estado", "label" => "Estado"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';