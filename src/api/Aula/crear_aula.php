<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Aula.php';

try {
    $db = (new Database())->getConnection();
    $aula = new Aula($db);

    $aula->nombre = $_POST['nombre'] ?? null;
    $aula->codigo = $_POST['codigo'] ?? null;
    $aula->piso = !empty($_POST['piso']) ? trim($_POST['piso']) : '1° Piso';
    $aula->ubicacion = !empty($_POST['ubicacion']) ? trim($_POST['ubicacion']) : $aula->piso;

    if (!$aula->nombre || !$aula->codigo) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "El nombre y el código de sala son obligatorios."
        ]);
        exit;
    }

    $resultado = $aula->crear();

    http_response_code($resultado ? 201 : 400);
    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Aula creada correctamente" : "Error al crear aula"
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Error al registrar aula: " . $e->getMessage()
    ]);
}
