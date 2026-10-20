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
    <script>
        tailwind.config = {
            darkMode: 'class'
        };
    </script>
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('sisco_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', savedTheme);
                if (savedTheme === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                if (localStorage.getItem('sisco_sidebar_collapsed') === 'true' && window.innerWidth >= 768) {
                    document.documentElement.classList.add('sidebar-init-collapsed');
                }
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="<?= base_url('public/css/theme.css?v=' . filemtime(__DIR__ . '/../../../public/css/theme.css')) ?>">
    <script src="<?= base_url('public/js/notifications.js?v=' . filemtime(__DIR__ . '/../../../public/js/notifications.js')) ?>"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>/";
        window.CONFIG_RED = {
            ipRegistrador: "<?= IP_ESP32_REGISTRADOR ?>",
            ipGateway: "<?= IP_ESP32_GATEWAY ?>",
            servidorUrl: "<?= base_url() ?>/"
        };
    </script>
</head>
<body class="min-h-screen text-slate-900 dark:text-slate-100 transition-colors duration-200" style="background: var(--color-bg);">

<!-- Botón Toggle de Modo Claro / Oscuro (Siempre visible y disponible) -->
<button id="app-theme-toggle" class="app-theme-toggle" type="button" aria-label="Alternar modo claro u oscuro" title="Alternar Modo Oscuro / Claro">
    <svg class="theme-icon-sun" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>
    <svg class="theme-icon-moon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
    </svg>
</button>

<div class="app-shell flex min-h-screen">
