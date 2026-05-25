<?php
$titulo = "Aulas";
$api = "src/api/Aula/leer_aulas.php";
$columnas = ["id_aula", "nombre", "codigo", "ubicacion", "activo"];
require_once __DIR__ . '/../partials/table_page.php';