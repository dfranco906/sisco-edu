<?php
$titulo = "Horarios";
$api = "src/api/Horario/leer_horarios.php";
$columnas = ["id_horario", "id_asignacion", "dia_semana", "hora_inicio", "hora_fin", "aula", "activo"];
require_once __DIR__ . '/../partials/table_page.php';