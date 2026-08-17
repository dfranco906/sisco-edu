<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$rolesValidos = ['SuperAdmin', 'Coordinador', 'Profesor', 'Administracion'];
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$apellido = trim((string) ($_POST['apellido'] ?? ''));
$nombreUsuario = trim((string) ($_POST['usuario'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$celular = trim((string) ($_POST['celular'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$rol = (string) ($_POST['rol'] ?? '');

if ($nombre === '' || $apellido === '' || $nombreUsuario === '' || $email === '' || $celular === '' || $password === '' || !in_array($rol, $rolesValidos, true)) {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "Completá todos los campos y seleccioná un rol válido."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "El email no tiene un formato válido."]);
    exit;
}

if (!ctype_digit($celular) || strlen($celular) > 10) {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "El celular debe contener hasta 10 dígitos."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $usuario = new Usuario($db);
    $usuario->nombre = $nombre;
    $usuario->apellido = $apellido;
    $usuario->usuario = $nombreUsuario;
    $usuario->email = $email;
    $usuario->celular = $celular;
    $usuario->password = $password;
    $usuario->rol = $rol;

    $resultado = $usuario->crear();
    if (!$resultado) throw new RuntimeException('La operación de inserción no se completó.');

    $idUsuario = (int) $db->lastInsertId();
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "status" => "success",
        "message" => "Usuario creado correctamente.",
        "data" => ["id_usuario" => $idUsuario]
    ]);
} catch (PDOException $e) {
    error_log('crear_usuario: ' . $e->getMessage());
    $duplicado = $e->getCode() === '23000';
    http_response_code($duplicado ? 409 : 500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => $duplicado
            ? "El nombre de usuario ya está registrado."
            : "No se pudo crear el usuario."
    ]);
} catch (Throwable $e) {
    error_log('crear_usuario: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo crear el usuario."]);
}
?>
