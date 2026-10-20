<?php
$titulo = "Tutores desactivados";
$api = "src/api/Tutor/leer_tutores_desactivados.php";

$columnas = ["id_tutor", "nombre", "apellido", "cedula_identidad", "celular", "email", "total_hijos", "activo"];

$filtros = [
    "buscar" => ["nombre", "apellido", "cedula_identidad", "celular", "email"],
    "placeholder" => "Buscar tutor desactivado..."
];

$apiRestaurar = "src/api/Tutor/restaurar_tutor.php";
$urlVolver = "mvc/views/tutores/index.php";
$idCampo = "id_tutor";

require_once __DIR__ . '/../partials/table_page.php';
