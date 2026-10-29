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

    $resultado = $usuario->eliminar();

    if ($resultado) {
        echo json_encode([
            "status" => "success",
            "message" => "Usuario eliminado permanentemente"
        ]);
    } else {
        http_response_code(422);
        echo json_encode(["status" => "error", "message" => "No se pudo eliminar el usuario"]);
    }
} catch (Throwable $e) {
    error_log("Error en eliminar_usuario.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al eliminar usuario"]);
}
