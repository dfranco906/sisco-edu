<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Materia.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido"]);
    exit;
}

$db = (new Database())->getConnection();
$materia = new Materia($db);

$materia->id_materia = (int)($_POST['id_materia'] ?? 0);
$materia->nombre = trim((string)($_POST['nombre'] ?? ''));
$materia->descripcion = trim((string)($_POST['descripcion'] ?? ''));
$materia->carga_horaria_semanal = (int)($_POST['carga_horaria_semanal'] ?? $_POST['carga_horaria'] ?? 0);

if ($materia->id_materia <= 0 || empty($materia->nombre)) {
    http_response_code(422);
    echo json_encode([
        "status" => "error", 
        "message" => "El identificador y el nombre de la materia son obligatorios"
    ]);
    exit;
}

try {
    $resultado = $materia->actualizar();
    if ($resultado) {
        echo json_encode([
            "status" => "success",
            "message" => "Materia actualizada correctamente"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Error al actualizar la materia"
        ]);
    }
} catch (Throwable $e) {
    error_log("Error actualizando materia: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Ocurrió un error inesperado al actualizar la materia"
    ]);
}
?>
