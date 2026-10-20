<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Aula.php';

try {
    $db = (new Database())->getConnection();
    $aula = new Aula($db);

    $aula->id_aula = $_POST['id_aula'] ?? null;
    $aula->nombre = $_POST['nombre'] ?? null;
    $aula->codigo = $_POST['codigo'] ?? null;
    $aula->piso = !empty($_POST['piso']) ? trim($_POST['piso']) : '1° Piso';
    $aula->ubicacion = !empty($_POST['ubicacion']) ? trim($_POST['ubicacion']) : $aula->piso;

    if (!$aula->id_aula || !$aula->nombre || !$aula->codigo) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Datos incompletos para actualizar el aula"
        ]);
        exit;
    }

    $resultado = $aula->actualizar();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Aula actualizada correctamente" : "Error al actualizar aula"
    ]);

} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Error al actualizar aula: " . $e->getMessage()
    ]);
}
