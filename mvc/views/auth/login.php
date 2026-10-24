<?php
require_once __DIR__ . '/../../../src/config/app.php';

$error = $_GET['error'] ?? null;

$mensaje = "";

if ($error === "campos") {
    $mensaje = "Completá todos los campos.";
} elseif ($error === "usuario") {
    $mensaje = "El usuario no existe.";
} elseif ($error === "password") {
    $mensaje = "La contraseña es incorrecta.";
} elseif ($error === "inactivo") {
    $mensaje = "Este usuario está inactivo.";
} elseif ($error === "logout") {
    $mensaje = "Sesión cerrada correctamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login | SISCO-EDU</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e1b4b">

    <!-- Favicon & PWA Branding -->
    <link rel="icon" type="image/svg+xml" href="<?= base_url('public/favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= base_url('public/favicon-96x96.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= base_url('public/favicon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('public/favicon-16x16.png') ?>">
    <link rel="shortcut icon" href="<?= base_url('public/favicon.ico') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= base_url('public/apple-touch-icon.png') ?>">
    <link rel="manifest" href="<?= base_url('public/site.webmanifest') ?>">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Variables globales -->
    <link rel="stylesheet" href="<?= base_url('public/css/theme.css') ?>">
</head>

<body class="min-h-screen flex items-center justify-center px-4" style="background: var(--color-bg);">

    <div class="w-full max-w-md bg-white shadow-xl rounded-3xl p-8 border border-slate-200">

        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 mb-4 rounded-3xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-cyan-500 p-0.5 shadow-lg shadow-indigo-500/25">
                <img src="<?= base_url('public/img/logo-badge.svg') ?>" alt="SISCO-EDU Logo" class="w-full h-full rounded-[22px] object-cover">
            </div>
            <h1 class="text-3xl font-black tracking-tight" style="color: var(--color-text);">
                SISCO-EDU
            </h1>
            <p class="mt-1 text-sm font-semibold" style="color: var(--color-muted);">
                Gestión Académica &amp; Biometría IoT
            </p>
        </div>

        <form action="<?= base_url('src/api/login.php') ?>" method="POST" class="space-y-5">
<?php if (!empty($mensaje)): ?>
    <div class="mb-4 p-3 rounded-xl text-sm bg-red-100 text-red-700">
        <?= $mensaje ?>
    </div>
<?php endif; ?>
            <div>
                <label class="block text-sm font-medium mb-1" style="color: var(--color-text);">
                    Usuario
                </label>
                <input 
                    type="text" 
                    name="usuario" 
                    required
                    class="app-input w-full"
                    placeholder="Ej: admin1"
                >
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" style="color: var(--color-text);">
                    Contraseña
                </label>
                <input 
                    type="password" 
                    name="password" 
                    required
                    class="app-input w-full"
                    placeholder="••••••••"
                >
            </div>

            <button 
                type="submit"
                class="btn btn-primary w-full"
            >
                Ingresar
            </button>

        </form>

        <p class="text-center text-xs mt-6" style="color: var(--color-muted);">
            Sistema de control de asistencia
        </p>

    </div>

</body>
</html>
