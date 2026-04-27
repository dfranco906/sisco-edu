<?php
session_start();

// Verifica si está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: /sisco-edu/index.php");
    exit();
}

// Función para validar rol
function verificarRol($rolesPermitidos) {
    if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
        echo "⛔ Acceso denegado";
        exit();
    }
}
?>