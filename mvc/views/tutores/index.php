<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/config/app.php';

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
    <span>+ Vincular Estudiantes</span>
</button>
<button type="button" onclick="abrirModalImportarTutores()" class="btn btn-secondary inline-flex items-center gap-1.5" title="Importar tutores desde archivo CSV / Excel">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
    <span>📥 Importar</span>
</button>
<a href="' . base_url('src/api/Tutor/exportar_tutores.php') . '" class="btn btn-secondary inline-flex items-center gap-1.5" title="Exportar directorio de tutores y familias a CSV / Excel">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
    <span>📤 Exportar</span>
</a>
';

ob_start();
require_once __DIR__ . '/modal_vincular_familia.php';
require_once __DIR__ . '/modal_importar_tutores.php';
$modalExtraHtml = ob_get_clean();

require_once __DIR__ . '/../partials/table_page.php';
