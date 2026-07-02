<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metodo no permitido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $usuario = new Usuario($db);

    $usuario->nombre = $_POST['nombre'] ?? null;
    $usuario->apellido = $_POST['apellido'] ?? null;
    $usuario->usuario = $_POST['usuario'] ?? null;
    $usuario->email = $_POST['email'] ?? null;
    $usuario->celular = $_POST['celular'] ?? null;
    $usuario->password = $_POST['password'] ?? null;
    $usuario->rol = $_POST['rol'] ?? null;

    if (!$usuario->nombre || !$usuario->apellido || !$usuario->usuario || !$usuario->email || !$usuario->celular || !$usuario->password || !$usuario->rol) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Datos incompletos"]);
        exit;
    }

    $resultado = $usuario->crear();

    if ($resultado) {
        http_response_code(201);
    }

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Usuario creado correctamente" : "Error al crear usuario"
    ]);
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Error interno", "debug" => $e->getMessage()]);
}
?>
