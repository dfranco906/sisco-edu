<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

try {
    $rawInput = file_get_contents('php://input');
    $json = json_decode($rawInput, true);
    $data = is_array($json) ? array_merge($_POST, $json) : $_POST;

    $db = (new Database())->getConnection();
    $usuario = new Usuario($db);

    $usuario->nombre = trim((string)($data['nombre'] ?? ''));
    $usuario->apellido = trim((string)($data['apellido'] ?? ''));
    $usuario->usuario = trim((string)($data['usuario'] ?? ''));
    $usuario->email = trim((string)($data['email'] ?? ''));
    $usuario->celular = trim((string)($data['celular'] ?? ''));
    $usuario->password = (string)($data['password'] ?? '');
    $usuario->rol = trim((string)($data['rol'] ?? ''));

    if (empty($usuario->nombre) || empty($usuario->apellido) || empty($usuario->usuario) || empty($usuario->password) || empty($usuario->rol)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Campos obligatorios incompletos (Nombre, Apellido, Usuario, Contraseña y Rol)"]);
        exit;
    }

    $resultado = $usuario->crear();

    if ($resultado) {
        http_response_code(201);
        echo json_encode([
            "status" => "success",
            "message" => "Usuario creado exitosamente",
            "data" => ["id_usuario" => $usuario->id_usuario]
        ]);
    } else {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "Error al registrar el usuario en el sistema"]);
    }
} catch (Throwable $e) {
    error_log("Error en crear_usuario.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al crear usuario"]);
}
