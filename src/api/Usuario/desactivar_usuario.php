<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Metodo no permitido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $idUsuario = filter_var($_POST['id_usuario'] ?? null, FILTER_VALIDATE_INT);

    if ($idUsuario === false || $idUsuario === null || $idUsuario < 0) {
        http_response_code(422);
        echo json_encode(["success" => false, "status" => "error", "message" => "ID de usuario invalido"]);
        exit;
    }

    if ($idUsuario === 0) {
        http_response_code(409);
        echo json_encode([
            "success" => false,
            "status" => "error",
            "message" => "El administrador inicial no puede desactivarse."
        ]);
        exit;
    }

    $usuario = new Usuario($db);
    $usuario->id_usuario = $idUsuario;
    $resultado = $usuario->desactivar();

    echo json_encode([
        "success" => (bool) $resultado,
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Usuario desactivado correctamente" : "Error al desactivar usuario"
    ]);
} catch (Throwable $e) {
    error_log('desactivar_usuario: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo desactivar el usuario."]);
}
?>
