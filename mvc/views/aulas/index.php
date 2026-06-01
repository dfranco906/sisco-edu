<?php
$titulo = "Aulas";
$api = "src/api/Aula/leer_aulas.php";
$columnas = ["id_aula", "nombre", "codigo", "ubicacion", "activo"];
$filtros = [
    "buscar" => ["nombre", "codigo"],
    "placeholder" => "Buscar aula...",
    "selects" => [
        ["campo" => "ubicacion", "label" => "Ubicación"],
        ["campo" => "activo", "label" => "Estado"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';