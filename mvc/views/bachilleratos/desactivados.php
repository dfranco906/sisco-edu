<?php
$titulo = "Bachilleratos Desactivados";
$icono = "🎓";
$subtitulo = "Historial de modalidades o bachilleratos que han sido dados de baja.";
$api = "src/api/Bachillerato/leer_bachilleratos_desactivados.php";

$columnas = ["id_bachillerato", "nombre", "codigo", "tipo", "descripcion", "activo"];

$filtros = [
    "buscar" => ["nombre", "codigo", "tipo", "descripcion"],
    "placeholder" => "Buscar bachillerato desactivado...",
];

$apiRestaurar = "src/api/Bachillerato/restaurar_bachillerato.php";
$apiEliminar = "src/api/Bachillerato/eliminar_bachillerato.php";
$urlVolver = "mvc/views/bachilleratos/index.php";
$idCampo = "id_bachillerato";

require_once __DIR__ . '/../partials/table_page.php';
