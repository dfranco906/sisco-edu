<?php
$titulo = "Bachilleratos / Especialidades";
$api = "src/api/Bachillerato/leer_bachilleratos.php";
$columnas = ["id_bachillerato", "nombre", "codigo", "tipo", "descripcion", "activo"];

$filtros = [
    "buscar" => ["nombre", "codigo", "tipo", "descripcion"],
    "placeholder" => "Buscar bachillerato o especialidad...",
];

$formCrear = [
    "api" => "src/api/Bachillerato/crear_bachillerato.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre de la Especialidad (ej. Informática, Ciencias Básicas)", "type" => "text"],
        ["name" => "codigo", "label" => "Código / Sigla (ej. BTI, BCB, BCS, BTC)", "type" => "text"],
        [
            "name" => "tipo",
            "label" => "Tipo de Bachillerato",
            "type" => "select",
            "options" => [
                ["value" => "Técnico", "label" => "Bachillerato Técnico"],
                ["value" => "Científico", "label" => "Bachillerato Científico"]
            ]
        ],
        ["name" => "descripcion", "label" => "Descripción / Enfoque curricular", "type" => "text"]
    ]
];

$apiActualizar = "src/api/Bachillerato/actualizar_bachillerato.php";
$apiDesactivar = "src/api/Bachillerato/desactivar_bachillerato.php";
$urlDesactivados = "mvc/views/bachilleratos/desactivados.php";
$urlVolver = "mvc/views/administracion/index.php";
$idCampo = "id_bachillerato";
$camposEditar = ["nombre", "codigo", "tipo", "descripcion"];

require_once __DIR__ . '/../partials/table_page.php';
