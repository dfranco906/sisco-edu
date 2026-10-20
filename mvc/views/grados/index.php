<?php
$titulo = "Grados";
$api = "src/api/Grado/leer_grados.php";

$columnas = ["id_grado", "nombre", "aula", "turno", "nivel", "bachillerato", "activo"];

$filtros = [
    "buscar" => ["nombre", "aula", "turno", "nivel", "bachillerato"],
    "placeholder" => "Buscar grado...",
    "selects" => [
        ["campo" => "aula", "label" => "Aula"],
        ["campo" => "turno", "label" => "Turno"],
        ["campo" => "nivel", "label" => "Nivel"],
        ["campo" => "bachillerato", "label" => "Bachillerato"]
    ]
];

$formCrear = [
    "api" => "src/api/Grado/crear_grado.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre del grado (ej. 1° BTI, 7° Grado)", "type" => "text"],
        [
            "name" => "id_aula",
            "label" => "Aula asignada",
            "type" => "select",
            "api" => "src/api/Aula/leer_aulas.php",
            "value" => "id_aula",
            "labelField" => "nombre"
        ],
        [
            "name" => "id_turno",
            "label" => "Turno escolar",
            "type" => "select",
            "api" => "src/api/Turno/leer_turnos.php",
            "value" => "id_turno",
            "labelField" => "nombre"
        ],
        [
            "name" => "id_nivel",
            "label" => "Nivel / Ciclo académico",
            "type" => "select",
            "api" => "src/api/NivelAcademico/leer_niveles.php",
            "value" => "id_nivel",
            "labelField" => "nombre"
        ],
        [
            "name" => "id_bachillerato",
            "label" => "Bachillerato / Especialidad (si aplica)",
            "type" => "select",
            "api" => "src/api/Bachillerato/leer_bachilleratos.php",
            "value" => "id_bachillerato",
            "labelField" => "nombre",
            "required" => false
        ]
    ]
];

$apiActualizar = "src/api/Grado/actualizar_grado.php";
$apiDesactivar = "src/api/Grado/desactivar_grado.php";
$urlDesactivados = "mvc/views/grados/desactivados.php";
$urlVolver = "mvc/views/administracion/index.php";
$idCampo = "id_grado";
$camposEditar = ["nombre", "id_aula"];

require_once __DIR__ . '/../partials/table_page.php';
