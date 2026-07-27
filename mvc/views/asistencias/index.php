<?php
$titulo = "Asistencias";
$api = "src/api/Asistencia/leer_asistencias.php";
$columnas = ["id_asistencia", "user_id_global", "tipo_usuario", "id_aula", "fecha_hora", "estado"];
$filtros = [
    "selects" => [
        ["campo" => "id_aula", "label" => "Aula"],
        ["campo" => "fecha_hora", "label" => "Fecha"],
        ["campo" => "estado", "label" => "Estado"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';