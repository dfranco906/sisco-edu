<?php
$titulo = "Usuarios desactivados";
$api = "src/api/Usuario/leer_usuarios_desactivados.php";

$columnas = ["id_usuario", "nombre", "apellido", "usuario", "email", "celular", "rol", "activo"];

$filtros = [
    "buscar" => ["nombre", "apellido", "usuario", "email"],
    "placeholder" => "Buscar usuario desactivado...",
    "selects" => [
        ["campo" => "rol", "label" => "Rol"]
    ]
];

$apiRestaurar = "src/api/Usuario/restaurar_usuario.php";
$apiEliminar = "src/api/Usuario/eliminar_usuario.php";
$urlVolver = "mvc/views/usuarios/index.php";
$idCampo = "id_usuario";

require_once __DIR__ . '/../partials/table_page.php';
