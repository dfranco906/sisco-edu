<?php
$titulo = "Asignaciones Docentes";
$api = "src/api/Asignaciones/leer_asignaciones.php";

$columnas = [
    "id_asignacion",
    "grado",
    "materia",
    "profesor",
    "carga_horaria",
    "año_lectivo"
];

$filtros = [
    "buscar" => ["grado", "materia", "profesor"],
    "placeholder" => "Buscar por curso, materia o profesor...",
    "pestanas" => [
        "campo" => "grado",
        "todosLabel" => "Todos los cursos"
    ],
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
            "name" => "id_grado",
            "label" => "Grado / Curso",
            "type" => "select",
            "api" => "src/api/Grado/leer_grados.php",
            "value" => "id_grado",
            "labelField" => "descripcion",
            "required" => true
        ],
        [
            "name" => "id_materia",
            "label" => "Materia",
            "type" => "select",
            "api" => "src/api/Materia/leer_materias.php",
            "value" => "id_materia",
            "labelField" => "nombre",
            "required" => true
        ],
        [
            "name" => "id_profesor",
            "label" => "Profesor Responsable",
            "type" => "select",
            "api" => "src/api/Profesor/leer_profesores.php",
            "value" => "id_profesor",
            "labelField" => "nombre_completo",
            "required" => true
        ],
        [
            "name" => "carga_horaria",
            "label" => "Carga Horaria Semanal (Horas Cátedra)",
            "type" => "number",
            "required" => true
        ],
        [
            "name" => "año_lectivo",
            "label" => "Año Lectivo",
            "type" => "number",
            "required" => true
        ]
    ]
];

$apiActualizar = "src/api/Asignaciones/actualizar_asignacion.php";
$apiDesactivar = "src/api/Asignaciones/desactivar_asignacion.php";
$urlDesactivados = "mvc/views/asignaciones/desactivadas.php";
$urlVolver = "mvc/views/administracion/index.php";
$idCampo = "id_asignacion";
$camposEditar = ["id_grado", "id_materia", "id_profesor", "carga_horaria", "año_lectivo"];

require_once __DIR__ . '/../partials/table_page.php';
