<?php
$titulo = "Asignaciones";
$api = "src/api/Asignaciones/leer_asignaciones.php";
$columnas = [
    "id_asignacion",
    "profesor",
    "materia",
    "año_lectivo"
];

require_once __DIR__ . '/../partials/table_page.php';