<?php
$titulo = "Profesores desactivados";
$api = "src/api/Profesor/leer_profesores_desactivados.php";

$columnas = ["id_profesor", "nombre", "apellido", "cedula_identidad", "activo"];

$filtros = [
    "buscar" => ["nombre", "apellido", "cedula_identidad"],
    "placeholder" => "Buscar profesor desactivado..."
];

$apiRestaurar = "src/api/Profesor/restaurar_profesor.php";
$apiEliminar = "src/api/Profesor/eliminar_profesor.php";
$urlVolver = "mvc/views/profesores/index.php";
$idCampo = "id_profesor";

require_once __DIR__ . '/../partials/table_page.php';
