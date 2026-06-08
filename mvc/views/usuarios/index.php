<?php
$titulo = "Usuarios";
$api = "src/api/Usuario/leer_usuarios.php";
$columnas = ["id_usuario", "usuario", "rol"];
$filtros = [
    "buscar" => ["usuario"],
    "placeholder" => "Buscar usuario...",
    "selects" => [
        ["campo" => "rol", "label" => "Rol"],
        ["campo" => "activo", "label" => "Estado"]
    ]
];
$formCrear = [
    "api" => "src/api/Usuario/crear_usuario.php",
    "campos" => [
        ["name" => "usuario", "label" => "Usuario", "type" => "text"],
        ["name" => "password", "label" => "Contraseña", "type" => "password"],
        ["name" => "rol", "label" => "Rol", "type" => "text"]
    ]
];
require_once __DIR__ . '/../partials/table_page.php';