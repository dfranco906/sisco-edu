<?php
$titulo = "Ciclos y Niveles Académicos";
$icono = "🏛️";
$subtitulo = "Organización curricular por ciclos formativos (EEB 1°, 2°, 3° Ciclo y Nivel Medio).";
$tituloBotonCrear = "Ciclo / Nivel";
$api = "src/api/NivelAcademico/leer_niveles.php";
$columnas = ["id_nivel", "nombre", "codigo", "descripcion", "activo"];

$filtros = [
    "buscar" => ["nombre", "codigo", "descripcion"],
    "placeholder" => "Buscar ciclo o nivel...",
];

$formCrear = [
    "api" => "src/api/NivelAcademico/crear_nivel.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre del Nivel/Ciclo (ej. EEB - 3° Ciclo, Nivel Medio)", "type" => "text"],
        ["name" => "codigo", "label" => "Código / Abreviación (ej. EEB3, NM)", "type" => "text"],
        ["name" => "descripcion", "label" => "Descripción / Grados que abarca", "type" => "text"]
    ]
];

$apiActualizar = "src/api/NivelAcademico/actualizar_nivel.php";
$apiDesactivar = "src/api/NivelAcademico/desactivar_nivel.php";
$urlDesactivados = "mvc/views/niveles/desactivados.php";
$urlVolver = "mvc/views/administracion/index.php";
$idCampo = "id_nivel";
$camposEditar = ["nombre", "codigo", "descripcion"];

require_once __DIR__ . '/../partials/table_page.php';
