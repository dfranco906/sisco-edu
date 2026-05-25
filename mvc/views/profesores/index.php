<?php
$titulo = "Profesores";
$api = "src/api/leer_profesores.php";
$columnas = ["id_profesor", "nombre", "apellido", "cedula_identidad", "huella_id"];
require_once __DIR__ . '/../partials/table_page.php';