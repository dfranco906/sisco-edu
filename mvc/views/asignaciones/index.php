<?php
$titulo = "Asignaciones";
$subtitulo = "Un profesor puede dictar la misma materia en varios grados; cada grado conserva su propia carga, aula y horarios.";
$api = "src/api/Asignaciones/leer_asignaciones.php";

$columnas = [
    "id_asignacion",
    "profesor",
    "materia",
    "grado",
    "aula",
    "carga_horaria",
    "anio_lectivo",
    "activo"
];

$filtros = [
    "principal" => [
        "campo" => "id_grado",
        "label" => "Asignaciones del grado",
        "labelFields" => ["nombre", "grado", "aula"],
        "api" => "src/api/Grado/leer_grados.php?opciones=1",
        "opcionTodos" => "Todos los grados",
        "porDefecto" => "primero"
    ],
    "buscar" => ["profesor", "materia"],
    "placeholder" => "Buscar profesor o materia en el grado...",
    "selects" => [
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
            "labelField" => "nombre_completo",
            "searchable" => true,
            "searchPlaceholder" => "Buscar profesor..."
        ],
        [
            "name" => "id_materia",
            "label" => "Materia",
            "type" => "select",
            "api" => "src/api/Materia/leer_materias.php",
            "value" => "id_materia",
            "labelField" => "nombre",
            "searchable" => true,
            "searchPlaceholder" => "Buscar materia..."
        ],
        [
            "name" => "id_grado",
            "label" => "Grado y aula",
            "type" => "select",
            "api" => "src/api/Grado/leer_grados.php?opciones=1",
            "value" => "id_grado",
            "labelField" => "descripcion",
            "searchable" => true,
            "searchPlaceholder" => "Buscar grado o aula..."
        ],
        [
            "name" => "carga_horaria",
            "label" => "Carga horaria semanal",
            "type" => "number",
            "min" => 1,
            "max" => 100
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
$campoContextoCrear = "id_grado";
$camposEditar = [
    [
        "name" => "id_profesor",
        "label" => "Profesor",
        "type" => "select",
        "api" => "src/api/Profesor/leer_profesores.php",
        "value" => "id_profesor",
        "labelField" => "nombre_completo",
        "currentLabelField" => "profesor",
        "searchable" => true,
        "searchPlaceholder" => "Buscar profesor..."
    ],
    [
        "name" => "id_materia",
        "label" => "Materia",
        "type" => "select",
        "api" => "src/api/Materia/leer_materias.php",
        "value" => "id_materia",
        "labelField" => "nombre",
        "currentLabelField" => "materia",
        "searchable" => true,
        "searchPlaceholder" => "Buscar materia..."
    ],
    [
        "name" => "id_grado",
        "label" => "Grado y aula",
        "type" => "select",
        "api" => "src/api/Grado/leer_grados.php?opciones=1",
        "value" => "id_grado",
        "labelField" => "descripcion",
        "currentLabelFields" => ["grado", "aula"],
        "searchable" => true,
        "searchPlaceholder" => "Buscar grado o aula..."
    ],
    [
        "name" => "carga_horaria",
        "label" => "Carga horaria semanal",
        "type" => "number",
        "min" => 1,
        "max" => 100
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
