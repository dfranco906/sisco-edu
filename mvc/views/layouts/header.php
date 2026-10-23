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
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <script>
        window.BASE_URL = "<?= base_url() ?>/";
        window.CONFIG_RED = {
            ipRegistrador: "<?= IP_ESP32_REGISTRADOR ?>",
            ipGateway: "<?= IP_ESP32_GATEWAY ?>",
            servidorUrl: "<?= base_url() ?>/"
        };
        window.CONFIG_SUPABASE = {
            url: "<?= defined('SUPABASE_URL') ? SUPABASE_URL : '' ?>",
            anonKey: "<?= defined('SUPABASE_ANON_KEY') ? SUPABASE_ANON_KEY : '' ?>"
        };
    </script>
</head>
<body class="min-h-screen text-slate-900 dark:text-slate-100 transition-colors duration-200" style="background: var(--color-bg);">

<div class="app-shell flex min-h-screen">
