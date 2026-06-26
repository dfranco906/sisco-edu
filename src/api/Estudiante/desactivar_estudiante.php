<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Estudiante.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Metodo no permitido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $estudiante = new Estudiante($db);

    $estudiante->id_estudiante = $_POST['id_estudiante'] ?? null;

    if (!$estudiante->id_estudiante) {
        echo json_encode(["status" => "error", "message" => "ID de estudiante requerido"]);
        exit;
    }

    $resultado = $estudiante->desactivar();

    echo json_encode([
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Estudiante desactivado correctamente" : "Error al desactivar estudiante"
    ]);

} catch (Throwable $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error interno",
        "debug" => $e->getMessage()
    ]);
}
?>
