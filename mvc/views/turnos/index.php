<?php
$titulo = "Turnos Escolares";
$icono = "⏱️";
$subtitulo = "Gestión de turnos escolares institucionales, horarios de entrada y salida habituales.";
$tituloBotonCrear = "Turno";
$api = "src/api/Turno/leer_turnos.php";
$columnas = ["id_turno", "nombre", "hora_inicio", "hora_fin", "descripcion", "activo"];

$filtros = [
    "buscar" => ["nombre", "descripcion"],
    "placeholder" => "Buscar turno...",
];

$formCrear = [
    "api" => "src/api/Turno/crear_turno.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre del Turno (ej. Mañana, Tarde, Noche)", "type" => "text"],
        ["name" => "hora_inicio", "label" => "Hora de Inicio habitual", "type" => "time"],
        ["name" => "hora_fin", "label" => "Hora de Fin habitual", "type" => "time"],
        ["name" => "descripcion", "label" => "Descripción u Observaciones", "type" => "text"]
    ]
];

$apiActualizar = "src/api/Turno/actualizar_turno.php";
$apiDesactivar = "src/api/Turno/desactivar_turno.php";
$urlDesactivados = "mvc/views/turnos/desactivados.php";
$urlVolver = "mvc/views/administracion/index.php";
$idCampo = "id_turno";
$camposEditar = ["nombre", "hora_inicio", "hora_fin", "descripcion"];

require_once __DIR__ . '/../partials/table_page.php';
