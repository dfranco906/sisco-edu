<?php
$titulo = "Asignaciones";
$api = "src/api/leer_asignaciones.php";
$columnas = ["id_asignacion", "id_profesor", "id_materia", "id_horario"];
require_once __DIR__ . '/../partials/table_page.php';