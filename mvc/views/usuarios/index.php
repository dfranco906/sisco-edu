<?php
$titulo = "Usuarios";
$api = "src/api/Usuario/leer_usuarios.php";
$columnas = ["id_usuario", "nombre", "apellido", "usuario", "email", "celular", "rol"];

$filtros = [
    "buscar" => ["nombre", "apellido", "usuario", "email", "rol"],
    "placeholder" => "Buscar nombre, usuario o email...",
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

$formCrear = [
    "api" => "src/api/Usuario/crear_usuario.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre", "type" => "text"],
        ["name" => "apellido", "label" => "Apellido", "type" => "text"],
        ["name" => "usuario", "label" => "Usuario", "type" => "text"],
        ["name" => "email", "label" => "Email", "type" => "email"],
        ["name" => "celular", "label" => "Celular", "type" => "number"],
        ["name" => "password", "label" => "Contraseña", "type" => "password"],
        [
            "name" => "rol",
            "label" => "Rol en el Sistema",
            "type" => "select",
            "options" => [
                ["value" => "SuperAdmin", "label" => "SuperAdmin (Acceso Total)"],
                ["value" => "Administrador", "label" => "Administrador"],
                ["value" => "Coordinador", "label" => "Coordinador Académico"],
                ["value" => "Profesor", "label" => "Profesor"],
                ["value" => "Estudiante", "label" => "Estudiante (Solo Lectura)"],
                ["value" => "Tutor", "label" => "Padre / Madre / Tutor (Solo Lectura)"],
            ]
        ]
    ]
];

$apiActualizar = "src/api/Usuario/actualizar_usuario.php";
$apiDesactivar = "src/api/Usuario/desactivar_usuario.php";
$urlDesactivados = "mvc/views/usuarios/desactivados.php";
$urlVolver = "mvc/views/administracion/index.php";
$idCampo = "id_usuario";
$camposEditar = ["nombre", "apellido", "usuario", "email", "celular", "rol"];

// Modal granular para segmentar turnos, niveles, bachilleratos y cursos por usuario
ob_start();
require_once __DIR__ . '/modal_alcance_usuario.php';
$modalExtraHtml = ob_get_clean();

require_once __DIR__ . '/../partials/table_page.php';
