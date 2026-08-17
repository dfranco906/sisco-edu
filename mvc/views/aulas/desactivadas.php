<?php
$titulo = "Aulas desactivadas";
$api = "src/api/Aula/leer_aulas_desactivadas.php";
$columnas = ["id_aula", "nombre", "codigo", "ubicacion", "activo"];

$filtros = [
    "buscar" => ["nombre", "codigo"],
    "placeholder" => "Buscar aula desactivada...",
    "selects" => [
        ["campo" => "ubicacion", "label" => "Ubicacion"]
    ]
];

$apiRestaurar = "src/api/Aula/restaurar_aula.php";
$apiEliminar = "src/api/Aula/eliminar_aula.php";
$urlVolver = "mvc/views/aulas/index.php";
$idCampo = "id_aula";

require_once __DIR__ . '/../partials/table_page.php';
?>
