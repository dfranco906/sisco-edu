<?php
$titulo = "Asignaciones desactivadas";
$api = "src/api/Asignaciones/leer_asignaciones_desactivadas.php";

$columnas = ["id_asignacion", "profesor", "materia", "grado", "aula", "carga_horaria", "anio_lectivo", "activo"];

$filtros = [
    "buscar" => ["profesor", "materia", "grado", "aula"],
    "placeholder" => "Buscar profesor, materia, grado o aula...",
    "selects" => [
        ["campo" => "profesor", "label" => "Profesor"],
        ["campo" => "materia", "label" => "Materia"],
        ["campo" => "grado", "label" => "Grado"],
        ["campo" => "aula", "label" => "Aula"],
        ["campo" => "anio_lectivo", "label" => "Año lectivo"]
    ]
];

$apiRestaurar = "src/api/Asignaciones/restaurar_asignacion.php";
$apiEliminar = "src/api/Asignaciones/eliminar_asignacion.php";
$urlVolver = "mvc/views/asignaciones/index.php";
$idCampo = "id_asignacion";

require_once __DIR__ . '/../partials/table_page.php';
