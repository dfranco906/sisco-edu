<?php
$titulo = "Grados";
$api = "src/api/Grado/leer_grados.php";

$columnas = ["id_grado", "nombre", "aula", "room_id", "activo"];

$filtros = [
    "buscar" => ["nombre", "aula", "room_id"],
    "placeholder" => "Buscar grado...",
    "selects" => [
        ["campo" => "aula", "label" => "Aula"],
        ["campo" => "room_id", "label" => "Room ID"]
    ]
];

$formCrear = [
    "api" => "src/api/Grado/crear_grado.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre del grado", "type" => "text"],
        [
            "name" => "id_aula",
            "label" => "Aula asignada",
            "type" => "select",
            "api" => "src/api/Aula/leer_aulas.php",
            "value" => "id_aula",
            "labelField" => "nombre"
        ]
    ]
];

$apiActualizar = "src/api/Grado/actualizar_grado.php";
$apiDesactivar = "src/api/Grado/desactivar_grado.php";
$urlDesactivados = "mvc/views/grados/desactivados.php";
$idCampo = "id_grado";
$camposEditar = ["nombre", "id_aula"];

require_once __DIR__ . '/../partials/table_page.php';
