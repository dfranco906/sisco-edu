<?php
$titulo = "Estudiantes";
$api = "src/api/Estudiante/leer_estudiantes.php";

$columnas = ["id_estudiante", "nombre", "apellido", "cedula_identidad", "grado", "materias_matriculadas", "activo"];

$filtros = [
    "buscar" => ["nombre", "apellido", "cedula_identidad", "grado"],
    "placeholder" => "Buscar estudiante por nombre, apellido o CI...",
    "pestanas" => [
        "campo" => "grado",
        "todosLabel" => "Todos los cursos"
    ]
];

$formCrear = [
    "api" => "src/api/Estudiante/crear_estudiante.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre", "type" => "text"],
        ["name" => "apellido", "label" => "Apellido", "type" => "text"],
        ["name" => "cedula_identidad", "label" => "Cédula de Identidad", "type" => "text"],
        [
            "name" => "id_grado",
            "label" => "Grado / Curso (Matriculación automática de materias)",
            "type" => "select",
            "api" => "src/api/Grado/leer_grados.php",
            "value" => "id_grado",
            "labelField" => "descripcion"
        ]
    ]
];

$apiActualizar = "src/api/Estudiante/actualizar_estudiante.php";
$apiDesactivar = "src/api/Estudiante/desactivar_estudiante.php";
$urlDesactivados = "mvc/views/estudiantes/desactivados.php";
$urlVolver = "mvc/views/dashboard.php";
$idCampo = "id_estudiante";
$camposEditar = ["nombre", "apellido", "cedula_identidad"];

require_once __DIR__ . '/../partials/table_page.php';
