<?php
$titulo = "Grados desactivados";
$api = "src/api/Grado/leer_grados_desactivados.php";

$columnas = ["id_grado", "nombre", "aula", "room_id", "activo"];

$filtros = [
    "buscar" => ["nombre", "aula", "room_id"],
    "placeholder" => "Buscar grado desactivado...",
    "selects" => [
        ["campo" => "aula", "label" => "Aula"],
        ["campo" => "room_id", "label" => "Room ID"]
    ]
];

$apiRestaurar = "src/api/Grado/restaurar_grado.php";
$apiEliminar = "src/api/Grado/eliminar_grado.php";
$urlVolver = "mvc/views/grados/index.php";
$idCampo = "id_grado";

require_once __DIR__ . '/../partials/table_page.php';
?>
