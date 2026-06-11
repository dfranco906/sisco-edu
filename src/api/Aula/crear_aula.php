<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Aula.php';

try {
    $db = (new Database())->getConnection();
    $aula = new Aula($db);

    $aula->nombre = $_POST['nombre'] ?? null;
    $aula->codigo = $_POST['codigo'] ?? null;
    $aula->ubicacion = $_POST['ubicacion'] ?? null;

    if (!$aula->nombre || !$aula->codigo || !$aula->ubicacion) {
        echo json_encode([
            "status" => "error",
            "message" => "Datos incompletos"
        ]);
        exit;
    }

    $resultado = $aula->crear();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Aula creada correctamente" : "Error al crear aula"
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error interno",
        "debug" => $e->getMessage()
    ]);
}