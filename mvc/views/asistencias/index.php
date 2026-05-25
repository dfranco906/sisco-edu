<?php
$titulo = "Asistencias";
$api = "src/api/leer_asistencias.php";
$columnas = ["id_asistencia", "id_estudiante", "id_horario", "fecha", "hora", "estado"];
require_once __DIR__ . '/../partials/table_page.php';