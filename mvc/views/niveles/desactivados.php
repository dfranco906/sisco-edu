<?php
$titulo = "Niveles y Ciclos Desactivados";
$icono = "🏛️";
$subtitulo = "Historial de ciclos y niveles académicos archivados o inactivos.";
$api = "src/api/NivelAcademico/leer_niveles_desactivados.php";

$columnas = ["id_nivel", "nombre", "codigo", "descripcion", "activo"];

$filtros = [
    "buscar" => ["nombre", "codigo", "descripcion"],
    "placeholder" => "Buscar nivel desactivado...",
];

$apiRestaurar = "src/api/NivelAcademico/restaurar_nivel.php";
$apiEliminar = "src/api/NivelAcademico/eliminar_nivel.php";
$urlVolver = "mvc/views/niveles/index.php";
$idCampo = "id_nivel";

require_once __DIR__ . '/../partials/table_page.php';
