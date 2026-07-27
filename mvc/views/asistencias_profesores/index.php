<?php
$titulo = "Asistencias de Profesores";
$api = "src/api/AsistenciaProfesor/leer_asistencias_profesores.php";
$columnas = [
    "id_asistencia_profesor",
    "user_id_global",
    "tipo_usuario",
    "nombre",
    "apellido",
    "fecha_hora",
    "estado"
];
$filtros = [
    "buscar" => ["nombre", "apellido"],
    "placeholder" => "Buscar profesor...",
    "selects" => [
        ["campo" => "id_profesor", "label" => "Profesor", "labelFields" => ["nombre", "apellido"]],
        ["campo" => "fecha_hora", "label" => "Fecha", "orden" => "desc"],
        ["campo" => "estado", "label" => "Estado", "formato" => "titulo"]
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
$apiActualizar = "src/api/AsistenciaProfesor/actualizar_asistencia_profesor.php";
$apiDesactivar = "src/api/AsistenciaProfesor/desactivar_asistencia_profesor.php";
$urlDesactivados = "mvc/views/asistencias_profesores/desactivados.php";
$idCampo = "id_asistencia_profesor";
$camposEditar = ["id_profesor", "fecha_hora", "estado"];
require_once __DIR__ . '/../partials/table_page.php';
