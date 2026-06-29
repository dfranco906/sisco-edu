<?php
$titulo = "Asistencias de profesores desactivadas";
$api = "src/api/AsistenciaProfesor/leer_asistencias_profesores_desactivadas.php";

$columnas = ["id_asistencia_profesor", "id_profesor", "huella_id", "nombre", "apellido", "profesor", "fecha", "hora", "estado", "activo"];

$filtros = [
    "buscar" => ["nombre", "apellido"],
    "placeholder" => "Buscar profesor...",
    "selects" => [
        ["campo" => "id_profesor", "label" => "Profesor", "labelFields" => ["nombre", "apellido"]],
        ["campo" => "fecha", "label" => "Fecha", "orden" => "desc"],
        ["campo" => "estado", "label" => "Estado", "formato" => "titulo"]
    ]
];

$apiRestaurar = "src/api/AsistenciaProfesor/restaurar_asistencia_profesor.php";
$apiEliminar = "src/api/AsistenciaProfesor/eliminar_asistencia_profesor.php";
$urlVolver = "mvc/views/asistencias_profesores/index.php";
$idCampo = "id_asistencia_profesor";

require_once __DIR__ . '/../partials/table_page.php';
