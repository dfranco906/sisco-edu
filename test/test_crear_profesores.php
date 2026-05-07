<?php
// test/test_profesor.php

require_once '../src/config/db.php';
require_once '../src/classes/Profesor.php';

echo "<h2>TEST CREAR PROFESOR</h2>";

// Conexión
$database = new Database();
$db = $database->getConnection();

// Instanciar clase
$profesor = new Profesor($db);

// Datos de prueba
$profesor->nombre = "Juan";
$profesor->apellido = "Perez";
$profesor->cedula_identidad = "1234567";
$profesor->huella_id = 1;

// Ejecutar
if ($profesor->crear()) {
    echo "✅ Profesor creado correctamente<br>";
} else {
    echo "❌ Error al crear profesor<br>";
}
?>