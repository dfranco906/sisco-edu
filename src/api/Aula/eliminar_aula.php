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

    $gradosAsociados = $aula->contarGradosAsociados();

    if ($gradosAsociados > 0) {
        echo json_encode([
            "status" => "error",
            "message" => "No se puede eliminar el aula porque tiene grados asociados"
        ]);
        exit;
    }

    $resultado = $aula->eliminar();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Aula eliminada correctamente" : "No se encontro el aula para eliminar"
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error interno",
        "debug" => $e->getMessage()
    ]);
}
?>
