<?php
$titulo = "Profesores";
$api = "src/api/Profesor/leer_profesores.php";
$columnas = ["id_profesor", "nombre", "apellido", "cedula_identidad", "email"];

$filtros = [
    "buscar" => ["nombre", "apellido", "cedula_identidad", "email"],
    "placeholder" => "Buscar profesor por nombre, apellido, CI o email..."
];
$formCrear = [
    "api" => "src/api/Profesor/crear_profesor.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre", "type" => "text"],
        ["name" => "apellido", "label" => "Apellido", "type" => "text"],
        ["name" => "cedula_identidad", "label" => "Cédula de Identidad (Usuario y contraseña inicial)", "type" => "text"]
    ]
];
$apiActualizar = "src/api/Profesor/actualizar_profesor.php";
$apiDesactivar = "src/api/Profesor/desactivar_profesor.php";
$urlDesactivados = "mvc/views/profesores/desactivados.php";
$urlVolver = "mvc/views/administracion/index.php";
$idCampo = "id_profesor";
$camposEditar = ["nombre", "apellido", "cedula_identidad"];
require_once __DIR__ . '/../partials/table_page.php';
