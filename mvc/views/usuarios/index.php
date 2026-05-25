<?php
$titulo = "Usuarios";
$api = "src/api/Usuario/leer_usuarios.php";
$columnas = ["id_usuario", "usuario", "rol", "activo"];
require_once __DIR__ . '/../partials/table_page.php';