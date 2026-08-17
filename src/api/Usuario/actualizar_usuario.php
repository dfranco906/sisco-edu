<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$idRaw = $_POST['id_usuario'] ?? null;
$idUsuario = filter_var($idRaw, FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
$rolesValidos = ['SuperAdmin', 'Coordinador', 'Profesor', 'Administracion'];
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$apellido = trim((string) ($_POST['apellido'] ?? ''));
$nombreUsuario = trim((string) ($_POST['usuario'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$celular = trim((string) ($_POST['celular'] ?? ''));
$rol = (string) ($_POST['rol'] ?? '');

if ($idUsuario === false || $idUsuario === null || $nombre === '' || $apellido === '' || $nombreUsuario === '' || $email === '' || $celular === '' || !in_array($rol, $rolesValidos, true)) {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "Completá todos los campos y seleccioná un rol válido."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !ctype_digit($celular) || strlen($celular) > 10) {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "Email o celular no válido."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $usuario = new Usuario($db);
    $usuario->id_usuario = $idUsuario;
    $usuario->nombre = $nombre;
    $usuario->apellido = $apellido;
    $usuario->usuario = $nombreUsuario;
    $usuario->email = $email;
    $usuario->celular = $celular;
    $usuario->rol = $rol;

    $resultado = $usuario->actualizar();
    echo json_encode([
        "success" => (bool) $resultado,
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Usuario actualizado correctamente." : "No se pudo actualizar el usuario.",
        "data" => $resultado ? ["id_usuario" => $idUsuario] : null
    ]);
} catch (PDOException $e) {
    error_log('actualizar_usuario: ' . $e->getMessage());
    $duplicado = $e->getCode() === '23000';
    http_response_code($duplicado ? 409 : 500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => $duplicado ? "El nombre de usuario ya está registrado." : "No se pudo actualizar el usuario."
    ]);
} catch (Throwable $e) {
    error_log('actualizar_usuario: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo actualizar el usuario."]);
}
?>
