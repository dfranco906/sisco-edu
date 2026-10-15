<?php
require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario'])) {
    header("Location: " . base_url('index.php'));
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