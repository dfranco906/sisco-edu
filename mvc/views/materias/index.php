<?php
$titulo = "Materias";
$api = "src/api/Materia/leer_materias.php";
$columnas = ["id_materia", "nombre", "activo"];
$filtros = [
    "buscar" => ["nombre"],
    "placeholder" => "Buscar materia..."
];
$formCrear = [
    "api" => "src/api/Materia/crear_materia.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre", "type" => "text"]
    ]
];
$apiActualizar = "src/api/Materia/actualizar_materia.php";
$apiDesactivar = "src/api/Materia/desactivar_materia.php";
$urlDesactivados = "mvc/views/materias/desactivadas.php";
$idCampo = "id_materia";
$camposEditar = [
    ["name" => "nombre", "label" => "Nombre", "type" => "text"]
];
require_once __DIR__ . '/../partials/table_page.php';
