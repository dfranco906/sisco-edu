<?php
$titulo = "Turnos Desactivados";
$api = "src/api/Turno/leer_turnos_desactivados.php";

$columnas = ["id_turno", "nombre", "hora_inicio", "hora_fin", "descripcion", "activo"];

$filtros = [
    "buscar" => ["nombre", "descripcion"],
    "placeholder" => "Buscar turno desactivado...",
];

$apiRestaurar = "src/api/Turno/restaurar_turno.php";
$apiEliminar = "src/api/Turno/eliminar_turno.php";
$urlVolver = "mvc/views/turnos/index.php";
$idCampo = "id_turno";

require_once __DIR__ . '/../partials/table_page.php';
