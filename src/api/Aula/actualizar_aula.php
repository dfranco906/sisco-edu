<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Aula.php';

try {
    $db = (new Database())->getConnection();
    $aula = new Aula($db);

    $aula->id_aula = !empty($_POST['id_aula']) ? (int)$_POST['id_aula'] : null;
    $aula->nombre = !empty($_POST['nombre']) ? trim($_POST['nombre']) : null;
    $aula->bloque = !empty($_POST['bloque']) ? trim($_POST['bloque']) : 'Bloque A';
    $aula->piso = !empty($_POST['piso']) ? trim($_POST['piso']) : 'Planta Baja';

    $codigo = !empty($_POST['codigo']) ? trim($_POST['codigo']) : null;
    if (!$codigo) {
        $codigo = $aula->generarSiguienteCodigo($aula->bloque, $aula->piso, $aula->id_aula);
    }
    $aula->codigo = strtoupper($codigo);

    $aula->ubicacion = !empty($_POST['ubicacion']) ? trim($_POST['ubicacion']) : "{$aula->bloque} - {$aula->piso}";

    if (!$aula->id_aula || !$aula->nombre || !$aula->codigo) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Datos incompletos para actualizar el aula."
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
