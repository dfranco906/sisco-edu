<?php
$titulo = "Profesores";
$api = "src/api/Profesor/leer_profesores.php";
$columnas = ["id_profesor", "nombre", "apellido", "cedula_identidad", "cuenta", "huella", "activo"];

$filtros = [
    "buscar" => ["nombre", "apellido", "cedula_identidad"],
    "placeholder" => "Buscar profesor..."
];
$formCrear = [
    "api" => "src/api/Profesor/crear_profesor.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre", "type" => "text"],
        ["name" => "apellido", "label" => "Apellido", "type" => "text"],
        ["name" => "cedula_identidad", "label" => "Cédula", "type" => "text"],
        ["name" => "id_usuario", "label" => "Cuenta de acceso", "type" => "select", "api" => "src/api/Profesor/leer_usuarios_profesor.php", "value" => "id_usuario", "labelField" => "descripcion"]
    ]
];
$apiActualizar = "src/api/Profesor/actualizar_profesor.php";
$apiDesactivar = "src/api/Profesor/desactivar_profesor.php";
$urlDesactivados = "mvc/views/profesores/desactivados.php";
$idCampo = "id_profesor";
$camposEditar = [
    "nombre", "apellido", "cedula_identidad",
    ["name"=>"id_usuario","label"=>"Cuenta de acceso","type"=>"select","api"=>"src/api/Profesor/leer_usuarios_profesor.php","value"=>"id_usuario","labelField"=>"descripcion","currentLabelField"=>"cuenta"]
];
require_once __DIR__ . '/../partials/table_page.php';
