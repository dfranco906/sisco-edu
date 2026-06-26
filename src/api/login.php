<?php
session_start();

require_once '../config/db.php';

$db = (new Database())->getConnection();

$usuario = trim($_POST['usuario'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($usuario) || empty($password)) {
    header("Location: ../../mvc/views/auth/login.php?error=campos");
    exit();
}

$query = "SELECT id_usuario, usuario, password, rol, activo
          FROM usuarios
          WHERE usuario = :usuario
          LIMIT 1";

$stmt = $db->prepare($query);
$stmt->bindParam(":usuario", $usuario);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    header("Location: ../../mvc/views/auth/login.php?error=usuario");
    exit();
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user['activo'] != 1) {
    header("Location: ../../mvc/views/auth/login.php?error=inactivo");
    exit();
}

if (!password_verify($password, $user['password'])) {
    header("Location: ../../mvc/views/auth/login.php?error=password");
    exit();
}

session_regenerate_id(true);

$_SESSION['id_usuario'] = $user['id_usuario'];
$_SESSION['usuario'] = $user['usuario'];
$_SESSION['rol'] = $user['rol'];

header("Location: ../../mvc/views/dashboard.php");
exit();
?>