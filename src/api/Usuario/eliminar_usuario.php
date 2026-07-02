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
    $usuario->id_usuario = $_POST['id_usuario'] ?? null;

    if (!$usuario->id_usuario) {
        echo json_encode(["status" => "error", "message" => "ID de usuario requerido"]);
        exit;
    }

    $resultado = $usuario->eliminar();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Usuario eliminado correctamente" : "No se pudo eliminar el usuario"
    ]);
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Error interno", "debug" => $e->getMessage()]);
}
?>
