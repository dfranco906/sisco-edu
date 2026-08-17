<?php
$titulo = "Usuarios";
$api = "src/api/Usuario/leer_usuarios.php";
$columnas = ["id_usuario", "nombre", "apellido", "usuario", "email", "celular", "rol"];
$filtros = [
    "buscar" => ["nombre", "apellido", "usuario", "email"],
    "placeholder" => "Buscar nombre, usuario o email...",
    "selects" => [
        ["campo" => "rol", "label" => "Rol"],
    ]
];
$formCrear = [
    "api" => "src/api/Usuario/crear_usuario.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre", "type" => "text"],
        ["name" => "apellido", "label" => "Apellido", "type" => "text"],
        ["name" => "usuario", "label" => "Usuario", "type" => "text"],
        ["name" => "email", "label" => "Email", "type" => "email"],
        ["name" => "celular", "label" => "Celular", "type" => "number"],
        ["name" => "password", "label" => "Contraseña", "type" => "password"],
        ["name" => "rol", "label" => "Rol", "type" => "text"]
    ]
];
$apiActualizar = "src/api/Usuario/actualizar_usuario.php";
$apiDesactivar = "src/api/Usuario/desactivar_usuario.php";
$urlDesactivados = "mvc/views/usuarios/desactivados.php";
$idCampo = "id_usuario";
$camposEditar = ["nombre", "apellido", "usuario", "email", "celular", "rol"];
require_once __DIR__ . '/../partials/table_page.php';
