<?php
$titulo = "Materias";
$api = "src/api/Materia/leer_materias.php";
$columnas = ["id_materia", "nombre", "descripcion", "carga_horaria_semanal", "activo"];
$filtros = [
    "buscar" => ["nombre"],
    "placeholder" => "Buscar materia...",
    "selects" => [
        ["campo" => "carga_horaria_semanal", "label" => "Carga horaria"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';