<?php
require_once '../middleware/auth.php';
?>

<h1>Bienvenido <?php echo $_SESSION['usuario']; ?></h1>
<p>Rol: <?php echo $_SESSION['rol']; ?></p>

<hr>

<?php
switch($_SESSION['rol']) {

    case "SuperAdmin":
        echo "<h2>Panel Super Admin</h2>";
        break;

    case "Profesor":
        echo "<h2>Panel Profesor</h2>";
        break;

    case "Coordinador":
        echo "<h2>Panel Coordinador</h2>";
        break;

    default:
        echo "<h2>Panel General</h2>";
}
?>

<br>
<a href="../api/logout.php">Cerrar sesión</a>