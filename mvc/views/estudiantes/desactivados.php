<?php
$titulo = "Estudiantes desactivados";
$api = "src/api/Estudiante/leer_estudiantes_desactivados.php";

$columnas = ["id_estudiante", "nombre", "apellido", "cedula_identidad", "grado", "materias_matriculadas", "activo"];

$filtros = [
    "buscar" => ["nombre", "apellido", "cedula_identidad", "grado"],
    "placeholder" => "Buscar estudiante desactivado...",
    "pestanas" => [
        "campo" => "grado",
        "todosLabel" => "Todos los cursos"
    ]
];

$apiRestaurar = "src/api/Estudiante/restaurar_estudiante.php";
$apiEliminar = "src/api/Estudiante/eliminar_estudiante.php";
$urlVolver = "mvc/views/estudiantes/index.php";
$idCampo = "id_estudiante";

require_once __DIR__ . '/../partials/table_page.php';
