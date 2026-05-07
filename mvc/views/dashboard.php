<?php require_once 'layouts/header.php'; ?>
<?php require_once 'layouts/sidebar.php'; ?>

<main class="flex-1 p-6 md:p-10">

    <div class="bg-white rounded-2xl shadow-md p-6 mb-8">
        <h2 class="text-2xl font-bold" style="color: var(--color-text);">
            Bienvenido, <?php echo $_SESSION['usuario']; ?>
        </h2>
        <p class="mt-1" style="color: var(--color-muted);">
            Rol: <?php echo $_SESSION['rol']; ?>
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

        <a href="profesores/index.php" class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
            <h3 class="text-lg font-bold">Profesores</h3>
            <p class="text-sm mt-2" style="color: var(--color-muted);">Gestionar profesores</p>
        </a>

        <a href="estudiantes/index.php" class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
            <h3 class="text-lg font-bold">Estudiantes</h3>
            <p class="text-sm mt-2" style="color: var(--color-muted);">Gestionar estudiantes</p>
        </a>

        <a href="materias/index.php" class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
            <h3 class="text-lg font-bold">Materias</h3>
            <p class="text-sm mt-2" style="color: var(--color-muted);">Gestionar materias</p>
        </a>

        <a href="horarios/index.php" class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
            <h3 class="text-lg font-bold">Horarios</h3>
            <p class="text-sm mt-2" style="color: var(--color-muted);">Gestionar horarios</p>
        </a>

        <a href="asignaciones/index.php" class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
            <h3 class="text-lg font-bold">Asignaciones</h3>
            <p class="text-sm mt-2" style="color: var(--color-muted);">Gestionar asignaciones</p>
        </a>

        <a href="usuarios/index.php" class="bg-white p-6 rounded-2xl shadow hover:shadow-lg transition">
            <h3 class="text-lg font-bold">Usuarios</h3>
            <p class="text-sm mt-2" style="color: var(--color-muted);">Control de acceso</p>
        </a>

    </div>

</main>

<?php require_once 'layouts/footer.php'; ?>