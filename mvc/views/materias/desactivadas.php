<?php
$titulo = "Materias desactivadas";
$api = "src/api/Materia/leer_materias_desactivadas.php";

$columnas = ["id_materia", "nombre", "activo"];

$filtros = [
    "buscar" => ["nombre"],
    "placeholder" => "Buscar materia desactivada..."
];

$apiRestaurar = "src/api/Materia/restaurar_materia.php";
$apiEliminar = "src/api/Materia/eliminar_materia.php";
$urlVolver = "mvc/views/materias/index.php";
$idCampo = "id_materia";

require_once __DIR__ . '/../partials/table_page.php';
