<?php
$titulo = "Asistencias de estudiantes desactivadas";
$api = "src/api/AsistenciaEstudiante/leer_asistencias_estudiantes_desactivadas.php";

$columnas = ["id_asistencia_estudiante", "id_estudiante", "huella_id", "nombre", "apellido", "grado", "fecha", "hora", "estado", "activo"];

$filtros = [
    "buscar" => ["nombre", "apellido"],
    "placeholder" => "Buscar estudiante...",
    "selects" => [
        ["campo" => "id_estudiante", "label" => "Estudiante", "labelFields" => ["nombre", "apellido"]],
        ["campo" => "grado", "label" => "Grado"],
        ["campo" => "fecha", "label" => "Fecha", "orden" => "desc"],
        ["campo" => "estado", "label" => "Estado", "formato" => "titulo"]
    ]
];

$apiRestaurar = "src/api/AsistenciaEstudiante/restaurar_asistencia_estudiante.php";
$apiEliminar = "src/api/AsistenciaEstudiante/eliminar_asistencia_estudiante.php";
$urlVolver = "mvc/views/asistencias_estudiantes/index.php";
$idCampo = "id_asistencia_estudiante";

require_once __DIR__ . '/../partials/table_page.php';
