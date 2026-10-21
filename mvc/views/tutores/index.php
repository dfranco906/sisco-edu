<?php
$titulo = "Tutores y Familias";
$api = "src/api/Tutor/leer_tutores.php";

$columnas = [
    "id_tutor",
    "nombre",
    "apellido",
    "cedula_identidad",
    "celular",
    "email",
    "total_hijos",
    "activo"
];

$filtros = [
    "buscar" => ["nombre", "apellido", "cedula_identidad", "celular", "email"],
    "placeholder" => "Buscar tutor por nombre, apellido, CI o celular..."
];

$formCrear = [
    "api" => "src/api/Tutor/crear_tutor.php",
    "campos" => [
        ["name" => "nombre", "label" => "Nombre", "type" => "text"],
        ["name" => "apellido", "label" => "Apellido", "type" => "text"],
        ["name" => "cedula_identidad", "label" => "Cédula de Identidad (Usuario y contraseña de acceso)", "type" => "text"],
        ["name" => "celular", "label" => "Celular / WhatsApp", "type" => "number"],
        ["name" => "email", "label" => "Correo Electrónico", "type" => "email"],
        ["name" => "direccion", "label" => "Dirección Domiciliaria", "type" => "text"]
    ]
];

$apiActualizar = "src/api/Tutor/actualizar_tutor.php";
$apiDesactivar = "src/api/Tutor/desactivar_tutor.php";
$urlDesactivados = "mvc/views/tutores/desactivados.php";
$urlVolver = "mvc/views/administracion/index.php";
$idCampo = "id_tutor";
$camposEditar = ["nombre", "apellido", "cedula_identidad", "celular", "email", "direccion"];

$toolbarExtraHtml = '
<button id="btn-vincular-familia" type="button" onclick="GestorFamilias.abrir()" class="btn btn-secondary inline-flex items-center gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
    <span>+ Vincular Estudiantes a Tutor</span>
</button>
';

ob_start();
require_once __DIR__ . '/modal_vincular_familia.php';
$modalExtraHtml = ob_get_clean();

require_once __DIR__ . '/../partials/table_page.php';
