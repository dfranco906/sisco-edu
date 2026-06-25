<?php require_once __DIR__ . '/../../../src/config/app.php'; ?>

<aside class="hidden md:flex w-64 bg-white shadow-lg flex-col p-6">
    <h1 class="text-2xl font-bold mb-8" style="color: var(--color-primary);">
        SISCO-EDU
    </h1>

    <nav class="space-y-3">
        <a href="<?= base_url('mvc/views/dashboard.php') ?>" class="block px-4 py-3 rounded-xl hover:bg-blue-50">Dashboard</a>
        <a href="<?= base_url('mvc/views/profesores/index.php') ?>" class="block px-4 py-3 rounded-xl hover:bg-blue-50">Profesores</a>
        <a href="<?= base_url('mvc/views/estudiantes/index.php') ?>" class="block px-4 py-3 rounded-xl hover:bg-blue-50">Estudiantes</a>
        <a href="<?= base_url('mvc/views/horarios/index.php') ?>" class="block px-4 py-3 rounded-xl hover:bg-blue-50">Horarios</a>
        <a href="<?= base_url('mvc/views/asignaciones/index.php') ?>" class="block px-4 py-3 rounded-xl hover:bg-blue-50">Asignaciones</a>
        <a href="<?= base_url('mvc/views/asistencias_estudiantes/index.php') ?>" class="block px-4 py-3 rounded-xl hover:bg-blue-50">Asist. Estudiantes</a>
        <a href="<?= base_url('mvc/views/asistencias_profesores/index.php') ?>" class="block px-4 py-3 rounded-xl hover:bg-blue-50">Asist. Profesores</a>
        <a href="<?= base_url('mvc/views/administracion/index.php') ?>" class="block px-4 py-3 rounded-xl hover:bg-blue-50">Administraci&oacute;n</a>
    </nav>

    <a href="<?= base_url('src/api/logout.php') ?>" class="mt-auto text-red-500 font-semibold">
        Cerrar sesión
    </a>
</aside>
