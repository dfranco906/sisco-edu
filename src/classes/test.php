<?php
// src/classes/test.php

// Definimos la ruta absoluta para evitar errores de carpetas
$path = __DIR__ . '/../config/db.php';

if (file_exists($path)) {
    require_once($path);
} else {
    die("Error: No se pudo encontrar el archivo db.php en la ruta: " . $path);
}

// Intentamos instanciar
if (class_exists('Database')) {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "¡Conexión exitosa a SISCO-EDU!";
    }
} else {
    echo "Error: La clase 'Database' no existe dentro de db.php. Revisa el contenido de ese archivo.";
}