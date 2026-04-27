<?php
require_once '../middleware/auth.php';
?>

<h1>Bienvenido <?php echo $_SESSION['usuario']; ?></h1>
<p>Rol: <?php echo $_SESSION['rol']; ?></p>

<?php
if ($_SESSION['rol'] == "SuperAdmin") {
    echo "<h2>Panel Super Admin</h2>";
}

if ($_SESSION['rol'] == "Profesor") {
    echo "<h2>Panel Profesor</h2>";
}

if ($_SESSION['rol'] == "Coordinador") {
    echo "<h2>Panel Coordinador</h2>";
}
?>

<a href="../api/logout.php">Cerrar sesión</a>