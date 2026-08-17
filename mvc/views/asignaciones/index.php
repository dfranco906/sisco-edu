<?php
$titulo = "Asignaciones";
$api = "src/api/Asignaciones/leer_asignaciones.php";

$columnas = [
    "id_asignacion",
    "profesor",
    "materia",
    "anio_lectivo",
    "activo"
];

$filtros = [
    "buscar" => ["profesor", "materia"],
    "placeholder" => "Buscar profesor o materia...",
    "selects" => [
        ["campo" => "profesor", "label" => "Profesor"],
        ["campo" => "materia", "label" => "Materia"],
        ["campo" => "anio_lectivo", "label" => "Año lectivo"]
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
        [
            "name" => "anio_lectivo",
            "label" => "Año lectivo",
            "type" => "number",
            "min" => 2000,
            "max" => 2100,
            "value" => (int) date('Y')
        ]
    ]
];
$apiActualizar = "src/api/Asignaciones/actualizar_asignacion.php";
$apiDesactivar = "src/api/Asignaciones/desactivar_asignacion.php";
$urlDesactivados = "mvc/views/asignaciones/desactivadas.php";
$idCampo = "id_asignacion";
$camposEditar = [
    [
        "name" => "id_profesor",
        "label" => "Profesor",
        "type" => "select",
        "api" => "src/api/Profesor/leer_profesores.php",
        "value" => "id_profesor",
        "labelField" => "nombre_completo",
        "currentLabelField" => "profesor"
    ],
    [
        "name" => "id_materia",
        "label" => "Materia",
        "type" => "select",
        "api" => "src/api/Materia/leer_materias.php",
        "value" => "id_materia",
        "labelField" => "nombre",
        "currentLabelField" => "materia"
    ],
    [
        "name" => "anio_lectivo",
        "label" => "Año lectivo",
        "type" => "number",
        "min" => 2000,
        "max" => 2100
    ]
];

require_once __DIR__ . '/../partials/table_page.php';
