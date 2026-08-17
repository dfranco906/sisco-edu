<?php
$titulo = "Aulas";
$api = "src/api/Aula/leer_aulas.php";
$columnas = ["id_aula", "nombre", "codigo", "ubicacion", "activo"];
$filtros = [
    "buscar" => ["nombre", "codigo", "ubicacion"],
    "placeholder" => "Buscar aula...",
    "selects" => [
        ["campo" => "ubicacion", "label" => "Ubicación"],
    ]
];
$formCrear = [
    "api" => "src/api/Aula/crear_aula.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre", "type" => "text"],
        ["name" => "codigo", "label" => "Código", "type" => "text"],
        ["name" => "ubicacion", "label" => "Ubicación", "type" => "text", "required" => false]
    ]
];
$apiActualizar = "src/api/Aula/actualizar_aula.php";
$apiDesactivar = "src/api/Aula/desactivar_aula.php";
$urlDesactivados = "mvc/views/aulas/desactivadas.php";
$idCampo = "id_aula";
$camposEditar = [
    ["name" => "nombre", "label" => "Nombre", "type" => "text"],
    ["name" => "codigo", "label" => "Código", "type" => "text"],
    ["name" => "ubicacion", "label" => "Ubicación", "type" => "text", "required" => false]
];
require_once __DIR__ . '/../partials/table_page.php';
