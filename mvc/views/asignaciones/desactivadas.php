<?php
$titulo = "Asignaciones desactivadas";
$api = "src/api/Asignaciones/leer_asignaciones_desactivadas.php";

$columnas = ["id_asignacion", "profesor", "materia", "anio_lectivo", "activo"];

$filtros = [
    "buscar" => ["profesor", "materia"],
    "placeholder" => "Buscar profesor o materia...",
    "selects" => [
        ["campo" => "profesor", "label" => "Profesor"],
        ["campo" => "materia", "label" => "Materia"],
        ["campo" => "anio_lectivo", "label" => "Año lectivo"]
    ]
];

$apiRestaurar = "src/api/Asignaciones/restaurar_asignacion.php";
$apiEliminar = "src/api/Asignaciones/eliminar_asignacion.php";
$urlVolver = "mvc/views/asignaciones/index.php";
$idCampo = "id_asignacion";

require_once __DIR__ . '/../partials/table_page.php';
