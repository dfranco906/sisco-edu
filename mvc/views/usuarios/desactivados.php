<?php
$titulo = "Usuarios desactivados";
$api = "src/api/Usuario/leer_usuarios_desactivados.php";

$columnas = ["id_usuario", "nombre", "apellido", "usuario", "email", "celular", "rol", "activo"];

$filtros = [
    "buscar" => ["nombre", "apellido", "usuario", "email", "rol"],
    "placeholder" => "Buscar usuario desactivado...",
    "pestanas" => [
        "campo" => "rol",
        "todosLabel" => "Todos",
        "opciones" => [
            ["valor" => "", "label" => "Todos"],
            ["valor" => "SuperAdmin", "label" => "SuperAdmin"],
            ["valor" => "Administrador", "label" => "Administradores"],
            ["valor" => "Coordinador", "label" => "Coordinadores"],
            ["valor" => "Profesor", "label" => "Profesores"],
            ["valor" => "Estudiante", "label" => "Estudiantes"],
            ["valor" => "Tutor", "label" => "Tutores"]
        ]
    ]
];

$apiRestaurar = "src/api/Usuario/restaurar_usuario.php";
$apiEliminar = "src/api/Usuario/eliminar_usuario.php";
$urlVolver = "mvc/views/usuarios/index.php";
$idCampo = "id_usuario";

require_once __DIR__ . '/../partials/table_page.php';
