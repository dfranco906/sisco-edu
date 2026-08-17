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
    $aula->ubicacion = $_POST['ubicacion'] ?? null;

    if (!$aula->id_aula || !$aula->nombre || !$aula->codigo || !$aula->ubicacion) {
        echo json_encode([
            "status" => "error",
            "message" => "Datos incompletos"
        ]);
        exit;
    }

    $resultado = $aula->actualizar();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Aula actualizada correctamente" : "Error al actualizar aula"
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error interno",
        "debug" => $e->getMessage()
    ]);
}
?>