<?php
$titulo = "Asignaciones";
$api = "src/api/Asignaciones/leer_asignaciones.php";

$columnas = [
    "id_asignacion",
    "profesor",
    "materia",
    "año_lectivo"
];

$filtros = [
    "selects" => [
        ["campo" => "profesor", "label" => "Profesor"],
        ["campo" => "materia", "label" => "Materia"],
        ["campo" => "año_lectivo", "label" => "Año lectivo"]
    ]
];

$formCrear = [
    "api" => "src/api/Asignaciones/crear_asignacion.php",
    "campos" => [
        [
            "name" => "id_profesor",
            "label" => "Profesor",
            "type" => "select",
            "api" => "src/api/Profesor/leer_profesores.php",
            "value" => "id_profesor",
            "labelField" => "nombre_completo"
        ],
        [
            "name" => "id_materia",
            "label" => "Materia",
            "type" => "select",
            "api" => "src/api/Materia/leer_materias.php",
            "value" => "id_materia",
            "labelField" => "nombre"
        ],
        ["name" => "año_lectivo", "label" => "Año lectivo", "type" => "number"]
    ]
];
$apiActualizar = "src/api/Asignaciones/actualizar_asignacion.php";
$apiDesactivar = "src/api/Asignaciones/desactivar_asignacion.php";
$urlDesactivados = "mvc/views/asignaciones/desactivadas.php";
$idCampo = "id_asignacion";
$camposEditar = ["id_profesor", "id_materia", "aÃ±o_lectivo"];

require_once __DIR__ . '/../partials/table_page.php';
