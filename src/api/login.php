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

require_once '../config/app.php';
require_once '../middleware/auth.php';

$query = "SELECT id_usuario, usuario, password, rol, activo, nombre, apellido
          FROM usuarios
          WHERE usuario = :usuario
          LIMIT 1";

$stmt = $db->prepare($query);
$stmt->bindParam(":usuario", $usuario);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    header("Location: " . base_url('mvc/views/auth/login.php?error=usuario'));
    exit();
}

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ((int)$user['activo'] !== 1) {
    header("Location: " . base_url('mvc/views/auth/login.php?error=inactivo'));
    exit();
}

if (!password_verify($password, $user['password'])) {
    header("Location: " . base_url('mvc/views/auth/login.php?error=password'));
    exit();
}

session_regenerate_id(true);

$_SESSION['id_usuario'] = (int)$user['id_usuario'];
$_SESSION['usuario'] = $user['usuario'];
$_SESSION['rol'] = $user['rol'];
$_SESSION['nombre'] = $user['nombre'];
$_SESSION['apellido'] = $user['apellido'];

// Si es profesor, estudiante o tutor, guardar su id de entidad para agilizar consultas
if ($user['rol'] === 'Profesor') {
    $stmtProf = $db->prepare("SELECT id_profesor FROM profesores WHERE id_usuario = :id_usuario LIMIT 1");
    $stmtProf->execute([':id_usuario' => $user['id_usuario']]);
    $_SESSION['id_profesor'] = $stmtProf->fetchColumn() ?: null;
} elseif ($user['rol'] === 'Estudiante') {
    $stmtEst = $db->prepare("SELECT id_estudiante, id_grado FROM estudiantes WHERE id_usuario = :id_usuario LIMIT 1");
    $stmtEst->execute([':id_usuario' => $user['id_usuario']]);
    $estRow = $stmtEst->fetch(PDO::FETCH_ASSOC);
    if ($estRow) {
        $_SESSION['id_estudiante'] = (int)$estRow['id_estudiante'];
        $_SESSION['id_grado'] = (int)$estRow['id_grado'];
    }
} elseif ($user['rol'] === 'Tutor') {
    $stmtTut = $db->prepare("SELECT id_tutor FROM tutores WHERE id_usuario = :id_usuario LIMIT 1");
    $stmtTut->execute([':id_usuario' => $user['id_usuario']]);
    $_SESSION['id_tutor'] = $stmtTut->fetchColumn() ?: null;
}

$rutaDestino = obtenerRutaDashboardPorRol($user['rol']);
header("Location: " . base_url($rutaDestino));
exit();
?>