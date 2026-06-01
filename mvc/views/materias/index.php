<?php
$titulo = "Materias";
$api = "src/api/Materia/leer_materias.php";
$columnas = ["id_materia", "nombre", "descripcion", "carga_horaria_semanal", "activo"];
require_once __DIR__ . '/../partials/table_page.php';