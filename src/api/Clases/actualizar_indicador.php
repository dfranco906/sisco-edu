<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ClaseDiaria.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$id_clase = filter_var($data['id_clase'] ?? null, FILTER_VALIDATE_INT);
$id_indicador = filter_var($data['id_indicador'] ?? null, FILTER_VALIDATE_INT);
$cumplido = isset($data['cumplido']) ? (int)(bool)$data['cumplido'] : 1;
$observaciones = isset($data['observaciones']) ? trim((string)$data['observaciones']) : null;

if (!$id_clase || !$id_indicador) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Faltan datos requeridos (id_clase, id_indicador)"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $claseModel = new ClaseDiaria($db);

    $ok = $claseModel->actualizarIndicador($id_clase, $id_indicador, $cumplido, $observaciones);

    http_response_code(200);
    echo json_encode([
        "status" => $ok ? "success" : "error",
        "message" => $ok ? "Indicador actualizado" : "No se pudo actualizar el indicador"
    ]);
} catch (Throwable $e) {
    error_log("Error en Clases/actualizar_indicador: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al actualizar indicador"]);
}
