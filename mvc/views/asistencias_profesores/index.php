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
$formCrear = [
    "api" => "src/api/AsistenciaProfesor/crear_asistencia_profesor.php",
    "campos" => [
        [
            "name" => "id_profesor",
            "label" => "Profesor",
            "type" => "select",
            "api" => "src/api/Profesor/leer_profesores.php",
            "value" => "id_profesor",
            "labelField" => "nombre_completo"
        ],
        ["name" => "fecha", "label" => "Fecha", "type" => "date"],
        ["name" => "hora", "label" => "Hora", "type" => "time"],
        [
            "name" => "estado",
            "label" => "Estado",
            "type" => "select",
            "options" => [
                ["value" => "PRESENTE", "label" => "Presente"],
                ["value" => "AUSENTE", "label" => "Ausente"],
                ["value" => "TARDANZA", "label" => "Tardanza"]
            ]
        ]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';