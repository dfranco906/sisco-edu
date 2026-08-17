<?php
$titulo = "Materias";
$api = "src/api/Materia/leer_materias.php";
$columnas = ["id_materia", "nombre", "descripcion", "carga_horaria_semanal", "activo"];
$filtros = [
    "buscar" => ["nombre", "descripcion"],
    "placeholder" => "Buscar materia...",
    "selects" => [
        ["campo" => "carga_horaria_semanal", "label" => "Carga horaria"]
    ]
];
$formCrear = [
    "api" => "src/api/Materia/crear_materia.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre", "type" => "text"],
        ["name" => "descripcion", "label" => "Descripción", "type" => "text", "required" => false],
        ["name" => "carga_horaria_semanal", "label" => "Carga horaria semanal", "type" => "number", "min" => 1]
    ]
];
$apiActualizar = "src/api/Materia/actualizar_materia.php";
$apiDesactivar = "src/api/Materia/desactivar_materia.php";
$urlDesactivados = "mvc/views/materias/desactivadas.php";
$idCampo = "id_materia";
$camposEditar = [
    ["name" => "nombre", "label" => "Nombre", "type" => "text"],
    ["name" => "descripcion", "label" => "Descripción", "type" => "text", "required" => false],
    ["name" => "carga_horaria_semanal", "label" => "Carga horaria semanal", "type" => "number", "min" => 1]
];
require_once __DIR__ . '/../partials/table_page.php';
