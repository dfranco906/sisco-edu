<?php
$titulo = "Asistencias de profesores desactivadas";
$api = "src/api/AsistenciaProfesor/leer_asistencias_profesores_desactivadas.php";

$columnas = ["id_asistencia_profesor", "id_profesor", "huella_id", "nombre", "apellido", "profesor", "fecha", "hora", "estado", "activo"];

$filtros = [
    "selects" => [
        ["campo" => "fecha", "label" => "Fecha"],
        ["campo" => "profesor", "label" => "Profesor"],
        ["campo" => "estado", "label" => "Estado"]
    ]
];

$apiRestaurar = "src/api/AsistenciaProfesor/restaurar_asistencia_profesor.php";
$apiEliminar = "src/api/AsistenciaProfesor/eliminar_asistencia_profesor.php";
$urlVolver = "mvc/views/asistencias_profesores/index.php";
$idCampo = "id_asistencia_profesor";

require_once __DIR__ . '/../partials/table_page.php';
