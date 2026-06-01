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
require_once __DIR__ . '/../partials/table_page.php';