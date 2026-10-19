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

// Unificamos la consulta del usuario con sus entidades asociadas en UNA sola sentencia (cero latencia de roundtrips)
$query = "SELECT u.id_usuario, u.usuario, u.password, u.rol, u.activo, u.nombre, u.apellido,
                 p.id_profesor,
                 e.id_estudiante, e.id_grado,
                 t.id_tutor
          FROM usuarios u
          LEFT JOIN profesores p ON p.id_usuario = u.id_usuario
          LEFT JOIN estudiantes e ON e.id_usuario = u.id_usuario
          LEFT JOIN tutores t ON t.id_usuario = u.id_usuario
          WHERE u.usuario = :usuario
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

// Guardar los IDs asociados obtenidos de forma atómica en la misma consulta
$_SESSION['id_profesor'] = !empty($user['id_profesor']) ? (int)$user['id_profesor'] : null;
$_SESSION['id_estudiante'] = !empty($user['id_estudiante']) ? (int)$user['id_estudiante'] : null;
$_SESSION['id_grado'] = !empty($user['id_grado']) ? (int)$user['id_grado'] : null;
$_SESSION['id_tutor'] = !empty($user['id_tutor']) ? (int)$user['id_tutor'] : null;

$rutaDestino = obtenerRutaDashboardPorRol($user['rol']);
header("Location: " . base_url($rutaDestino));
exit();
?>