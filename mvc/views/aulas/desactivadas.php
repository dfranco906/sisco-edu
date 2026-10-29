<?php
$titulo = "Aulas Desactivadas";
$icono = "🏢";
$subtitulo = "Historial de aulas y espacios físicos archivados o inactivos.";
$api = "src/api/Aula/leer_aulas_desactivadas.php";
$columnas = ["id_aula", "nombre", "codigo", "piso", "ubicacion", "activo"];

$filtros = [
    "buscar" => ["nombre", "codigo", "piso", "ubicacion"],
    "placeholder" => "Buscar aula desactivada...",
    "pestanas" => [
        "campo" => "piso",
        "todosLabel" => "Todos los pisos"
    ]
];

$apiRestaurar = "src/api/Aula/restaurar_aula.php";
$apiEliminar = "src/api/Aula/eliminar_aula.php";
$urlVolver = "mvc/views/aulas/index.php";
$idCampo = "id_aula";

require_once __DIR__ . '/../partials/table_page.php';
