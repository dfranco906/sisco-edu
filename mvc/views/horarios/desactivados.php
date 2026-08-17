<?php
$titulo = "Horarios desactivados";
$api = "src/api/Horario/leer_horarios_desactivados.php";

$columnas = ["id_horario", "grado", "dia_semana", "hora_inicio", "hora_fin", "materia", "profesor", "aula", "activo"];

$filtros = [
    "buscar" => ["materia", "profesor", "aula"],
    "placeholder" => "Buscar horario desactivado...",
    "selects" => [
        ["campo" => "grado", "label" => "Grado"],
        ["campo" => "dia_semana", "label" => "Dia"]
    ]
];

$apiRestaurar = "src/api/Horario/restaurar_horario.php";
$apiEliminar = "src/api/Horario/eliminar_horario.php";
$urlVolver = "mvc/views/horarios/index.php";
$idCampo = "id_horario";

require_once __DIR__ . '/../partials/table_page.php';
