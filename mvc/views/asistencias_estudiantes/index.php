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
        [
            "name" => "id_estudiante",
            "label" => "Estudiante",
            "type" => "select",
            "api" => "src/api/Estudiante/leer_estudiantes.php",
            "value" => "id_estudiante",
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
$apiActualizar = "src/api/AsistenciaEstudiante/actualizar_asistencia_estudiante.php";
$apiDesactivar = "src/api/AsistenciaEstudiante/desactivar_asistencia_estudiante.php";
$urlDesactivados = "mvc/views/asistencias_estudiantes/desactivados.php";
$idCampo = "id_asistencia_estudiante";
$camposEditar = ["id_estudiante", "huella_id", "fecha", "hora", "estado"];
require_once __DIR__ . '/../partials/table_page.php';
