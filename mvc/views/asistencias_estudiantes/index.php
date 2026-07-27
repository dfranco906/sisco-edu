<?php
$titulo = "Asistencias de Estudiantes";
$api = "src/api/AsistenciaEstudiante/leer_asistencias_estudiantes.php";
$columnas = [
    "id_asistencia_estudiante",
    "user_id_global",
    "tipo_usuario",
    "nombre",
    "apellido",
    "fecha_hora",
    "estado"
];
$filtros = [
    "buscar" => ["nombre", "apellido"],
    "placeholder" => "Buscar estudiante...",
    "selects" => [
        ["campo" => "id_estudiante", "label" => "Estudiante", "labelFields" => ["nombre", "apellido"]],
        ["campo" => "grado", "label" => "Grado"],
        ["campo" => "fecha_hora", "label" => "Fecha", "orden" => "desc"],
        ["campo" => "estado", "label" => "Estado", "formato" => "titulo"]
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
        ["name" => "fecha_hora", "label" => "Fecha y hora", "type" => "datetime-local"],
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
$camposEditar = ["id_estudiante", "fecha_hora", "estado"];
require_once __DIR__ . '/../partials/table_page.php';
