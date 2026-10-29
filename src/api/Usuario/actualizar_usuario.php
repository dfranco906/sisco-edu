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

    $id_usuario = isset($data['id_usuario']) ? (int)$data['id_usuario'] : null;
    if (!$id_usuario) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "ID de usuario requerido"]);
        exit;
    }

    $db = (new Database())->getConnection();
    $usuario = new Usuario($db);

    $usuario->id_usuario = $id_usuario;
    $usuario->nombre = trim((string)($data['nombre'] ?? ''));
    $usuario->apellido = trim((string)($data['apellido'] ?? ''));
    $usuario->usuario = trim((string)($data['usuario'] ?? ''));
    $usuario->email = trim((string)($data['email'] ?? ''));
    $usuario->celular = trim((string)($data['celular'] ?? ''));
    $usuario->rol = trim((string)($data['rol'] ?? ''));

    if (!empty($data['password'])) {
        $usuario->password = (string)$data['password'];
    }

    if (empty($usuario->nombre) || empty($usuario->apellido) || empty($usuario->usuario) || empty($usuario->rol)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Nombre, apellido, usuario y rol son obligatorios"]);
        exit;
    }

    $resultado = $usuario->actualizar();

    if ($resultado) {
        echo json_encode([
            "status" => "success",
            "message" => "Usuario actualizado correctamente"
        ]);
    } else {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "No se pudo actualizar el usuario"]);
    }
} catch (Throwable $e) {
    error_log("Error en actualizar_usuario.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al actualizar usuario"]);
}
