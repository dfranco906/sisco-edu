<?php
$titulo = "Estudiantes desactivados";
$api = "src/api/Estudiante/leer_estudiantes_desactivados.php";

$columnas = ["id_estudiante", "nombre", "apellido", "cedula_identidad", "grado", "aula", "huella", "activo"];

$filtros = [
    "buscar" => ["nombre", "apellido", "cedula_identidad"],
    "placeholder" => "Buscar estudiante desactivado...",
    "selects" => [
        ["campo" => "grado", "label" => "Grado"],
        ["campo" => "aula", "label" => "Aula"]
    ]
];

$apiRestaurar = "src/api/Estudiante/restaurar_estudiante.php";
$apiEliminar = "src/api/Estudiante/eliminar_estudiante.php";
$urlVolver = "mvc/views/estudiantes/index.php";
$idCampo = "id_estudiante";

require_once __DIR__ . '/../partials/table_page.php';
