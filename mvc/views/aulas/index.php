<?php
$titulo = "Aulas";
$api = "src/api/Aula/leer_aulas.php";
$columnas = ["id_aula", "nombre", "codigo", "piso", "ubicacion", "activo"];

$filtros = [
    "buscar" => ["nombre", "codigo", "piso", "ubicacion"],
    "placeholder" => "Buscar aula por nombre o código...",
    "pestanas" => [
        "campo" => "piso",
        "todosLabel" => "Todos los pisos"
    ]
];

$formCrear = [
    "api" => "src/api/Aula/crear_aula.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre del Aula / Espacio", "type" => "text"],
        ["name" => "codigo", "label" => "Código de Sala (ej. 1_RO_BTI, LAB_1)", "type" => "text"],
        [
            "name" => "piso",
            "label" => "Piso / Planta",
            "type" => "select",
            "options" => [
                ["value" => "Planta Baja", "label" => "Planta Baja"],
                ["value" => "1° Piso", "label" => "1° Piso"],
                ["value" => "2° Piso", "label" => "2° Piso"],
                ["value" => "3° Piso", "label" => "3° Piso"],
            ]
        ],
        ["name" => "ubicacion", "label" => "Ubicación detallada (ej. Bloque A - Frente al patio)", "type" => "text"]
    ]
];

$apiActualizar = "src/api/Aula/actualizar_aula.php";
$apiDesactivar = "src/api/Aula/desactivar_aula.php";
$urlDesactivados = "mvc/views/aulas/desactivadas.php";
$urlVolver = "mvc/views/administracion/index.php";
$idCampo = "id_aula";
$camposEditar = ["nombre", "codigo", "piso", "ubicacion"];

require_once __DIR__ . '/../partials/table_page.php';
