<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: auth/login.php");
    exit();
}
?>

<h2>Bienvenido, <?php echo $_SESSION['usuario']; ?></h2>
<p>Rol: <?php echo $_SESSION['rol']; ?></p>

<hr>

<a href="profesores/index.php">Profesores</a><br>
<a href="estudiantes/index.php">Estudiantes</a><br>
<a href="materias/index.php">Materias</a><br>
<a href="horarios/index.php">Horarios</a><br>
<a href="usuarios/index.php">Usuarios</a><br><br>

<a href="../../src/api/logout.php">Cerrar sesión</a>