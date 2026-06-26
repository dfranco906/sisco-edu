<?php
require_once __DIR__ . '/../../../src/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_usuario'])) {
    header("Location: " . base_url('mvc/views/auth/login.php'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SISCO-EDU</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= base_url('public/css/theme.css') ?>">
</head>
<body class="min-h-screen" style="background: var(--color-bg);">

<div class="flex min-h-screen">