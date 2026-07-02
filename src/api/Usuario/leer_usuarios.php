<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Usuario.php';

try {
    $db = (new Database())->getConnection();
    $usuario = new Usuario($db);
    $stmt = $usuario->leer();

    echo json_encode(["status" => "success", "message" => "Usuarios obtenidos", "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    echo json_encode(["status" => "error", "message" => "Error interno", "data" => [], "debug" => $e->getMessage()]);
}
?>
