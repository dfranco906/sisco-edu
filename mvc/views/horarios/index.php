<?php
$titulo = "Horarios por Grado";
$api = "src/api/Horario/leer_horarios.php";
$columnas = [
    "grado",
    "dia_semana",
    "hora_inicio",
    "hora_fin",
    "materia",
    "profesor",
    "aula"
];

require_once __DIR__ . '/../partials/table_page.php';