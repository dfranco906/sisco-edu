<?php
$titulo = "Estudiantes";
$api = "src/api/leer_estudiantes.php";
$columnas = ["id_estudiante", "nombre", "apellido", "cedula_identidad", "huella_id"];
require_once __DIR__ . '/../partials/table_page.php';