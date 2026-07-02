<?php
$titulo = "Materias desactivadas";
$api = "src/api/Materia/leer_materias_desactivadas.php";

$columnas = ["id_materia", "nombre", "descripcion", "carga_horaria_semanal", "activo"];

$filtros = [
    "buscar" => ["nombre", "descripcion"],
    "placeholder" => "Buscar materia desactivada...",
    "selects" => [
        ["campo" => "carga_horaria_semanal", "label" => "Carga horaria"]
    ]
];

$apiRestaurar = "src/api/Materia/restaurar_materia.php";
$apiEliminar = "src/api/Materia/eliminar_materia.php";
$urlVolver = "mvc/views/materias/index.php";
$idCampo = "id_materia";

require_once __DIR__ . '/../partials/table_page.php';
