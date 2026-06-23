<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Aula.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metodo no permitido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $aula = new Aula($db);

    $aula->id_aula = $_POST['id_aula'] ?? null;

    if (!$aula->id_aula) {
        echo json_encode(["status" => "error", "message" => "ID de aula requerido"]);
        exit;
    }

    $resultado = $aula->desactivar();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Aula desactivada correctamente" : "Error al desactivar aula"
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error interno",
        "debug" => $e->getMessage()
    ]);
}
?>
