<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: /tiago3roBTI2026/sisco-edu/index.php");
    exit();
}

// 🔥 función por rol
function verificarRol($rolesPermitidos) {
    if (!in_array($_SESSION['rol'], $rolesPermitidos)) {
        echo "⛔ Acceso denegado";
        exit();
    }
}
?>